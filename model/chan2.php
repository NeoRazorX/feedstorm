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
                  self::$keywords[] = trim( fgets($file) );
               
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
            fwrite($file, $k);
         
         fclose($file);
      }
   }
   
   public function answer($txt, $nick = 'anónimo')
   {
      $txt = strtolower($txt);
      
      if( preg_match("/(^|\s)@chan2/i", $txt) )
      {
         
      }
      else
      {
         
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
   
   public function add_keyword($key)
   {
      if( !in_array($key, self::$keywords) )
         self::$keywords[] = trim( strtolower($key) );
   }
}

?>