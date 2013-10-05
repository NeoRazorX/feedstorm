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
require_once 'model/feed_story.php';
require_once 'model/story_media.php';
require_once 'model/story_edition.php';

class story extends fs_model
{
   /*
    * Google prefiere urls del tipo:
    * http://www.locierto.es/story/titulo-de-la-noticia.html
    * así que $name = 'titulo-de-la-noticia.html'
    */
   public $name;
   public $date;
   public $title;
   public $description;
   public $link;
   public $media_id;
   public $clics;
   public $tweets;
   public $meneos;
   public $likes;
   public $plusones;
   public $popularity;
   public $native_lang; /// ¿La historia está en español?
   
   private static $mi0;
   private $media_items;
   public $media_item;
   
   public function __construct($item=FALSE)
   {
      parent::__construct('stories');
      if($item)
      {
         $this->id = $item['_id'];
         
         if( isset($item['name']) )
            $this->name = $item['name'];
         else
            $this->name = '';
         
         $this->date = $item['date'];
         $this->title = $item['title'];
         $this->description = $item['description'];
         $this->link = $item['link'];
         $this->media_id = $item['media_id'];
         $this->clics = $item['clics'];
         $this->tweets = $item['tweets'];
         
         if( isset($item['meneos']) )
            $this->meneos = $item['meneos'];
         else
            $this->meneos = 0;
         
         if( isset($item['likes']) )
            $this->likes = $item['likes'];
         else
            $this->likes = 0;
         
         if( isset($item['plusones']) )
            $this->plusones = $item['plusones'];
         else
            $this->plusones = 0;
         
         $this->popularity = $item['popularity'];
         
         if( isset($item['native_lang']) )
            $this->native_lang = $item['native_lang'];
         else
            $this->native_lang = TRUE;
         
         if( is_null($this->media_id) )
            $this->media_item = NULL;
         else
         {
            if( !isset(self::$mi0) )
               self::$mi0 = new media_item();
            
            $this->media_item = self::$mi0->get($this->media_id);
         }
      }
      else
      {
         $this->id = NULL;
         $this->name = '';
         $this->date = time();
         $this->title = NULL;
         $this->description = NULL;
         $this->link = NULL;
         $this->media_id = NULL;
         $this->clics = 0;
         $this->tweets = 0;
         $this->meneos = 0;
         $this->likes = 0;
         $this->plusones = 0;
         $this->popularity = 0;
         $this->native_lang = TRUE;
         
         $this->media_item = NULL;
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('popularity' => -1) );
      $this->collection->ensureIndex( array('date' => -1) );
      $this->collection->ensureIndex('link');
      $this->collection->ensureIndex('name');
   }
   
   public function url($w3c = TRUE)
   {
      if( is_null($this->id) )
         return 'index.php';
      else if(FS_MOD_REWRITE AND $this->name != '')
         return 'show_story/'.$this->name;
      else if($w3c)
         return 'index.php?page=show_story&amp;id='.$this->id;
      else
         return 'index.php?page=show_story&id='.$this->id;
   }
   
