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

require_once 'model/topic.php';

class topic_list extends fs_controller
{
   public $topic;
   public $t_title;
   public $t_description;
   public $t_keywords;
   public $t_parent;
   
   public function __construct()
   {
      parent::__construct('topic_list', 'Temas &lsaquo; '.FS_NAME);
      
      $this->noindex = FALSE;
      $this->topic = new topic();
      $this->t_title = '';
      $this->t_description = '';
      $this->t_keywords = '';
      
      $this->t_parent = NULL;
      if( isset($_REQUEST['parent']) )
      {
         $this->t_parent = $_REQUEST['parent'];
      }
      
      if( isset($_POST['title']) )
      {
         $this->t_title = trim($_POST['title']);
         $this->t_description = trim($_POST['description']);
         $this->t_keywords = mb_strtolower( trim($_POST['title']), 'utf8' );
         
         if($this->t_parent != '')
         {
            $parent = $this->topic->get($this->t_parent);
            if($parent)
            {
               $this->topic->parent = $parent->get_id();
               $this->topic->importance = $parent->importance + 1;
            }
         }
         
         $this->topic->title = $this->t_title;
         $this->topic->description = $this->t_description;
         $this->topic->keywords = $this->t_keywords;
         $this->topic->valid = $this->visitor->admin;
         $this->topic->save();
         $this->new_message('Tema añadido correctamente.');
         
         /// ¿Avisamos al admin?
         if(!$this->topic->valid)
         {
            $comment = new comment();
            $comment->visitor_id = $this->visitor->get_id();
            $comment->nick = $this->visitor->nick;
            $comment->text = 'Nuevo tema añadido: '.$this->domain().$this->topic->url();
            $comment->save();
         }
         
         $this->t_title = '';
         $this->t_description = '';
         $this->t_keywords = '';
         
         header( 'Location: '.$this->topic->url() );
      }
      else if( isset($_GET['delete']) )
      {
         $topic = $this->topic->get($_GET['delete']);
         if($topic)
         {
            if( $this->visitor->admin )
            {
               $topic->delete();
               $this->new_message('Tema eliminado correctamente');
            }
            else
               $this->new_error_msg('Sólo un administrador puede eliminar un tema.');
         }
         else
            $this->new_error_msg('Tema no encontrado.');
      }
   }
   
   public function get_description()
   {
      return 'Listado de temas de '.FS_NAME.'.';
   }
}
