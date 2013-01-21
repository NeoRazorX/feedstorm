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

class home extends fs_controller
{
   public $popular;
   public $show_info;
   public $stories;
   
   public function __construct()
   {
      parent::__construct('home', FS_NAME, 'home');
   }
   
   protected function process()
   {
      if( isset($_GET['show_info']) )
      {
         $this->show_info = FALSE;
         setcookie('home_info', 'FALSE', time()+315360000);
      }
      else
         $this->show_info = !isset($_COOKIE['home_info']);
      
      $this->stories = $this->visitor->last_stories();
      if( count($this->stories) == 0 )
      {
         $story = new story();
         $this->popular = $story->popular_stories();
      }
      else
         $this->popular = array();
   }
}

?>