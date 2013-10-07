<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/chan.php';

class chan2 extends chan
{
   public function __construct()
   {
      $this->nick = 'chan2';
      $this->valid_comment = FALSE;
   }
   
   public function answer($txt, $nick = 'anónimo')
   {
      $txt = strtolower($txt);
      
      if( preg_match("/(^|\s)@chan2/", $txt) )
      {
         $this->valid_comment = TRUE;
         return $this->random_from_file('chan2_personal', $nick);
      }
      else
      {
         return '';
      }
   }
   
   public function personal_answer($txt, $nick = 'anónimo')
   {
      if($nick == 'chan')
      {
         return $this->answer_from_file('chan2_a_chan', $txt);
      }
      else if( preg_match("/(^|\s)@chan2/", $txt) )
      {
         $this->valid_comment = TRUE;
         return $this->random_from_file('chan2_personal', $nick);
      }
   }
   
   public function cron_job()
   {
      /// respondemos a los últimos comentarios
      $comment = new comment();
      $threads = array();
      foreach($comment->all() as $com2)
      {
         if($com2->nick == $this->nick)
            $threads[] = $com2->thread;
         else
         {
            $com3 = new comment();
            $com3->text = $this->personal_answer($com2->text, $com2->nick);
            
            if( $this->save_answer() AND !in_array($com2->thread, $threads) AND $com3->date > $com2->date )
            {
               echo "\nChan2 ha contestado a un comentario.";
               $com3->nick = $this->nick;
               $com3->thread = $com2->thread;
               $com3->save();
               
               $threads[] = $com2->thread;
            }
         }
      }
   }
}

?>