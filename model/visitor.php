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
require_once 'model/feed_story.php';
require_once 'model/story.php';
require_once 'model/story_visit.php';
require_once 'model/suscription.php';

class visitor extends fs_model
{
   public $nick;
   public $ip;
   public $user_agent;
   public $first_login_date;
   public $last_login_date;
   public $admin;
   public $num_suscriptions;
   public $num_stories;
   public $num_editions;
   public $num_comments;
   public $num_visits;
   public $points;
   public $extra_points;
   
   public $noob;
   public $need_save;
   private $suscriptions;
   
   public function __construct($k=FALSE)
   {
      parent::__construct('visitors');
      if( $k )
      {
         $this->id = $k['_id'];
         $this->nick = $k['nick'];
         $this->ip = $k['ip'];
         $this->user_agent = $k['user_agent'];
         $this->first_login_date = $k['first_login_date'];
         $this->last_login_date = $k['last_login_date'];
         $this->admin = $k['admin'];
         $this->num_suscriptions = $k['num_suscriptions'];
         $this->num_stories = $k['num_stories'];
         $this->num_editions = $k['num_editions'];
         $this->num_comments = $k['num_comments'];
         $this->num_visits = $k['num_visits'];
         $this->points = $k['points'];
         $this->extra_points = $k['extra_points'];
         $this->noob = FALSE;
      }
      else
      {
         $this->id = NULL;
         $this->nick = $this->random_string(12);
         $this->ip = 'unknown';
         $this->user_agent = 'unknown';
         $this->first_login_date = time();
         $this->last_login_date = 0;
         $this->admin = FALSE;
         $this->num_suscriptions = 0;
         $this->num_stories = 0;
         $this->num_editions = 0;
         $this->num_comments = 0;
         $this->num_visits = 0;
         $this->points = 0;
         $this->extra_points = 0;
         $this->noob = TRUE;
      }
      
      $this->need_save = FALSE;
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex('last_login_date');
   }
   
   public function login_date()
   {
      return Date('Y-m-d H:m', $this->last_login_date);
   }
   
   public function login_timesince()
   {
      return $this->time2timesince($this->last_login_date);
   }
   
   public function age()
   {
      $time = $this->last_login_date - $this->first_login_date;
      
      if($time <= 60)
         return $time.' segundos';
      else if(60 < $time && $time <= 3600)
         return round($time/60,0).' minutos';
      else if(3600 < $time && $time <= 86400)
         return round($time/3600,0).' horas';
      else if(86400 < $time && $time <= 604800)
         return round($time/86400,0).' dias';
      else if(604800 < $time && $time <= 2592000)
         return round($time/604800,0).' semanas';
      else if(2592000 < $time && $time <= 29030400)
         return round($time/2592000,0).' meses';
      else if($time > 29030400)
         return 'más de un año';
   }
   
   public function mobile()
   {
      return (strstr(strtolower($this->user_agent), 'mobile') || strstr(strtolower($this->user_agent), 'android'));
   }
   
   public function human()
   {
      if($this->user_agent == 'unknown')
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'mozilla') === FALSE AND strstr(strtolower($this->user_agent), 'opera') === FALSE )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'href="http') )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'bot') )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'spider') )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'wget') )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'curl') )
         return FALSE;
      else if( strstr(strtolower($this->user_agent), 'sistrix') )
         return FALSE;
      else
         return TRUE;
   }
   
   public function login()
   {
      if( isset($_SERVER['REMOTE_ADDR']) )
         $this->ip = $_SERVER['REMOTE_ADDR'];
      else
         $this->ip = 'unknown';
      
      if( isset($_SERVER['HTTP_USER_AGENT']) )
         $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
      else
         $this->user_agent = 'unknown';
      
      if( time() > $this->last_login_date + 300 )
      {
         $this->last_login_date = time();
         $this->need_save = TRUE;
      }
      
      if( $this->num_editions < (2*$this->num_visits) AND $this->num_stories < (2*$this->num_visits) )
         $this->points = intval( ($this->num_comments+$this->num_editions+$this->num_stories)/3 ) + $this->extra_points;
      else
         $this->points = 0;
   }
   
   public function last_visits()
   {
      $sv = new story_visit();
      
      $num_visits = $sv->count4visitor($this->id);
      if($this->num_visits != $num_visits)
      {
         $this->num_visits = $num_visits;
         $this->need_save = TRUE;
         $this->save();
      }
      
      return $sv->all4visitor($this->id);
   }
   
   public function suscriptions()
   {
      if( !isset($this->suscriptions) )
      {
         $sus0 = new suscription();
         $this->suscriptions = $sus0->all4visitor($this->id);
         
         if($this->num_suscriptions != count($this->suscriptions) )
         {
            $this->num_suscriptions = count($this->suscriptions);
            $this->need_save = TRUE;
            $this->save();
         }
      }
      return $this->suscriptions;
   }
   
   public function browser()
   {
      return $this->true_text_break($this->user_agent, 70);
   }
   
   public function last_stories()
   {
      if( $this->suscriptions() )
      {
         $fids = array();
         foreach($this->suscriptions as $sus)
            $fids[] = $sus->feed_id;
         $feed_story = new feed_story();
         $stories = array();
         foreach($feed_story->last4feeds($fids) as $fs)
         {
            if( $fs->story() )
               $stories[] = $fs->story();
         }
         return $stories;
      }
      else
      {
         $story = new story();
         return $story->published_stories();
      }
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         $data = $this->collection->findone( array('_id' => new MongoId($id)) );
         if($data)
            return new visitor($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
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
      if( $this->need_save AND $this->human() )
      {
         $data = array(
             'nick' => $this->nick,
             'ip' => $this->ip,
             'user_agent' => $this->user_agent,
             'first_login_date' => $this->first_login_date,
             'last_login_date' => $this->last_login_date,
             'admin' => $this->admin,
             'num_suscriptions' => $this->num_suscriptions,
             'num_stories' => $this->num_stories,
             'num_editions' => $this->num_editions,
             'num_comments' => $this->num_comments,
             'num_visits' => $this->num_visits,
             'points' => $this->points,
             'extra_points' => $this->extra_points
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
         
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function force_insert($id)
   {
      $this->set_id($id);
      $data = array(
          '_id' => $this->id,
          'nick' => $this->nick,
          'ip' => $this->ip,
          'user_agent' => $this->user_agent,
          'first_login_date' => $this->first_login_date,
          'last_login_date' => $this->last_login_date,
          'admin' => $this->admin,
          'num_suscriptions' => $this->num_suscriptions,
          'num_stories' => $this->num_stories,
          'num_editions' => $this->num_editions,
          'num_comments' => $this->num_comments,
          'num_visits' => $this->num_visits,
          'points' => $this->points,
          'extra_points' => $this->extra_points
      );
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->insert($data);
   }
   
   public function delete()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
      
      /// eliminamos las suscripciones
      $sus0 = new suscription();
      $sus0->delete4visitor($this->id);
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $vlist = array();
      foreach($this->collection->find() as $v)
         $vlist[] = new visitor($v);
      return $vlist;
   }
   
   public function last()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $vlist = array();
      foreach($this->collection->find()->sort(array('last_login_date'=>-1))->limit(FS_MAX_STORIES) as $v)
         $vlist[] = new visitor($v);
      return $vlist;
   }
   
   public function cron_job()
   {
      echo "\nEliminamos usuarios inactivos...";
      foreach($this->collection->find( array('last_login_date' => array('$lt'=>time()-FS_MAX_AGE)) ) as $v)
      {
         $visit0 = new visitor($v);
         $visit0->delete();
         echo '.';
      }
   }
}

?>