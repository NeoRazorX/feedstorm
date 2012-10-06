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

class tweet extends fs_model
{
   public $author;
   public $author_url;
   public $author_img;
   public $content;
   public $date;
   
   public function __construct($entry=FALSE)
   {
      parent::__construct();
      if( $entry )
      {
         $this->author = (string)$entry->author->name;
         $this->author_url = (string)$entry->author->uri;
         
         try
         {
            $this->author_img = (string)$entry->link[1]['href'];
         }
         catch(Exception $e)
         {
            $this->author_img = 'https://si0.twimg.com/sticky/default_profile_images/default_profile_2_normal.png';
         }
         
         $this->content = html_entity_decode( (string)$entry->content );
         $this->date = (string)$entry->published;
      }
      else
      {
         $this->author = 'nadie';
         $this->author_url = 'http://www.twitter.com';
         $this->author_img = 'https://si0.twimg.com/sticky/default_profile_images/default_profile_2_normal.png';
         $this->content = ':-(';
         $this->date = Date('Y-m-d H:m:i');
      }
   }
   
   public function all()
   {
      $tweets = $this->cache->get_array('tweets');
      if( !$tweets )
      {
         $ch = curl_init('http://search.twitter.com/search.atom?result_type=recent&rpp=50&q=%23'.FS_NAME);
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
         $html = curl_exec($ch);
         curl_close($ch);
         $xml = simplexml_load_string( $html );
         if( $xml )
         {
            if( $xml->entry )
            {
               foreach($xml->entry as $entry)
                  $tweets[] = new tweet($entry);
               
               if( $tweets )
                  $this->cache->set('tweets', $tweets, 600);
            }
            else
               $this->new_error('Estructura del feed de twitter irreconocible.');
         }
         else
            $this->new_error('No se encuentra el feed de twitter.');
      }
      return $tweets;
   }
   
   public function all_from_url($url)
   {
      $tweets = $this->cache->get_array('tweets_from_'.$url);
      if( !$tweets )
      {
         $ch = curl_init('http://search.twitter.com/search.atom?rpp=50&q='.$url);
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
         $html = curl_exec($ch);
         curl_close($ch);
         $xml = simplexml_load_string( $html );
         if( $xml )
         {
            if( $xml->entry )
            {
               foreach($xml->entry as $entry)
                  $tweets[] = new tweet($entry);
               
               if( $tweets )
                  $this->cache->set('tweets_from_'.$url, $tweets, 1200);
            }
            else
               $this->new_error('Estructura del feed de twitter irreconocible.');
         }
         else
            $this->new_error('No se encuentra el feed de twitter.');
      }
      return $tweets;
   }
}

?>
