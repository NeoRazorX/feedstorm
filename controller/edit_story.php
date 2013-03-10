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
require_once 'model/story_edition.php';
require_once 'model/story_visit.php';

class edit_story extends fs_controller
{
   public $story;
   public $story_edition;
   public $story_visit;
   
   public function __construct()
   {
      parent::__construct('edit_story', 'Editar noticia', 'Editar noticia', 'edit_story');
   }
   
   protected function process()
   {
      $this->story_edition = new story_edition();
      $this->story_visit = new story_visit();
      
      if( isset($_GET['id']) )
      {
         $story = new story();
         $this->story = $story->get($_GET['id']);
      }
      else
         $this->story = FALSE;
      
      if($this->story)
      {
         if( $this->visitor->human() AND isset($_SERVER['REMOTE_ADDR']) )
         {
            $se0 = $this->story_edition->get_by_params($this->story->get_id(), $this->visitor->get_id());
            if( $se0 )
               $this->story_edition = $se0;
            else
            {
               $this->story_edition->description = $this->story->description;
               $this->story_edition->story_id = $this->story->get_id();
               $this->story_edition->media_id = $this->story->media_id;
               $this->story_edition->title = $this->story->title;
               $this->story_edition->visitor_id = $this->visitor->get_id();
            }
            
            if( isset($_POST['title']) AND isset($_POST['description']) )
            {
               $this->story_edition->title = $_POST['title'];
               $this->story_edition->description = $_POST['description'];
               
               if( !isset($_POST['media_id']) )
                  $this->story_edition->media_id = NULL;
               else if($_POST['media_id'] == 'none')
                  $this->story_edition->media_id = NULL;
               else
                  $this->story_edition->media_id = $_POST['media_id'];
               
               $this->story_edition->save();
               $this->new_message('Noticia editada correctamente. Hac clic <a href="'.
                  $this->story_edition->url().'">aquí</a> para verla. Recuerda que
                     aparecerá en la sección <a href="'.FS_PATH.'/index.php?page=last_editions">ediciones</a>.');
            }
            
            $sv0 = $this->story_visit->get_by_params($this->story->get_id(), $_SERVER['REMOTE_ADDR']);
            if( $sv0 )
            {
               $sv0->edition_id = $this->story_edition->get_id();
               $sv0->save();
            }
            else
            {
               $this->story_visit->story_id = $this->story->get_id();
               $this->story_visit->edition_id = $this->story_edition->get_id();
               $this->story_visit->save();
               $this->story->clics++;
               $this->story->save();
            }
         }
      }
      else
         $this->new_error_msg('Noticia no encontrada.');
   }
   
   public function url()
   {
      if( $this->story )
         return $this->story->edit_url();
      else
         return parent::url();
   }
   
   public function get_description()
   {
      if( $this->story )
         return $this->story->description();
      else
         return parent::get_description();
   }
}

?>
