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

class explore_feed extends fs_controller
{
   public function __construct()
   {
      parent::__construct('explore_feed', 'Explorar fuentes de '.FS_NAME, 'main_page');
   }
   
   protected function process()
   {
      $encontrado = FALSE;
      if( isset($_GET['feed']) )
      {
         $feed = new feed();
         $feed0 = $feed->get($_GET['feed']);
         if( $feed0 )
         {
            $this->new_message("Historias de <b>".$feed0->name.'</b>. Puedes configurar tus fuentes de noticias desde
               <b>menu &gt; preferencias</b>.');
            $this->stories = $feed0->get_stories();
            $this->feed_name = $feed0->name;
            $encontrado = TRUE;
         }
      }
      if( !$encontrado )
         $this->new_error_msg('Fuente no encontrada.');
   }
}

?>
