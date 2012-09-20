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

class feed
{
   private $cache;
   
   public $name;
   public $url;
   
   public function __construct($fn=FALSE)
   {
      $this->cache = new fs_cache();
      if( $fn )
      {
         $this->url = trim( $fn );
         $aux = split('/', $this->url);
         $this->name = $aux[2];
      }
      else
      {
         $this->name = NULL;
         $this->url = NULL;
      }
   }
   
   public function get_stories()
   {
      $stories = $this->cache->get_array('stories_from_'.$this->name);
      if( $stories )
         return $stories;
      else
         return $this->read();
   }
   
   public function read()
   {
      $stories = array();
      $ch = curl_init( $this->url );
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      $html = curl_exec($ch);
      curl_close($ch);
      $xml = simplexml_load_string( $html );
      if( $xml )
      {
         $i = 0;
         foreach($xml->channel->item as $item)
         {
            if( $i < FS_MAX_STORIES )
               $stories[] = new story($item, $this->name);
            else
               break;
            $i++;
         }
         $this->cache->set('stories_from_'.$this->name, $stories, 28800);
      }
      return $stories;
   }
   
   public function all()
   {
      $feeds = array();
      foreach(split(',', FS_FEEDS) as $fn)
         $feeds[] = new feed($fn);
      return $feeds;
   }
}

?>
