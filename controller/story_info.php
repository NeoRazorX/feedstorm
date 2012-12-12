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

class story_info extends fs_controller
{
   public $full_page;
   public $story;
   public $story_link_urlenc;
   
   public function __construct()
   {
      parent::__construct('story_info', 'Información de la noticia', 'story_info');
   }
   
   protected function process()
   {
      $this->story = FALSE;
      $this->full_page = !isset($_POST['no_full_page']);
      
      if( isset($_GET['story_id']) )
      {
         $story = new story();
         $this->story = $story->get($_GET['story_id']);
         if( $this->story )
         {
            $this->story_link_urlenc = urlencode( $this->story->link );
            $this->visitor->add2log($this->story);
         }
      }
   }
   
   public function get_description()
   {
      if( $this->story )
         return $this->story->description;
      else
         parent::get_description();
   }
   
   public function url()
   {
      if( $this->story )
         return $this->story->url();
      else
         return parent::url();
   }
}

?>
