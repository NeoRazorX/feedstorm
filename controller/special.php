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

require_once 'model/story.php';
require_once 'model/story_preview.php';

class special extends fs_controller
{
   public $data;
   public $stories;
   public $feeds;
   public $topics;
   public $preview;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Especial');
      
      $this->preview = new story_preview();
      
      $this->data = FALSE;
      if( isset($_GET['id']) )
      {
         if( file_exists('tmp/special/'.$_GET['id'].'.json') )
         {
            $this->data = json_decode( file_get_contents('tmp/special/'.$_GET['id'].'.json') );
         }
      }
      
      if($this->data)
      {
         $this->stories = array();
         $story = new story();
         foreach($this->data->stories->popular as $sid)
         {
            $this->stories[] = $story->get($sid);
         }
         
         $this->feeds = array();
         $feed = new feed();
         foreach($this->data->feeds->popular as $fid)
         {
            $this->feeds[] = $feed->get($fid);
         }
         
         $this->topics = array();
         $topic = new topic();
         foreach($this->data->topics->popular as $tid)
         {
            $this->topics[] = $topic->get($tid);
         }
      }
      else
         $this->new_error_msg('Archivo no encontrado.');
   }
   
   public function show_number($num)
   {
      if($num >= 1000)
      {
         return intval($num/1000).'K';
      }
      else
         return $num;
   }
   
   public function divide_number($num1, $num2)
   {
      if($num1 == 0 OR $num2 == 0)
      {
         return 0;
      }
      else
         return $this->show_number( intval($num1/$num2) );
   }
   
   public function get_topics($tids)
   {
      $tlist = array();
      $topic = new topic();
      $topics = array();
      
      foreach($tids as $tid)
      {
         $t0 = $topic->get($tid);
         if($t0)
         {
            $topics[] = $t0;
         }
      }
      
      foreach($topics as $t1)
      {
         $found = FALSE;
         foreach($topics as $t2)
         {
            if( $t2->parent == (string)$t1->get_id() )
            {
               $found = TRUE;
               break;
            }
         }
         
         if(!$found AND count($tlist) < 3)
         {
            $tlist[] = $t1;
         }
      }
      
      return $tlist;
   }
   
   public function get_children($tid)
   {
      $tlist = array();
      
      $topic = new topic();
      foreach($topic->all_from($tid) as $tpic)
      {
         if( count($tlist) < 3 AND $tpic->icon != '')
         {
            $tlist[] = $tpic;
         }
      }
      
      return $tlist;
   }
}
