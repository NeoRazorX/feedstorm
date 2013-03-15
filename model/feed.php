<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_once 'model/feed_story.php';
require_once 'model/story.php';
require_once 'model/story_media.php';
require_once 'model/media_item.php';

class feed extends fs_model
{
   public $url;
   public $name;
   public $description;
   public $last_check_date;
   public $last_update;
   public $suscriptors;
   public $strikes;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('feeds');
      if( $f )
      {
         $this->id = $f['_id'];
         $this->url = $f['url'];
         $this->name = $f['name'];
         $this->description = $f['description'];
         $this->last_check_date = $f['last_check_date'];
         $this->last_update = $f['last_update'];
         $this->suscriptors = $f['suscriptors'];
         $this->strikes = $f['strikes'];
      }
      else
      {
         $this->id = NULL;
         $this->url = NULL;
         $this->name = $this->random_string(15);
         $this->description = 'Sin descriptión';
         $this->last_check_date = 0;
         $this->last_update = 0;
         $this->suscriptors = 0;
         $this->strikes = 0;
      }
   }
   
   public function url($sitemap=FALSE)
   {
      if( is_null($this->id) )
         return 'index.php';
      else if($sitemap)
         return 'index.php?page=explore_feed&amp;id='.$this->id;
      else
         return 'index.php?page=explore_feed&id='.$this->id;
   }
   
   public function show_url()
   {
      return $this->true_word_break($this->url);
   }
   
   public function last_check_date()
   {
      if( is_null($this->last_check_date) )
         return '-';
      else
         return Date('Y-m-d H:m', $this->last_check_date);
   }
   
   public function last_check_timesince()
   {
      if( is_null($this->last_check_date) )
         return '-';
      else
         return $this->time2timesince($this->last_check_date);
   }
   
   public function last_update()
   {
      if( is_null($this->last_update) )
         return '-';
      else
         return Date('Y-m-d H:m', $this->last_update);
   }
   
   public function last_update_timesince()
   {
      if( is_null($this->last_update) )
         return '-';
      else
         return $this->time2timesince($this->last_update);
   }
   
   public function meneame()
   {
      return ( substr($this->url, 0, 23) == 'http://www.meneame.net/' );
   }
   
   public function stories()
   {
      $feed_story = new feed_story();
      $stories = array();
      foreach($feed_story->last4feed($this->id) as $fs)
         $stories[] = $fs->story();
      return $stories;
   }
   
   public function read()
   {
      try
      {
         $ch = curl_init( $this->url );
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
         curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
         $html = curl_exec($ch);
         curl_close($ch);
         
         libxml_use_internal_errors(TRUE);
         $xml = simplexml_load_string( iconv('', 'UTF-8//IGNORE//TRANSLIT', $html) );
         if( $xml )
         {
            /// nos guardamos una copia del feed
            if( file_exists("tmp/".$this->get_id().".xml") )
               unlink("tmp/".$this->get_id().".xml");
            $xml->saveXML("tmp/".$this->get_id().".xml");
            
            /// intentamos leer las noticias
            if( $xml->channel->item )
            {
               foreach($xml->channel->item as $item)
                  $this->new_story($item);
            }
            else if( $xml->item )
            {
               foreach($xml->item as $item)
                  $this->new_story($item);
            }
            else if( $xml->feed->entry )
            {
               foreach($xml->feed->entry as $item)
                  $this->new_story($item);
            }
            else if( $xml->entry )
            {
               foreach($xml->entry as $item)
                  $this->new_story($item);
            }
            else
               $this->new_error("Estructura irreconocible en el feed: ".$this->name);
            
            /// leemos el titulo del feed
            if( $xml->channel->title )
               $this->name = (string)$xml->channel->title;
            else if( $xml->title )
            {
               foreach($xml->title as $item)
               {
                  $this->name = (string)$item;
                  break;
               }
            }
            /// leemos la descripción
            if( $xml->channel->description )
               $this->description = (string)$xml->channel->description;
            else if( $xml->description )
            {
               foreach($xml->description as $item)
               {
                  $this->description = (string)$item;
                  break;
               }
            }
            /// guardamos los cambios en el feed
            $this->last_check_date = time();
            $this->strikes = 0;
            $this->save();
         }
         else
         {
            $this->new_error("Imposible leer el feed: ".$this->name);
            $this->strikes++;
            $this->save();
         }
      }
      catch(Exception $e)
      {
         $this->new_error("Error al leer el feed: ".$this->name);
         $this->strikes++;
         $this->save();
      }
   }
   
   private function new_story($item)
   {
      $feed_story = new feed_story();
      $feed_story->feed_id = $this->id;
      $feed_story->title = (string)$item->title;
      
      $story = new story();
      $story->title = (string)$item->title;
      
      /// intentamos obtener el enlace original de meneame
      foreach($item->children('meneame', TRUE) as $element)
      {
         if($element->getName() == 'url')
         {
            $story->link = (string)$element;
            $feed_story->link = (string)$item->link;
            break;
         }
      }
      
      if( is_null($story->link) )
      {
         /// intentamos obtener el enlace original de feedburner
         foreach($item->children('feedburner', TRUE) as $element)
         {
            if($element->getName() == 'origLink')
            {
               $story->link = (string)$element;
               break;
            }
         }
         
         /// intentamos leer el/los links
         if( is_null($story->link) AND $item->link)
         {
            foreach($item->link as $l)
            {
               if( substr((string)$l, 0, 4) == 'http' )
                  $story->link = (string)$l;
               else
               {
                  if( $l->attributes()->rel == 'alternate' AND $l->attributes()->type == 'text/html' )
                     $story->link = (string)$l->attributes()->href;
                  else if( $l->attributes()->type == 'text/html' )
                     $story->link = (string)$l->attributes()->href;
               }
            }
         }
      }
      
      if( $item->pubDate )
         $story->date = strtotime( (string)$item->pubDate );
      else if( $item->published )
         $story->date = strtotime( (string)$item->published );
      
      $feed_story->date = $story->date;
      
      if($feed_story->date > $this->last_update)
         $this->last_update = $feed_story->date;
      
      /// ¿story ya existe?
      $story2 = $story->get_by_link($story->link);
      if($story2)
      {
         /// ¿la noticia ya está enlazada con esta fuente?
         $encontrada = FALSE;
         foreach($feed_story->all4story($story2->get_id()) as $fs)
         {
            if($fs->feed_id == $this->id)
            {
               $encontrada = TRUE;
               break;
            }
         }
         
         if( !$encontrada )
         {
            $feed_story->story_id = $story2->get_id();
            $feed_story->save();
         }
      }
      else
      {
         if( $item->description )
            $description = (string)$item->description;
         else if( $item->content )
            $description = (string)$item->content;
         else if( $item->summary )
            $description = (string)$item->summary;
         else
         {
            $description = '';
            /// intentamos leer el espacio de nombres atom
            foreach($item->children('atom', TRUE) as $element)
            {
               if($element->getName() == 'summary')
               {
                  $description = (string)$element;
                  break;
               }
            }
            foreach($item->children('content', TRUE) as $element)
            {
               if($element->getName() == 'encoded')
               {
                  $description = (string)$element;
                  break;
               }
            }
         }
         $story->set_description($description, $this->meneame());
         
         $story->save();
         $feed_story->story_id = $story->get_id();
         $feed_story->save();
         
         $width = 0;
         $height = 0;
         $media_item = new media_item();
         foreach($media_item->find_media($item, $story->link) as $mi)
         {
            $story_media = new story_media();
            $story_media->story_id = $story->get_id();
            
            if( !$media_item->get_by_url($mi->url) )
            {
               if( $mi->download() )
               {
                  $mi->save();
                  $story_media->media_id = $mi->get_id();
                  $story_media->save();
                  
                  if($mi->width > 0 AND $mi->height > 0)
                     $ratio = $mi->width / $mi->height;
                  else
                     $ratio = 0;
                  
                  if($ratio > 1 AND $ratio < 2 AND $mi->width > $width AND $mi->height > $height)
                  {
                     $story->media_id = $mi->get_id();
                     $width = $mi->original_width;
                     $height = $mi->original_height;
                     $story->save();
                  }
               }
            }
         }
      }
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new feed($data);
      else
         return FALSE;
   }
   
   public function get_by_url($url)
   {
      $data = $this->collection->findone( array('url' => $this->var2str($url) ) );
      if($data)
         return new feed($data);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
      {
         $data = $this->collection->findone( array('_id' => $this->id) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function test()
   {
      $this->name = $this->no_html($this->name);
      $this->description = $this->no_html($this->description);
      if( $this->suscriptors < 0 )
         $this->suscriptors = 0;
      
      if( filter_var($this->url, FILTER_VALIDATE_URL) )
         return TRUE;
      else
      {
         $this->new_error('URL no válida.');
         return FALSE;
      }
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $data = array(
             'url' => $this->url,
             'name' => $this->name,
             'description' => $this->description,
             'last_check_date' => $this->last_check_date,
             'last_update' => $this->last_update,
             'suscriptors' => $this->suscriptors,
             'strikes' => $this->strikes
         );
         
         if( $this->exists() )
         {
            $filter = array('_id' => $this->id);
            $this->collection->update($filter, $data);
         }
         else
         {
            $this->collection->insert($data);
            $this->id = $data['_id'];
         }
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->collection->remove( array('_id' => $this->id) );
      
      $suscription = new suscription();
      foreach($suscription->all4feed($this->id) as $sus)
         $sus->delete();
      
      $feed_story = new feed_story();
      foreach($feed_story->all4feed($this->id) as $fs)
         $fs->delete();
   }
   
   public function all()
   {
      $feeds = array();
      foreach($this->collection->find() as $f)
         $feeds[] = new feed($f);
      return $feeds;
   }
   
   public function random()
   {
      $feed = FALSE;
      $all_feeds = $this->all();
      if( count($all_feeds) > 1 )
      {
         $selection = rand(0, count($all_feeds));
         $i = 0;
         foreach($all_feeds as $f)
         {
            if($i == $selection)
            {
               $feed = $f;
               break;
            }
            $i++;
         }
      }
      return $feed;
   }
}

?>