   public function link()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return $this->link;
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return 'index.php';
      else
         return 'index.php?page=edit_story&id='.$this->id;
   }
   
   public function show_date($iso=FALSE)
   {
      if($iso)
         return Date('c', $this->date);
      else
         return Date('Y-m-d H:m', $this->date);
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
   
   public function popularity()
   {
      return number_format($this->popularity, 2, ',', ' ');
   }
   
   public function feed_links()
   {
      $feed_story = new feed_story();
      return $feed_story->all4story( $this->id );
   }
   
   public function media_items()
   {
      if( !isset($this->media_items) )
      {
         $this->media_items = array();
         $story_media = new story_media();
         foreach($story_media->all4story($this->id) as $sm)
            $this->media_items[] = $sm->media_item();
      }
      return $this->media_items;
   }
   
   public function editions()
   {
      $edition = new story_edition();
      return $edition->all4story( $this->id );
   }
   
   public function description($width=300)
   {
      return $this->true_text_break($this->description, $width);
   }
   
   private function calculate_popularity()
   {
      $tclics = $this->clics;
      
      if($this->native_lang)
      {
         if($this->tweets > 10000)
            $tclics += min( array($this->tweets, 20 + 2*$this->clics) );
         else if($this->tweets > 1000)
            $tclics += min( array($this->tweets, 10 + 2*$this->clics) );
         else
            $tclics += min( array($this->tweets, 1 + $this->clics) );
         
         if($this->likes > 10000)
            $tclics += min( array($this->likes, 20 + 2*$this->clics) );
         else if($this->likes > 1000)
            $tclics += min( array($this->likes, 10 + 2*$this->clics) );
         else
            $tclics += min( array($this->likes, 1 + $this->clics) );
         
         if($this->meneos > 1000)
            $tclics += min( array($this->meneos, 20 + 2*$this->clics) );
         else if($this->meneos > 200)
            $tclics += min( array($this->meneos, 10 + 2*$this->clics) );
         else
            $tclics += min( array($this->meneos, 1 + $this->clics) );
         
         $tclics += min( array($this->plusones, 1 + $this->clics) );
      }
      
      $dias = 1 + intval( (time() - $this->date) / 86400 );
      $semanas = pow(2, intval($dias/7));
      if($tclics > 0)
         $this->popularity = $tclics / ($dias * $semanas);
      else
         $this->popularity = 0;
   }
   
   public function readed()
   {
      return isset($_COOKIE['s_'.$this->id]);
   }
   
   public function read()
   {
      if( !isset($_COOKIE['s_'.$this->id]) )
      {
         /*
          * Eliminamos cookies para no sobrepasar el límite
          */
         if( count($_COOKIE) > 50 )
         {
            $num = 10;
            foreach($_COOKIE as $k => $value)
            {
               if($num <= 0)
                  break;
               else if( substr($k, 0, 2) == 's_' )
               {
                  setcookie($k, $value, time()-86400, FS_PATH);
                  $num--;
               }
            }
         }
         
         setcookie('s_'.$this->id, $this->id, time()+86400, FS_PATH);
      }
   }
   
   public function tweet_count()
   {
      $json_string = $this->curl_download('http://urls.api.twitter.com/1/urls/count.json?url='.
              rawurlencode($this->link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      $this->tweets = isset($json['count']) ? intval($json['count']) : 0;
   }
   
   public function facebook_count()
   {
      $json_string = $this->curl_download('http://api.facebook.com/restserver.php?method=links.getStats&format=json&urls='.
              rawurlencode($this->link), FALSE);
      $json = json_decode($json_string, TRUE);
      
      $this->likes = isset($json[0]['total_count']) ? intval($json[0]['total_count']) : 0;
   }
   
   public function meneame_count()
   {
      $string = $this->curl_download('http://www.meneame.net/api/url.php?url='.rawurlencode($this->link), FALSE);
      $vars = explode( ' ', $string);
      
      if( count($vars) == 4 )
         $this->meneos = intval( $vars[2] );
      else
         $this->meneos = 0;
   }
   
   public function plusones_count()
   {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc");
      curl_setopt($curl, CURLOPT_POST, TRUE);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'.
              rawurldecode($this->link).'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
      curl_setopt($curl, CURLOPT_TIMEOUT, FS_TIMEOUT);
      $curl_results = curl_exec ($curl);
      curl_close ($curl);
      $json = json_decode($curl_results, TRUE);
      $this->plusones = isset($json[0]['result']['metadata']['globalCounts']['count'])?intval( $json[0]['result']['metadata']['globalCounts']['count'] ):0;
   }
   
   public function random_count($meneame = TRUE)
   {
      if($this->native_lang)
      {
         switch( mt_rand(0, 3) )
         {
            case 0:
               $this->tweet_count();
               break;
            
            case 1:
               $this->facebook_count();
               break;
            
            case 2:
               if($meneame)
                  $this->meneame_count();
               else if($this->likes == 0)
                  $this->facebook_count();
               else if($this->tweets == 0)
                  $this->tweet_count();
               else
                  $this->plusones_count();
               break;
               
            default:
               $this->plusones_count();
               break;
         }
      }
   }
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         if( substr($id, -5) == '.html' )
            $data = $this->collection->findone( array('name' => $id) );
         else
            $data = $this->collection->findone( array('_id' => new MongoId($id)) );
         
         if($data)
            return new story($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
   }
   
   public function get_by_link($url)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('link' => $url) );
      if($data)
         return new story($data);
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
         $data = $this->collection->findone( array('_id' => $this->id) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
   }
   
   public function save()
   {
      $this->title = $this->true_text_break($this->title, 149, 18);
      $this->description = $this->true_text_break($this->description, 499, 25);
      $this->media_id = $this->var2str($this->media_id);
      $this->calculate_popularity();
      
      $data = array(
          'name' => $this->name,
          'date' => $this->date,
          'title' => $this->title,
          'description' => $this->description,
          'link' => $this->link,
          'media_id' => $this->media_id,
          'clics' => $this->clics,
          'tweets' => $this->tweets,
          'meneos' => $this->meneos,
          'likes' => $this->likes,
          'plusones' => $this->plusones,
          'popularity' => $this->popularity,
          'native_lang' => $this->native_lang
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
         $data['name'] = $this->new_name();
         $this->collection->insert($data);
         $this->id = $data['_id'];
      }
   }
   
   private function new_name()
   {
      $this->name = strtolower( $this->true_text_break($this->title, 85) );
      $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
          '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
          '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
          '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
          '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
          '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y', '/ñ/' => 'ny',
          '/&quot;/' => '-'
      );
      $this->name = preg_replace(array_keys($changes), $changes, $this->name);
      $this->name = preg_replace('/[^a-z0-9]/i', '-', $this->name);
      $this->name = preg_replace('/-+/', '-', $this->name);
      
      if( substr($this->name, 0, 1) == '-' )
         $this->name = substr($this->name, 1);
      
      if( substr($this->name, -1) == '-' )
         $this->name = substr($this->name, 0, -1);
      
      $this->name .= '-'.mt_rand(0, 999).'.html';
      
      return $this->name;
   }
   
   public function delete()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1)) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function all_from_array($selection)
   {
      $stories = array();
      if($selection)
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
         foreach($this->collection->find(array('_id'=>array('$in'=>$selection))) as $s)
            $stories[] = new story($s);
      }
      return $stories;
   }
   
   public function last_stories()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('date'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function popular_stories($num = FS_MAX_STORIES)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('popularity'=>-1))->limit($num) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function random_stories($limit=FS_MAX_STORIES)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      
      if( mt_rand(0, 1) == 0 )
         $order = array('date' => -1);
      else
         $order = array('popularity' => -1);
      
      $offset = mt_rand(0, max( array(0, $this->count()-$limit) ) );
      
      foreach($this->collection->find()->sort($order)->skip($offset)->limit($limit) as $a)
         $stlist[] = new story($a);
      
      return $stlist;
   }
   
   public function search($query)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      $search = array( 'title' => new MongoRegex('/'.$query.'/i') );
      foreach($this->collection->find($search)->sort(array('popularity'=>-1))->limit(FS_MAX_STORIES) as $s)
         $stlist[] = new story($s);
      return $stlist;
   }
   
   public function add_media_items($item=FALSE, $search_link=TRUE)
   {
      $num_downloads = 0;
      $width = 0;
      $height = 0;
      $first_forced = FALSE;
      $media_item = new media_item();
      foreach($media_item->find_media($item, $this->link, $search_link) as $mi)
      {
         $story_media = new story_media();
         $story_media->story_id = $this->id;
         
         if( !$media_item->get_by_url($mi->url) )
         {
            if( $mi->download() )
            {
               echo 'D';
               $num_downloads++;
               
               $mi->save();
               $story_media->media_id = $mi->get_id();
               $story_media->save();
               
               if($this->link == $mi->url)
               {
                  echo 'S';
                  
                  $this->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  break;
               }
               else if($num_downloads == 1)
               {
                  echo 'S';
                  
                  $this->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
                  
                  if($mi->ratio() < 1 OR $mi->ratio() > 2)
                     $first_forced = TRUE;
               }
               else if($num_downloads > FS_MAX_DOWNLOADS)
               {
                  break;
               }
               else if($first_forced AND $mi->ratio() >= 1 AND $mi->ratio() <= 2)
               {
                  echo 'S';
                  
                  $this->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
               }
               else if($mi->ratio() >= 1 AND $mi->ratio() <= 2 AND $mi->width > $width AND $mi->height > $height)
               {
                  echo 'S';
                  
                  $this->media_id = $mi->get_id();
                  $width = $mi->original_width;
                  $height = $mi->original_height;
               }
            }
            else
               echo 'E';
         }
         else
            echo 'I';
      }
      
      echo "F\n";
      $this->save();
   }
   
   public function cron_job()
   {
      if( mt_rand(0, 2) == 0 )
      {
         echo "\nEliminamos historias antiguas...";
         /// eliminamos los registros más antiguos que FS_MAX_AGE y con menos de 100 clics
         $this->collection->remove(
            array('date' => array('$lt' => time()-FS_MAX_AGE), 'clics' => array('$lt' => 100))
         );
      }
      
      echo "\nActualizamos las historias populares...\n";
      $i = 0;
      foreach($this->popular_stories(FS_MAX_STORIES * 4) as $ps)
      {
         /// obtenemos las menciones de la historia
         $ps->random_count();
         
         if($i < FS_MAX_STORIES)
         {
            /// si no hay imagen, buscamos más
            if( !$ps->media_item AND mt_rand(0, 2) == 0 )
               $ps->add_media_items();
            else
               echo '.';
         }
         else
            echo '.';
         
         $ps->save();
         $i++;
      }
   }
   
   public function full_redownload()
   {
      echo "\nEliminamos historias antiguas...";
      /// eliminamos los registros más antiguos que FS_MAX_AGE y con menos de 100 clics
      $this->collection->remove(
         array('date' => array('$lt' => time()-FS_MAX_AGE), 'clics' => array('$lt' => 100))
      );
      
      echo "\nComprobamos TODAS las historias...";
      foreach($this->all() as $s)
      {
         if($s->media_item)
         {
            $s->media_item->redownload();
            echo 'D';
         }
         else
            echo '.';
      }
   }
}

?>