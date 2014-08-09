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
 * Corrige descripciones y enlaces de artículos.
 */
class chan4
{
   public function __construct()
   {
      $story = new story();
      
      if( mt_rand(0, 23) == 0 )
      {
         foreach($story->all() as $sto)
            $this->check_story($sto);
      }
      else
      {
         foreach($story->last_stories(1000) as $sto)
            $this->check_story($sto);
      }
   }
   
   private function check_story(&$sto)
   {
      echo '.';
      
      if( mb_strpos($sto->description, 'Continuar leyendo...') !== FALSE )
      {
         $sto->description = mb_substr($sto->description, 0, mb_strpos($sto->description, 'Continuar leyendo...'));
         
         /// también reseteamos temas y keywords por si ha habido falsos positivos
         $sto->topics = array();
         $sto->keywords = '';
         $sto->save();
         
         /// y eliminamos las relaciones con temas
         $topic_story = new topic_story();
         foreach($topic_story->all4story($sto->get_id()) as $ts0)
            $ts0->delete();
         
         echo '-';
      }
      else if( mb_strpos($sto->description, '… Lea más →') !== FALSE )
      {
         $sto->description = mb_substr($sto->description, 0, mb_strpos($sto->description, '… Lea más →'));
         $sto->save();
         
         echo '-';
      }
      else if( mb_strpos($sto->description, '… Sigue leyendo →') !== FALSE )
      {
         $sto->description = mb_substr($sto->description, 0, mb_strpos($sto->description, '… Sigue leyendo →'));
         $sto->save();
         
         echo '-';
      }
      else if( mb_strpos($sto->description, 'Read more...') !== FALSE )
      {
         $sto->description = mb_substr($sto->description, 0, mb_strpos($sto->description, 'Read more...'));
         $sto->save();
         
         echo '-';
      }
      else if( !$sto->parody AND (stripos($sto->title, '[humor]') !== FALSE OR stripos($sto->title, '(humor)') !== FALSE) )
      {
         $sto->parody = TRUE;
         $sto->save();
         
         echo 'P';
      }
      else if( substr($sto->link, 0, 22) == 'https://humanos.uci.cu' )
      {
         $sto->link = str_replace('https://', 'http://', $sto->link);
         $sto->save();
         
         echo 'L';
      }
      else if( mb_strpos($sto->description, 'function(d,s,id){va​r js,fjs=d.getElementsByTag​Name(s)[0];if(!d.getElemen​tById(id)){js=d.createElem​ent(s);js.id=id;js.src="//​platform.twitter.com/widge​ts.js";fjs.parentNode.inse​rtBefore(js,fjs);}}(docume​nt,"script","twitter-wjs")​;') !== FALSE )
      {
         $sto->description = str_replace('function(d,s,id){va​r js,fjs=d.getElementsByTag​Name(s)[0];if(!d.getElemen​tById(id)){js=d.createElem​ent(s);js.id=id;js.src="//​platform.twitter.com/widge​ts.js";fjs.parentNode.inse​rtBefore(js,fjs);}}(docume​nt,"script","twitter-wjs")​;', ' ', $sto->description);
         $sto->save();
         
         echo '-';
      }
   }
}

new chan4();