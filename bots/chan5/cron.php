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
 * Este bot se encarga de comentar los artículos.
 */
class chan5
{
   public function __construct()
   {
      $story = new story();
      $last_stories = $story->published_stories();
      if($last_stories)
      {
         switch( mt_rand(0, 23) )
         {
            case 0:
               $this->comentar_por_refranero($last_stories);
               break;
            
            case 1:
               $this->comentar_por_tema($last_stories);
               break;
            
            case 2:
               $this->comentar_por_fuente($last_stories);
               break;
         }
      }
   }
   
   private function comentar_por_refranero($stories)
   {
      foreach($stories as $sto)
      {
         echo '.';
         
         if($sto->num_comments == 0)
         {
            $archivo = FALSE;
            if( preg_match('/\bgato\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_gatos.txt';
            }
            else if( preg_match('/\bgatos\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_gatos.txt';
            }
            else if( preg_match('/\bdinero\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_dinero.txt';
            }
            else if( preg_match('/\btrabajo\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_trabajo.txt';
            }
            else if( preg_match('/\boptimizar\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_optimizacion.txt';
            }
            else if( preg_match('/\boptimización\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_optimizacion.txt';
            }
            else if( preg_match('/\bprogramador\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_optimizacion.txt';
            }
            else if( preg_match('/\bgoogle\b/iu', $sto->title.' '.$sto->description_uncut()) )
            {
               $archivo = 'refranes_optimizacion.txt';
            }
            
            if($archivo)
            {
               $refranes = explode("\n", file_get_contents('bots/chan5/'.$archivo));
               
               $comm = new comment();
               $comm->thread = $sto->get_id();
               $comm->nick = __CLASS__;
               
               if($archivo == 'refranes_optimizacion.txt')
               {
                  if( mt_rand(0, 1) == 0 )
                  {
                     $comm->text = "Siempre que leo algo relacionado con programación me acuerdo de Jeff Dean:\n";
                  }
                  else
                     $comm->text = "Esta es una buena ocasión para hablaros de Jeff Dean:\n";
               }
               
               $comm->text .= $refranes[ mt_rand(0, count($refranes)-1) ];
               $comm->save();
               echo '+';
               
               break;
            }
         }
      }
   }
   
   private function comentar_por_tema($stories)
   {
      
   }
   
   private function comentar_por_fuente($stories)
   {
      
   }
}

new chan5();