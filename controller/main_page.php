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

require_once 'base/fs_controller.php';

class main_page extends fs_controller
{
   public $stories;
   
   public function __construct()
   {
      parent::__construct('main_page', FS_NAME, FALSE);
   }
   
   protected function process()
   {
      $this->stories = $this->visitor->get_new_stories();
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
         $i = TRUE;
         foreach($this->stories as $s)
         {
            if($i)
               $columns[0][] = $s;
            else
               $columns[1][] = $s;
            $i = !$i;
         }
      }
      return $columns;
   }
}

?>