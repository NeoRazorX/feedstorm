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
 * Enlaza artículos en función de los temas.
 */
class chan9
{
   public function __construct()
   {
      $story = new story();
      $topic_story = new topic_story();
      
      foreach($story->popular_stories( mt_rand(100, 500) ) as $lsto)
      {
         echo '.';
         
         if( is_null($lsto->related_id) AND count($lsto->topics) > 1 )
         {
            $option2 = FALSE;
            $option3 = FALSE;
            $offset = 0;
            $continuar = TRUE;
            
            /// si ya hay algún artículo relacionado, no hace falta buscar siempre
            if( !is_null($lsto->related_id2) AND mt_rand(0,4) > 0 )
            {
               $continuar = FALSE;
            }
            
            while($continuar)
            {
               $continuar = FALSE;
               
               foreach($topic_story->all4topic($lsto->topics[0], $offset) as $ts)
               {
                  $st0 = $story->get($ts->story_id);
                  if($st0)
                  {
                     $offset++;
                     $continuar = TRUE;
                     
                     if( $st0->native_lang AND !$st0->parody AND !$st0->penalize AND count($st0->topics) >= count($lsto->topics) )
                     {
                        $valid = TRUE;
                        $valids = count($lsto->topics);
                        foreach($lsto->topics as $tid)
                        {
                           if( !in_array($tid, $st0->topics) )
                           {
                              $valid = FALSE;
                              $valids--;
                           }
                        }
                        
                        if($valid AND ($lsto->date-$st0->date) > 86400 )
                        {
                           if( count($st0->topics) == count($lsto->topics) )
                           {
                              echo '*';
                              
                              $lsto->related_id2 = $st0->get_id();
                              $lsto->save();
                              $continuar = FALSE;
                              $option2 = FALSE;
                              $option3 = FALSE;
                              break;
                           }
                           else
                           {
                              if(!$option2)
                              {
                                 $option2 = $st0;
                              }
                              else if($st0->popularity > $option2->popularity)
                              {
                                 $option2 = $st0;
                              }
                           }
                        }
                        else if($valids > 1 AND ($lsto->date-$st0->date) > 86400 )
                        {
                           if(!$option3)
                           {
                              $option3 = $st0;
                           }
                           else if($st0->popularity > $option3->popularity)
                           {
                              $option3 = $st0;
                           }
                        }
                     }
                  }
               }
            }
            
            if($option2)
            {
               echo '2';
               
               $lsto->related_id2 = $option2->get_id();
               $lsto->save();
            }
            else if($option3)
            {
               echo '3';
               
               $lsto->related_id2 = $option3->get_id();
               $lsto->save();
            }
         }
         else if( is_null($lsto->related_id) AND is_null($lsto->related_id2) AND count($lsto->topics) == 1 )
         {
            foreach($topic_story->best4topic($lsto->topics[0]) as $ts)
            {
               $st0 = $story->get($ts->story_id);
               if($st0)
               {
                  if( $st0->native_lang AND !$st0->parody AND !$st0->penalize AND ($lsto->date-$st0->date) > 86400 )
                  {
                     echo '*';
                     
                     $lsto->related_id2 = $st0->get_id();
                     $lsto->save();
                     break;
                  }
               }
            }
         }
      }
   }
}

new chan9();