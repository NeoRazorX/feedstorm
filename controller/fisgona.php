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

require_once 'base/fs_cache.php';

class fisgona extends fs_controller
{
   private $cache;
   public $logs;
   
   public function __construct()
   {
      parent::__construct('fisgona', 'Fisgona de '.FS_NAME, 'fisgona');
   }
   
   protected function process()
   {
      $this->cache = new fs_cache();
      $this->logs = $this->visitor->get_logs();
   }
   
   public function get_story_ids()
   {
      if($_SERVER['REMOTE_ADDR'] == '127.0.0.1')
         return $this->cache->get_array('story_ids');
      else
         return FALSE;
   }
   
   public function get_image_filenames()
   {
      if($_SERVER['REMOTE_ADDR'] == '127.0.0.1')
         return $this->cache->get_array('filenames');
      else
         return FALSE;
   }
   
   public function get_image_votes()
   {
      if($_SERVER['REMOTE_ADDR'] == '127.0.0.1')
         return $this->cache->get_array('story_image_votes');
      else
         return FALSE;
   }
}

?>
