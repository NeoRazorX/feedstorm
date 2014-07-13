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
 * Procesa un mix entre los últimos artículos y una ración aleatoria
 * para enlazarlas con los temas.
 * @param type $topic
 * @param type $story
 * @param type $topic_story
 */
function chan3(&$topic, &$story, &$topic_story)
{
   $all_topics = $topic->all();
   $last_stories = array_merge($story->last_stories(), $story->random_stories());
   
   foreach($last_stories as $i => $lsto)
   {
      echo '.';
      
      foreach($all_topics as $tpic)
      {
         if( !in_array($tpic->get_id(), $lsto->topics) )
         {
            foreach($tpic->keywords() as $key)
            {
               if( preg_match('/\b'.$key.'\b/iu', $lsto->title) OR preg_match('/\b'.$key.'\b/iu', $lsto->description) )
               {
                  echo '+';
                  
                  if($last_stories[$i]->keywords == '')
                     $last_stories[$i]->keywords = $key;
                  else if( strpos($last_stories[$i]->keywords, $key) === FALSE )
                     $last_stories[$i]->keywords .= ', '.$key;
                  
                  $last_stories[$i]->topics[] = $tpic->get_id();
                  $last_stories[$i]->save();
                  
                  $ts0 = new topic_story();
                  $ts0->topic_id = $tpic->get_id();
                  $ts0->story_id = $lsto->get_id();
                  $ts0->date = $lsto->date;
                  $ts0->popularity = $lsto->max_popularity();
                  $ts0->save();
               }
            }
         }
         else
         {
            /// ¿Actualizamos la popularidad?
            $ts0 = $topic_story->get2($tpic->get_id(), $last_stories[$i]->get_id());
            if($ts0)
            {
               if( $ts0->popularity != $last_stories[$i]->max_popularity() )
               {
                  $ts0->popularity = $last_stories[$i]->max_popularity();
                  $ts0->save();
               }
            }
            else
            {
               $last_stories[$i]->topics = array();
               $last_stories[$i]->keywords = '';
               $last_stories[$i]->save();
            }
         }
      }
   }
}

chan3($topic, $story, $topic_story);