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

class select_feeds extends fs_controller
{
   public $feeds;
   
   public function __construct()
   {
      parent::__construct('select_feeds', 'Seleccionar fuentes de '.FS_NAME, 'select_feeds');
   }
   
   protected function process()
   {
      if( isset($_POST['feeds']) )
      {
         $this->visitor->clean_feeds();
         foreach($_POST['feeds'] as $fn)
            $this->visitor->add_feed($fn);
         
         $this->visitor->save_feeds();
         $this->new_message('Datos guardado correctamente.');
      }
      
      $feed = new feed();
      $this->feeds = $feed->all();
      foreach($this->feeds as &$f)
      {
         if( in_array($f, $this->visitor->get_feeds()) )
            $f->selected = TRUE;
      }
      unset($f);
   }
}

?>
