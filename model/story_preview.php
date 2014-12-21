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

require_once 'model/topic.php';

class story_preview
{
   public $filename;
   public $link;
   public $type;
   
   private $topic;
   private $topic_list;
   
   private static $downloads;
   
   public function __construct()
   {
      $this->type = FALSE;
      
      $this->topic = new topic();
      $this->topic_list = array();
      
      if( !isset(self::$downloads) )
      {
         self::$downloads = 1;
      }
   }
   
   public function clean()
   {
      $this->link = FALSE;
      $this->type = FALSE;
      $this->filename = FALSE;
   }
   
   public function set_downloads($num)
   {
      self::$downloads = $num;
   }
   
   public function load($url, $text='')
   {
      $this->link = FALSE;
      $this->type = FALSE;
      $this->filename = FALSE;
      
      $links = array($url);
      
      /// extraemos urls del texto
      $aux = array();
      if( preg_match_all('@((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', $text, $aux) )
      {
         foreach($aux[0] as $a)
            $links[] = $a;
      }
      
      foreach($links as $link)
      {
         if( mb_substr($link, 0, 19) == 'http://i.imgur.com/' )
         {
            $parts = explode('/', $link);
            if( count($parts) >= 4 )
            {
               $this->filename = $parts[3];
               $this->type = 'imgur';
               $this->link = $link;
            }
            break;
         }
         else if( $this->is_valid_image_url($link) )
         {
            $this->link = $link;
            $this->filename = $link;
            $this->type = 'image';
            break;
         }
         else if( mb_substr($link, 0, 29) == 'http://www.youtube.com/embed/' )
         {
            $parts = explode('/', $link);
            if( count($parts) >= 5 )
            {
               $this->filename = $this->clean_youtube_id($parts[4]);
               $this->type = 'youtube';
               $this->link = 'http://www.youtube.com/embed/'.$this->filename;
            }
            break;
         }
         else if( mb_substr($link, 0, 23) == 'http://www.youtube.com/' OR mb_substr($link, 0, 24) == 'https://www.youtube.com/' )
         {
            $my_array_of_vars = array();
            parse_str( parse_url($link, PHP_URL_QUERY), $my_array_of_vars);
            if( isset($my_array_of_vars['v']) )
            {
               $this->filename = $this->clean_youtube_id($my_array_of_vars['v']);
               $this->type = 'youtube';
               $this->link = 'http://www.youtube.com/embed/'.$this->filename;
               break;
            }
         }
         else if( mb_substr($link, 0, 16) == 'http://youtu.be/' )
         {
            $parts = explode('/', $link);
            if( count($parts) >= 4 )
            {
               $this->filename = $this->clean_youtube_id($parts[3]);
               $this->type = 'youtube';
               $this->link = 'http://www.youtube.com/embed/'.$this->filename;
            }
            break;
         }
         else if( mb_substr($link, 0, 17) == 'http://vimeo.com/' )
         {
            if( !file_exists('tmp/vimeo') )
               mkdir('tmp/vimeo');
            
            $parts = explode('/', $link);
            if( count($parts) >= 4 )
            {
               $video_id = $this->clean_youtube_id($parts[3]);
               $this->filename = 'tmp/vimeo/'.str_replace( '/', '_', str_replace( ':', '_', $link) );
               if( is_numeric($video_id) )
               {
                  if( !file_exists($this->filename) )
                  {
                     if( self::$downloads > 0 )
                     {
                        try
                        {
                           $html = $this->curl_download('http://vimeo.com/api/v2/video/'.$video_id.'.php', FALSE);
                           if($html)
                           {
                              $hash = unserialize($html);
                              if( isset($hash[0]['thumbnail_medium']) )
                              {
                                 $this->curl_save($hash[0]['thumbnail_medium'], 'tmp/vimeo/'.$video_id);
                                 $this->type = 'vimeo';
                                 $this->filename = $video_id;
                                 $this->link = 'http://vimeo.com/'.$video_id;
                                 break;
                              }
                           }
                        }
                        catch(Exception $e)
                        {
                           $this->new_error('Imposible obtener los datos del vídeo de vimeo: '.$link."\n".$e);
                        }
                        
                        self::$downloads--;
                     }
                  }
                  else if( filesize($this->filename) > 0 )
                  {
                     $this->type = 'vimeo';
                     $this->filename = $video_id;
                     $this->link = 'http://vimeo.com/'.$video_id;
                     break;
                  }
               }
            }
         }
         else if( strpos($link, 'imgur.com/') !== FALSE )
         {
            if( !file_exists('tmp/imgur2') )
               mkdir('tmp/imgur2');
            
            $filename = 'tmp/imgur2/'.str_replace( '/', '_', str_replace( ':', '_', $link) );
            if( !file_exists($filename) )
            {
               if( self::$downloads > 0 )
               {
                  $html = $this->curl_download($link);
                  $urls = array();
                  if( preg_match_all('#content="https://i.imgur.com/(\w*).(\w*)#', $html, $urls) )
                  {
                     foreach($urls[1] as $i => $value)
                     {
                        if( $this->is_valid_image_url('https://i.imgur.com/'.$urls[1][$i].'.'.$urls[2][$i]) )
                        {
                           $this->filename = 'https://i.imgur.com/'.$urls[1][$i].'.'.$urls[2][$i];
                           break;
                        }
                     }
                  }
                  else if( preg_match_all('#content="http://i.imgur.com/(\w*).(\w*)#', $html, $urls) )
                  {
                     foreach($urls[1] as $i => $value)
                     {
                        if( $this->is_valid_image_url('https://i.imgur.com/'.$urls[1][$i].'.'.$urls[2][$i]) )
                        {
                           $this->filename = 'http://i.imgur.com/'.$urls[1][$i].'.'.$urls[2][$i];
                           break;
                        }
                     }
                  }
                  
                  if($this->filename)
                  {
                     $file = fopen($filename, 'w');
                     if($file)
                     {
                        fwrite($file, $this->filename);
                        fclose($file);
                     }
                  }
                  
                  self::$downloads--;
               }
            }
            else
            {
               $this->filename = file_get_contents($filename);
            }
            
            $parts = explode('/', $this->filename);
            if( count($parts) >= 4 )
            {
               $this->filename = $parts[3];
               $this->type = 'imgur';
               $this->link = $link;
            }
            break;
         }
         else if( strpos($link, 'twitter.com/') !== FALSE )
         {
            if( !file_exists('tmp/twitter') )
               mkdir('tmp/twitter');
            
            $filename = 'tmp/twitter/'.str_replace( '/', '_', str_replace( ':', '_', $link) );
            if( !file_exists($filename) )
            {
               if( self::$downloads > 0 )
               {
                  $html = $this->curl_download($link);
                  if( strpos($html, '<div class="replies-to') !== FALSE )
                  {
                     /// cortamos hasta las respuestas
                     $html = substr($html, 0, strpos($html, '<div class="replies-to'));
                  }
                  
                  $urls = array();
                  if( preg_match_all('#https://pbs.twimg.com/media/([a-zA-Z0-9\-_]*).(\w*)#', $html, $urls) )
                  {
                     $this->filename = 'https://pbs.twimg.com/media/'.$urls[1][0].'.'.$urls[2][0];
                     $this->link = $this->filename;
                     $this->type = 'image';
                  }
                  else if( preg_match_all('#data-expanded-url="https://www.youtube.com/watch\?v=([a-zA-Z0-9\-_]*)#', $html, $urls) )
                  {
                     $this->filename = $urls[1][0];
                     $this->link = 'http://www.youtube.com/embed/'.$this->filename;
                     $this->type = 'youtube';
                  }
                  else if( preg_match_all('#https://pbs.twimg.com/profile_images/(\w*)/(\w*)_bigger.(\w*)#', $html, $urls) )
                  {
                     $this->filename = 'https://pbs.twimg.com/profile_images/'.$urls[1][0].'/'.$urls[2][0].'_bigger.'.$urls[3][0];
                     $this->link = $this->filename;
                     $this->type = 'image';
                  }
                  
                  $file = fopen($filename, 'w');
                  if($file)
                  {
                     fwrite($file, 'filename = "'.$this->filename."\";\n");
                     fwrite($file, 'link = "'.$this->link."\";\n");
                     fwrite($file, 'type = "'.$this->type."\";\n");
                     fclose($file);
                  }
                  
                  self::$downloads--;
               }
            }
            else
            {
               $aux2 = parse_ini_file($filename);
               $this->filename = $aux2['filename'];
               $this->link = $aux2['link'];
               $this->type = $aux2['type'];
            }
            
            break;
         }
      }
   }
   
