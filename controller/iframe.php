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

class iframe extends fs_controller
{
   public $stories;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Noticias relacionadas &lsaquo; '.FS_NAME);
      $story = new story();
      
      $max_stories = 6;
      if( isset($_GET['search']) )
      {
         $this->stories = $story->search($_GET['search']);
      }
      else
         $this->stories = $story->popular_stories($max_stories);
   }
   
   public function get_description()
   {
      return 'Noticias relacionadas de '.FS_NAME;
   }
}
