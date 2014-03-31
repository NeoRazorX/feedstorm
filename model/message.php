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
require_once 'model/visitor.php';

class message extends fs_model
{
   public $from;
   public $from_nick;
   public $to;
   public $to_nick;
   public $date;
   public $ip;
   public $text;
   public $readed;
   public $broadcast;
   
   public function __construct($m = FALSE)
   {
      parent::__construct('messages');
      
      $this->id = NULL;
      $this->from = NULL;
      $this->from_nick = 'anonymous';
      $this->to = NULL;
      $this->to_nick = 'anonymous';
      $this->date = time();
      
      $this->ip = 'unknown';
      if( isset($_SERVER['REMOTE_ADDR']) )
         $this->ip = $_SERVER['REMOTE_ADDR'];
      
      $this->text = '';
      $this->readed = FALSE;
      $this->broadcast = FALSE;
      
      if($m)
      {
         $this->id = $m['_id'];
         $this->from = $m['from'];
         $this->from_nick = $m['from_nick'];
         $this->to = $m['to'];
         $this->to_nick = $m['to_nick'];
         $this->date = $m['date'];
         $this->ip = $m['ip'];
         $this->text = $m['text'];
         $this->readed = $m['readed'];
         $this->broadcast = $m['broadcast'];
      }
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function install_indexes()
   {
      
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new message($data);
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
      $this->from = $this->var2str($this->from);
      $this->to = $this->var2str($this->to);
      $this->text = $this->true_text_break($this->text, 999);
      
      $data = array(
          'from' => $this->from,
          'from_nick' => $this->from_nick,
          'to' => $this->to,
          'to_nick' => $this->to_nick,
          'date' => $this->date,
          'ip' => $this->ip,
          'text' => $this->text,
          'readed' => $this->readed,
          'broadcast' => $this->broadcast
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
      
      $mlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $m)
         $mlist[] = new message($m);
      
      return $mlist;
   }
   
   public function all2visitor($vid)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $mlist = array();
      $search = array( 'to' => $this->var2str($vid) );
      foreach($this->collection->find($search)->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $m)
         $mlist[] = new message($m);
      
      return $mlist;
   }
   
   public function cron_job()
   {
      $visitor = new visitor();
      
      foreach($this->collection->find( array('broadcast'=>TRUE) ) as $m)
      {
         $message = new message($m);
         
         foreach($visitor->usuals(1000) as $v)
         {
            if($v->num_visits > 1)
            {
               $msg2 = new message();
               $msg2->from = $message->from;
               $msg2->from_nick = $message->from_nick;
               $msg2->ip = $message->ip;
               $msg2->to = $v->get_id();
               $msg2->to_nick = $v->nick;
               $msg2->text = $message->text;
               $msg2->save();
            }
         }
         
         $message->delete();
      }
   }
}