   public function load_topics($topics)
   {
      foreach($topics as $tid)
      {
         $encontrado = FALSE;
         foreach($this->topic_list as $topic)
         {
            if($topic->get_id() == $tid)
            {
               if($topic->icon != '')
               {
                  $this->load($topic->icon);
               }
               
               $encontrado = TRUE;
               break;
            }
         }
         
         if( !$encontrado )
         {
            $topic = $this->topic->get($tid);
            if($topic)
            {
               $this->topic_list[] = $topic;
               
               if($topic->icon != '')
               {
                  $this->load($topic->icon);
               }
            }
         }
         
         if($this->type)
         {
            break;
         }
      }
   }
   
   public function min_height()
   {
      if($this->type == 'imgur')
         return 125;
      else if($this->type == 'youtube' OR $this->type == 'vimeo')
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
            $thumbnail = 'http://i.imgur.com/'.$parts2[0].'b.'.$parts2[1];
            break;
         
         case 'youtube':
            $thumbnail = 'http://img.youtube.com/vi/'.$this->filename.'/0.jpg';
            break;
         
         case 'vimeo':
            $thumbnail = FS_PATH.'tmp/vimeo/'.$this->filename;
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
      {
         $status = FALSE;
      }
      else if( mb_strlen($url) > 200 )
      {
         $status = FALSE;
      }
      else if( !in_array( mb_strtolower( mb_substr($url, -4) ), $extensions) )
      {
         $status = FALSE;
      }
      else if( mb_substr($url, -9) == 'blank.jpg' )
      {
         $status = FALSE;
      }
      
      return $status;
   }
   
   public function curl_download($url, $googlebot=TRUE, $timeout=FS_TIMEOUT)
   {
      $ch0 = curl_init($url);
      curl_setopt($ch0, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch0, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch0, CURLOPT_FOLLOWLOCATION, true);
      
      if($googlebot)
         curl_setopt($ch0, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      
      $html = curl_exec($ch0);
      curl_close($ch0);
      
      return $html;
   }
   
   public function curl_save($url, $filename, $googlebot=FALSE, $followlocation=FALSE)
   {
      $ch = curl_init($url);
      $fp = fopen($filename, 'wb');
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, FS_TIMEOUT);
      
      if($followlocation)
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
      
      if($googlebot)
         curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
      
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
   }
}
