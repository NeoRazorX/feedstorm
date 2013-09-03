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

require_once 'model/comment.php';

class comments extends fs_controller
{
   public $comment;
   public $txt_comment;
   
   public function __construct()
   {
      parent::__construct('comments', 'Comentarios', 'comentarios@'.FS_NAME, 'comments');
      
      $this->comment = new comment();
      $this->txt_comment = '¡Escribe algo!';
      
      if( isset($_POST['comment']) )
      {
         if($this->visitor->human() AND $_POST['human'] == 'POZI' )
         {
            $comment2 = new comment();
            $comment2->nick = $this->visitor->nick;
            $comment2->text = $_POST['comment'];
            $comment2->save();
         }
         else
         {
            $this->new_error_msg('Ahhh, se siente. Has dicho que no eras humano.');
            $this->txt_comment = $_POST['comment'];
         }
      }
   }
}

?>