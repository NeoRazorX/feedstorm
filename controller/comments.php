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

class comments extends fs_controller
{
   public $comments;
   
   public function __construct()
   {
      parent::__construct('comments', 'Foro &lsaquo; '.FS_NAME);
      
      $this->noindex = FALSE;
      $comment = new comment();
      $this->comments = $comment->all();
   }
   
   public function get_description()
   {
      return 'Chat general de '.FS_NAME.' junto con todas las conversaciones recientes de la web.';
   }
}
