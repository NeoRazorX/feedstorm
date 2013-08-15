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

date_default_timezone_set('Europe/Madrid');

require_once 'config.php';

if( !defined('FS_MAX_AGE') )
   define('FS_MAX_AGE', 2592000);

require_once 'base/fs_mongo.php';
require_once 'model/feed.php';
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
         $visitor = new visitor();
         $feed = new feed();
         
         foreach($xml->item as $item)
         {
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