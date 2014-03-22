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

class stats extends fs_controller
{
   public $visits;
   
   public function __construct()
   {
      parent::__construct('stats', 'Estadísticas &lsaquo; '.FS_NAME);
      
      $this->visits = $this->analyze_visits();
      
      if( count($this->visits) == 0 )
         $this->new_message('No hay suficientes visitas para analizar.');
   }
   
   private function analyze_visits()
   {
      if( isset($_SERVER['REMOTE_ADDR']) )
         $ip = $_SERVER['REMOTE_ADDR'];
      else
         $ip = 'unknown';
      
      $visit0 = new story_visit();
      $visits = $visit0->last(FS_MAX_STORIES * 4, $ip);
      $aux = array();
      
      foreach($visits as $i => $value)
      {
         if( array_key_exists($value->story_id, $aux) )
         {
            $aux[$value->story_id]['visits']++;
            $aux[$value->story_id]['date'] = $value->date;
         }
         else
         {
            $aux[$value->story_id] = array(
                'visits' => 1,
                'date' => $value->date
            );
         }
      }
      
      arsort($aux);
      
      $stlist = array();
      $story0 = new story();
      $n = 0;
      foreach($aux as $i => $value)
      {
         if($n < FS_MAX_STORIES AND $value['visits'] > 1)
         {
            $stlist[] = array(
                'story' => $story0->get($i),
                'visits' => $value['visits'],
                'date' => $value['date'],
                'spc' => intval( (time()-$value['date'])/$value['visits'] )
            );
         }
         else
            break;
         
         $n++;
      }
      
      return $stlist;
   }
}

?>