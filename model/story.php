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
require_once 'model/feed.php';

class story extends fs_model
{
   private $id;
   public $title;
   public $description;
   public $link;
   public $link2;
   public $date;
   public $youtube;
   public $image;
   public $image_width;
   public $image_height;
   private $images; /// no se pueden/deben descartar
   private $more_images; /// estas son las descartables
   public $feed_name;
   public $feed_url;
   public $selected;
   
   private static $story_ids; /// un array con las correspondencias entre url e id
   private static $filenames; /// un array con las correspondencia entre url y filename
   private static $image_votes; /// un array con los votos de imágenes para cada noticia
   
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
            $this->date = time();
         
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
         if( is_null($this->youtube) )
            $this->images = $this->find_images($description, $item);
         else
            $this->images = array();
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
      
      $this->new_id();
      $this->selected = FALSE;
   }
   
   private function load_story_ids()
   {
      if( !isset(self::$story_ids) )
         self::$story_ids = $this->cache->get_array('story_ids');
   }
   
   private function save_story_ids()
   {
      $this->load_story_ids();
      /// limpiamos registros caducados
      foreach(self::$story_ids as $i => $value)
      {
         if( $value['expires'] < time() )
            unset(self::$story_ids[$i]);
      }
      /// guardamos
      $this->cache->set('story_ids', self::$story_ids, 86400);
   }
   
   private function new_id()
   {
      if( $this->link != '/' )
      {
         $maxid = 0;
         $encontrado = FALSE;
         $this->load_story_ids();
         foreach(self::$story_ids as $i => $value)
         {
            if( $value['id'] > $maxid )
               $maxid = $value['id'];
            
            if($this->link == $value['link'])
            {
               $this->id = $value['id'];
               self::$story_ids[$i]['expires'] = time()+86400;
               $encontrado = TRUE;
            }
         }
         if( !$encontrado )
         {
            $this->id = $maxid+1;
            self::$story_ids[] = array(
                'id' => $this->id,
                'link' => $this->link,
                'feed_name' => $this->feed_name,
                'expires' => time()+86400
            );
         }
         $this->save_story_ids();
      }
   }
   
   public function get_id()
   {
      return $this->id;
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
      return 'index.php?page=story_info&story_id='.$this->id;
   }
   
   public function go2url()
   {
      return 'index.php?page=go2url&story_id='.$this->id;
   }
   
   public function wrong_image_url()
   {
      return 'index.php?page=wrong_image&story_id='.$this->id;
   }
   
   public function meneame()
   {
       $result = FALSE;
       if( strlen($this->link2) > 4 )
       {
           $result = ( substr($this->link2, 0, 16) == 'http://menea.me/' );
       }
       return $result;
   }
   
   private function set_description($desc)
   {
      $desc = strip_tags( preg_replace("/(<br\ ?\/?>)+/", "\n", $desc) );
      if( strlen($desc) > 300 )
         $desc = substr($desc, 0, 300) . '...';
      return $this->true_word_break( preg_replace("/(\n)+/", "<br/>", trim($desc)) );
   }
   
   public function get($sid)
   {
      $story = FALSE;
      $feed = new feed();
      $this->load_story_ids();
      foreach(self::$story_ids as $id)
      {
         if($id['id'] == $sid)
         {
            $feed0 = $feed->get($id['feed_name']);
            if( $feed0 )
               $story = $feed0->get_story($sid);
            break;
         }
      }
      return $story;
   }
   
   public function save()
   {
      $feed = new feed();
      $feed0 = $feed->get( $this->feed_name );
      if( $feed0 )
         $feed0->save_story( $this );
   }
   
   private function find_urls($text)
   {
      $text = html_entity_decode($text);
      $found = array( $this->link );
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
         else if( substr($url, 0, 23) == 'http://www.youtube.com/' OR substr($url, 0, 24) == 'https://www.youtube.com/' )
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
         if( $this->is_valid_image_url($url) AND in_array(substr($url, -4), $extensions) )
            $imgs[] = $url;
      }
      if( preg_match_all("/<img .*?(?=src)src=\"([^\"]+)\"/si", $text, $urls2) )
      {
         foreach($urls2 as $url)
         {
            foreach($url as $u)
            {
               if( $this->is_valid_image_url($u) AND !in_array($u, $imgs) )
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
               if( $this->is_valid_image_url($aux) AND !in_array($aux, $imgs) )
                  $imgs[] = $aux;
            }
         }
      }
      return $imgs;
   }
   
   private function is_valid_image_url($url)
   {
      $status = TRUE;
      
      if( substr($url, 0, 4) != 'http' )
         $status = FALSE;
      else if( strlen($url) > 200 )
         $status = FALSE;
      else if( strstr($url, '/favicon.') )
         $status = FALSE;
      else if( strstr($url, 'doubleclick.net') )
         $status = FALSE;
      else if( substr($url, 0, 10) == 'http://ad.' )
         $status = FALSE;
      else if( strstr($url, '/avatar') )
         $status = FALSE;
      else if( substr($url, 0, 47) == 'http://www.meneame.net/backend/vote_com_img.php' )
         $status = FALSE;
      else if( substr($url, 0, 26) == 'http://publicidadinternet.' )
         $status = FALSE;
      else if( substr($url, -3) == '.js' )
         $status = FALSE;
      
      return $status;
   }
   
   public function pre_process_images(&$work, &$discarded)
   {
      echo '+';
      
      if( is_null($this->youtube) )
      {
         /// buscamos más imágenes en el link, después descartamos
         $ch0 = curl_init( $this->link );
         curl_setopt($ch0, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
         curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
         $html = curl_exec($ch0);
         curl_close($ch0);
         
         foreach($this->find_images($html) as $img)
         {
            if( !in_array($img, $this->images) )
               $this->more_images[] = $img;
         }
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
            foreach($work as $i => $wi)
            {
               if( $img == $wi[0] )
               {
                  $encontrada = TRUE;
                  $work[$i][1]++;
                  break;
               }
            }
            if( !$encontrada )
               $work[] = array($img, 1);
         }
         /// ahora rellenamos las descartadas
         foreach($work as $wi)
         {
            if( $wi[1] > 1 )
            {
               if( !in_array($wi[0], $discarded) )
                  $discarded[] = $wi[0];
            }
         }
      }
   }
   
   public function process_images(&$discarded, &$selected)
   {
      echo '*';
      
      if( is_null($this->youtube) )
      {
         $num = 20;
         $url = FALSE;
         $url2 = FALSE;
         $preselected = array();
         
         foreach($this->images as $img)
         {
            if( $num > 0 )
            {
               if( $this->process_image($img, $selected) )
               {
                  $url = $img;
                  $url2 = $this->image;
                  $preselected[] = $this->image;
               }
            }
            $num--;
         }
         
         /// si no hemos encontrado alguna imágen grande, buscamos en more_images
         if( $this->image_width < 200 OR $this->image_height < 50 )
         {
            foreach($this->more_images as $img)
            {
               if( $num > 0 )
               {
                  if( !in_array($img, $discarded) )
                  {
                     if( $this->process_image($img, $selected) )
                     {
                        $url = $img;
                        $url2 = $this->image;
                        $preselected[] = $this->image;
                     }
                  }
               }
               $num--;
            }
         }
         
         if( $url )
         {
            /// nos guardamos como seleccionadas tanto la url original como la nueva ruta
            $selected[] = $url;
            $selected[] = $url2;
         }
         
         /// limpiamos
         unset($this->images);
         unset($this->more_images);
         $this->images = $preselected;
         $this->more_images = array();
         
         $this->process_image_votes();
      }
   }
   
   private function process_image($url, &$selections)
   {
      echo '-';
      
      $selected = FALSE;
      $filename = $this->get_filename($url);
      
      if( !in_array($url, $selections) )
      {
         $continuar = TRUE;
         
         if( !file_exists('tmp/images/'.$filename) )
         {
            echo 'D';
            
            try
            {
               $ch1 = curl_init($url);
               $fp = fopen('tmp/images/'.$filename, 'wb');
               curl_setopt($ch1, CURLOPT_FILE, $fp);
               curl_setopt($ch1, CURLOPT_HEADER, 0);
               curl_setopt($ch1, CURLOPT_TIMEOUT, 30);
               curl_exec($ch1);
               curl_close($ch1);
               fclose($fp);
            }
            catch(Exception $e)
            {
               $continuar = FALSE;
               
               /*
                * añadimos la url de la imágen que ha fallado para descartarla
                * en las siguientes noticias
                */
               $selections[] = $url;
            }
         }
         
         if( file_exists('tmp/images/'.$filename) AND $continuar )
         {
            $image = new my_image();
            $image->load('tmp/images/'.$filename);
            if( $image->getWidth() > max(array(50, $this->image_width)) AND $image->getHeight() > max(array(30, $this->image_height)) )
            {
               $this->image = FS_PATH.'/'.$image->path;
               if($image->getWidth() > 225)
               {
                  echo 'R';
                  $image->resizeToWidth(225);
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
      {
         self::$filenames = $this->cache->get_array('filenames');
         if( file_exists('tmp/images') )
         {
            if( !self::$filenames )
            {
               $files = glob('tmp/images/*');
               foreach($files as $file)
               {
                  if( is_file($file) )
                     unlink($file);
               }
            }
         }
         else
            mkdir('tmp/images');
      }
      
      $filename = strval(rand());
      $encontrada = FALSE;
      foreach(self::$filenames as $i => $fn)
      {
         if($url == $fn['url'])
         {
            $filename = $fn['filename'];
            self::$filenames[$i]['expires'] = time()+86400;
            $encontrada = TRUE;
            break;
         }
      }
      if( !$encontrada )
      {
         while( file_exists('tmp/images/'.$filename) )
            $filename = strval(rand());
         
         /// lo añadimos a la lista
         self::$filenames[] = array(
             'url' => $url,
             'filename' => $filename,
             'expires' => time()+86400
         );
         
         /// eliminamos los elementos caducados
         foreach(self::$filenames as $i => $fn)
         {
            if( $fn['expires'] < time() )
            {
               if( file_exists('tmp/images/'.$fn['filename']) )
                  unlink('tmp/images/'.$fn['filename']);
               unset(self::$filenames[$i]);
            }
         }
      }
      $this->cache->set('filenames', self::$filenames, 86400);
      
      return $filename;
   }
   
   public function get_local_images()
   {
      $images = array();
      foreach($this->images as $img)
      {
         $aux = explode('/', $img);
         $images[] = array(
             'image' => $img,
             'filename' => $aux[ count($aux) - 1 ]
         );
      }
      return $images;
   }
   
   public function load_story_image_votes()
   {
      if( !isset(self::$image_votes) )
         self::$image_votes = $this->cache->get_array('story_image_votes');
   }
   
   private function save_image_votes()
   {
      /// eliminamos elementos caducados
      $this->load_story_image_votes();
      foreach(self::$image_votes as $i => $value)
      {
         if( $value['expires'] < time() )
            unset(self::$image_votes[$i]);
      }
      $this->cache->set('story_image_votes', self::$image_votes, 86400);
   }
   
   public function select_new_image($filename)
   {
      if( $filename == '-none-' AND is_null($this->image) )
         return FALSE;
      else if( $this->image == FS_PATH.'/tmp/images/'.$filename )
         return FALSE;
      else
      {
         $encontrado = FALSE;
         $this->load_story_image_votes();
         foreach(self::$image_votes as $i => $value)
         {
            if($value['story_id'] == $this->id AND $value['filename'] == $filename)
            {
               $encontrado = TRUE;
               if( !in_array($_SERVER['REMOTE_ADDR'], $value['ips']) )
               {
                  self::$image_votes[$i]['votes']++;
                  self::$image_votes[$i]['ips'][] = $_SERVER['REMOTE_ADDR'];
                  self::$image_votes[$i]['expires'] = time()+86400;
                  $this->process_image_votes();
               }
               break;
            }
         }
         if( !$encontrado )
         {
            self::$image_votes[] = array(
                'story_id' => $this->id,
                'filename' => $filename,
                'votes' => 1,
                'ips' => array($_SERVER['REMOTE_ADDR']),
                'expires' => time()+86400
            );
            $this->process_image_votes();
         }
         $this->save_image_votes();
      }
   }
   
   /*
    * Procesa los votos y cambia la imágen si es oportuno.
    * Devuelve TRUE si se produce el cambio
    */
   private function process_image_votes()
   {
      $changed = FALSE;
      $max = 0;
      $new_image = FALSE;
      $this->load_story_image_votes();
      foreach(self::$image_votes as $v)
      {
         if($v['story_id'] == $this->id AND $v['votes'] > $max)
         {
            $max = $v['votes'];
            
            if($v['filename'] == '-none-')
               $new_image = $v['filename'];
            else
               $new_image = 'tmp/images/'.$v['filename'];
         }
      }
      if( $max > 0 )
      {
         if($new_image == '-none-')
         {
            $this->clean_image();
            $this->save();
            $changed = TRUE;
         }
         else if( file_exists($new_image) )
         {
            $image = new my_image();
            $image->load( $new_image );
            $this->image = FS_PATH.'/'.$image->path;
            $this->image_height = $image->getHeight();
            $this->image_width = $image->getWidth();
            $this->save();
            $changed = TRUE;
         }
      }
      return $changed;
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
