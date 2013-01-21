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
   
   public function url()
   {
      if( is_null($this->id) )
         return 'index.php';
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
      
      $description = '';
      $desc = preg_replace('/\s+/', ' ', strip_tags($desc) );
      foreach(explode(' ', $desc) as $aux)
      {
         if( strlen($description.' '.$aux) < 300 )
            $description .= ' ' . $aux;
      }
      if( strlen($description) < strlen($desc) )
         $description .= '...';
      $this->description = trim( $this->true_word_break($description) );
   }
   
   private function calculate_popularity()
   {
      $difft = 1 + intval( (time() - $this->date) / 43200 );
      if($difft > 0 AND $this->clics > 0)
         $this->popularity = $this->clics / $difft;
      else
         $this->popularity = 0;
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new story($data);
      else
         return FALSE;
   }
   
   public function get_by_link($url)
   {
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
         $data = $this->collection->findone( array('_id' => $this->id) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $this->title = $this->no_html($this->title);
      $this->description = $this->no_html($this->description);
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
         $filter = array('_id' => $this->id);
         $this->collection->update($filter, $data);
      }
      else
      {
         $this->collection->insert($data);
         $this->id = $data['_id'];
      }
   }
   
   public function delete()
   {
      $this->collection->remove( array('_id' => $this->id) );
   }
   
   public function all()
   {
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function last_stories()
   {
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function popular_stories()
   {
      $stlist = array();
      foreach($this->collection->find()->sort(array('popularity'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function random_stories()
   {
      $stlist = array();
      $i = 0;
      foreach($this->collection->find()->sort(array('date'=>-1)) as $s)
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
}

?>