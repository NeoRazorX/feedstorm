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

class comment extends fs_model
{
   public $thread;
   public $date;
   public $text;
   public $nick;
   public $ip;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('comments');
      if($c)
      {
         $this->id = $c['_id'];
         $this->thread = $c['thread'];
         $this->date = $c['date'];
         $this->text = $c['text'];
         $this->nick = $c['nick'];
         $this->ip = $c['ip'];
      }
      else
      {
         $this->id = NULL;
         $this->thread = NULL;
         $this->date = time();
         $this->text = '';
         $this->nick = 'anónimo';
         
         if( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
         else
            $this->ip = 'unknown';
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('date' => -1) );
      $this->collection->ensureIndex( array('thread' => 1, 'date' => 1) );
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function url()
   {
      if( isset($this->thread) )
      {
         $story = new story();
         $story2 = $story->get($this->thread);
         if($story2)
            return $story2->url();
         else
            return FS_PATH.'/index.php?page=comments';
      }
      else
         return FS_PATH.'/index.php?page=comments';
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new comment($data);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( isset($this->id) )
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
         $data = $this->collection->findone( array('_id' => $this->id) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function save()
   {
      $this->thread = $this->var2str($this->thread);
      $this->text = $this->true_text_break($this->text, 1000);
      
      $data = array(
          'thread' => $this->thread,
          'date' => $this->date,
          'text' => $this->text,
          'nick' => $this->nick,
          'ip' => $this->ip
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
   
   public function all($limit = FS_MAX_STORIES)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $comlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $c)
         $comlist[] = new comment($c);
      return $comlist;
   }
   
   public function all4thread($thread = NULL)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $find = array('thread' => $this->var2str($thread));
      $comlist = array();
      foreach($this->collection->find($find)->sort(array('date'=>1)) as $c)
         $comlist[] = new comment($c);
      
      return $comlist;
   }
   
   public function cron_job()
   {
      
   }
}

?>