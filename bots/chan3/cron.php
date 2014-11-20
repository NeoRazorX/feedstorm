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
 * Asigna temas a los artículos.
 */
class chan3
{
   private $all_topics;
   private $story;
   private $topic;
   private $topic_story;
   
   public function __construct()
   {
      $this->story = new story();
      $this->topic = new topic();
      $this->topic_story = new topic_story();
      $this->all_topics = $this->topic->all();
      
      if( mt_rand(0, 9) == 0 )
      {
         $this->search_all_topics();
      }
      else
      {
         $this->topics4some_stories();
      }
   }
   
   private function search_all_topics()
   {
      foreach($this->all_topics as $tpic)
      {
         if( $tpic->valid )
         {
            foreach($tpic->keywords() as $key)
            {
               echo '('.$key.')';
               $search_title = (mt_rand(0, 1) == 0);
               
               foreach($this->story->search($key, $search_title) as $lsto)
               {
                  if( !in_array($tpic->get_id(), $lsto->topics) )
                  {
                     echo '+';
                     
                     /// añadimos la keyword
                     if($lsto->keywords == '')
                     {
                        $lsto->keywords = $key;
                     }
                     else if( strpos($lsto->keywords, $key) === FALSE )
                     {
                        $lsto->keywords .= ', '.$key;
                     }
                     
                     $lsto->topics[] = $tpic->get_id();
                     
                     if($lsto->native_lang AND !$lsto->parody AND !$lsto->penalize)
                     {
                        $ts0 = new topic_story();
                        $ts0->topic_id = $tpic->get_id();
                        $ts0->story_id = $lsto->get_id();
                        $ts0->date = $lsto->date;
                        $ts0->popularity = $lsto->max_popularity();
                        $ts0->save();
                     }
                  }
                  
                  $this->fix_topics4story($lsto);
                  $lsto->save();
               }
            }
         }
      }
   }
   
   private function topics4some_stories()
   {
      switch( mt_rand(0,2) )
      {
         case 0:
            $last_stories = $this->story->last_stories(500);
            break;
         
         case 1:
            $last_stories = $this->story->popular_stories(500);
            break;
         
         default:
            $last_stories = $this->story->random_stories(500);
            break;
      }
      
      foreach($last_stories as $i => $lsto)
      {
         echo '.';
         
         foreach($this->all_topics as $tpic)
         {
            if( !in_array($tpic->get_id(), $lsto->topics) AND $tpic->valid )
            {
               foreach($tpic->keywords() as $key)
               {
                  if( preg_match('/\b'.$key.'\b/iu', $lsto->title.' '.$lsto->description_uncut()) )
                  {
                     echo '+';
                     
                     /// añadimos la keyword
                     if($last_stories[$i]->keywords == '')
                     {
                        $last_stories[$i]->keywords = $key;
                     }
                     else if( strpos($last_stories[$i]->keywords, $key) === FALSE )
                     {
                        $last_stories[$i]->keywords .= ', '.$key;
                     }
                     
                     $last_stories[$i]->topics[] = $tpic->get_id();
                     
                     if($lsto->native_lang AND !$lsto->parody AND !$lsto->penalize)
                     {
                        $ts0 = new topic_story();
                        $ts0->topic_id = $tpic->get_id();
                        $ts0->story_id = $lsto->get_id();
                        $ts0->date = $lsto->date;
                        $ts0->popularity = $lsto->max_popularity();
                        $ts0->save();
                     }
                     
                     break;
                  }
               }
            }
            else if($lsto->native_lang AND !$lsto->parody AND !$lsto->penalize)
            {
               /// ¿Actualizamos la popularidad?
               $ts0 = $this->topic_story->get2($tpic->get_id(), $lsto->get_id());
               if($ts0)
               {
                  if( $ts0->popularity != $lsto->max_popularity() )
                  {
                     $ts0->popularity = $lsto->max_popularity();
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
            else
            {
               /*
                * No quiero que parodias, artículos penalizados o que no estén en español
                * cuenten para los temas.
                */
               $ts0 = $this->topic_story->get2($tpic->get_id(), $lsto->get_id());
               if($ts0)
               {
                  echo '-';
                  $ts0->delete();
               }
            }
         }
         
         $this->fix_topics4story($last_stories[$i]);
         $last_stories[$i]->save();
      }
   }
   
   private function fix_topics4story(&$sto)
   {
      $aux_topics = array();
      
      foreach($sto->topics as $tid)
      {
         foreach($this->all_topics as $tpic2)
         {
            if( $tpic2->get_id() == $tid )
            {
               $pos = -1;
               foreach($tpic2->keywords() as $key)
               {
                  if( preg_match('/\b'.$key.'\b/iu', $sto->title.' '.$sto->description_uncut()) )
                  {
                     $pos2 = stripos($sto->title.' '.$sto->description_uncut(), $key);
                     if($pos2 < $pos OR $pos == -1)
                     {
                        $pos = $pos2;
                     }
                  }
               }
               
               if($pos == -1)
               {
                  $aux_topics[ count($aux_topics) ] = $tid;
               }
               else
               {
                  $aux_topics[$pos] = $tid;
               }
               
               break;
            }
         }
      }
      
      ksort($aux_topics);
      $sto->topics = array();
      foreach($aux_topics as $atopic)
         $sto->topics[] = $atopic;
   }
}

new chan3();