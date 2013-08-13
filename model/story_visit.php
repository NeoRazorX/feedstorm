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
require_once 'model/story.php';

class story_visit extends fs_model
{
   public $visitor_id;
   public $story_id;
   public $edition_id;
   public $ip;
   public $date;
   
   private $story;
   
   public function __construct($sv = FALSE)
   {
      parent::__construct('story_visits');
      if($sv)
      {
         $this->id = $sv['_id'];
         
         if( isset($sv['visitor_id']) )
            $this->visitor_id = $sv['visitor_id'];
         else
            $this->visitor_id = NULL;
         
         $this->story_id = $sv['story_id'];
         $this->edition_id = $sv['edition_id'];
         $this->ip = $sv['ip'];
         $this->date = $sv['date'];
      }
      else
      {
         $this->id = NULL;
         $this->visitor_id = NULL;
         $this->story_id = NULL;
         $this->edition_id = NULL;
         
         if( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
         else
            $this->ip = 'unknown';
         
         $this->date = time();
      }
      
      $this->story = NULL;
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('date' => -1) );
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function url()
   {
      return 'index.php?page=show_story&id='.$this->story_id;
   }
   
   public function edition_url()
   {
      return 'index.php?page=show_edition&id='.$this->edition_id;
   }
   
   public function title()
   {
      if( !isset($this->story) )
      {
         $story = new story();
         $this->story = $story->get($this->story_id);
      }
      
      return $this->story->title;
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new story_visit($data);
      else
         return FALSE;
   }
   
   public function get_by_params($sid, $ip)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('story_id' => $this->var2str($sid), 'ip' => $ip) );
      if($data)
         return new story_visit($data);
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
         $data = $this->collection->findone( array('_id' => new MongoId($this->id)) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $this->visitor_id = $this->var2str($this->visitor_id);
      $this->story_id = $this->var2str($this->story_id);
      $this->edition_id = $this->var2str($this->edition_id);
      
      $data = array(
          'visitor_id' => $this->visitor_id,
          'story_id' => $this->story_id,
          'edition_id' => $this->edition_id,
          'ip' => $this->ip,
          'date' => $this->date
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
      $svlist = array();
      foreach($this->collection->find() as $sv)
         $svlist[] = new story_visit($sv);
      return $svlist;
   }
   
   public function all4visitor($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $svlist = array();
      foreach($this->collection->find(array('visitor_id'=>$this->var2str($id)))->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $sv)
         $svlist[] = new story_visit($sv);
      return $svlist;
   }
   
   public function last()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $svlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $sv)
         $svlist[] = new story_visit($sv);
      return $svlist;
   }
   
   public function count4visitor($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return $this->collection->find(array('visitor_id'=>$this->var2str($id)))->count();
   }
   
   public function cron_job()
   {
      if( mt_rand(0, 9) == 0 )
      {
         echo "\nEliminamos visitas antiguas...";
         /// eliminamos los registros más antiguos que FS_MAX_AGE
         $this->collection->remove( array('date' => array('$lt'=>time()-FS_MAX_AGE)) );
      }
   }
}

?>