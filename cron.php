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
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once 'config.php';
require_once 'base/fs_mongo.php';
require_once 'model/feed.php';
require_once 'model/media_item.php';
require_once 'model/story.php';
require_once 'model/story_media.php';

$mongo = new fs_mongo();

echo "\nProcesamos las fuentes:";
$feed = new feed();
foreach($feed->all() as $f)
{
   if($f->strikes > 48)
   {
      $f->delete();
      echo "\n * Eliminada la fuente ".$f->name.".\n";
   }
   else
   {
      echo "\n * Procesando ".$f->name."...\n";
      $f->read();
      
      foreach($f->get_errors() as $e)
         echo $e."\n";
      $f->clean_errors();
      
      foreach($f->get_messages() as $m)
         echo $m."\n";
      $f->clean_messages();
   }
}

echo "\nActualizamos las noticias populares...\n";
$story = new story();
{
   foreach($story->popular_stories() as $s)
   {
      if( is_null($s->media_id) )
      {
         if( count( $s->media_items() ) == 0 )
         {
            /*
             * buscamos más fotos para la noticia
             */
            $width = 0;
            $height = 0;
            $media_item = new media_item();
            foreach($media_item->find_media(FALSE, $s->link) as $mi)
            {
               $story_media = new story_media();
               $story_media->story_id = $s->get_id();
               
               if( !$media_item->get_by_url($mi->url) )
               {
                  if( $mi->download() )
                  {
                     $mi->save();
                     $story_media->media_id = $mi->get_id();
                     $story_media->save();
                     
                     if($mi->width > 0 AND $mi->height > 0)
                        $ratio = $mi->width / $mi->height;
                     else
                        $ratio = 0;
                     
                     if($ratio > 1 AND $ratio < 2 AND $mi->width > $width AND $mi->height > $height)
                     {
                        $s->media_id = $mi->get_id();
                        $width = $mi->original_width;
                        $height = $mi->original_height;
                     }
                  }
               }
            }
         }
      }
      else
      {
         /*
          * Elegimos la foto de la edición más votada de la noticia
          */
         $maxvotes = 0;
         foreach($s->editions() as $edi)
         {
            if($edi->votes > $maxvotes)
            {
               $maxvotes = $edi->votes;
               $s->media_id = $edi->media_id;
            }
         }
      }
      
      $s->save();
   }
}

$mongo->close();

?>
