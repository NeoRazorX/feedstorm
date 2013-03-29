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
require_once 'model/story_media.php';
require_once 'model/story_edition.php';

class story extends fs_model
{
   public $date;
   public $title;
   public $description;
   public $link;
   public $media_id;
   public $clics;
   public $popularity;
   
   private $media_items;
   public $media_item;
   
   public function __construct($item=FALSE)
   {
      parent::__construct('stories');
      if( $item )
      {
         $this->id = $item['_id'];
         $this->date = $item['date'];
         $this->title = $item['title'];
         $this->description = $item['description'];
         $this->link = $item['link'];
         $this->media_id = $item['media_id'];
         $this->clics = $item['clics'];
      }
      else
      {
         $this->id = NULL;
         $this->date = time();
         $this->title = NULL;
         $this->description = NULL;
         $this->link = NULL;
         $this->media_id = NULL;
         $this->clics = 0;
      }
      
      if( is_null($this->media_id) )
         $this->media_item = NULL;
      else
      {
         $mi0 = new media_item();
         $this->media_item = $mi0->get($this->media_id);
      }
      
      $this->calculate_popularity();
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('popularity' => -1) );
      $this->collection->ensureIndex( array('date' => -1) );
      $this->collection->ensureIndex('link');
   }
   
   public function url($sitemap=FALSE)
   {
      if( is_null($this->id) )
         return 'index.php';
      else if($sitemap)
         return 'index.php?page=show_story&amp;id='.$this->id;
      else
         return 'index.php?page=show_story&id='.$this->id;
   }
   
   public function link()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=show_story&id='.$this->id.'&redir=TRUE';
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=edit_story&id='.$this->id;
   }
   
   public function show_date()
   {
      return Date('Y-m-d H:m', $this->date);
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function popularity()
   {
      return number_format($this->popularity, 3);
   }
   
   public function feed_links()
   {
      $feed_story = new feed_story();
      return $feed_story->all4story( $this->id );
   }
   
   public function media_items()
   {
      if( !isset($this->media_items) )
      {
         $this->media_items = array();
         $story_media = new story_media();
         foreach($story_media->all4story($this->id) as $sm)
            $this->media_items[] = $sm->media_item();
      }
      return $this->media_items;
   }
   
   public function editions()
   {
      $edition = new story_edition();
      return $edition->all4story( $this->id );
   }
   
   public function description($width=300)
   {
      if( strlen($this->description) > $width )
         return substr($this->description, 0, $width).'...';
      else
         return $this->description;
   }
   
   public function set_description($desc, $meneame=FALSE)
   {
      if( $meneame )
      {
         $aux = '';
         for($i=0; $i<strlen($desc); $i++)
         {
            if( substr($desc, $i, 4) == '</p>' )
               break;
            else
               $aux .= substr($desc, $i, 1);
         }
         $desc = $aux;
      }
      
      $this->description = strip_tags($desc);
   }
   
   private function calculate_popularity()
   {
      $difft = 1 + intval( (time() - $this->date) / 86400 );
      if($difft > 0 AND $this->clics > 0)
         $this->popularity = $this->clics / $difft;
      else
         $this->popularity = 0;
   }
   
   public function readed()
   {
      return isset($_COOKIE['s_'.$this->id]);
   }
   
   public function read()
   {
      if( !isset($_COOKIE['s_'.$this->id]) )
         setcookie('s_'.$this->id, $this->id, time()+86400);
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new story($data);
      else
         return FALSE;
   }
   
   public function get_by_link($url)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('link' => $url) );
      if($data)
         return new story($data);
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
   
   public function save()
   {
      $this->title = $this->true_text_break($this->title, 149, 15);
      $this->description = $this->true_text_break($this->description, 499, 30);
      $this->media_id = $this->var2str($this->media_id);
      $this->calculate_popularity();
      
      $data = array(
          'date' => $this->date,
          'title' => $this->title,
          'description' => $this->description,
          'link' => $this->link,
          'media_id' => $this->media_id,
          'clics' => $this->clics,
          'popularity' => $this->popularity
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
   }
   
   public function delete()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function all_from_array($selection)
   {
      $stories = array();
      if($selection)
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
         foreach($this->collection->find(array('_id'=>array('$in'=>$selection))) as $s)
            $stories[] = new story($s);
      }
      return $stories;
   }
   
   public function last_stories()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function popular_stories()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('popularity'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function random_stories()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      $i = 0;
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(3*FS_MAX_STORIES) as $s)
      {
         if($i < FS_MAX_STORIES)
         {
            if( rand(0, 3) == 0 )
            {
               $stlist[] = new story($s);
               $i++;
            }
         }
         else
            break;
      }
      return $stlist;
   }
   
   public function cron_job()
   {
      if( rand(0, 9) == 0 )
      {
         echo "\nEliminamos historias antiguas...";
         /// eliminamos los registros más antiguos que FS_MAX_AGE
         $this->collection->remove( array('date' => array('$lt'=>time()-FS_MAX_AGE)) );
      }
      else
      {
         echo "\nActualizamos las noticias populares...";
         foreach($this->popular_stories() as $ps)
         {
            /// si la imagen seleccionada no está en tmp, la redescargamos
            if( $ps->media_item )
               $ps->media_item->redownload();
            
            $ps->save();
         }
      }
   }
}

?>