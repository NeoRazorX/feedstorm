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

require_once 'base/fs_model.php';
require_once 'model/my_image.php';

class media_item extends fs_model
{
   public $url;
   public $type;
   public $filename;
   public $width;
   public $original_width;
   public $height;
   public $original_height;
   public $thumbnail_url;
   
   public function __construct($m = FALSE)
   {
      parent::__construct('media_items');
      if($m)
      {
         $this->id = $m['_id'];
         $this->url = $m['url'];
         $this->type = $m['type'];
         $this->filename = $m['filename'];
         $this->width = $m['width'];
         $this->original_width = $m['original_width'];
         $this->height = $m['height'];
         $this->original_height = $m['original_height'];
         $this->thumbnail_url = $m['thumbnail_url'];
      }
      else
      {
         $this->id = NULL;
         $this->url = NULL;
         $this->type = NULL;
         $this->filename = NULL;
         $this->width = 0;
         $this->original_width = 0;
         $this->height = 0;
         $this->original_height = 0;
         $this->thumbnail_url = NULL;
      }
   }
   
   public function show_image()
   {
      if($this->type == 'image')
         return '<img src="'.FS_PATH.'/tmp/images/'.$this->filename.'" alt="'.$this->filename.
              '" width="'.$this->width.'" height="'.$this->height.'"/>';
      else if($this->type == 'youtube')
         return '<img src="'.$this->thumbnail_url.'" alt="'.$this->filename.
              '" width="225" height="127"/>';
      else if($this->type == 'vimeo')
         return '<img src="'.$this->thumbnail_url.'" alt="'.$this->filename.
              '" width="225" height="127"/>';
      else
         return '';
   }
   
   public function show()
   {
      if($this->type == 'image')
         return '<img src="'.FS_PATH.'/tmp/images/'.$this->filename.'" alt="'.$this->filename.
              '" width="'.$this->width.'" height="'.$this->height.'"/>';
      else if($this->type == 'youtube')
         return '<iframe width="640" height="360" src="http://www.youtube.com/embed/'.$this->filename.'" frameborder="0" allowfullscreen>
            </iframe>';
      else if($this->type == 'vimeo')
         return '<iframe src="http://player.vimeo.com/video/'.$this->filename.'" width="500" height="281" frameborder="0" '.
            'webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
      else
         return '';
   }
   
