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
   public $link2;
   public $date;
   public $youtube;
   
   public $image;
   public $image_width;
   public $image_height;
   
   public $images; /// no se pueden/deben descartar
   public $more_images; /// estas son las descartables
   private static $filenames; /// un array con las correspondencia entre url y filename

   public $feed_name;
   public $feed_url;
   
   public $selected;
   
   public function __construct($item=FALSE, $f=FALSE)
   {
      parent::__construct();
      if( $item )
      {
         $this->title = (string)$item->title;
         
         $this->link2 = '/';
         /// intentamos obtener el enlace original de meneame
         foreach($item->children('meneame', TRUE) as $element)
         {
            if($element->getName() == 'url')
            {
               $this->link = (string)$element;
               $this->link2 = (string)$item->link;
               break;
            }
         }
         if( !isset($this->link) )
         {
            /// intentamos obtener el enlace original de feedburner
            foreach($item->children('feedburner', TRUE) as $element)
            {
               if($element->getName() == 'origLink')
               {
                  $this->link = (string)$element;
                  break;
               }
            }
            /// intentamos leer el/los links
            if( !isset($this->link) AND $item->link )
            {
               foreach($item->link as $l)
               {
                  if( substr((string)$l, 0, 4) == 'http' )
                     $this->link = (string)$l;
                  else
                  {
                     if( $l->attributes()->rel == 'alternate' AND $l->attributes()->type == 'text/html' )
                        $this->link = (string)$l->attributes()->href;
                     else if( $l->attributes()->type == 'text/html' )
                        $this->link = (string)$l->attributes()->href;
                  }
               }
            }
            /// si aun así no hemos encontrado un link
            if( !isset($this->link) )
            {
               $this->link = $f->url();
               $this->new_error("¡Impopsible encontrar un link válido!");
            }
         }
         
         if( $item->pubDate )
            $this->date = strtotime( (string)$item->pubDate );
         else if( $item->published )
            $this->date = strtotime( (string)$item->published );
         else
            $this->date = strtotime( Date('Y-m-d H:m:i') );
         
         if( $item->description )
            $description = (string)$item->description;
         else if( $item->content )
            $description = (string)$item->content;
         else if( $item->summary )
            $description = (string)$item->summary;
         else
         {
            $description = '';
            /// intentamos leer el espacio de nombres atom
            foreach($item->children('atom', TRUE) as $element)
            {
               if($element->getName() == 'summary')
               {
                  $description = (string)$element;
                  break;
               }
            }
            foreach($item->children('content', TRUE) as $element)
            {
               if($element->getName() == 'encoded')
               {
                  $description = (string)$element;
                  break;
               }
            }
         }
         
         $this->description = $this->set_description($description);
         $this->youtube = $this->find_youtube($description);
         
         $this->image = NULL;
         $this->images = $this->find_images($description, $item);
         $this->more_images = array();
      }
      else
      {
         $this->title = 'None';
         $this->link = '/';
         $this->link2 = '/';
         $this->date = strtotime( Date('Y-m-d H:m:i') );
         $this->description = 'Sin descripción';
         $this->youtube = NULL;
         $this->image = NULL;
         $this->images = array();
         $this->more_images = array();
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
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function url()
   {
      return 'index.php?page=story_info&feed='.urlencode($this->feed_name).'&url='.urlencode($this->link);
   }
   
   public function go2url()
   {
      return 'index.php?page=go2url&feed='.urlencode($this->feed_name).'&url='.urlencode($this->link);
   }
   
   private function set_description($desc)
   {
      $desc = strip_tags( preg_replace("/(<br\ ?\/?>)+/", "\n", $desc) );
      if( strlen($desc) > 300 )
         $desc = substr($desc, 0, 300) . '...';
      return $this->true_word_break( preg_replace("/(\n)+/", "<br/>", trim($desc)) );
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
               if( substr($u, 0, 4) == 'http' AND !in_array($u, $found) )
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
   
   private function find_images($text, $item=FALSE)
   {
      $imgs = array();
      $extensions = array('.png', '.PNG', '.jpg', '.JPG', 'jpeg', 'JPEG', '.gif', '.GIF');
      $urls = $this->find_urls($text);
      foreach($urls as $url)
      {
         if( substr($url, 0, 4) == 'http' AND in_array(substr($url, -4, 4), $extensions) )
            $imgs[] = $url;
      }
      if( preg_match_all("/<img .*?(?=src)src=\"([^\"]+)\"/si", $text, $urls2) )
      {
         foreach($urls2 as $url)
         {
            foreach($url as $u)
            {
               if( substr($u, 0, 4) == 'http' AND !in_array($u, $imgs) )
                  $imgs[] = $u;
            }
         }
      }
      if( $item )
      {
         /// intentamos obtener alguna imágen en el xml
         foreach($item->children('media', TRUE) as $element)
         {
            if($element->getName() == 'thumbnail')
            {
               $aux = (string)$element->attributes()->url;
               if( substr($aux, 0, 4) == 'http' AND !in_array($aux, $imgs) )
                  $imgs[] = $aux;
            }
         }
      }
      return $imgs;
   }
   
   public function pre_process_images(&$work, &$discarded)
   {
      echo '+';
      
      /// buscamos más imágenes en el link, después descartamos
      $ch0 = curl_init( $this->link );
      curl_setopt($ch0, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
      $html = curl_exec($ch0);
      curl_close($ch0);
      $this->more_images = $this->find_images($html);
      /*
       * $work lo rellenamos con arrays con la siguiente estructura:
       * array(
       *    0 => imágen,
       *    1 => número de repeticiones
       * )
       */
      foreach($this->more_images as $img)
      {
         $encontrada = FALSE;
         foreach($work as &$di)
         {
            if( $img == $di[0] )
            {
               $encontrada = TRUE;
               $di[1]++;
               break;
            }
         }
         if( !$encontrada )
            $work[] = array($img, 1);
      }
      /// ahora rellenamos las descartadas
      foreach($work as $di)
      {
         if( $di[1] > 1 )
         {
            if( !in_array($di[0], $discarded) )
               $discarded[] = $di[0];
         }
      }
   }
   
   public function process_images(&$discarded, &$selected)
   {
      echo '*';
      
      $url = FALSE;
      $url2 = FALSE;
      
      foreach($this->images as $img)
      {
         if( $this->process_image($img, $selected) )
         {
            $url = $img;
            $url2 = $this->image;
         }
      }
      
      if( $this->image_width < 250 OR $this->image_height < 50 )
      {
         foreach($this->more_images as $img)
         {
            if( !in_array($img, $discarded) )
            {
               if( $this->process_image($img, $selected) )
               {
                  $url = $img;
                  $url2 = $this->image;
               }
            }
         }
      }
      
      if( $url )
      {
         /// nos guardamos como seleccionadas tanto la url original como la nueva ruta
         $selected[] = $url;
         $selected[] = $url2;
      }
   }
   
   private function process_image($url, &$selections)
   {
      echo '-';
      
      $selected = FALSE;
      $filename = $this->get_filename($url);
      
      if( substr($url, 0, 4) == 'http' AND !in_array($url, $selections) AND !in_array(FS_PATH.'/tmp/images/'.$filename, $selections) )
      {
         if( !file_exists('tmp/images/'.$filename) )
         {
            echo 'D';
            
            if( !file_exists('tmp/images') )
               mkdir('tmp/images');
            
            $ch1 = curl_init($url);
            $fp = fopen('tmp/images/'.$filename, 'wb');
            curl_setopt($ch1, CURLOPT_FILE, $fp);
            curl_setopt($ch1, CURLOPT_HEADER, 0);
            curl_exec($ch1);
            curl_close($ch1);
            fclose($fp);
         }
         
         if( file_exists('tmp/images/'.$filename) )
         {
            $image = new my_image();
            $image->load('tmp/images/'.$filename);
            if( $image->getWidth() > max(array(50, $this->image_width)) AND $image->getHeight() > max(array(30, $this->image_height)) )
            {
               $this->image = FS_PATH.'/'.$image->path;
               if($image->getWidth() > 320)
               {
                  echo 'R';
                  $image->resizeToWidth(320);
                  $image->save();
               }
               $this->image_height = $image->getHeight();
               $this->image_width = $image->getWidth();
               $selected = TRUE;
            }
         }
      }
      
      return $selected;
   }
   
   public function clean_image()
   {
      $this->image = NULL;
      $this->image_height = 0;
      $this->image_width = 0;
   }
   
   private function get_filename($url)
   {
      if( !isset(self::$filenames) )
         self::$filenames = $this->cache->get_array('filenames');
      
      $aux = explode('/', $url);
      $filename = $aux[ count($aux) - 1 ];
      
      /// es un nombre válido
      if( !preg_match("/^[A-Z0-9_\+\.\*\/\-]{1,18}$/i", $filename) )
      {
         $encontrada = FALSE;
         foreach(self::$filenames as $fn)
         {
            if( $url == $fn[0] )
            {
               $filename = $fn[1];
               $encontrada = TRUE;
               break;
            }
         }
         if( !$encontrada )
         {
            $filename = strval(rand());
            while( file_exists('tmp/images/'.$filename) )
               $filename = strval(rand());
            
            self::$filenames[] = array($url, $filename);
            $this->cache->set('filenames', self::$filenames, 6000);
         }
      }
      
      return $filename;
   }
   
   public function get_local_images()
   {
      $images = array();
      foreach($this->images as $url)
      {
         $filename = $this->get_filename($url);
         if( strlen($filename) > 0 )
            $images[] = 'tmp/images/'.$this->get_filename($url);
         else
            $images[] = $url;
      }
      return $images;
   }
   
   public function get_local_more_images()
   {
      $images = array();
      foreach($this->more_images as $url)
      {
         $filename = $this->get_filename($url);
         if( strlen($filename) > 0 )
            $images[] = 'tmp/images/'.$this->get_filename($url);
         else
            $images[] = $url;
      }
      return $images;
   }
}


class my_image
{
   public $image;
   public $image_type;
   public $path;
   
   function load($filename)
   {
      $this->path = $filename;
      
      try
      {
         $image_info = getimagesize($filename);
         $this->image_type = $image_info[2];
         
         if( $this->image_type == IMAGETYPE_JPEG )
            $this->image = imagecreatefromjpeg($filename);
         else if( $this->image_type == IMAGETYPE_GIF )
            $this->image = imagecreatefromgif($filename);
         else if( $this->image_type == IMAGETYPE_PNG )
            $this->image = imagecreatefrompng($filename);
         else
            $this->image = NULL;
      }
      catch(Exception $e)
      {
         $this->image = NULL;
         $this->image_type = NULL;
      }
   }
   
   function save($filename=FALSE, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null)
   {
      if( !is_null($this->image) )
      {
         if( !$filename )
            $filename = $this->path;
         
         if( $image_type == IMAGETYPE_JPEG )
            imagejpeg($this->image,$filename,$compression);
         else if( $image_type == IMAGETYPE_GIF )
            imagegif($this->image,$filename);
         else if( $image_type == IMAGETYPE_PNG )
            imagepng($this->image,$filename);
         
         if( $permissions != null)
            chmod($filename,$permissions);
      }
   }
   
   function output($image_type=IMAGETYPE_JPEG)
   {
      if( !is_null($this->image) )
      {
         if( $image_type == IMAGETYPE_JPEG )
            imagejpeg($this->image);
         else if( $image_type == IMAGETYPE_GIF )
            imagegif($this->image);
         else if( $image_type == IMAGETYPE_PNG )
            imagepng($this->image);
      }
   }
   
   function getWidth()
   {
      if( is_null($this->image) )
         return 0;
      else
         return imagesx($this->image);
   }
   
   function getHeight()
   {
      if( is_null($this->image) )
         return 0;
      else
         return imagesy($this->image);
   }
   
   function resizeToHeight($height)
   {
      if( !is_null($this->image) )
      {
         $ratio = $height / $this->getHeight();
         $width = $this->getWidth() * $ratio;
         $this->resize($width,$height);
      }
   }
   
   function resizeToWidth($width)
   {
      if( !is_null($this->image) )
      {
         $ratio = $width / $this->getWidth();
         $height = $this->getheight() * $ratio;
         $this->resize($width,$height);
      }
   }
   
   function scale($scale)
   {
      if( !is_null($this->image) )
      {
         $width = $this->getWidth() * $scale/100;
         $height = $this->getheight() * $scale/100;
         $this->resize($width,$height);
      }
   }
   
   function resize($width,$height)
   {
      if( !is_null($this->image) )
      {
         $new_image = imagecreatetruecolor($width, $height);
         
         if( $this->image_type == IMAGETYPE_GIF || $this->image_type == IMAGETYPE_PNG )
         {
            $current_transparent = imagecolortransparent($this->image);
            
            if($current_transparent != -1)
            {
               $transparent_color = imagecolorsforindex($this->image, $current_transparent);
               $current_transparent = imagecolorallocate($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
               imagefill($new_image, 0, 0, $current_transparent);
               imagecolortransparent($new_image, $current_transparent);
            }
            else if( $this->image_type == IMAGETYPE_PNG)
            {
               imagealphablending($new_image, false);
               $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
               imagefill($new_image, 0, 0, $color);
               imagesavealpha($new_image, true);
            }
         }
         
         imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
         $this->image = $new_image;
      }
   }
}

?>
