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
require_once 'model/story_edition.php';
require_once 'model/story_visit.php';

class show_edition extends fs_controller
{
   public $story;
   public $edition;
   
   public function __construct()
   {
      parent::__construct('show_edition', 'Edición...');
      
      $se = new story_edition();
      $this->edition = FALSE;
      if( isset($_GET['id']) )
      {
         $this->edition = $se->get($_GET['id']);
         if($this->edition)
         {
            $this->story = $this->edition->story();
            if(!$this->story)
            {
               $this->edition->delete();
               $this->edition = FALSE;
            }
         }
      }
      
      if($this->edition AND isset($_POST['delete']) AND $this->visitor->admin)
      {
         $this->edition->delete();
         $this->edition = FALSE;
         $this->new_message('Edición eliminada correctamente.');
      }
      else if($this->edition)
      {
         $this->title = $this->edition->title . ' (edición)';
         
         if( !$this->story->readed() AND $this->visitor->human() AND  isset($_SERVER['REMOTE_ADDR']) )
            $this->story->read();
      }
      else
         $this->new_error_msg('Edición no encontrada. <a href="'.FS_PATH.'/index.php?page=search">Usa el buscador</a>.');
   }
   
   public function url()
   {
      if($this->edition)
         return $this->edition->url();
      else
         return parent::url();
   }
   
   public function get_description()
   {
      if($this->edition)
         return $this->edition->description;
      else
         return parent::get_description();
   }
}

?>