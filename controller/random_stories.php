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

class random_stories extends fs_controller
{
   public function __construct()
   {
      parent::__construct('random_stories', 'Ración aleatoria de '.FS_NAME, 'main_page');
   }
   
   protected function process()
   {
      $this->new_message('Aquí tienes una ración aleatoria de noticias.');
      
      $feed = new feed();
      $all_feeds = $feed->all();
      if( $all_feeds )
      {
         $random_feed = $all_feeds[ rand(0, count($all_feeds)-1) ];
         $this->stories = $random_feed->get_stories();
         $this->feed_name = $random_feed->name;
         $this->new_message('La fuente seleccionada es <b>'.$random_feed->name.'</b>.');
      }
   }
}

?>
