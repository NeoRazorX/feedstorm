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

require_once 'model/story_edition.php';

class last_editions extends fs_controller
{
   public $editions;
   
   public function __construct()
   {
      parent::__construct('last_editions', 'Ediciones &lsaquo; '.FS_NAME);
      
      $se = new story_edition();
      $this->editions = $se->last_editions();
   }
   
   public function get_description()
   {
      return 'Últimas modificaciones realizadas por los usuarios. Artículos corregidos o mejorados.';
   }
}
