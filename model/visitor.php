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
require_once 'model/story.php';

class visitor
{
   private $cache;
   public $key;
   public $history;
   
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
      $story = new story();
      $stories = $story->all();
      $news = array();
      foreach($stories as $s)
      {
         if( !$this->in_history($s->link) )
            $news[] = $s;
      }
      return $news;
   }
}

?>
