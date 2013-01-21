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

class story_visit extends fs_model
{
   public $story_id;
   public $edition_id;
   public $ip;
   public $date;
   public $user_agent;
   
   public function __construct($sv = FALSE)
   {
      parent::__construct('story_visits');
      if($sv)
      {
         $this->id = $sv['_id'];
         $this->story_id = $sv['story_id'];
         $this->edition_id = $sv['edition_id'];
         $this->ip = $sv['ip'];
         $this->date = $sv['date'];
         $this->user_agent = $sv['user_agent'];
      }
      else
      {
         $this->id = NULL;
         $this->story_id = NULL;
         $this->edition_id = NULL;
         
         if( isset($_SERVER['REMOTE_ADDR']) )
            $this->ip = $_SERVER['REMOTE_ADDR'];
         else
            $this->ip = 'unknown';
         
         $this->date = time();
         
         if( isset($_SERVER['HTTP_USER_AGENT']) )
            $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
         else
            $this->user_agent = 'unknown';
      }
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new story_visit($data);
      else
         return FALSE;
   }
   
   public function get_by_params($sid, $ip)
   {
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
         $data = $this->collection->findone( array('_id' => new MongoId($this->id)) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $this->story_id = $this->var2str($this->story_id);
      
      $data = array(
          'story_id' => $this->story_id,
          'edition_id' => $this->edition_id,
          'ip' => $this->ip,
          'date' => $this->date,
          'user_agent' => $this->user_agent
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
      $svlist = array();
      foreach($this->collection->find() as $sv)
         $svlist[] = new story_visit($sv);
      return $svlist;
   }
}

?>