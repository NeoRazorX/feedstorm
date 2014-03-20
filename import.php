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

date_default_timezone_set('Europe/Madrid');

require_once 'config.php';
require_once 'base/fs_mongo.php';
require_once 'model/feed.php';
require_once 'model/story.php';
require_once 'model/suscription.php';
require_once 'model/visitor.php';

if( !isset($_SERVER["argv"]) )
   echo "uso: php5 import.php ruta_del_archivo.xml\n";
else if( count($_SERVER["argv"]) != 2 )
   echo "uso: php5 import.php ruta_del_archivo.xml\n";
else if( !file_exists($_SERVER["argv"][1]) )
   echo "Archivo no encontrado.\n";
else
{
   $mongo = new fs_mongo();
   
   $xml = simplexml_load_file($_SERVER["argv"][1]);
   if($xml)
   {
      if( $xml->item )
      {
         echo "Procesando...";
         
         $visitor = new visitor();
         $feed = new feed();
         $feeds = array();
         
         /// importamos fuentes, usuarios y suscripciones
         foreach($xml->item as $item)
         {
            echo '.';
            
            $f0 = $feed->get_by_url( base64_decode( (string)$item->feed ) );
            if( !$f0 )
            {
               $f0 = new feed();
               $f0->url = base64_decode( (string)$item->feed );
               
               if( $f0->reddit() )
                  $f0->native_lang = FALSE;
               
               $f0->save();
            }
            
            if( (string)$item->user != '-' )
            {
               $vis0 = $visitor->get( (string)$item->user );
               if( !$vis0 )
               {
                  $vis0 = new visitor();
                  $vis0->force_insert( (string)$item->user );
               }
               
               $suscription = new suscription();
               $suscription->visitor_id = $vis0->get_id();
               $suscription->feed_id = $f0->get_id();
               $suscription->save();
            }
         }
         
         /// importamos los artículos más populares
         $story = new story();
         foreach($xml->story as $item)
         {
            echo '+';
            
            $story2 = $story->get_by_link( base64_decode( (string)$item->link ) );
            if(!$story2)
            {
               $st0 = new story;
               $st0->title = base64_decode( (string)$item->title );
               $st0->description = base64_decode( (string)$item->description );
               $st0->link = base64_decode( (string)$item->link );
               $st0->date = intval( (string)$item->date );
               $st0->published = $st0->date;
               $st0->clics = intval( (string)$item->clics );
               $st0->keywords = base64_decode( (string)$item->keywords );
               $st0->native_lang = ( (string)$item->native == 'TRUE' );
               $st0->name = base64_decode( (string)$item->name );
               $st0->save();
            }
         }
      }
      else
         echo "Estructura irreconocible.\n";
   }
   else
      echo "Error al leer el archivo.\n";
   
   $mongo->close();
   
   echo "Importación finalizada.\n";
}

?>