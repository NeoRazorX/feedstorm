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

require_once 'model/feed.php';
require_once 'model/feed_story.php';
require_once 'model/media_item.php';
require_once 'model/story.php';
require_once 'model/story_edition.php';
require_once 'model/story_media.php';
require_once 'model/story_visit.php';
require_once 'model/suscription.php';
require_once 'model/visitor.php';

class stats extends fs_controller
{
   public $feed;
   public $feed_story;
   public $media_item;
   public $story;
   public $story_edition;
   public $story_media;
   public $story_visit;
   public $suscription;
   
   public $showing;
   
   public function __construct()
   {
      parent::__construct('stats', 'Estadísticas', 'Estadísticas &lsaquo; '.FS_NAME, 'stats');
      
      $this->feed = new feed();
      $this->feed_story = new feed_story();
      $this->media_item = new media_item();
      $this->story = new story();
      $this->story_edition = new story_edition();
      $this->story_media = new story_media();
      $this->story_visit = new story_visit();
      $this->suscription = new suscription();
      
      $this->showing = 'visits';
      if( isset($_GET['showing']) )
         $this->showing = $_GET['showing'];
   }
   
   public function tmp_size($path='tmp', $show_units=TRUE)
   {
      $total_size = 0;
      $files = scandir($path);
      
      foreach($files as $t)
      {
         if(is_dir(rtrim($path, '/') . '/' . $t))
         {
            if($t<>"." && $t<>"..")
            {
               $size = $this->tmp_size( rtrim($path, '/').'/'.$t, FALSE );
               $total_size += $size;
            }
         }
         else
         {
            $size = filesize( rtrim($path, '/').'/'.$t );
            $total_size += $size;
         }
      }
      
      if($show_units)
      {
         $mod = 1024;
         $units = explode(' ','B KB MB GB TB PB');
         
         for($i = 0; $total_size > $mod; $i++)
            $total_size /= $mod;
         
         return round($total_size, 2) . ' ' . $units[$i];
      }
      else
         return $total_size;
   }
   
   public function analyze_visits()
   {
      if( isset($_SERVER['REMOTE_ADDR']) )
         $ip = $_SERVER['REMOTE_ADDR'];
      else
         $ip = 'unknown';
      
      $visits = $this->story_visit->last(FS_MAX_STORIES * 4, $ip);
      $aux = array();
      
      foreach($visits as $i => $value)
      {
         if( array_key_exists($value->story_id, $aux) )
            $aux[$value->story_id]++;
         else
            $aux[$value->story_id] = 1;
      }
      
      arsort($aux);
      
      $stlist = array();
      $n = 0;
      foreach($aux as $i => $value)
      {
         if($n < FS_MAX_STORIES AND $value > 1)
         {
            $stlist[] = array(
                'story' => $this->story->get($i),
                'visits' => $value
            );
         }
         else
            break;
         
         $n++;
      }
      
      return $stlist;
   }
}

?>