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
require_once 'model/media_item.php';

class story_edition extends fs_model
{
   public $story_id;
   public $visitor_id;
   public $ip;
   public $date;
   public $title;
   public $description;
   public $media_id;
   public $votes;
   
   public $media_item;
   public $story;
   
   public function __construct($se = FALSE)
   {
      parent::__construct('story_editions');
      if($se)
      {
         $this->id = $se['_id'];
         $this->story_id = $se['story_id'];
         $this->visitor_id = $se['visitor_id'];
         $this->ip = $se['ip'];
         $this->date = $se['date'];
         $this->title = $se['title'];
         $this->description = $se['description'];
         $this->media_id = $se['media_id'];
         $this->votes = $se['votes'];
      }
      else
      {
         $this->id = NULL;
         $this->story_id = NULL;
         $this->visitor_id = NULL;
         
         if( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
         else
            $this->ip = 'unknown';
         
         $this->date = time();
         $this->title = '';
         $this->description = '';
         $this->media_id = NULL;
         $this->votes = 0;
      }
      
      if( is_null($this->story_id) )
         $this->story = NULL;
      else
      {
         $story = new story();
         $this->story = $story->get($this->story_id);
      }
      
      if( is_null($this->media_id) )
         $this->media_item = NULL;
      else
      {
         $mi0 = new media_item();
         $this->media_item = $mi0->get($this->media_id);
      }
   }
   
   public function show_date()
   {
      return Date('Y-m-d H:m', $this->date);
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=show_edition&id='.$this->id;
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=edit_story&id='.$this->id;
   }
   
   public function vote_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=show_edition&id='.$this->id.'&vote=TRUE';
   }
   
   public function editions()
   {
      return $this->all4story( $this->story_id );
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new story_edition($data);
      else
         return FALSE;
   }
   
   public function get_by_params($sid, $vid)
   {
      $data = $this->collection->findone(
         array('story_id' => $this->var2str($sid), 'visitor_id' => $this->var2str($vid))
      );
      if($data)
         return new story_edition($data);
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
      $this->story_id = $this->var2str($this->story_id);
      $this->visitor_id = $this->var2str($this->visitor_id);
      $this->title = $this->no_html( $this->true_word_break($this->title, 15) );
      $this->description = $this->no_html( $this->true_word_break($this->description) );
      $this->media_id = $this->var2str($this->media_id);
      
      $data = array(
          'story_id' => $this->story_id,
          'visitor_id' => $this->visitor_id,
          'ip' => $this->ip,
          'date' => $this->date,
          'title' => $this->title,
          'description' => $this->description,
          'media_id' => $this->media_id,
          'votes' => $this->votes
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
      $selist = array();
      foreach($this->collection->find() as $se)
         $selist[] = new story_edition($se);
      return $selist;
   }
   
   public function all4story($sid)
   {
      $selist = array();
      foreach($this->collection->find(array('story_id'=> $this->var2str($sid)))->sort(array('date'=>-1)) as $se)
         $selist[] = new story_edition($se);
      return $selist;
   }
   
   public function last_editions()
   {
      $stlist = array();
      $num = 0;
      foreach($this->collection->find()->sort(array('date'=>-1)) as $se)
      {
         if($num < FS_MAX_STORIES)
         {
            $encontrada = FALSE;
            foreach($stlist as $se2)
            {
               if($se['story_id'] == $se2->story_id)
               {
                  $encontrada = TRUE;
                  break;
               }
            }
            if( !$encontrada )
            {
               $stlist[] = new story_edition($se);
               $num++;
            }
         }
         else
            break;
      }
      return $stlist;
   }
}

?>