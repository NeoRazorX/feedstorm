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

require_once 'model/comment.php';
require_once 'model/story.php';

class chan
{
   public $nick;
   public $valid_comment;
   
   public function __construct()
   {
      $this->nick = 'chan';
      $this->valid_comment = FALSE;
   }
   
   public function answer($txt, $nick = 'anónimo')
   {
      $txt = strtolower($txt);
      
      if( preg_match("/(^|\s)@chan/", $txt) )
      {
         $this->valid_comment = TRUE;
         return $this->random_from_file('chan_personal', $nick);
      }
      else
      {
         $answer = 'No se me ocurre nada :-(';
         $this->valid_comment = $this->answer_from_dictionary($txt, $answer);
         
         if( !$this->valid_comment )
         {
            if( mt_rand(0, 29) == 0 )
            {
               $this->valid_comment = TRUE;
               
               if( mt_rand(0, 2) == 0 )
                  $answer = 'Dato curioso: '.$this->random_from_file('chan_a_datos', $nick);
               else
                  $answer = '¿Sabías que...? '.$this->random_from_file('chan_a_datos', $nick);
            }
            else
            {
               $this->valid_comment = TRUE;
               $answer = $this->random_from_file('chan_a_paridas', $nick);
            }
         }
         
         return $answer;
      }
   }
   
   public function personal_answer($txt, $nick = 'anónimo')
   {
      if( preg_match("/(^|\s)@chan/", $txt) )
      {
         $this->valid_comment = TRUE;
         return $this->random_from_file('chan_personal', $nick);
      }
   }
   
   public function save_answer()
   {
      if($this->valid_comment)
      {
         $this->valid_comment = FALSE;
         return TRUE;
      }
      else
         return FALSE;
   }
   
   protected function random_from_file($filename, $nick = 'anónimo')
   {
      if( file_exists('data/'.$filename) )
      {
         $file = fopen('data/'.$filename, 'r');
         if($file)
         {
            $nick = str_replace(' ', '_', $nick);
            $answers = array();
            while( !feof($file) )
            {
               $answers[] = str_replace('$nick', $nick, trim( fgets($file) ) );
            }
            fclose($file);
            
            if( count($answers) == 0 )
               return 'No se me ocurre nada :-(';
            else if( count($answers) == 1 )
               return $answers[0];
            else
            {
               shuffle($answers);
               $answer = $answers[ mt_rand(0, count($answers)-1) ];
               
               if($filename != 'chan_a_chiste' AND strstr($answer, '$chiste') )
                  $answer = str_replace('$chiste', $this->random_from_file('chan_a_chiste'), $answer);
               else if($filename != 'chan_a_datos' AND strstr($answer, '$dato') )
                  $answer = str_replace('$dato', $this->random_from_file('chan_a_datos'), $answer);
               else if($filename != 'chan_meme1' AND $filename != 'chan_meme2' AND strstr($answer, '$meme') )
               {
                  $answer = str_replace('$meme1', $this->random_from_file('chan_meme1'), $answer);
                  $answer = str_replace('$meme2', $this->random_from_file('chan_meme2'), $answer);
               }
               
               return $answer;
            }
         }
         else
            return '¡Me he roto mucho!';
      }
      else
         return '¡Me he roto!';
   }
   
   protected function search_from_file($filename, $word)
   {
      if( file_exists('data/'.$filename) )
      {
         $file = fopen('data/'.$filename, 'r');
         if($file)
         {
            $answer = 'No se me ocurre nada :-(';
            $answers = array();
            while( !feof($file) )
               $answers[] = trim( fgets($file) );
            fclose($file);
            
            shuffle($answers);
            foreach($answers as $a)
            {
               if( preg_match("#\b$word\b#i", $a) )
               {
                  $answer = $a;
                  break;
               }
            }
            
            return $answer;
         }
         else
            return '¡Me he roto mucho!';
      }
      else
         return '¡Me he roto!';
   }
   
   protected function answer_from_dictionary($txt, &$answer)
   {
      $found = FALSE;
      
      $file = fopen('data/chan_dictionary', 'r');
      if($file)
      {
         $data = array();
         while( !feof($file) )
            $data[] = trim( fgets($file) );
         fclose($file);
         
         shuffle($data);
         foreach($data as $d)
         {
            $aux = explode(';', $d);
            
            if( preg_match("/\b".$aux[0]."\b/i", $txt) )
            {
               if($aux[2] == 0)
                  $answer = $this->random_from_file('chan_a_'.$aux[1], $aux[0]);
               else
                  $answer = $this->search_from_file('chan_a_'.$aux[1], $aux[0]);
               
               $found = TRUE;
               break;
            }
         }
      }
      
      return $found;
   }
   
   protected function answer_from_file($filename, $txt)
   {
      if( file_exists('data/'.$filename) )
      {
         $file = fopen('data/'.$filename, 'r');
         if($file)
         {
            $answer = 'No se me ocurre nada :-(';
            $data = array();
            while( !feof($file) )
               $data[] = trim( fgets($file) );
            fclose($file);
            
            foreach($data as $d)
            {
               $aux = explode(';', $d);
               
               if( strstr($txt, $aux[0]) )
               {
                  $answer = $aux[1];
                  $this->valid_comment = TRUE;
                  break;
               }
            }
            
            return $answer;
         }
      }
   }
   
   public function cron_job()
   {
      /// comentamos alguna noticia aleatoria
      $story = new story();
      foreach($story->random_stories() as $s)
      {
         if( mt_rand(0, FS_MAX_STORIES) == 0 )
         {
            $com = new comment();
            $com->thread = $s->get_id();
            $com->nick = $this->nick;
            $com->text = $this->answer($s->description);
            
            if( $this->save_answer() )
            {
               echo "\nChan ha añadido un comentario.";
               $com->save();
               break;
            }
         }
      }
      
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
               echo "\nChan ha contestado a un comentario.";
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