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
require_once 'model/story_media.php';

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
   public $date;
   
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
         $this->date = $m['date'];
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
         $this->date = time();
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex('url');
   }
   
   public function ratio()
   {
      if($this->width > 0 AND $this->height > 0)
         return($this->width / $this->height);
      else
         return 0;
   }
   
   public function show_image()
   {
      if($this->type == 'imgur')
      {
         return '<img src="'.$this->url.'" alt="'.$this->date.'" width="'.$this->width.'"
               height="'.$this->height.'"/>';
      }
      else if($this->type == 'image')
      {
         if( file_exists('tmp/images/'.$this->filename) )
         {
            return '<img src="'.FS_PATH.'/tmp/images/'.$this->filename.'" alt="'.$this->filename.
                 '" width="'.$this->width.'" height="'.$this->height.'"/>';
         }
         else
            return '';
      }
      else if($this->type == 'youtube')
         return '<img src="'.$this->thumbnail_url.'" alt="'.$this->filename.
              '" width="225" height="127"/>';
      else if($this->type == 'vimeo')
         return '<img src="'.$this->thumbnail_url.'" alt="'.$this->filename.
              '" width="225" height="127"/>';
      else if($this->type == 'video')
         return '<img src="'.FS_PATH.'/view/img/video.png" alt="'.$this->date.'" width="225" height="127"/>';
      else
         return '';
   }
   
   private function mobile()
   {
      $user_agent = 'unknown';
      if( isset($_SERVER['HTTP_USER_AGENT']) )
         $user_agent = $_SERVER['HTTP_USER_AGENT'];
      
      return (strstr(strtolower($user_agent), 'mobile') || strstr(strtolower($user_agent), 'android'));
   }
   
   public function show($url=FALSE)
   {
      if($this->type == 'imgur')
      {
         $aux = '<img itemprop="image" src="'.$this->url.'" alt="'.$this->date.'" width="'.$this->width.'"
               height="'.$this->height.'"/>';
         
         if($url)
            return '<a rel="nofollow" target="_blank" href="'.$url.'">'.$aux.'</a>';
         else
            return $aux;
      }
      else if($this->type == 'image')
      {
         if( !file_exists('tmp/images/'.$this->filename) )
            return '';
         else
         {
            $aux = '<img itemprop="image" src="'.FS_PATH.'/tmp/images/'.$this->filename.'" alt="'.$this->filename.
                    '" width="'.$this->width.'" height="'.$this->height.'"/>';
            
            if($url)
               return '<a rel="nofollow" target="_blank" href="'.$url.'">'.$aux.'</a>';
            else
               return $aux;
         }
      }
      else if($this->type == 'youtube')
      {
         if( $this->mobile() )
         {
            return '<iframe width="300" height="169" src="http://www.youtube.com/embed/'.
                    $this->filename.'" frameborder="0" allowfullscreen></iframe>';
         }
         else
         {
            return '<iframe width="640" height="360" src="http://www.youtube.com/embed/'.
                    $this->filename.'" frameborder="0" allowfullscreen></iframe>';
         }
      }
      else if($this->type == 'vimeo')
      {
         if( $this->mobile() )
         {
            return '<iframe src="http://player.vimeo.com/video/'.$this->filename.
                    '" width="300" height="169" frameborder="0" '.
               'webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
         }
         else
         {
            return '<iframe src="http://player.vimeo.com/video/'.$this->filename.
                    '" width="500" height="281" frameborder="0" '.
               'webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
         }
      }
      else if($this->type == 'video')
      {
         if( $this->mobile() )
            return '<video width="300" height="170" src="'.$this->url.'" controls>
               Navegador no compatible.
               </video>';
         else
            return '<video width="500" height="281" src="'.$this->url.'" controls>
               Navegador no compatible.
               </video>';
      }
      else
         return '';
   }
   
   public function find_media($item, $link, $search_link=TRUE)
   {
      $mlist = array();
      
      if( !$this->find_media_aux($link, $mlist) )
      {
         if($item)
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
            
            $urls = $this->find_urls($text);
         }
         else
            $urls = array();
         
         /// buscamos más imágenes en el link, después descartamos
         if($search_link)
         {
            $html = $this->curl_download($link);
            foreach($this->find_urls($html) as $url)
            {
               if( !in_array($url, $urls) )
                  $urls[] = $url;
            }
         }
         
         foreach($urls as $url)
            $this->find_media_aux($url, $mlist);
      }
      
      return $mlist;
   }
   
   private function find_media_aux($link, &$mlist)
   {
      if( mb_substr($link, 0, 19) == 'http://i.imgur.com/' )
      {
         $mi = new media_item();
         $mi->url = $link;
         $mi->type = 'imgur';
         $mlist[] = $mi;
         return TRUE;
      }
      else if( $this->is_valid_image_url($link) )
      {
         $mi = new media_item();
         $mi->url = $link;
         $mi->type = 'image';
         $mlist[] = $mi;
         return TRUE;
      }
      else if($this->is_valid_video($link) )
      {
         $mi = new media_item();
         $mi->url = $link;
         $mi->type = 'video';
         $mlist[] = $mi;
         return TRUE;
      }
      else if( mb_substr($link, 0, 29) == 'http://www.youtube.com/embed/' )
      {
         $mi = new media_item();
         $mi->type = 'youtube';
         $parts = explode('/', $link);
         $mi->filename = $this->clean_youtube_id($parts[4]);
         $mi->url = 'http://www.youtube.com/embed/'.$mi->filename;
         $mi->original_width = $mi->width = 225;
         $mi->original_height = $mi->height = 127;
         $mi->thumbnail_url = 'http://img.youtube.com/vi/'.$mi->filename.'/0.jpg';
         $mlist[] = $mi;
         return TRUE;
      }
      else if( mb_substr($link, 0, 23) == 'http://www.youtube.com/' OR mb_substr($link, 0, 24) == 'https://www.youtube.com/' )
      {
         $my_array_of_vars = array();
         parse_str( parse_url($link, PHP_URL_QUERY), $my_array_of_vars);
         if( isset($my_array_of_vars['v']) )
         {
            $mi = new media_item();
            $mi->type = 'youtube';
            $mi->filename = $this->clean_youtube_id($my_array_of_vars['v']);
            $mi->url = 'http://www.youtube.com/embed/'.$mi->filename;
            $mi->original_width = $mi->width = 225;
            $mi->original_height = $mi->height = 127;
            $mi->thumbnail_url = 'http://img.youtube.com/vi/'.$mi->filename.'/0.jpg';
            $mlist[] = $mi;
         }
         return TRUE;
      }
      else if( mb_substr($link, 0, 16) == 'http://youtu.be/' )
      {
         $mi = new media_item();
         $mi->type = 'youtube';
         $parts = explode('/', $link);
         $mi->filename = $this->clean_youtube_id($parts[3]);
         $mi->url = 'http://www.youtube.com/embed/'.$mi->filename;
         $mi->original_width = $mi->width = 225;
         $mi->original_height = $mi->height = 127;
         $mi->thumbnail_url = 'http://img.youtube.com/vi/'.$mi->filename.'/0.jpg';
         $mlist[] = $mi;
         return TRUE;
      }
      else if( mb_substr($link, 0, 17) == 'http://vimeo.com/' )
      {
         $mi = new media_item();
         $mi->type = 'vimeo';
         $parts = explode('/', $link);
         $mi->filename = $this->clean_youtube_id($parts[3]);
         if( is_numeric($mi->filename) )
         {
            $mi->url = 'http://vimeo.com/'.$mi->filename;
            $mi->original_width = $mi->width = 225;
            $mi->original_height = $mi->height = 127;
            try
            {
               $hash = unserialize( $this->curl_download('http://vimeo.com/api/v2/video/'.$mi->filename.'.php', FALSE) );
               $mi->thumbnail_url = $hash[0]['thumbnail_medium'];
               $mlist[] = $mi;
            }
            catch(Exception $e)
            {
               $this->new_error('Imposible obtener los datos del vídeo de vimeo: '.$link."\n".$e);
            }
         }
         return TRUE;
      }
      else if( mb_substr($link, 0, 17) == 'http://imgur.com/' )
      {
         $status = FALSE;
         $aux = explode('/', $link);
         $html = $this->curl_download($link);
         foreach($this->find_urls($html) as $url)
         {
            if( $this->is_valid_image_url($url) )
            {
               if( strstr($url, $aux[3]) )
               {
                  $mi = new media_item();
                  $mi->url = $url;
                  $mi->type = 'imgur';
                  $mlist[] = $mi;
                  $status = TRUE;
                  break;
               }
            }
         }
         return $status;
      }
      else
         return FALSE;
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
      $extensions = array('.png', '.jpg', 'jpeg', '.gif');
      
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
   
   private function is_valid_video($url)
   {
      $status = TRUE;
      $extensions = array('.mp4', 'webm');
      
      if( mb_substr($url, 0, 4) != 'http' )
         $status = FALSE;
      else if( mb_strlen($url) > 200 )
         $status = FALSE;
      else if( !in_array( mb_strtolower( mb_substr($url, -4) ), $extensions) )
         $status = FALSE;
      
      return $status;
   }
   
   public function download()
   {
      $status = FALSE;
      
      if( in_array( $this->type, array('youtube', 'vimeo', 'video') ) )
      {
         $status = TRUE;
      }
      else if($this->type == 'image' OR $this->type == 'imgur')
      {
         $this->filename = $this->random_string(30);
         try
         {
            if( !file_exists('tmp/images') )
               mkdir('tmp/images');
            
            $this->curl_save($this->url, 'tmp/images/'.$this->filename);
            
            if( file_exists('tmp/images/'.$this->filename) )
            {
               $image = new my_image();
               $image->load('tmp/images/'.$this->filename);
               $this->original_width = $image->getWidth();
               $this->original_height = $image->getHeight();
               
               if($image->getWidth() > 100 AND $image->getHeight() > 80)
               {
                  $image->resizeToWidth(225);
                  $image->save();
                  $this->height = $image->getHeight();
                  $this->width = $image->getWidth();
                  $status = TRUE;
                  
                  /*
                   * Las imágenes de imgur solo las descargamos para obtener las
                   * dimensiones.
                   */
                  if( $this->type == 'imgur' )
                     unlink('tmp/images/'.$this->filename);
               }
               else
               {
                  /*
                   * Si la imágen no nos vale, la borramos, pero nos guardamos los
                   * datos (la url) para no descargarla de nuevo.
                   */
                  unlink('tmp/images/'.$this->filename);
                  $this->save();
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
   
   public function redownload()
   {
      if($this->type == 'image' AND !file_exists('tmp/images/'.$this->filename) )
      {
         try
         {
            if( !file_exists('tmp/images') )
               mkdir('tmp/images');
            
            $this->curl_save($this->url, 'tmp/images/'.$this->filename);
            
            if( file_exists('tmp/images/'.$this->filename) )
            {
               $image = new my_image();
               $image->load('tmp/images/'.$this->filename);
               $this->original_width = $image->getWidth();
               $this->original_height = $image->getHeight();
               if($image->getWidth() > 100 AND $image->getHeight() > 80)
               {
                  $image->resizeToWidth(225);
                  $image->save();
                  $this->height = $image->getHeight();
                  $this->width = $image->getWidth();
               }
               else
                  unlink('tmp/images/'.$this->filename);
            }
            else
               $this->delete();
         }
         catch(Exception $e)
         {
            $this->new_error('Error al descargar '.$this->url.' : '.$e);
            $this->delete();
         }
      }
      
      $this->date = time();
      $this->save();
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('_id' => new MongoId($id)) );
      if($data)
         return new media_item($data);
      else
         return FALSE;
   }
   
   public function get_by_url($url)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
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
          'thumbnail_url' => $this->thumbnail_url,
          'date' => $this->date
      );
      
      if( $this->exists() )
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__.'@update');
         $filter = array('_id' => $this->id);
         $this->collection->update($filter, $data);
      }
      else
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__.'@insert');
         $this->collection->insert($data);
         $this->id = $data['_id'];
      }
   }
   
   public function delete()
   {
      if( file_exists('tmp/images/'.$this->filename) )
         unlink('tmp/images/'.$this->filename);
      
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
      
      $story_media = new story_media();
      foreach($story_media->all4media($this->get_id()) as $sm)
         $sm->delete();
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $mlist = array();
      foreach($this->collection->find() as $i)
         $mlist[] = new media_item($i);
      return $mlist;
   }
   
   public function random($limit=FS_MAX_STORIES)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $mlist = array();
      $offset = mt_rand(0, max( array(0, $this->count()-$limit) ) );
      foreach($this->collection->find()->skip($offset)->limit($limit) as $i)
         $mlist[] = new media_item($i);
      return $mlist;
   }
   
   public function stats()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return array(
          array('imgur', number_format( $this->collection->find(array('type'=>'imgur'))->count()) , 0, ',', '.'),
          array('image', number_format( $this->collection->find(array('type'=>'image'))->count()) , 0, ',', '.'),
          array('youtube', number_format( $this->collection->find(array('type'=>'youtube'))->count()) , 0, ',', '.'),
          array('vimeo', number_format( $this->collection->find(array('type'=>'vimeo'))->count()) , 0, ',', '.'),
          array('video', number_format( $this->collection->find(array('type'=>'video'))->count()) , 0, ',', '.'),
      );
   }
   
   public function cron_job()
   {
      if( mt_rand(0, 2) == 0 )
      {
         $DIR = 'tmp/images/';
         if( file_exists($DIR) )
         {
            echo "\nEliminamos imágenes antiguas... ";
            foreach(scandir($DIR) as $file)
            {
               if( filemtime($DIR.$file) <= time()-FS_MAX_AGE )
               {
                  unlink($DIR.$file);
                  echo '-';
               }
            }
         }
         
         echo "\nEliminamos media_items antiguos...";
         /// eliminamos los registros más antiguos que FS_MAX_AGE
         $this->collection->remove( array('date' => array('$lt'=>time()-FS_MAX_AGE)) );
      }
   }
}

?>