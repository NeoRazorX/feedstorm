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

require_once 'base/fs_cache.php';
require_once 'model/feed.php';
require_once 'model/story.php';

class visitor
{
   private $cache;
   
   public $key;
   public $history;
   public $feeds;
   
   public function __construct($k=FALSE)
   {
      $this->cache = new fs_cache();
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
      
      $this->feeds = array();
      foreach(split(',', FS_FEEDS) as $fn)
         $this->feeds[] = new feed($fn);
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
   
   public function get_new_stories()
   {
      /// reads all feed's stories
      $all = array();
      foreach($this->feeds as $f)
         $all = array_merge( $all, $f->get_stories() );
      /// sort by date and limit to FS_MAX_STORIES
      $stories = array();
      while(count($stories) != count($all) AND count($stories) < FS_MAX_STORIES)
      {
         $selected = FALSE;
         foreach($all as $s)
         {
            if( !in_array($s, $stories) )
            {
               if( !$this->in_history($s->link) )
               {
                  if( !$selected  )
                     $selected = $s;
                  else if( $s->date > $selected->date )
                     $selected = $s;
               }
            }
         }
         $stories[] = $selected;
      }
      return $stories;
   }
}

?>
