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
///error_reporting(E_ERROR | E_WARNING | E_PARSE);

if( !function_exists('curl_init') )
{
   echo "Necesitas instalar php5-curl\n";
}
else if( !file_exists('config.php') )
{
   echo "Tienes que modificar el archivo config.php a partir del config-sample.php\n";
}
else
{
   require_once 'config.php';
   require_once 'base/fs_mongo.php';
   require_once 'model/comment.php';
   require_once 'model/feed.php';
   require_once 'model/feed_story.php';
   require_once 'model/message.php';
   require_once 'model/story.php';
   require_once 'model/story_edition.php';
   require_once 'model/story_preview.php';
   require_once 'model/story_visit.php';
   require_once 'model/suscription.php';
   require_once 'model/topic.php';
   require_once 'model/topic_story.php';
   require_once 'model/visitor.php';
   
   $mongo = new fs_mongo();
   $comment = new comment();
   $feed = new feed();
   $feed_story = new feed_story();
   $message = new message();
   $story = new story();
   $story_edition = new story_edition();
   $story_visit = new story_visit();
   $suscription = new suscription();
   $topic = new topic();
   $topic_story = new topic_story();
   $visitor = new visitor();
   
   if( count($_SERVER["argv"]) == 2 )
   {
      if($_SERVER["argv"][1] == 'END')
      {
         echo "\nComprobamos los modelos... ";
         $comment->cron_job();
         $feed_story->cron_job();
         $message->cron_job();
         $story->cron_job();
         $story_edition->cron_job();
         $story_visit->cron_job();
         $suscription->cron_job();
         $topic->cron_job();
         $topic_story->cron_job();
         $visitor->cron_job();
         echo "\n";
         
         if( defined('FS_BOTS') )
         {
            if(FS_BOTS != '')
            {
               echo "\nEjecutamos los bots... ";
               $bots = explode(',', FS_BOTS);
               foreach($bots as $bot)
               {
                  echo "\n".$bot."... ";
                  include 'bots/'.$bot.'/cron.php';
               }
               echo "\n";
            }
         }
      }
      else
      {
         $feed0 = $feed->get( $_SERVER["argv"][1] );
         if($feed0)
            $feed0->mini_cron_job();
         else
            echo "¡Feed ".$_SERVER["argv"][1]." no encontrado!";
      }
   }
   else
   {
      echo "Comprobamos los índices... ";
      $comment->install_indexes();
      $feed->install_indexes();
      $feed_story->install_indexes();
      $message->install_indexes();
      $story->install_indexes();
      $story_edition->install_indexes();
      $story_visit->install_indexes();
      $suscription->install_indexes();
      $topic->install_indexes();
      $topic_story->install_indexes();
      $visitor->install_indexes();
      
      echo "\n";
      
      /// metemos los IDs de los feeds para asignárlos a cada hilo
      $fp = fopen('tmp/feeds.txt', 'wb');
      foreach($feed->all() as $f)
         fwrite ($fp, $f->get_id()."\n");
      fclose($fp);
   }
   
   $mongo->close();
}
