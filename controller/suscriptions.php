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

class suscriptions extends fs_controller
{
   public $suscriptions;
   
   public function __construct()
   {
      parent::__construct('suscriptions', FS_NAME, 'suscriptions');
   }
   
   protected function process()
   {
      $suscription = new suscription();
      
      if( isset($_GET['suscribe']) AND $this->visitor->human() )
      {
         $suscription->visitor_id = $this->visitor->get_id();
         $suscription->feed_id = $_GET['suscribe'];
         $suscription->save();
         $this->new_message('Suscripción añadida.');
         
         /// actualizamos el número de suscriptores
         $feed = $suscription->feed();
         if( $feed )
         {
            $feed->suscriptors++;
            $feed->save();
         }
      }
      else if( isset($_GET['unsuscribe']) AND $this->visitor->human() )
      {
         $suscription2 = $suscription->get($_GET['unsuscribe']);
         if($suscription2)
         {
            /// actualizamos el número de suscriptores
            $feed = $suscription2->feed();
            if($feed)
            {
               $feed->suscriptors--;
               $feed->save();
            }
            
            $suscription2->delete();
            $this->new_message('Suscripción anulada.');
         }
         else
            $this->new_error_msg('Suscripción no encontrada.');
      }
      
      $this->suscriptions = $this->visitor->suscriptions();
   }
}

?>
