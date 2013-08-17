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
require_once 'model/feed_story.php';
require_once 'model/story.php';
require_once 'model/story_visit.php';
require_once 'model/suscription.php';

class visitor extends fs_model
{
   public $nick;
   public $user_agent;
   public $first_login_date;
   public $last_login_date;
   public $human; /// humano confirmado (ha contenstado que si es humano)
   public $num_suscriptions;
   public $age;
   
   public $noob;
   public $need_save;
   
   private static $sus0;
   private $suscriptions;
   
   public function __construct($k=FALSE)
   {
      parent::__construct('visitors');
      if( $k )
      {
         $this->id = $k['_id'];
         $this->nick = $k['nick'];
         $this->user_agent = $k['user_agent'];
         
         if( isset($k['first_login_date']) )
            $this->first_login_date = $k['first_login_date'];
         else
            $this->first_login_date = $k['last_login_date'];
         
         $this->last_login_date = $k['last_login_date'];
         
         if( isset($k['human']) )
            $this->human = $k['human'];
         else
            $this->human = FALSE;
         
         if( isset($k['num_suscriptions']) )
            $this->num_suscriptions = $k['num_suscriptions'];
         else
            $this->num_suscriptions = 0;
         
         $this->age = $this->last_login_date - $this->first_login_date;
         $this->noob = FALSE;
         $this->need_save = FALSE;
      }
      else
      {
         $this->id = NULL;
         $this->nick = $this->random_string(12);
         $this->first_login_date = time();
         $this->human = FALSE;
         $this->num_suscriptions = 0;
         $this->login();
         $this->age = 0;
         $this->noob = TRUE;
         $this->need_save = TRUE;
      }
      
      if( !isset(self::$sus0) )
         self::$sus0 = new suscription();
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex('last_login_date');
      $this->collection->ensureIndex( array('age' => -1) );
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
      else if( !strstr(strtolower($this->user_agent), 'mozilla') AND !strstr(strtolower($this->user_agent), 'opera') )
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
      if( isset($_SERVER['HTTP_USER_AGENT']) )
         $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
      else
         $this->user_agent = 'unknown';
      
      if( time() > $this->last_login_date + 300 )
      {
         $this->last_login_date = time();
         $this->need_save = TRUE;
      }
   }
   
   public function num_visits()
   {
      $sv = new story_visit();
      return $sv->count4visitor($this->id);
   }
   
   public function last_visits()
   {
      $sv = new story_visit();
      return $sv->all4visitor($this->id);
   }
   
   public function suscriptions()
   {
      if( !isset($this->suscriptions) )
      {
         $this->suscriptions = self::$sus0->all4visitor($this->id);
         
         if( $this->num_suscriptions != count($this->suscriptions) )
         {
            $this->num_suscriptions = count($this->suscriptions);
            $this->need_save = TRUE;
            $this->save();
         }
      }
      return $this->suscriptions;
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
         return $story->popular_stories();
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
             'user_agent' => $this->user_agent,
             'first_login_date' => $this->first_login_date,
             'last_login_date' => $this->last_login_date,
             'human' => $this->human,
             'num_suscriptions' => $this->num_suscriptions,
             'mobile' => $this->mobile(),
             'age' => $this->age
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
   }
   
   public function force_insert($id)
   {
      $this->set_id($id);
      $data = array(
          '_id' => $this->id,
          'nick' => $this->nick,
          'user_agent' => $this->user_agent,
          'first_login_date' => $this->first_login_date,
          'last_login_date' => $this->last_login_date,
          'human' => $this->human,
          'num_suscriptions' => $this->num_suscriptions,
          'mobile' => $this->mobile(),
          'age' => $this->age
      );
      $this->add2history(__CLASS__.'::'.__FUNCTION__.'@insert');
      $this->collection->insert($data);
   }
   
   public function delete()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
      
      /// eliminamos las suscripciones
      self::$sus0->delete4visitor($this->id);
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
   
   public function usuals()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $vlist = array();
      foreach($this->collection->find( array('age' => array('$gt'=>300)) )->limit(FS_MAX_STORIES) as $v)
         $vlist[] = new visitor($v);
      return $vlist;
   }
   
   public function count_usuals()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return $this->collection->find( array('age' => array('$gt'=>300)) )->count();
   }
   
   public function show_count_usuals()
   {
      return number_format($this->count_usuals(), 0, ',', '.');
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