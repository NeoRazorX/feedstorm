<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'base/fs_model.php';
require_once 'model/feed.php';
require_once 'model/story.php';

class visitor extends fs_model
{
   public $key;
   private $feeds;
   
   public function __construct($k=FALSE)
   {
      parent::__construct();
      if( $k )
      {
         $this->key = $k;
      }
      else
      {
         $this->key = sha1( strval(rand()) );
         $this->new_message('¡Bienvenido a '.FS_NAME.'! Esta web recopila las últimas noticias de una serie de fuentes.
            Puedes seleccionar tus fuentes desde <b>menu &gt; preferencias</b>.');
      }
   }
   
   public function mobile()
   {
      return (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android'));
   }
   
   public function get_logs($reverse=TRUE)
   {
      if( $reverse )
         return array_reverse( $this->cache->get_array('logs') );
      else
         return $this->cache->get_array('logs');
   }
   
   public function add2log($info='-')
   {
      $entry = array(
          'date' => Date('Y-m-d H:i:s'),
          'ip' => 'X.X.X.X',
          'user_agent' => 'unknown',
          'url' => '/',
          'info' => $info,
          'count' => 1
      );
      
      if( isset($_SERVER['REMOTE_ADDR']) )
      {
         $ip4 = explode('.', $_SERVER['REMOTE_ADDR']);
         $ip6 = explode(':', $_SERVER['REMOTE_ADDR']);
         if( count($ip4) == 4 )
            $entry['ip'] = $ip4[0].'.'.$ip4[1].'.'.$ip4[2].'.X';
         else if( count($ip6) == 8 )
            $entry['ip'] = $ip6[0].':'.$ip6[1].':'.$ip6[2].':'.$ip6[3].':X:X:X:X';
      }
      
      if( isset($_SERVER['HTTP_USER_AGENT']) )
         $entry['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
      
      if( isset($_SERVER['REQUEST_URI']) )
         $entry['url'] = $_SERVER['REQUEST_URI'];
      
      $encontrado = FALSE;
      $logs = $this->get_logs(FALSE);
      if( count($logs) > 0 )
      {
         /// reducimos
         if( count($logs) > 100 )
         {
            $new_logs = array();
            for($j=50; $j<count($logs); $j++)
               $new_logs[] = $logs[$j];
            $logs = $new_logs;
         }
         
         $i = count($logs) - 1;
         if($logs[$i]['ip'] == $entry['ip'] AND $logs[$i]['url'] == $entry['url'] AND $logs[$i]['info'] == $entry['info'] AND $logs[$i]['user_agent'] == $entry['user_agent'])
         {
            $logs[$i]['date'] = Date('Y-m-d H:i:s');
            $logs[$i]['count']++;
            $encontrado = TRUE;
         }
      }
      if( !$encontrado )
         $logs[] = $entry;
      $this->cache->set('logs', $logs);
   }
   
   public function get_feeds()
   {
      if( !isset($this->feeds) )
      {
         $feed = new feed();
         if( isset($_COOKIE['feeds']) )
         {
            $cookie_feeds = split(';', urldecode($_COOKIE['feeds']));
            $this->feeds = array();
            foreach($feed->all() as $f)
            {
               if( in_array($f->name, $cookie_feeds) )
                  $this->feeds[] = $f;
            }
         }
         else
         {
            $this->feeds = $feed->defaults();
            $this->save_feeds();
         }
      }
      return $this->feeds;
   }
   
   public function clean_feeds()
   {
      $this->feeds = array();
   }
   
   public function add_feed($fn)
   {
      $this->get_feeds();
      
      $feed = new feed();
      $feed0 = $feed->get($fn);
      if($feed0)
         $this->feeds[] = $feed0;
   }
   
   public function delete_feed($fn)
   {
      $this->get_feeds();
      
      $i = 0;
      while($i < count($this->feeds))
      {
         if( $this->feeds[$i]->name == $fn )
            unset($this->feeds[$i]);
         $i++;
      }
   }
   
   public function save_feeds()
   {
      $fns = array();
      foreach($this->get_feeds() as $f)
         $fns[] = $f->name;
      setcookie('feeds', implode(';', $fns), time()+31536000);
   }
   
   public function get_new_stories()
   {
      /// leemos todas las historias de los feeds
      $all = array();
      foreach($this->get_feeds() as $f)
         $all = array_merge( $all, $f->get_stories() );
      /// ordenamos por fecha, no visitados y limitamos a FS_MAX_STORIES
      $stories = array();
      $selections = 0;
      while($selections < count($all) AND count($stories) < FS_MAX_STORIES)
      {
         $selected = -1;
         for($i=0; $i < count($all); $i++)
         {
            if( !$all[$i]->selected )
            {
               if($selected < 0)
                  $selected = $i;
               else if( $all[$i]->date > $all[$selected]->date )
                  $selected = $i;
            }
         }
         $selections++;
         
         if($selected >= 0)
         {
            $all[$selected]->selected = TRUE;
            $stories[] = $all[$selected];
         }
      }
      return $stories;
   }
}

?>
