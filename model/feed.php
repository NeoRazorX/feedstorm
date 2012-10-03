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
require_once 'model/story.php';

class feed extends fs_model
{
   public $name;
   public $url;
   public $default;
   
   public $selected;
   
   public function __construct($f=FALSE)
   {
      parent::__construct();
      if( $f )
      {
         $this->name = (string)$f->name;
         $this->url = (string)$f->url;
         if( $f->default )
            $this->default = TRUE;
         else
            $this->default = FALSE;
      }
      else
      {
         $this->name = NULL;
         $this->url = NULL;
         $this->default = FALSE;
      }
      
      $this->selected = FALSE;
   }
   
   public function url()
   {
      return 'index.php?page=explore_feed&feed='.$this->name;
   }
   
   public function get($fn)
   {
      $feed = FALSE;
      foreach($this->all() as $f)
      {
         if($f->name == $fn)
         {
            $feed = $f;
            break;
         }
      }
      return $feed;
   }
   
   public function get_stories()
   {
      $stories = $this->cache->get_array('stories_from_'.$this->name);
      if( $stories )
         return $stories;
      else
         return $this->read();
   }
   
   public function get_story_by_url($url)
   {
      $story = FALSE;
      $stories = $this->cache->get_array('stories_from_'.$this->name);
      if( !$stories )
         $stories = $this->read();
      foreach($stories as $s)
      {
         if($s->link == $url)
         {
            $story = $s;
            break;
         }
      }
      return $story;
   }
   
   public function read($images=FALSE)
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
         if( $xml->channel )
         {
            $i = 0;
            foreach($xml->channel->item as $item)
            {
               if( $i < FS_MAX_STORIES )
                  $stories[] = new story($item, $this);
               else
                  break;
               $i++;
            }
         }
         else if( $xml->item )
         {
            $i = 0;
            foreach($xml->item as $item)
            {
               if( $i < FS_MAX_STORIES )
                  $stories[] = new story($item, $this);
               else
                  break;
               $i++;
            }
         }
         else if( $xml->feed )
         {
            $i = 0;
            foreach($xml->feed->entry as $item)
            {
               if( $i < FS_MAX_STORIES )
                  $stories[] = new story($item, $this);
               else
                  break;
               $i++;
            }
         }
         else if( $xml->entry )
         {
            $i = 0;
            foreach($xml->entry as $item)
            {
               if( $i < FS_MAX_STORIES )
                  $stories[] = new story($item, $this);
               else
                  break;
               $i++;
            }
         }
         else
            $this->new_error("Estructura irreconocible en el feed: ".$this->name);
         
         if( $stories )
         {
            if( $images )
            {
               foreach($stories as $s)
                  $s->process_image();
            }
            $this->cache->set('stories_from_'.$this->name, $stories, 28800);
         }
      }
      else
         $this->new_error("Imposible leer el feed: ".$this->name);
      return $stories;
   }
   
   public function all()
   {
      $feeds = array();
      if( file_exists('feeds.xml') )
      {
         $xml = simplexml_load_file('feeds.xml');
         if( $xml )
         {
            if( $xml->feed )
            {
               foreach($xml->feed as $item)
                  $feeds[] = new feed($item);
            }
            else
               $this->new_error("Error al leer el archivo feeds.xml");
         }
         else
            $this->new_error("Imposible leer el archivo feeds.xml");
      }
      else
         $this->new_error("No se encuentra el archivo feeds.xml");
      return $feeds;
   }
   
   public function defaults()
   {
      $defaults = array();
      foreach($this->all() as $f)
      {
         if( $f->default )
            $defaults[] = $f;
      }
      return $defaults;
   }
}

?>
