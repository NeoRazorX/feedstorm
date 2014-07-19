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

require_once 'model/story_preview.php';

/**
 * Hace una previsualización de artículos para forzar la comprobación de imágenes.
 * @param type $story
 */
function chan6(&$story)
{
   $story_preview = new story_preview();
   $last_stories = array_merge($story->last_stories(200), $story->random_stories(100));
   
   foreach($last_stories as $value)
   {
      echo '.';
      
      $story_preview->load($value->link, $value->description_uncut());
   }
}

chan6($story);