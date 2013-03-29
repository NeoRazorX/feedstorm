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
   
   libxml_use_internal_errors(TRUE);
   $xml = simplexml_load_file($_SERVER["argv"][1]);
   if($xml)
   {
      if( $xml->suscriptions )
      {
         foreach($xml->suscriptions as $sus)
         {
            $visitor = new visitor();
            $visitor->set_id( (string)$sus->user );
            $visitor->save();
            
            $feed = new feed();
            $feed->url = base64_decode( (string)$sus->feed );
            $feed->save();
            
            $suscription = new suscription();
            $suscription->visitor_id = $visitor->get_id();
            $suscription->feed_id = $feed->get_id();
            $suscription->save();
         }
      }
   }
   
   $mongo->close();
}

?>
