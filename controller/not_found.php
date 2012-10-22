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
   public function __construct()
   {
      parent::__construct('not_found', '¡Página no encontrada en '.FS_NAME.'!');
   }
   
   protected function process()
   {
      if( $this->visitor->mobile() )
         $this->template = 'main_page_mobile';
      else
         $this->template = 'main_page';
      
      $this->new_error_msg('¡Página no encontrada!');
      $this->visitor->add2log('Página no encontrada');
      
      $feed = new feed();
      $all_feeds = $feed->defaults();
      if( $all_feeds )
      {
         $random_feed = $all_feeds[ rand(0, count($all_feeds)-1) ];
         $this->stories = $random_feed->get_stories();
      }
   }
}

?>
