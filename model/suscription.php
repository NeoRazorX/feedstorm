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

class suscription extends fs_model
{
   public $visitor_id;
   public $feed_id;
   
   private $feed;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('suscriptions');
      if($s)
      {
         $this->id = $s['_id'];
         $this->visitor_id = $s['visitor_id'];
         $this->feed_id = $s['feed_id'];
      }
      else
      {
         $this->id = NULL;
         $this->visitor_id = NULL;
         $this->feed_id = NULL;
      }
   }
   
   public function install_indexes()
   {
      
   }
   
   public function feed()
   {
      if( !isset($this->feed) )
      {
         $feed = new feed();
         $this->feed = $feed->get($this->feed_id);
         
         /// si el feed ya no existe, mejor eliminar
         if( !$this->feed )
            $this->delete();
      }
      return $this->feed;
   }
   
   public function name()
   {
      $f = $this->feed();
      if($f)
         return $f->name;
      else
         return '-';
   }
   
   public function description()
   {
      $f = $this->feed();
      if($f)
         return $f->description;
      else
         return '-';
   }
   
   public function url()
   {
      $f = $this->feed();
      if($f)
         return $f->url();
      else
         return '#';
   }
   
   public function feed_url()
   {
      $f = $this->feed();
      if($f)
         return $f->url;
      else
         return '#';
   }
   
   public function last_update_timesince()
   {
      $f = $this->feed();
      if($f)
         return $f->last_update_timesince();
      else
         return '-';
   }
   
   public function suscriptors()
   {
      $f = $this->feed();
      if($f)
         return $f->suscriptors;
      else
         return '-';
   }
   
   public function num_stories()
   {
      $f = $this->feed();
      if($f)
         return $f->num_stories;
      else
         return '-';
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new suscription($data);
      else
         return FALSE;
   }
   
   public function exists()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => $this->id) );
      if($data)
         return TRUE;
      else
      {
         $filter = array(
             'visitor_id' => $this->var2str($this->visitor_id),
             'feed_id' => $this->var2str($this->feed_id)
         );
         $data = $this->collection->findone($filter);
         if($data)
         {
            $this->id = $data['_id'];
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $this->visitor_id = $this->var2str($this->visitor_id);
      $this->feed_id = $this->var2str($this->feed_id);
      
      $data = array(
          'visitor_id' => $this->visitor_id,
          'feed_id' => $this->feed_id
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
   
   public function delete4visitor($vid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('visitor_id' => $this->var2str($vid)) );
   }
   
   public function delete4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('feed_id' => $this->var2str($fid)) );
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $suslist = array();
      foreach($this->collection->find() as $s)
         $suslist[] = new suscription($s);
      return $suslist;
   }
   
   public function all4visitor($vid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $suslist = array();
      foreach($this->collection->find( array('visitor_id' => $this->var2str($vid)) ) as $s)
         $suslist[] = new suscription($s);
      return $suslist;
   }
   
   public function all4feed($fid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $suslist = array();
      foreach($this->collection->find( array('feed_id' => $this->var2str($fid)) ) as $s)
         $suslist[] = new suscription($s);
      return $suslist;
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

?>