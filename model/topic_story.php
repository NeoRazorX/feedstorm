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

class topic_story extends fs_model
{
   public $topic_id;
   public $story_id;
   public $date;
   public $popularity;
   
   public function __construct($ts=FALSE)
   {
      parent::__construct('topic_stories');
      
      $this->topic_id = NULL;
      $this->story_id = NULL;
      $this->date = time();
      $this->popularity = 0;
      
      if($ts)
      {
         $this->id = $ts['_id'];
         $this->topic_id = $ts['topic_id'];
         $this->story_id = $ts['story_id'];
         $this->date = $ts['date'];
         $this->popularity = $ts['popularity'];
      }
   }
   
   public function install_indexes()
   {
      
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         $data = $this->collection->findone( array('_id' => new MongoId($id)) );
         if($data)
            return new topic_story($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
   }
   
   public function get2($tid, $sid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         $data = $this->collection->findone( array('topic_id' => $this->var2str($tid), 'story_id' => $this->var2str($sid)) );
         if($data)
            return new topic_story($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
   }
   
   /**
    * He detectado muchos duplicados, así que he modificado esta función para eliminarlos.
    * Una vez solucionado habrá que encontrar el error que produce los duplicados
    * y volver a cambiar esta función.
    */
   public function exists()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $data2 = FALSE;
      
      if( isset($this->story_id) AND isset($this->topic_id) )
      {
         $data = $this->get2($this->topic_id, $this->story_id);
      }
      
      if( isset($this->id) )
      {
         $data2 = $this->collection->findone( array('_id' => $this->id) );
      }
      
      if($data AND $data2)
      {
         $this->id = $data->get_id();
         
         /// eliminamos el duplicado
         $aux = new topic_story($data2);
         $aux->delete();
         
         return TRUE;
      }
      else if($data)
      {
         $this->id = $data->get_id();
         return TRUE;
      }
      else if($data2)
      {
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function save()
   {
      $this->topic_id = $this->var2str($this->topic_id);
      $this->story_id = $this->var2str($this->story_id);
      
      $data = array(
          'topic_id' => $this->topic_id,
          'story_id' => $this->story_id,
          'date' => $this->date,
          'popularity' => $this->popularity
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
   
   public function delete4topic($tid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('topic_id' => $this->var2str($tid)) );
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $t)
         $tlist[] = new topic_story($t);
      
      return $tlist;
   }
   
   public function all4story($sid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      foreach($this->collection->find(array('story_id'=>$this->var2str($sid))) as $t)
         $tlist[] = new topic_story($t);
      
      return $tlist;
   }
   
   public function all4topic($tid, $offset=0)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      foreach($this->collection->find(array('topic_id'=>$this->var2str($tid)))->sort(array('date'=>-1))->skip($offset)->limit(FS_MAX_STORIES) as $t)
         $tlist[] = new topic_story($t);
      
      return $tlist;
   }
   
   public function best4topic($tid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      $search = array( 'topic_id' => $this->var2str($tid) );
      $tssid = FALSE;
      foreach($this->collection->find($search)->sort(array('popularity'=>-1))->limit(FS_MAX_STORIES) as $t)
      {
         /// aprobechamos para buscar fallos
         if($t['story_id'] == $tssid)
         {
            $this->new_error('Se ha detectado un artículo duplicado enlazado a este tema.');
            $fail = new topic_story($t);
            $fail->delete();
         }
         else
         {
            $tssid = $t['story_id'];
            $tlist[] = new topic_story($t);
         }
      }
      
      /// ordenamos por fecha
      usort($tlist, function($a, $b) {
         if($a->date == $b->date)
            return 0;
         else if($a->date > $b->date)
            return -1;
         else
            return 1;
      } );
      
      return $tlist;
   }
   
   public function count4topic($tid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return $this->collection->find( array('topic_id' => $this->var2str($tid)) )->count();
   }
   
   public function cron_job()
   {
      $story = new story();
      
      $offset = mt_rand(0, max( array(0, $this->count() - FS_MAX_STORIES) ) );
      foreach($this->collection->find()->sort(array('date'=>-1))->skip($offset)->limit(FS_MAX_STORIES) as $t)
      {
         $ts0 = new topic_story($t);
         $st0 = $story->get($ts0->story_id);
         if($st0)
         {
            if( $ts0->popularity != $st0->max_popularity() )
            {
               $ts0->popularity = $st0->max_popularity();
               $ts0->save();
            }
         }
         else
            $ts0->delete();
      }
   }
}
