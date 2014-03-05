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

require_once 'model/feed.php';
require_once 'model/suscription.php';

class feed_list extends fs_controller
{
   public $feed;
   public $feed_list;
   
   public function __construct()
   {
      parent::__construct('feed_list', 'Fuentes &lsaquo; '.FS_NAME);
      
      $this->noindex = FALSE;
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
            $this->new_message('Además te has suscrito a dicha fuente y por tanto
               sus noticias aparecerán en tu portada.');
            
            /// actualizamos el número de suscriptores
            $feed0->suscriptors++;
            $feed0->save();
         }
         else if( $_POST['human'] != '' )
         {
            $this->new_error_msg('No has borrado el número para demostrar que eres humano, y si no eres
               humano no puedes añadir fuentes. Y si, ya sé que esto es nazismo puro,
               pero es una forma sencilla de atajar el SPAM.');
         }
         else
         {
            $this->feed->url = $_POST['feed_url'];
            
            if( $this->feed->reddit() )
               $this->feed->native_lang = FALSE;
            
            if( $this->feed->save() )
            {
               $this->new_message('Se ha añadido '.$_POST['feed_url'].' como fuente.
                  Se examinará en los próximos minutos.');
               
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
      return 'Listado de fuentes de '.FS_NAME.'. Suscríbete a las más interesantes,
         o añade las tuyas, si no están ya.';
   }
}

?>