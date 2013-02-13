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

require_once 'model/story.php';
require_once 'model/story_visit.php';

class show_story extends fs_controller
{
   public $story;
   
   public function __construct()
   {
      parent::__construct('show_story', 'noticia...', 'show_story');
   }
   
   protected function process()
   {
      $story_visit = new story_visit();
      
      if( isset($_GET['id']) )
      {
         $story = new story();
         $this->story = $story->get($_GET['id']);
      }
      else
         $this->story = FALSE;
      
      if($this->story)
      {
         if( $this->visitor->human() AND  isset($_SERVER['REMOTE_ADDR']) )
         {
            $this->story->read();
            
            $sv0 = $story_visit->get_by_params($this->story->get_id(), $_SERVER['REMOTE_ADDR']);
            if( !$sv0 )
            {
               $story_visit->story_id = $this->story->get_id();
               $story_visit->save();
               $this->story->clics++;
               $this->story->save();
            }
         }
         
         if( isset($_GET['redir']) )
         {
            $this->template = FALSE;
            header('location: '.$this->story->link);
         }
         else
            $this->title = $this->story->title;
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
   
   public function get_description()
   {
      if( $this->story )
         return $this->story->description;
      else
         return parent::get_description();
   }
}

?>
