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

class not_found extends fs_controller
{
   public $stories;
   
   public function __construct()
   {
      parent::__construct('not_found', 'Not found!', FALSE);
   }
   
   protected function process()
   {
      $this->new_error_msg('Page not found!');
      
      $this->template = 'main_page';
      $this->stories = array();
      $feed = new feed();
      $all_feeds = $feed->all();
      if( $all_feeds )
      {
         $random_feed = $all_feeds[ rand(0, count($all_feeds)-1) ];
         $this->stories = $random_feed->get_stories();
      }
   }
   
   public function get_columns()
   {
      if($this->visitor->mobile() OR count($this->stories) < 10)
         $columns = array( $this->stories );
      else
      {
         $columns = array(
             array(),
             array()
         );
         $size0 = 0;
         $size1 = 0;
         foreach($this->stories as $s)
         {
            if( $size0 <= $size1 )
            {
               $columns[0][] = $s;
               $size0 += $s->size();
            }
            else
            {
               $columns[1][] = $s;
               $size1 += $s->size();
            }
         }
      }
      return $columns;
   }
}

?>
