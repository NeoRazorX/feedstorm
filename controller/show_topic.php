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

require_once 'model/story_preview.php';
require_once 'model/topic.php';

class show_topic extends fs_controller
{
   public $children_topics;
   public $parent;
   public $preview;
   public $topic;
   
   public function __construct()
   {
      parent::__construct('show_topic', 'Tema...');
      $this->noindex = FALSE;
      $topic = new topic();
      $this->preview = new story_preview();
      
      $this->topic = FALSE;
      if( isset($_GET['id']) )
         $this->topic = $topic->get($_GET['id']);
      
      if($this->topic)
      {
         if( isset($_POST['title']) )
         {
            if( !$this->visitor->admin AND $this->visitor->points < 10)
            {
               $this->new_error_msg('Debes ser administrador o tener más de 10 puntos para poder editar esto.');
            }
            else
            {
               $this->topic->title = trim($_POST['title']);
               $this->topic->description = trim($_POST['description']);
               $this->topic->keywords = mb_strtolower( trim($_POST['keywords']), 'utf8' );
               
               if($_POST['parent'] != '')
               {
                  $parent = $this->topic->get($_POST['parent']);
                  if($parent)
                  {
                     $this->topic->parent = $parent->get_id();
                     $this->topic->importance = $parent->importance + 1;
                  }
               }
               
               $this->topic->save();
               $this->new_message('Tema modificado correctamente.');
            }
         }
         
         $this->title = $this->topic->title;
         $this->children_topics = $this->topic->all_from($this->topic->get_id());
         $this->parent = $topic->get($this->topic->parent);
      }
      else
      {
         $this->new_error_msg('Tema no encontrado.');
         header("HTTP/1.0 404 Not Found");
      }
   }
   
   public function get_description()
   {
      if($this->topic)
         return $this->topic->description;
      else
         return parent::get_description();
   }
   
   public function get_keywords()
   {
      if($this->topic)
         return $this->topic->keywords;
      else
         return parent::get_keywords();
   }
   
   public function url()
   {
      if($this->topic)
         return $this->topic->url();
      else
         return parent::url();
   }
}
