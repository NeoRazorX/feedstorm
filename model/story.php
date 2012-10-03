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

require_once 'base/fs_model.php';

class story extends fs_model
{
   public $title;
   public $description;
   public $link;
   public $date;
   public $youtube;
   
   public $image;
   public $image_width;
   public $image_height;
   
   public $feed_name;
   public $feed_url;
   
   public $selected;
   
   public function __construct($item=FALSE, $f=FALSE)
   {
      parent::__construct();
      if( $item )
      {
         $this->title = (string)$item->title;
         
         $this->link = $f->url();
         if( $item->link )
         {
            if( substr((string)$item->link, 0, 4) == 'http' )
               $this->link = (string)$item->link;
            else
            {
               foreach($item->children('feedburner', TRUE) as $element)
               {
                  if($element->getName() == 'origLink')
                  {
                     $this->link = (string)$element;
                     break;
                  }
               }
            }
         }
         
         if( $item->pubDate )
            $this->date = strtotime( (string)$item->pubDate );
         else if( $item->published )
            $this->date = strtotime( (string)$item->published );
         else
            $this->date = strtotime( Date('Y-m-d') );
         
         if( $item->description )
            $description = (string)$item->description;
         else if( $item->content )
            $description = (string)$item->content;
         else if( $item->summary )
            $description = (string)$item->summary;
         else
         {
            $description = '';
            foreach($item->children('atom', TRUE) as $element)
            {
               if($element->getName() == 'summary')
               {
                  $description = (string)$element;
                  break;
               }
            }
         }
         
         $this->description = $this->set_description($description);
         $this->youtube = $this->find_youtube($description);
         $this->image = $this->find_image($description);
      }
      else
      {
         $this->title = 'None';
         $this->link = '/';
         $this->date = strtotime( Date('Y-m-d H:m') );
         $this->description = 'No description';
         $this->youtube = NULL;
         $this->image = NULL;
      }
      
      $this->image_width = 0;
      $this->image_height = 0;
      
      if($f)
      {
         $this->feed_name = $f->name;
         $this->feed_url = $f->url();
      }
      
      $this->selected = FALSE;
   }
   
   public function show_date()
   {
      return Date('Y-m-d H:m', $this->date);
   }
   
   public function go_to_url()
   {
      return 'index.php?page=go_to&url='.urlencode($this->link).'&feed='.urlencode($this->feed_name);
   }
   
   private function set_description($desc)
   {
      $desc = strip_tags( preg_replace("/(<br\ ?\/?>)+/", "\n", $desc) );
      if( strlen($desc) > 500 )
         $desc = substr($desc, 0, 500) . '...';
      return preg_replace("/(\n)+/", "<br/>", trim($desc));
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
   
   public function process_image()
   {
      if(substr($this->image, 0, 4) == 'http')
      {
         $aux = explode('/', $this->image);
         $filename = $aux[ count($aux) - 1 ];
         if( !file_exists('tmp/images/'.$filename) )
         {
            if( !file_exists('tmp/images') )
               mkdir('tmp/images');
            
            $ch = curl_init($this->image);
            $fp = fopen('tmp/images/'.$filename, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
         }
         
         $size = getimagesize('tmp/images/'.$filename);
         if($size[0] > 100 AND $size[1] > 50)
         {
            $this->image = FS_PATH.'/tmp/images/'.$filename;
            if($size[0] > 420)
            {
               $this->image_width = 420;
               $this->image_height = intval($size[1] * 420 / $size[0]);
            }
            else
            {
               $this->image_width = $size[0];
               $this->image_height = $size[1];
            }
         }
         else
         {
            $this->image = NULL;
            $this->image_width = 0;
            $this->image_height = 0;
         }
      }
   }
}

?>
