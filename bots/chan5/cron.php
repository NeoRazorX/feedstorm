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
 * Busca relaciones entre temas.
 * @param type $story
 * @param type $topic_story
 */
function chan5(&$topic, &$story)
{
   $all_topìcs = $topic->all();
   
   foreach($all_topìcs as $tpic)
   {
      echo '.';
      
      /// añadimos los temas a excluri: actual, padre, abuelo...
      $exclude = array( $tpic->get_id() );
      $ex_tpic = $topic->get($tpic->parent);
      while($ex_tpic)
      {
         $exclude[] = $ex_tpic->get_id();
         $ex_tpic = $topic->get($ex_tpic->parent);
      }
      /// también excluimos a los hijos:
      foreach($topic->all_from($tpic->get_id()) as $children)
         $exclude[] = $children->get_id();
      
      $common_topics = array();
      $max = 0;
      
      foreach($tpic->stories('d-m-Y') as $sto)
      {
         foreach($sto->topics as $tid)
         {
            if( !in_array($tid, $exclude) )
            {
               $found = FALSE;
               foreach($common_topics as $i => $value)
               {
                  if($value['tid'] == $tid)
                  {
                     $common_topics[$i]['count']++;
                     if($max < $common_topics[$i]['count'])
                        $max = $common_topics[$i]['count'];
                     
                     $found = TRUE;
                     break;
                  }
               }
               if(!$found)
               {
                  $common_topics[] = array('tid' => $tid, 'count' => 1);
                  if($max < 1)
                     $max = 1;
               }
            }
         }
      }
      
      foreach($common_topics as $i => $value)
      {
         if($value['count'] == $max AND $max > 5)
         {
            foreach($all_topìcs as $tpic2)
            {
               if($tpic2->get_id() == $value['tid'])
               {
                  foreach($tpic->stories() as $sto)
                  {
                     if( count($sto->comments()) == 0 AND in_array($tpic2->get_id(), $sto->topics) AND mt_rand(0,49) == 0)
                     {
                        $comm = new comment();
                        $comm->thread = $sto->get_id();
                        $comm->nick = 'chan5';
                        
                        switch ( mt_rand(0,3) )
                        {
                           case 0:
                              $comm->text = 'Es matemático, cada vez que se menciona a '.$tpic->title
                                .' también se menciona '.$tpic2->title.".";
                              break;
                           
                           case 1:
                              $comm->text = 'No os parece curioso que cada vez que se menciona a '.$tpic->title
                                .' también se menciona '.$tpic2->title.".";
                              break;
                           
                           default:
                              $comm->text = 'Yo no digo na, pero cada vez que se menciona a '.$tpic->title
                                .' también se menciona '.$tpic2->title.".";
                              break;
                        }
                        
                        $comm->save();
                        echo '+';
                     }
                     
                     break;
                  }
                  
                  break;
               }
            }
            
            break;
         }
      }
   }
}

chan5($topic, $story);