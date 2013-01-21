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
require_once 'model/feed.php';
require_once 'model/story.php';

class feed_story extends fs_model
{
   public $feed_id;
   public $story_id;
   public $date;
   public $title;
   public $link;
   
   private $feed;
   private $story;
   
   public function __construct($fs = FALSE)
   {
      parent::__construct('feed_stories');
      if($fs)
      {
         $this->id = $fs['_id'];
         $this->feed_id = $fs['feed_id'];
         $this->story_id = $fs['story_id'];
         $this->date = $fs['date'];
         $this->title = $fs['title'];
         $this->link = $fs['link'];
      }
      else
      {
         $this->id = NULL;
         $this->feed_id = NULL;
         $this->story_id = NULL;
         $this->date = time();
         $this->title = NULL;
         $this->link = NULL;
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
   
   public function link()
   {
      if( is_null($this->link) )
      {
         $this->feed();
         return $this->feed->url();
      }
      else
         return $this->link;
   }
   
   public function story()
   {
      if( !isset($this->story) )
      {
         $this->story = new story();
         $this->story = $this->story->get($this->story_id);
      }
      return $this->story;
   }
   
   public function feed()
   {
      if( !isset($this->feed) )
      {
         $this->feed = new feed();
         $this->feed = $this->feed->get($this->feed_id);
      }
      return $this->feed;
   }
   
   public function feed_name()
   {
      $this->feed();
      return $this->feed->name;
   }
   
   public function feed_url()
   {
      $this->feed();
      return $this->feed->url();
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
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
   
   public function save()
   {
      $this->feed_id = $this->var2str($this->feed_id);
      $this->story_id = $this->var2str($this->story_id);
      
      $data = array(
          'feed_id' => $this->feed_id,
          'story_id' => $this->story_id,
          'date' => $this->date,
          'title' => $this->title,
          'link' => $this->link
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
      $fslist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function all4feed($fid)
   {
      $fslist = array();
      foreach($this->collection->find( array('feed_id' => $this->var2str($fid)) )->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function all4story($sid)
   {
      $fslist = array();
      foreach($this->collection->find( array('story_id' => $this->var2str($sid)) )->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function last4feed($fid)
   {
      $fslist = array();
      foreach($this->collection->find( array('feed_id' => $this->var2str($fid)) )->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function last4feeds($fids)
   {
      $fslist = array();
      if( count($fids) > 0 )
      {
         if( count($fids) == 1 )
            $filter = array('feed_id' => $this->var2str($fids[0]) );
         else
         {
            $filter = array('$or' => array());
            foreach($fids as $fid)
               $filter['$or'][] = array('feed_id' => $this->var2str($fid) );
         }
         foreach($this->collection->find($filter)->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $fs)
            $fslist[] = new feed_story($fs);
      }
      return $fslist;
   }
}

?>