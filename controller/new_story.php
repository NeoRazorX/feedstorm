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

class new_story extends fs_controller
{
   public $s_title;
   public $s_description;
   public $s_link;
   
   public function __construct()
   {
      parent::__construct('new_story', 'Escribir &lsaquo; '.FS_NAME);
      
      if( isset($_POST['title']) )
         $this->s_title = $_POST['title'];
      
      if( isset($_POST['description']) )
         $this->s_description = $_POST['description'];
      
      if( isset($_POST['link']) )
         $this->s_link = $_POST['link'];
      
      if( isset($_POST['human']) AND $this->visitor->human() )
      {
         if($_POST['human'] == '' OR $this->visitor->admin)
         {
            if( mb_strlen($_POST['title']) > 5 AND mb_strlen($_POST['description']) > 50 )
            {
               $story = new story();
               $story->title = $_POST['title'];
               $story->description = $_POST['description'];
               
               if($_POST['link'] == '')
               {
                  $this->save_story_and_more($story);
               }
               else
               {
                  $story2 = $story->get_by_link($_POST['link']);
                  if($story2)
                  {
                     $this->new_error_msg('Ya han enviado este enlace, puedes ver el artículo <a href="'.$story2->url().'">aquí</a>.');
                  }
                  else
                  {
                     $story->link = $_POST['link'];
                     $this->save_story_and_more($story);
                  }
               }
            }
            else
               $this->new_error_msg('Tienes que escribir más...');
         }
         else
            $this->new_error_msg('Tienes que borrar el número para demostrar que eres humano.');
      }
   }
   
   private function save_story_and_more(&$story)
   {
      $story->save();
      
      /// guardamos una edicion para saber el usuario y la ip
      $se = new story_edition();
      $se->story_id = $story->get_id();
      $se->visitor_id = $this->visitor->get_id();
      $se->nick = $this->visitor->nick;
      $se->title = $story->title;
      $se->description = $story->description;
      $se->points = $this->visitor->points;
      $se->save();
      
      /// enlazamos el artículo con la edición
      $story->num_editions = 1;
      $story->edition_id = $se->get_id();
      $story->save();
      
      /// ahora actualizamos al usuario
      $this->visitor->num_stories++;
      $this->visitor->num_editions++;
      $this->visitor->need_save = TRUE;
      $this->visitor->save();
      
      header( 'Location: '.$story->url() );
   }
}

?>