   public function find_media($item, $link)
   {
      $mlist = array();
      
      if( $item )
      {
         $text = '';
         if( $item->description )
            $text .= (string)$item->description;
         if( $item->content )
            $text .= (string)$item->content;
         else if( $item->summary )
            $text .= (string)$item->summary;
         else
         {
            /// intentamos leer el espacio de nombres atom
            foreach($item->children('atom', TRUE) as $element)
            {
               if($element->getName() == 'summary')
               {
                  $text .= (string)$element;
                  break;
               }
            }
            foreach($item->children('content', TRUE) as $element)
            {
               if($element->getName() == 'encoded')
               {
                  $text .= (string)$element;
                  break;
               }
            }
         }
      }
      
      $urls = $this->find_urls($text);
      $urls[] = $link;
      if( count($urls) < 10 )
      {
         /// buscamos más imágenes en el link, después descartamos
         $ch0 = curl_init( $link );
         curl_setopt($ch0, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
         $html = curl_exec($ch0);
         curl_close($ch0);
         foreach($this->find_urls($html) as $url)
         {
            if( !in_array($url, $urls) )
               $urls[] = $url;
         }
      }
      foreach($urls as $url)
      {
         $mi = new media_item();
         
         if( $this->is_valid_image_url($url) )
         {
            $mi->url = $url;
            $mi->type = 'image';
            $mlist[] = $mi;
         }
         else if( substr($url, 0, 29) == 'http://www.youtube.com/embed/' )
         {
            $mi->type = 'youtube';
            $parts = explode('/', $url);
            $mi->filename = $this->clean_youtube_id($parts[4]);
            $mi->url = 'http://www.youtube.com/embed/'.$mi->filename;
            $mi->original_width = $mi->width = 225;
            $mi->original_height = $mi->height = 127;
            $mi->thumbnail_url = 'http://img.youtube.com/vi/'.$mi->filename.'/0.jpg';
            $mlist[] = $mi;
         }
         else if( substr($url, 0, 23) == 'http://www.youtube.com/' OR substr($url, 0, 24) == 'https://www.youtube.com/' )
         {
            $my_array_of_vars = array();
            parse_str( parse_url($url, PHP_URL_QUERY), $my_array_of_vars);
            if( isset($my_array_of_vars['v']) )
            {
               $mi->type = 'youtube';
               $mi->filename = $this->clean_youtube_id($my_array_of_vars['v']);
               $mi->url = 'http://www.youtube.com/embed/'.$mi->filename;
               $mi->original_width = $mi->width = 225;
               $mi->original_height = $mi->height = 127;
               $mi->thumbnail_url = 'http://img.youtube.com/vi/'.$mi->filename.'/0.jpg';
               $mlist[] = $mi;
            }
         }
         else if( substr($url, 0, 17) == 'http://vimeo.com/' )
         {
            $mi->type = 'vimeo';
            $parts = explode('/', $url);
            $mi->filename = $this->clean_youtube_id($parts[3]);
            if( is_numeric($mi->filename) )
            {
               $mi->url = 'http://vimeo.com/'.$mi->filename;
               $mi->original_width = $mi->width = 225;
               $mi->original_height = $mi->height = 127;
               try
               {
                  $hash = unserialize( file_get_contents('http://vimeo.com/api/v2/video/'.$mi->filename.'.php') );
                  $mi->thumbnail_url = $hash[0]['thumbnail_medium'];
                  $mlist[] = $mi;
               }
               catch(Exception $e)
               {
                  $this->new_error('Imposible obtener los datos del vídeo de vimeo: '.$url."\n".$e);
               }
            }
            else
               $this->new_error('Error al obtener el id de '.$url.' ID obtenido: '.$mi->filename);
         }
      }
      return $mlist;
   }
   
   private function clean_youtube_id($yid)
   {
      $new_yid = '';
      $yid = trim($yid);
      for($i=0; $i<strlen($yid); $i++)
      {
         $aux = substr($yid, $i, 1);
         if( preg_match("#[a-zA-Z0-9\-_]#", $aux) )
            $new_yid .= $aux;
         else
            break;
      }
      return $new_yid;
   }
   
   private function find_urls($text)
   {
      $text = html_entity_decode($text);
      $found = array();
      $urls = array();
      if( preg_match_all("#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#", $text, $urls) )
      {
         foreach($urls as $url)
         {
            foreach($url as $u)
            {
               if( !in_array($u, $found) )
                  $found[] = $u;
            }
         }
      }
      return $found;
   }
   
   private function is_valid_image_url($url)
   {
      $status = TRUE;
      $extensions = array('.png', '.PNG', '.jpg', '.JPG', 'jpeg', 'JPEG', '.gif', '.GIF');
      
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
      else if( !in_array(substr($url, -4), $extensions) )
         $status = FALSE;
      
      return $status;
   }
   
   public function download()
   {
      $status = FALSE;
      if($this->type == 'youtube' OR $this->type == 'vimeo')
         $status = TRUE;
      else if($this->type == 'image')
      {
         $this->filename = $this->random_string(30);
         try
         {
            if( !file_exists('tmp/images') )
               mkdir('tmp/images');
            
            $ch1 = curl_init( $this->url );
            $fp = fopen('tmp/images/'.$this->filename, 'wb');
            curl_setopt($ch1, CURLOPT_FILE, $fp);
            curl_setopt($ch1, CURLOPT_HEADER, 0);
            curl_setopt($ch1, CURLOPT_TIMEOUT, 30);
            curl_exec($ch1);
            curl_close($ch1);
            fclose($fp);
            
            if( file_exists('tmp/images/'.$this->filename) )
            {
               $image = new my_image();
               $image->load('tmp/images/'.$this->filename);
               $this->original_width = $image->getWidth();
               $this->original_height = $image->getHeight();
               if($image->getWidth() > 100 AND $image->getHeight() > 80)
               {
                  if($image->getWidth() > 225)
                  {
                     $image->resizeToWidth(225);
                     $image->save();
                  }
                  $this->height = $image->getHeight();
                  $this->width = $image->getWidth();
                  $status = TRUE;
               }
            }
            else
               $this->new_error('No se encuentra el archivo después de descargar '.$this->url);
         }
         catch(Exception $e)
         {
            $this->new_error('Error al descargar '.$this->url.' : '.$e);
         }
      }
      else
         $this->new_error('Tipo desconocido.');
      return $status;
   }
   
   public function get($id)
   {
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new media_item($data);
      else
         return FALSE;
   }
   
   public function get_by_url($url)
   {
      $data = $this->collection->findone( array('url' => $url) );
      if($data)
         return new media_item($data);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
      {
         $data = $this->collection->findone( array('_id' => new MongoId($this->id)) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $data = array(
          'url' => $this->url,
          'type' => $this->type,
          'filename' => $this->filename,
          'width' => $this->width,
          'original_width' => $this->original_width,
          'height' => $this->height,
          'original_height' => $this->original_height,
          'thumbnail_url' => $this->thumbnail_url
      );
      
      if( $this->exists() )
      {
         $filter = array('_id' => $this->id);
         $this->collection->update($filter, $data);
      }
      else
      {
         $this->collection->insert($data);
         $this->id = $data['_id'];
      }
   }
   
   public function delete()
   {
      $this->collection->remove( array('_id' => $this->id) );
   }
   
   public function all()
   {
      $mlist = array();
      foreach($this->collection->find() as $i)
         $mlist[] = new media_item($i);
      return $mlist;
   }
}

?>