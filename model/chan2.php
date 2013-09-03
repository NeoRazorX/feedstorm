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

class chan2
{
   public $nick;
   public $valid_comment;
   
   private static $keywords;
   
   public function __construct()
   {
      $this->nick = 'chan2';
      $this->valid_comment = FALSE;
      
      if( !isset(self::$keywords) )
      {
         self::$keywords = array();
         
         if( file_exists('tmp/keywords') )
         {
            $file = fopen('tmp/keywords', 'r');
            if($file)
            {
               while( !feof($file) )
               {
                  $line = trim( fgets($file) );
                  if( mb_strlen($line) > 1 )
                     self::$keywords[] = $line;
               }
               
               fclose($file);
            }
         }
      }
   }
   
   public function __destruct()
   {
      $file = fopen('tmp/keywords', 'w');
      if($file)
      {
         foreach(self::$keywords as $k)
            fwrite($file, $k."\n");
         
         fclose($file);
      }
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
      if( preg_match("/(^|\s)@chan2/", $txt) )
      {
         $this->valid_comment = TRUE;
         return $this->random_from_file('chan2_personal', $nick);
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
   
   private function random_from_file($filename, $nick = 'anónimo')
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
   
   public function add_keyword($key)
   {
      $key = trim( strtolower($key) );
      
      if( mb_strlen($key) > 1 )
      {
         if( !in_array($key, self::$keywords) )
            self::$keywords[] = $key;
      }
   }
   
   private function search_on_wikipedia($keyword)
   {
      return '';
   }
   
   private function find_keywords($xml)
   {
      $keywords = array();
      
      $auxlist = array();
      preg_match_all('#<category>(.*?)</category>#', $xml, $auxlist);
      if($auxlist)
      {
         foreach($auxlist[1] as $aux)
         {
            if( mb_substr($aux, 0, 9) == '<![CDATA[' )
            {
               $aux = str_replace('<![CDATA[', '', $aux);
               $aux = str_replace(']]>', '', $aux);
            }
            $keywords[] = $aux;
         }
      }
      else
      {
         preg_match_all('#<media:keywords>(.*?)</media:keywords>#', $xml, $auxlist);
         if($auxlist)
         {
            foreach($auxlist[1] as $aux)
            {
               $aux2 = explode(',', $aux);
               foreach($aux2 as $a)
                  $keywords[] = $a;
            }
         }
      }
      
      if($keywords)
      {
         /// chan2 necesitas las etiquetas para hacer sus cosas
         $chan2 = new chan2();
         foreach($keywords as $k)
            $chan2->add_keyword($k);
      }
   }
   
   public function cron_job()
   {
      $comment = new comment();
      $story = new story();
      
      /// leemos los archivos xml de los feeds
      foreach( scandir('tmp/') as $f )
      {
         if( substr($f, -4) == '.xml' )
         {
            $xml = file_get_contents('tmp/'.$f);
            if($xml)
            {
               /// extraemos todas las keywords y categorias del xml
               $this->find_keywords( $story->remove_bad_utf8($xml) );
            }
         }
      }
      
      /// usamos las keywords para buscar en wikipedia
      foreach(self::$keywords as $key)
      {
         $txt = $this->search_on_wikipedia($key);
         if($txt != '')
         {
            $comment2 = new comment();
            $comment2->nick = $this->nick;
            $comment2->text = $txt;
            $comment2->save();
         }
      }
   }
}

?>