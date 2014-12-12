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
require_once 'model/story_preview.php';

class popular_stories extends fs_controller
{
   public $preview;
   public $stories;
   public $totales;
   
   public function __construct()
   {
      parent::__construct('popular_stories', 'Populares &lsaquo; '.FS_NAME);
      $this->template = 'discover_stories';
      
      $this->noindex = FALSE;
      $this->preview = new story_preview();
      $story = new story();
      $this->stories = $story->published_stories();
      $this->totales = array(
          'clics' => 0,
          'tweets' => 0,
          'likes' => 0,
          'plusones' => 0,
          'meneos' => 0
      );
      
      foreach($this->stories as $i => $value)
      {
         $this->totales['clics'] += $value->clics;
         $this->totales['tweets'] += $value->tweets;
         $this->totales['likes'] += $value->likes;
         $this->totales['plusones'] += $value->plusones;
         $this->totales['meneos'] += $value->meneos;
      }
   }
   
   public function get_description()
   {
      return 'Listado de las noticias de actualidad más populares, las que tienen más clics
         y más menciones en las redes sociales.';
   }
   
   public function show_number($num)
   {
      if($num >= 1000)
      {
         return intval($num/1000).'K';
      }
      else
         return $num;
   }
}
