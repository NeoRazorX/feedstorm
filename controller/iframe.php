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

require_once 'model/story.php';

class iframe extends fs_controller
{
   public $stories;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Noticias relacionadas &lsaquo; '.FS_NAME);
      $story = new story();
      
      $max_stories = 6;
      if( isset($_POST['link']) )
      {
         $st0 = $story->get_by_link($_POST['link']);
         if($st0)
         {
            $this->stories = array();
            
            /// navegamos por los artículos relacionados
            $st1 = $st0->related_story();
            while($max_stories > 0)
            {
               if($st1)
               {
                  $this->stories[] = $st1;
                  $st1 = $st1->related_story();
                  $max_stories--;
               }
               else
                  break;
            }
            
            /*
             * Si no hay artículos relacionados, entonces hacemos una búsqueda por tema
             * y añadimos esos artículos
             */
            if( count($this->stories) == 0 AND count($st0->topics) > 0 )
            {
               $topic = new topic();
               $t0 = $topic->get($st0->topics[0]);
               if($t0)
               {
                  foreach($t0->stories() as $st3)
                  {
                     if( $st3->get_id() != $st0->get_id() )
                     {
                        $this->stories[] = $st3;
                        
                        $max_stories--;
                        if($max_stories <= 0)
                           break;
                     }
                  }
               }
            }
            
            /// si aun así no hay nada, añadimos artículos populares
            if( count($this->stories) == 0 )
            {
               $this->no_relateds = TRUE;
               foreach($story->popular_stories($max_stories) as $st4)
               {
                  if( $st4->get_id() != $st0->get_id() )
                  {
                     $this->stories[] = $st4;
                     
                     $max_stories--;
                     if($max_stories <= 0)
                        break;
                  }
               }
            }
         }
         else
            $this->stories = $story->popular_stories($max_stories);
      }
      else
         $this->stories = $story->popular_stories($max_stories);
   }
   
   public function get_description()
   {
      return 'Noticias relacionadas de '.FS_NAME;
   }
}
