<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/feed.php';

class help extends fs_controller
{
   public $feed;
   
   public function __construct()
   {
      parent::__construct('help', 'Ayuda', 'Ayuda &lsaquo; '.FS_NAME, 'help');
      
      $this->feed = new feed();
   }
   
   public function show_max_age()
   {
      $time = FS_MAX_AGE;
      
      if($time <= 60)
         return $time.' segundos';
      else if(60 < $time && $time <= 3600)
         return round($time/60,0).' minutos';
      else if(3600 < $time && $time <= 86400)
         return round($time/3600,0).' horas';
      else if(86400 < $time && $time <= 604800)
         return round($time/86400,0).' dias';
      else if(604800 < $time && $time <= 2592000)
         return round($time/604800,0).' semanas';
      else if(2592000 < $time && $time <= 29030400)
         return round($time/2592000,0).' meses';
      else if($time > 29030400)
         return 'mucho tiempo';
   }
}

?>