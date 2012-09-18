<?php
/*
 * This file is part of FeedStorm
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'base/fs_cache.php';

class story
{
   private $cache;
   public $title;
   public $description;
   public $link;
   public $image;
   public $youtube;
   public $date;
   
   public function __construct($item=FALSE)
   {
      $this->cache = new fs_cache();
      
      if( $item )
      {
         $this->title = (string)$item->title;
         $this->link = (string)$item->link;
         $this->date = strtotime( (string)$item->pubDate );
         
         $description = (string)$item->description;
         $this->description = $this->set_description($description);
         $this->image = $this->find_image($description);
         $this->youtube = $this->find_youtube($description);
      }
      else
      {
         $this->title = 'None';
         $this->link = '/';
         $this->date = strtotime( Date('Y-m-d H:m') );
         $this->description = 'No description';
         $this->image = NULL;
         $this->youtube = NULL;
      }
   }
   
   private function set_description($desc)
   {
      $desc = strip_tags( preg_replace("/(<br\ ?\/?>)+/", "\n", $desc) );
      return preg_replace("/(\n)+/", "<br/>", trim($desc));
   }
   
   private function find_image($text)
   {
      $img = NULL;
      $extensions = array('.png', '.PNG', '.jpg', '.JPG', 'jpeg', 'JPEG', '.gif', '.GIF');
      $urls = $this->find_urls($text);
      foreach($urls as $url)
      {
         if( substr($url, 0, 4) == 'http' AND in_array(substr($url, -4, 4), $extensions) )
         {
            $img = $url;
            break;
         }
      }
      return $img;
   }
   
   private function find_youtube($text)
   {
      $youtube = NULL;
      $urls = $this->find_urls($text);
      foreach($urls as $url)
      {
         if( substr($url, 0, 29) == 'http://www.youtube.com/embed/' )
         {
            $parts = split('/', $url);
            $youtube = $parts[4];
            break;
         }
         else if( substr($url, 0, 23) == 'http://www.youtube.com/' )
         {
            parse_str( parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
            if( isset($my_array_of_vars['v']) )
            {
               $youtube = $my_array_of_vars['v'];
               break;
            }
         }
      }
      return $youtube;
   }
   
   private function find_urls($text)
   {
      $text = html_entity_decode($text);
      $found = array();
      if( preg_match_all("#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#", $text, $urls) )
      {
         foreach($urls as $url)
         {
            foreach($url as $u)
            {
               if( substr($u, 0, 4) == 'http' )
                  $found[] = $u;
            }
         }
      }
      return $found;
   }
   
   public function show_date()
   {
      return Date('Y-m-d H:m', $this->date);
   }
   
   public function go_to_url()
   {
      return 'index.php?page=go_to&url=' . urlencode($this->link);
   }
   
   /// returns the aproximated story size in the screen
   public function size()
   {
      $size = 2;
      if( $this->youtube )
         $size += 15;
      else if( $this->image )
         $size += 8;
      if( strlen($this->description) > 0 )
         $size += strlen($this->description) / 60;
      return $size;
   }

   public function save_all($stories)
   {
      $all = array();
      while(count($all) != count($stories) AND count($all) < FS_MAX_STORIES)
      {
         $selected = FALSE;
         foreach($stories as $s)
         {
            if( !in_array($s, $all) )
            {
               if( !$selected  )
                  $selected = $s;
               else if( $s->date > $selected->date )
                  $selected = $s;
            }
         }
         $all[] = $selected;
      }
      $this->cache->set('story_all', $all, 28800);
   }
   
   public function all()
   {
      return $this->cache->get_array('story_all');
   }
}

?>
