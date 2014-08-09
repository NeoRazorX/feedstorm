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
require_once 'model/topic.php';

class xml_stories extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'XML &lsaquo; '.FS_NAME);
      $this->template = FALSE;
      $story = new story();
      $topic = new topic();
      
      if( isset($_GET['last']) )
         $stoires = $story->last_stories();
      else
         $stoires = $story->published_stories();
      
      header("Content-type: text/xml");
      echo '<?xml version="1.0" encoding="UTF-8"?>';
      echo '<stories>';
      
      foreach($stoires as $st0)
      {
         echo '<story>'
         . '<url>http://'.$_SERVER["SERVER_NAME"].$st0->url().'</url>'
         . '<link>'.$this->sanitize_url($st0->link).'</link>'
         . '<title>'.$st0->title.'</title>'
         . '<description>'.$st0->description_uncut().'</description>'
         . '<date>'.$st0->show_date().'</date>'
         . '<popularity>'.$st0->popularity.'</popularity>'
         . '<keywords>'.$st0->keywords.'</keywords>';
         
         if($st0->topics)
         {
            echo '<topics>';
            
            foreach($st0->topics as $tid)
            {
               $tpic = $topic->get($tid);
               if($tpic)
               {
                  echo '<topic>'
                  . '<name>'.$tpic->name.'</name>'
                  . '<url>http://'.$_SERVER["SERVER_NAME"].$tpic->url().'</url>';
                  
                  if($tpic->icon != '')
                     echo '<pic>'.$tpic->icon.'</pic>';
                  
                  echo '</topic>';
               }
            }
            
            echo '</topics>';
         }
         
         if($st0->native_lang)
            echo '<native_lang/>';
         
         if($st0->parody)
            echo '<parody/>';
         
         if($st0->penalize)
            echo '<penalize/>';
         
         if($st0->featured)
            echo '<featured/>';
         
         $related = $st0->related_story();
         if($related)
            echo '<related>http://'.$_SERVER["SERVER_NAME"].$related->url().'</related>';
         
         echo '</story>';
      }
      
      echo '</stories>';
   }
   
   private function sanitize_url($link)
   {
      $link = str_replace('&amp;', '&', $link);
      return str_replace('&', '&amp;', $link);
   }
}
