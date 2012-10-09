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

class go2url extends fs_controller
{
   public function __construct()
   {
      parent::__construct('go2url', 'Redireccionando...');
   }
   
   protected function process()
   {
      $this->template = FALSE;
      
      if( isset($_GET['url']) )
      {
         $url = urldecode( $_GET['url'] );
         
         $encontrada = FALSE;
         if( isset($_GET['feed']) )
         {
            $feed = new feed();
            $feed0 = $feed->get( urldecode($_GET['feed']) );
            if( $feed0 )
            {
               $story = $feed0->get_story_by_url($url);
               if( $story )
               {
                  $this->visitor->add2log($story->title);
                  $encontrada = TRUE;
               }
            }
         }
         if( !$encontrada )
            $this->visitor->add2log('Redireccionando a '.$url);
         
         header("location: ".$url);
      }
      else
         header("location: index.php");
   }
}

?>
