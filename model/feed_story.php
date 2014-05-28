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
      
      $this->id = NULL;
      $this->feed_id = NULL;
      $this->story_id = NULL;
      $this->date = time();
      $this->title = NULL;
      $this->link = NULL;
      
      if($fs)
      {
         $this->id = $fs['_id'];
         $this->feed_id = $fs['feed_id'];
         $this->story_id = $fs['story_id'];
         $this->date = $fs['date'];
         $this->title = $fs['title'];
         $this->link = $fs['link'];
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('feed_id' => 1, 'date' => -1) );
      $this->collection->ensureIndex( array('story_id' => 1, 'date' => -1) );
      $this->collection->ensureIndex('date');
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
         $story = new story();
         $this->story = $story->get($this->story_id);
      }
      return $this->story;
   }
   
   public function set_story($s)
   {
      $this->story = $s;
   }
   
   public function feed()
   {
      if( !isset($this->feed) )
      {
         $feed = new feed();
         $this->feed = $feed->get($this->feed_id);
      }
      return $this->feed;
   }
   
   public function feed_name()
   {
      $this->feed();
      if($this->feed)
         return $this->feed->name;
      else
         return '-';
   }
   
   public function feed_url()
   {
      $this->feed();
      if($this->feed)
         return $this->feed->url();
      else
         return '#';
   }
   
   public function meneame()
   {
      return ( mb_substr($this->link, 0, 23) == 'http://www.meneame.net/' );
   }
   
   public function reddit()
   {
      return ( mb_substr($this->link, 0, 22) == 'http://www.reddit.com/' );
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
   
   public function get_by_link($url)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('link' => $url) );
      if($data)
         return new feed_story($data);
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
      $this->feed_id = $this->var2str($this->feed_id);
      $this->story_id = $this->var2str($this->story_id);
      $this->title = $this->true_text_break($this->title, 149, 18);
      
      $data = array(
          'feed_id' => $this->feed_id,
          'story_id' => $this->story_id,
          'date' => $this->date,
          'title' => $this->title,
          'link' => $this->link
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
   
   public function delete4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('feed_id' => $this->var2str($fid)) );
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $fslist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function all4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $fslist = array();
      foreach($this->collection->find( array('feed_id' => $this->var2str($fid)) )->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function all4story($sid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $fslist = array();
      foreach($this->collection->find( array('story_id' => $this->var2str($sid)) )->sort(array('date'=>-1)) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function last4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $fslist = array();
      foreach($this->collection->find( array('feed_id' => $this->var2str($fid)) )->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $fs)
         $fslist[] = new feed_story($fs);
      return $fslist;
   }
   
   public function last4feeds($fids)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $fslist = array();
      $ssids = array();
      if($fids)
      {
         /// obtenemos los feed_stories
         $filter = array('feed_id' => array('$in' => $fids));
         foreach($this->collection->find($filter)->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $fs)
         {
            $found = FALSE;
            foreach($fslist as $fs2)
            {
               if($fs2->story_id == $fs['story_id'])
               {
                  $found = TRUE;
                  break;
               }
            }
            if( !$found )
            {
               $fslist[] = new feed_story($fs);
               $ssids[] = new MongoId($fs['story_id']);
            }
         }
         /// obtenemos las noticias
         $story = new story();
         foreach($story->all_from_array($ssids) as $s)
         {
            foreach($fslist as $i => $value)
            {
               if( $value->story_id == $this->var2str($s->get_id()) )
               {
                  $fslist[$i]->set_story($s);
                  break;
               }
            }
         }
      }
      return $fslist;
   }
   
   public function count4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return $this->collection->find( array('feed_id' => $this->var2str($fid)) )->count();
   }
   
   public function cron_job()
   {
      
   }
}
