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
require_once 'model/suscription.php';

class feed_list extends fs_controller
{
   public $feed;
   public $feed_list;
   
   public function __construct()
   {
      parent::__construct('feed_list', FS_NAME, 'feed_list');
   }
   
   protected function process()
   {
      $this->feed = new feed();
      
      if( isset($_POST['feed_url']) AND $this->visitor->human() )
      {
         $feed0 = $this->feed->get_by_url($_POST['feed_url']);
         if($feed0)
         {
            $this->new_message('Esta fuente ya existía.');
            
            /// nos suscribimos
            $suscription = new suscription();
            $suscription->visitor_id = $this->visitor->get_id();
            $suscription->feed_id = $feed0->get_id();
            $suscription->save();
            $this->new_message('Además te has suscrito a dicha fuente.');
            
            /// actualizamos el número de suscriptores
            $feed0->suscriptors++;
            $feed0->save();
         }
         else
         {
            $this->feed->url = $_POST['feed_url'];
            if( $this->feed->save() )
            {
               $this->new_message('Se ha añadido '.$_POST['feed_url'].' como fuente.');
               
               /// nos suscribimos
               $suscription = new suscription();
               $suscription->visitor_id = $this->visitor->get_id();
               $suscription->feed_id = $this->feed->get_id();
               $suscription->save();
               $this->new_message('Además te has suscrito a dicha fuente.');
               
               /// actualizamos el número de suscriptores
               $this->feed->suscriptors++;
               $this->feed->save();
            }
         }
      }
      
      $this->feed_list = $this->feed->all();
   }
   
   public function get_description()
   {
      if( $this->feed )
         return $this->feed->description;
      else
         return parent::get_description();
   }
}

?>
