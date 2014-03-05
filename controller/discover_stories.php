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

require_once 'model/feed.php';
require_once 'model/story.php';

class discover_stories extends fs_controller
{
   public $stories;
   
   public function __construct()
   {
      parent::__construct('discover_stories', 'Descubrir &lsaquo; '.FS_NAME);
      
      $this->stories = array();
      
      if( mt_rand(0, 1) == 0 )
      {
         $feed = new feed();
         $rfeed = $feed->random();
         if($rfeed)
            $this->stories = $rfeed->stories();
      }
      
      if( count($this->stories) < FS_MAX_STORIES )
      {
         $story = new story();
         $more_stories = $story->random_stories();
         
         $i = 0;
         while( $i < count($more_stories) AND count($this->stories) < FS_MAX_STORIES )
         {
            $this->stories[] = $more_stories[$i];
            $i++;
         }
      }
   }
   
   public function get_description()
   {
      return 'Descubre un mundo de noticias interesantes a golpe de clic.';
   }
}

?>