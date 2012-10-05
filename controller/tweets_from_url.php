<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

class tweets_from_url extends fs_controller
{
   public $story;
   public $tweets;
   
   public function __construct()
   {
      parent::__construct('tweets_from_url', 'Tweets...', 'tweets_from_url');
   }
   
   protected function process()
   {
      $this->story = FALSE;
      $this->tweets = array();
      
      if( isset($_GET['url']) )
      {
         $url = urldecode($_GET['url']);
         if( isset($_GET['feed']) )
         {
            $feed = new feed();
            $feed = $feed->get( urldecode($_GET['feed']) );
            if( $feed )
               $this->story = $feed->get_story_by_url($url);
         }
         
         $this->tweets = $this->tweet->all_from_url($url);
      }
   }
}

?>
