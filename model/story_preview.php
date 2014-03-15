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

class story_preview
{
   public $filename;
   public $link;
   public $type;
   
   public function __construct()
   {
      $this->type = FALSE;
   }
   
   public function load($link)
   {
      $this->link = $link;
      $this->type = FALSE;
      
      if( $this->is_valid_image_url($link) )
      {
         $this->filename = $link;
         $this->type = 'image';
      }
      else if( mb_substr($link, 0, 19) == 'http://i.imgur.com/' )
      {
         $parts = explode('/', $link);
         $this->filename = $parts[3];
         $this->type = 'imgur';
      }
      else if( mb_substr($link, 0, 29) == 'http://www.youtube.com/embed/' )
      {
         $parts = explode('/', $link);
         $this->filename = $this->clean_youtube_id($parts[4]);
         $this->type = 'youtube';
      }
      else if( mb_substr($link, 0, 23) == 'http://www.youtube.com/' OR mb_substr($link, 0, 24) == 'https://www.youtube.com/' )
      {
         $my_array_of_vars = array();
         parse_str( parse_url($link, PHP_URL_QUERY), $my_array_of_vars);
         if( isset($my_array_of_vars['v']) )
         {
            $this->filename = $this->clean_youtube_id($my_array_of_vars['v']);
            $this->type = 'youtube';
         }
      }
      else if( mb_substr($link, 0, 16) == 'http://youtu.be/' )
      {
         $parts = explode('/', $link);
         $this->filename = $this->clean_youtube_id($parts[3]);
         $this->type = 'youtube';
      }
   }
   
   public function min_height()
   {
      if($this->type == 'imgur')
         return 125;
      else if($this->type == 'youtube')
         return 95;
      else
         return 0;
   }
   
   public function min_width()
   {
      return 0;
   }
   
   public function preview()
   {
      $thumbnail = FALSE;
      
      switch ($this->type)
      {
         case 'image':
            $thumbnail = $this->filename;
            break;
         
         case 'imgur':
            $parts2 = explode('.', $this->filename);
            $thumbnail = 'http://i.imgur.com/'.$parts2[0].'s.'.$parts2[1];
            break;
         
         case 'youtube':
            $thumbnail = 'http://img.youtube.com/vi/'.$this->filename.'/0.jpg';
            break;
      }
      
      return $thumbnail;
   }
   
   private function clean_youtube_id($yid)
   {
      $new_yid = '';
      $yid = trim($yid);
      for($i = 0; $i < mb_strlen($yid); $i++)
      {
         $aux = mb_substr($yid, $i, 1);
         if( preg_match("#[a-zA-Z0-9\-_]#", $aux) )
            $new_yid .= $aux;
         else
            break;
      }
      return $new_yid;
   }
   
   private function is_valid_image_url($url)
   {
      $status = TRUE;
      $extensions = array('.png', '.jpg', 'jpeg', '.gif', 'webp');
      
      if( mb_substr($url, 0, 4) != 'http' )
         $status = FALSE;
      else if( mb_strlen($url) > 200 )
         $status = FALSE;
      else if( mb_strstr($url, '/favicon.') )
         $status = FALSE;
      else if( mb_strstr($url, 'doubleclick.net') )
         $status = FALSE;
      else if( mb_substr($url, 0, 10) == 'http://ad.' )
         $status = FALSE;
      else if( mb_strstr($url, '/avatar') OR mb_strstr($url, 'banner') )
         $status = FALSE;
      else if( mb_substr($url, 0, 47) == 'http://www.meneame.net/backend/vote_com_img.php' )
         $status = FALSE;
      else if( mb_substr($url, 0, 26) == 'http://publicidadinternet.' )
         $status = FALSE;
      else if( !in_array( mb_strtolower( mb_substr($url, -4) ), $extensions) )
         $status = FALSE;
      else if( mb_substr($url, -19) == 'vpreview_center.png' )
         $status = FALSE;
      
      return $status;
   }
}
