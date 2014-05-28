<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

class story_edition extends fs_model
{
   public $date;
   public $description;
   public $ip;
   public $nick;
   public $points;
   public $story_id;
   public $title;
   public $visitor_id;
   
   public function __construct($se = FALSE)
   {
      parent::__construct('story_editions');
      
      $this->id = NULL;
      $this->date = time();
      $this->description = '';
      
      $this->ip = 'unknown';
      if( isset($_SERVER['REMOTE_ADDR']) )
         $this->ip = $_SERVER['REMOTE_ADDR'];
      
      $this->nick = 'anonymous';
      $this->points = 0;
      $this->story_id = NULL;
      $this->title = '';
      $this->visitor_id = NULL;
      
      if($se)
      {
         $this->id = $se['_id'];
         $this->date = $se['date'];
         $this->description = $se['description'];
         $this->ip = $se['ip'];
         $this->nick = $se['nick'];
         $this->points = $se['points'];
         $this->story_id = $se['story_id'];
         $this->title = $se['title'];
         $this->visitor_id = $se['visitor_id'];
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex('story_id');
      $this->collection->ensureIndex( array('date' => -1) );
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
         return FS_PATH.'index.php';
      else
         return FS_PATH.'show_edition/'.$this->id;
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return FS_PATH.'index.php';
      else
         return FS_PATH.'edit_story/'.$this->id;
   }
   
   public function description($width=300)
   {
      return $this->true_text_break($this->description, $width);
   }
   
   public function story()
   {
      $story = new story();
      return $story->get($this->story_id);
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         $data = $this->collection->findone( array('_id' => new MongoId($id)) );
         if($data)
            return new story_edition($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
   }
   
   public function get_by_params($sid, $vid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
      $this->story_id = $this->var2str($this->story_id);
      $this->visitor_id = $this->var2str($this->visitor_id);
      $this->title = $this->true_text_break($this->title, 140, 18);
      $this->description = $this->true_text_break($this->description, 999, 25);
      
      $data = array(
          'date' => $this->date,
          'description' => $this->description,
          'ip' => $this->ip,
          'nick' => $this->nick,
          'points' => $this->points,
          'story_id' => $this->story_id,
          'title' => $this->title,
          'visitor_id' => $this->visitor_id
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
      $selist = array();
      foreach($this->collection->find() as $se)
         $selist[] = new story_edition($se);
      return $selist;
   }
   
   public function all4story($sid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $selist = array();
      foreach($this->collection->find(array('story_id'=> $this->var2str($sid)))->sort(array('date'=>-1)) as $se)
         $selist[] = new story_edition($se);
      return $selist;
   }
   
   public function last_editions($limit = FS_MAX_STORIES)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      $sids = array();
      
      foreach($this->collection->find()->sort(array('date'=>-1))->limit($limit) as $se)
      {
         if( !in_array($se['story_id'], $sids) )
         {
            $sids[] = $se['story_id'];
            $stlist[] = new story_edition($se);
         }
      }
      
      return $stlist;
   }
   
   public function cron_job()
   {
      
   }
}
