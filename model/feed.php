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
   
   private $stories;
   
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
      return 'index.php?page=explore_feed&feed='.urlencode($this->name);
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
   
   public function save($expiration=86400)
   {
      if( isset($this->stories) )
         $this->cache->set('stories_from_'.$this->name, $this->stories, $expiration);
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
   
   public function get_stories()
   {
      if( isset($this->stories) )
         return $this->stories;
      else
      {
         $error = FALSE;
         $this->stories = $this->cache->get_array2('stories_from_'.$this->name, $error);
         if( !$error )
            return $this->stories;
         else
            return $this->read();
      }
   }
   
   public function read($images=FALSE)
   {
      $this->stories = array();
      
      try
      {
         $ch = curl_init( $this->url );
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
         $html = curl_exec($ch);
         curl_close($ch);
         
         libxml_use_internal_errors(true);
         $xml = simplexml_load_string( $html );
         if( $xml )
         {
            if( $xml->channel )
            {
               $i = 0;
               foreach($xml->channel->item as $item)
               {
                  if( $i < FS_MAX_STORIES )
                     $this->stories[] = new story($item, $this);
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
                     $this->stories[] = new story($item, $this);
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
                     $this->stories[] = new story($item, $this);
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
                     $this->stories[] = new story($item, $this);
                  else
                     break;
                  $i++;
               }
            }
            else
               $this->new_error("Estructura irreconocible en el feed: ".$this->name);
            
            if( $images )
            {
               /// pre-procesamos, hasta el final
               $work_array = array();
               $discarded = array();
               foreach($this->stories as $s)
                  $s->pre_process_images($work_array, $discarded);
               
               /// después procesamos en orden inverso
               $selected = array();
               for($k=count($this->stories)-1; $k>0; $k--)
                  $this->stories[$k]->process_images($discarded, $selected);
            }
            $this->save();
         }
         else
         {
            $this->new_error("Imposible leer el feed: ".$this->name);
            $this->save(600);
         }
      }
      catch(Exception $e)
      {
         $this->new_error("Error al leer el feed: ".$this->name);
         $this->save(600);
      }
      
      return $this->stories;
   }
   
   public function get_story($id)
   {
      $story = FALSE;
      foreach($this->get_stories() as $s)
      {
         if($s->get_id() == $id)
         {
            $story = $s;
            break;
         }
      }
      return $story;
   }
   
   public function save_story($story)
   {
      /// forzamos la lectura de la lista de historias
      $this->get_stories();
      
      foreach($this->stories as $i => $value)
      {
         if( $value->get_id() == $story->get_id() )
         {
            $this->stories[$i] = $story;
            break;
         }
      }
      
      $this->save();
   }
}

?>
