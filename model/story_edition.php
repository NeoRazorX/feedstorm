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
require_once 'model/story.php';

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
   
   private static $mi0;
   public $media_item;
   
   private static $st0;
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
         
         if( is_null($this->story_id) )
            $this->story = NULL;
         else
         {
            if( !isset(self::$st0) )
               self::$st0 = new story();
            
            $this->story = self::$st0->get($this->story_id);
         }
         
         if( is_null($this->media_id) )
            $this->media_item = NULL;
         else
         {
            if( !isset(self::$mi0) )
               self::$mi0 = new media_item();
            
            $this->media_item = self::$mi0->get($this->media_id);
         }
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
         $this->votes = 1;
         
         $this->story = NULL;
         $this->media_item = NULL;
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
   
   public function url($sitemap=TRUE)
   {
      if( is_null($this->id) )
         return 'index.php';
      else if($sitemap)
         return 'index.php?page=show_edition&amp;id='.$this->id;
      else
         return 'index.php?page=show_edition&id='.$this->id;
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=edit_story&amp;id='.$this->id;
   }
   
   public function vote_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=show_edition&amp;id='.$this->id.'&amp;vote=TRUE';
   }
   
   public function description($width=300)
   {
      return $this->true_text_break($this->description, $width);
   }
   
   public function editions()
   {
      return $this->all4story( $this->story_id );
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
      $this->title = $this->true_text_break($this->title, 149, 18);
      $this->description = $this->true_text_break($this->description, 999, 25);
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
      foreach($this->collection->find()->sort(array('date'=>-1))->limit($limit) as $se)
         $stlist[] = new story_edition($se);
      return $stlist;
   }
   
   public function cron_job()
   {
      if( mt_rand(0, 2) == 0 )
      {
         echo "\nEliminamos ediciones antiguas...";
         /// eliminamos los registros más antiguos que FS_MAX_AGE
         $this->collection->remove( array('date' => array('$lt'=>time()-FS_MAX_AGE)) );
      }
   }
}

?>