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

if( !function_exists('curl_init') )
   echo "Necesitas instalar php5-curl\n";
else if( !function_exists('imagecreatefromjpeg') )
   echo "Necesitas instalar php5-gd\n";
else if( !file_exists('config.php') )
   echo "Tienes que modificar el archivo config.php a partir del config-sample.php\n";
else
{
   $tiempo = explode(' ', microtime());
   $uptime = $tiempo[1] + $tiempo[0];
   
   require_once 'config.php';
   require_once 'base/fs_mongo.php';
   require_once 'model/feed.php';
   require_once 'model/feed_story.php';
   require_once 'model/media_item.php';
   require_once 'model/story.php';
   require_once 'model/story_edition.php';
   require_once 'model/story_media.php';
   require_once 'model/story_visit.php';
   require_once 'model/suscription.php';
   require_once 'model/visitor.php';
   
   $mongo = new fs_mongo();
   $feed = new feed();
   $feed_story = new feed_story();
   $media_item = new media_item();
   $story = new story();
   $story_edition = new story_edition();
   $story_media = new story_media();
   $story_visit = new story_visit();
   $suscription = new suscription();
   $visitor = new visitor();
   
   echo "Comprobamos los índices... ";
   $feed->install_indexes();
   $feed_story->install_indexes();
   $media_item->install_indexes();
   $story->install_indexes();
   $story_edition->install_indexes();
   $story_media->install_indexes();
   $story_visit->install_indexes();
   $suscription->install_indexes();
   $visitor->install_indexes();
   
   /// si se pasa el parámetro full_stories procesamos todas las historias
   if( count($_SERVER["argv"]) == 2 )
      $full = ($_SERVER['argv'][1] == 'redownload');
   else
      $full = FALSE;
   
   echo "\nComprobamos los modelos... ";
   if($full)
   {
      $story->full_redownload();
   }
   else
   {
      echo "\nComprobamos los modelos... ";
      $feed->cron_job();
      $feed_story->cron_job();
      $media_item->cron_job();
      $story->cron_job();
      $story_edition->cron_job();
      $story_media->cron_job();
      $story_visit->cron_job();
      $suscription->cron_job();
      $visitor->cron_job();
   }
   
   $mongo->close();
   
   $tiempo = explode(' ', microtime());
   echo "\nTiempo de ejecución: ".number_format($tiempo[1] + $tiempo[0] - $uptime, 3)." s\n";
}

?>