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

class not_found extends fs_controller
{
   public $preview;
   public $stories;
   
   public function __construct()
   {
      parent::__construct('not_found', '¡Página no encontrada en '.FS_NAME.'!');
      
      $this->template = 'home';
      $this->new_error_msg('¡Página no encontrada! <a href="'.FS_PATH.'search">Usa el buscador</a>.');
      
      $this->preview = new story_preview();
      $story = new story();
      $this->stories = $story->popular_stories();
   }
   
   public function get_description()
   {
      return 'Artículo no encontrado en '.FS_NAME.'. ¡Usa el buscador! A Ver si tienes más suerte.';
   }
}

?>