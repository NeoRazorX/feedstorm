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

class feedback extends fs_controller
{
   public $comments;
   public $txt_comment;
   public $email;
   
   public function __construct()
   {
      parent::__construct('feedback', 'Feedback &lsaquo; '.FS_NAME);
      $comment = new comment();
      
      if($this->visitor->admin)
      {
         $this->comments = $comment->all4thread();
         if( count($this->comments) > 0 )
            setcookie('last_feedback', $this->comments[0]->get_id(), time()+FS_MAX_AGE, FS_PATH);
      }
      else
      {
         if( isset($_POST['comment']) )
            $this->txt_comment = $_POST['comment'];
         else
            $this->txt_comment = '';
         
         if( isset($_POST['email']) )
            $this->email = $_POST['email'];
         else
            $this->email = '';
         
         if( isset($_POST['human']) )
         {
            if($_POST['human'] == '')
            {
               $comment->visitor_id = $this->visitor->get_id();
               if($this->email == '')
                  $comment->nick = $this->visitor->nick;
               else
                  $comment->nick = $this->email;
               $comment->text = $this->txt_comment;
               $comment->save();
               
               $this->new_message('Mensaje enviado correctamente.');
               $this->txt_comment = '';
               $this->email = '';
            }
            else
               $this->new_error_msg('Tienes que borrar el número para demostrar que eres humano.');
         }
      }
   }
   
   public function get_description()
   {
      return 'Feedback de '.FS_NAME.'.';
   }
}

?>