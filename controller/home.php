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

require_once 'model/comment.php';
require_once 'model/message.php';
require_once 'model/story_preview.php';

class home extends fs_controller
{
   public $preview;
   public $stories;
   
   public function __construct()
   {
      parent::__construct('home', 'Portada &lsaquo; '.FS_NAME);
      
      $this->noindex = FALSE;
      $this->preview = new story_preview();
      $this->stories = $this->visitor->last_stories();
      
      if( $this->visitor->admin )
      {
         $comment = new comment();
         $feedbacks = $comment->all4thread();
         if( count($feedbacks) > 0 )
         {
            if( isset($_COOKIE['last_feedback']) )
            {
               if($feedbacks[0]->get_id() != $_COOKIE['last_feedback'])
               {
                  $this->new_message('Tienes comentarios de feedback por <a class="btn btn-sm btn-default" href="'.FS_PATH.'feedback">leer</a>');
               }
            }
            else
            {
               $this->new_message('Tienes comentarios de feedback por <a class="btn btn-sm btn-default" href="'.FS_PATH.'feedback">leer</a>');
            }
         }
      }
      
      if( count($this->get_errors()) + count($this->get_messages()) == 0 )
      {
         $msg = new message();
         $num_msgs = 0;
         $messages = $msg->all2visitor( $this->visitor->get_id() );
         foreach($messages as $m)
         {
            if( !$m->readed )
               $num_msgs++;
         }
         if( $num_msgs > 0 )
         {
            $this->new_message('Tienes '.$num_msgs.' mensaje(s) nuevo(s). <a class="btn btn-sm btn-default" href="'
               .FS_PATH.'messages#messages">ver mensaje(s)</a>');
         }
      }
   }
   
   public function get_keywords()
   {
      $keys = array();
      
      if($this->stories)
      {
         foreach($this->stories as $s)
         {
            foreach( explode(',', $s->keywords) as $k2 )
            {
               if(trim($k2) != '')
                  $keys[] = trim($k2);
            }
         }
      }
      
      return join(', ', array_unique($keys));
   }
}
