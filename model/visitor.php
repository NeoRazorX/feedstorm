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
   public $history;
   private $feeds;
   
   public function __construct($k=FALSE)
   {
      parent::__construct();
      if( $k )
      {
         $this->key = $k;
         $this->history = $this->cache->get_array('urls_from_'.$k);
      }
      else
      {
         $this->key = sha1( strval(rand()) );
         $this->history = array();
      }
   }
   
   public function mobile()
   {
      return (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') || strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'android'));
   }
   
   public function in_history($url)
   {
      return in_array($url, $this->history);
   }
   
   public function url2history($url)
   {
      if( !$this->in_history($url) )
      {
         $this->history[] = $url;
         $this->cache->set('urls_from_'.$this->key, $this->history, time()+31536000);
      }
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
      /// reads all feed's stories
      $all = array();
      foreach($this->get_feeds() as $f)
         $all = array_merge( $all, $f->get_stories() );
      /// sort by date and limit to FS_MAX_STORIES
      $stories = array();
      while(count($stories) != count($all) AND count($stories) < FS_MAX_STORIES)
      {
         $selected = FALSE;
         $i = 0;
         while($i < count($all))
         {
            if( !$all[$i]->selected )
            {
               if( !$this->in_history($all[$i]->link) )
               {
                  if( !$selected  )
                     $selected = $i;
                  else if( $all[$i]->date > $all[$selected]->date )
                     $selected = $i;
               }
            }
            $i++;
         }
         $all[$selected]->selected = TRUE;
         $stories[] = $all[$selected];
      }
      return $stories;
   }
}

?>
