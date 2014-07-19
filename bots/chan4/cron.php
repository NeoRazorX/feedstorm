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

/**
 * Corrige descripciones y enlaces de artículos
 * @param type $story
 * @param type $topic_story
 */
function chan4(&$story, &$topic_story)
{
   $last_stories = array_merge($story->last_stories(300), $story->random_stories(300));
   
   foreach($last_stories as $i => $lsto)
   {
      echo '.';
      
      if( strpos($lsto->description, 'Continuar leyendo...') !== FALSE )
      {
         $last_stories[$i]->description = mb_substr($lsto->description, 0, strpos($lsto->description, 'Continuar leyendo...'));
         
         /// también reseteamos temas y keywords por si ha habido falsos positivos
         $last_stories[$i]->topics = array();
         $last_stories[$i]->keywords = '';
         $last_stories[$i]->save();
         
         /// y eliminamos las relaciones con temas
         foreach($topic_story->all4story($lsto->get_id()) as $ts0)
            $ts0->delete();
         
         echo '-';
      }
      else if( strpos($lsto->description, '… Lea más →') !== FALSE )
      {
         $last_stories[$i]->description = mb_substr($lsto->description, 0, strpos($lsto->description, '… Lea más →'));
         $last_stories[$i]->save();
         
         echo '-';
      }
      else if( strpos($lsto->description, '… Sigue leyendo →') !== FALSE )
      {
         $last_stories[$i]->description = mb_substr($lsto->description, 0, strpos($lsto->description, '… Sigue leyendo →'));
         $last_stories[$i]->save();
         
         echo '-';
      }
      else if( strpos($lsto->description, 'Read more...') !== FALSE )
      {
         $last_stories[$i]->description = mb_substr($lsto->description, 0, strpos($lsto->description, 'Read more...'));
         $last_stories[$i]->save();
         
         echo '-';
      }
      else if( !$lsto->parody AND (stripos($lsto->title, '[humor]') !== FALSE OR stripos($lsto->title, '(humor)') !== FALSE) )
      {
         $last_stories[$i]->parody = TRUE;
         $last_stories[$i]->save();
         
         echo 'P';
      }
      else if( substr($lsto->link, 0, 22) == 'https://humanos.uci.cu' )
      {
         $last_stories[$i]->link = str_replace('https://', 'http://', $lsto->link);
         $last_stories[$i]->save();
         
         echo 'L';
      }
   }
}

chan4($story, $topic_story);