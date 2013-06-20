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
require_once 'model/suscription.php';
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
   
   public function install_indexes()
   {
      $this->collection->ensureIndex('url');
      $this->collection->ensureIndex('name');
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
   
   public function show_url($size=60)
   {
      if( mb_strlen($this->url) < $size )
         return $this->url;
      else
         return mb_substr($this->url, 0, $size).'...';
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
      return ( mb_substr($this->url, 0, 23) == 'http://www.meneame.net/' );
   }
   
   public function reddit()
   {
      return ( mb_substr($this->url, 0, 22) == 'http://www.reddit.com/' );
   }
   
   public function stories()
   {
      $feed_story = new feed_story();
      $stories = array();
      foreach($feed_story->last4feed($this->id) as $fs)
      {
         if( $fs->story() )
            $stories[] = $fs->story();
      }
      return $stories;
   }
   
   public function suscriptors()
   {
      $suscription = new suscription();
      return $suscription->count4feed( $this->get_id() );
   }
   
   public function read()
   {
      try
      {
         $ch = curl_init( $this->url );
         $fp = fopen('tmp/'.$this->get_id().'.xml', 'wb');
         curl_setopt($ch, CURLOPT_FILE, $fp);
         curl_setopt($ch, CURLOPT_HEADER, 0);
         curl_setopt($ch, CURLOPT_TIMEOUT, FS_TIMEOUT);
         
         if( !$this->reddit() )
         {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
         }
         
         curl_exec($ch);
         curl_close($ch);
         fclose($fp);
         
         if( file_exists('tmp/'.$this->get_id().'.xml') )
         {
            libxml_use_internal_errors(TRUE);
            $xml = simplexml_load_file('tmp/'.$this->get_id().'.xml');
            if( $xml )
            {
               /// intentamos leer las noticias
               $i = 0;
               if( $xml->channel->item )
               {
                  foreach($xml->channel->item as $item)
                  {
                     if($i < FS_MAX_STORIES)
                     {
                        $this->new_story($item);
                        $i++;
                     }
                     else
                        break;
                  }
               }
               else if( $xml->item )
               {
                  foreach($xml->item as $item)
                  {
                     if($i < FS_MAX_STORIES)
                     {
                        $this->new_story($item);
                        $i++;
                     }
                     else
                        break;
                  }
               }
               else if( $xml->feed->entry )
               {
                  foreach($xml->feed->entry as $item)
                  {
                     if($i < FS_MAX_STORIES)
                     {
                        $this->new_story($item);
                        $i++;
                     }
                     else
                        break;
                  }
               }
               else if( $xml->entry )
               {
                  foreach($xml->entry as $item)
                  {
                     if($i < FS_MAX_STORIES)
                     {
                        $this->new_story($item);
                        $i++;
                     }
                     else
                        break;
                  }
               }
               else
               {
                  $this->new_error("Estructura irreconocible en el feed: ".$this->name);
                  $this->strikes++;
               }
               
               /// leemos el titulo del feed
               if( $xml->channel->title )
                  $this->name = $this->remove_bad_utf8( (string)$xml->channel->title );
               else if( $xml->title )
               {
                  foreach($xml->title as $item)
                  {
                     $this->name = $this->remove_bad_utf8( (string)$item );
                     break;
                  }
               }
               /// leemos la descripción
               if( $xml->channel->description )
                  $this->description = $this->remove_bad_utf8( (string)$xml->channel->description );
               else if( $xml->description )
               {
                  foreach($xml->description as $item)
                  {
                     $this->description = $this->remove_bad_utf8( (string)$item );
                     break;
                  }
               }
            }
            else
            {
               $this->new_error("Imposible leer el xml.");
               $this->strikes++;
            }
         }
         else
         {
            $this->new_error("Imposible leer el archivo: tmp/".$this->get_id().'.xml');
            $this->strikes++;
         }
      }
      catch(Exception $e)
      {
         $this->new_error("Error al leer el feed: ".$this->url.'. '.$e);
         $this->strikes++;
      }
      
      $this->last_check_date = time();
      $this->suscriptors = $this->suscriptors();
      $this->save();
   }
   
   private function new_story(&$item)
   {
      $this->strikes = 0;
      
      $feed_story = new feed_story();
      $feed_story->feed_id = $this->id;
      $feed_story->title = $this->remove_bad_utf8( (string)$item->title );
      
      $story = new story();
      $story->title = $this->remove_bad_utf8( (string)$item->title );
      
      /// intentamos obtener el enlace original de meneame
      $meneos = 0;
      foreach($item->children('meneame', TRUE) as $element)
      {
         if($element->getName() == 'url')
         {
            $story->link = (string)$element;
            $feed_story->link = (string)$item->link;
         }
         else if($element->getName() == 'votes')
         {
            $meneos = intval( (string)$element );
         }
      }
      
      /// ¿reddit?
      if( $this->reddit() )
      {
         $links = array();
         if( preg_match_all("/<a href=\"([^\"]*)\">\[link/", (string)$item->description, $links) )
         {
            $story->link = $links[1][0];
            $feed_story->link = (string)$item->link;
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
               if( mb_substr((string)$l, 0, 4) == 'http' )
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
      
      /// reemplazamos los &amp;
      $story->link = str_replace('&amp;', '&', $story->link);
      
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
         
         $story2->tweet_count();
         $story2->facebook_count();
         $story2->meneos = $meneos;
         
         /* 
          * Si la historia no tiene asociado un elemento multimedia,
          * tiramos un dado y buscamos más elementos multimedia.
          */
         if( is_null($story2->media_id) AND mt_rand(0, 2) == 0 )
            $this->add_media_items($story2, $item);
      }
      else if( $story->date > time() - FS_MAX_AGE ) /// no guardamos noticias antiguas
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
         
         if( $this->meneame() )
         {
            $aux = '';
            for($i = 0; $i < mb_strlen($description); $i++)
            {
               if( mb_substr($description, $i, 4) == '</p>' )
                  break;
               else
                  $aux .= mb_substr($description, $i, 1);
            }
            $description = $aux;
         }
         
         /// eliminamos el html
         $description = preg_replace("/<\s*style.+?<\s*\/\s*style.*?>/si", '', html_entity_decode($description, ENT_QUOTES, 'UTF-8') );
         $story->description = $this->remove_bad_utf8( strip_tags($description) );
         $story->tweet_count();
         $story->facebook_count();
         $story->meneos = $meneos;
         $story->save();
         $feed_story->story_id = $story->get_id();
         $feed_story->save();
         
         $this->add_media_items($story, $item, FALSE);
      }
   }
   
   private function add_media_items(&$story, &$item, $search_link=TRUE)
   {
      $num_downloads = 0;
      $width = 0;
      $height = 0;
      $first_forced = FALSE;
      $media_item = new media_item();
      foreach($media_item->find_media($item, $story->link, $search_link) as $mi)
      {
         $story_media = new story_media();
         $story_media->story_id = $story->get_id();
         
         if( !$media_item->get_by_url($mi->url) )
         {
            if( $mi->download() )
            {
               echo 'D';
               $num_downloads++;
               
               $mi->save();
               $story_media->media_id = $mi->get_id();
               $story_media->save();
               
               if($story->link == $mi->url)
               {
                  echo 'S';
                  
                  $story->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  $story->save();
                  break;
               }
               else if($num_downloads == 1)
               {
                  echo 'S';
                  
                  $story->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  $story->save();
                  
                  if($mi->ratio() < 1 OR $mi->ratio() > 2)
                     $first_forced = TRUE;
               }
               else if($num_downloads > FS_MAX_DOWNLOADS)
               {
                  break;
               }
               else if($first_forced AND $mi->ratio() >= 1 AND $mi->ratio() <= 2)
               {
                  echo 'S';
                  
                  $story->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  $story->save();
               }
               else if($mi->ratio() >= 1 AND $mi->ratio() <= 2 AND $mi->width > $width AND $mi->height > $height)
               {
                  echo 'S';
                  
                  $story->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  $story->save();
               }
            }
            else
               echo 'E';
         }
         else
            echo 'I';
      }
      
      echo "F\n";
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new feed($data);
      else
         return FALSE;
   }
   
   public function get_by_url($url)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
      if( mb_strlen($this->name) > 60 )
         $this->name = mb_substr($this->name, 0, 57).'...';
      
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
            $this->add2history(__CLASS__.'::'.__FUNCTION__.'@update');
            $filter = array('_id' => $this->id);
            $this->collection->update($filter, $data);
         }
         else
         {
            $this->add2history(__CLASS__.'::'.__FUNCTION__.'@insert');
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
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $feeds = array();
      foreach($this->collection->find()->sort(array('name'=>1)) as $f)
         $feeds[] = new feed($f);
      return $feeds;
   }
   
   public function random()
   {
      $feed = FALSE;
      $all_feeds = $this->all();
      if( count($all_feeds) > 1 )
      {
         $selection = mt_rand(0, count($all_feeds));
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
   
   public function cron_job()
   {
      echo "\nProcesamos las fuentes...";
      foreach($this->all() as $f)
      {
         if($f->strikes > 72)
         {
            $f->delete();
            echo "\n * Eliminada la fuente ".$f->url.".\n";
         }
         else
         {
            echo "\n * Procesando: ".$f->url."\n ** Archivo: tmp/".$f->get_id().".xml ...\n";
            $f->read();
            
            foreach($f->get_errors() as $e)
               echo $e."\n";
            $f->clean_errors();
            
            foreach($f->get_messages() as $m)
               echo $m."\n";
            $f->clean_messages();
         }
      }
   }
}

?>