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
 * Calcula la popularidad de fuentes, temas y premia a los usuarios habituales.
 */
class chan10
{
   public function __construct()
   {
      /// comprobamos las fuentes
      $feed = new feed();
      foreach($feed->all() as $f0)
      {
         echo '.';
         
         $f0->popularity = 0;
         $num = 0;
         
         foreach($f0->stories() as $st)
         {
            if($st->published)
            {
               $f0->popularity += 10*$st->popularity;
            }
            else
               $f0->popularity += $st->popularity;
            
            $num++;
         }
         
         if($num > 0)
         {
            $f0->popularity = intval($f0->popularity / $num);
         }
         
         $f0->save();
      }
      
      /// comprobamos los temas
      $topic = new topic();
      foreach($topic->all() as $tpic)
      {
         echo '*';
         
         $tpic->popularity = 0;
         $num = 0;
         
         foreach($tpic->stories() as $st)
         {
            if($st->published)
            {
               $tpic->popularity += 10*$st->popularity;
            }
            else
               $tpic->popularity += $st->popularity;
            
            $num++;
         }
         
         if($num > 0)
         {
            $tpic->popularity = intval($tpic->popularity / $num);
         }
         
         $tpic->save();
      }
      
      /// premiamos a los usuarios habituales
      $visitor = new visitor();
      foreach($visitor->last() as $vis)
      {
         echo '.';
         
         if($vis->last_login_date != $vis->first_login_date AND $vis->last_login_date > time()-3600)
         {
            $vis->extra_points++;
            $vis->need_save = TRUE;
            $vis->save();
         }
      }
   }
}

new chan10();