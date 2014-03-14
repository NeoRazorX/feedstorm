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

require_once 'base/fs_model.php';
require_once 'model/comment.php';
require_once 'model/feed_story.php';
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
   public $published; /// sólo para las publicadas
   public $title;
   public $description;
   public $link;
   public $clics;
   public $tweets;
   public $meneos;
   public $likes;
   public $plusones;
   public $popularity;
   public $native_lang; /// ¿El artículo está en español?
   public $parody;
   public $penalize;
   public $featured;
   public $keywords;
   public $related_id;
   public $edition_id;
   public $num_editions;
   public $num_feeds;
   public $num_comments;
   
   public function __construct($item=FALSE)
   {
      parent::__construct('stories');
      if($item)
      {
         $this->id = $item['_id'];
         $this->name = $item['name'];
         $this->date = $item['date'];
         $this->published = $item['published'];
         $this->title = $item['title'];
         $this->description = $item['description'];
         $this->link = $item['link'];
         $this->clics = $item['clics'];
         $this->tweets = $item['tweets'];
         $this->meneos = $item['meneos'];
         $this->likes = $item['likes'];
         $this->plusones = $item['plusones'];
         $this->popularity = $item['popularity'];
         $this->native_lang = $item['native_lang'];
         $this->parody = $item['parody'];
         $this->penalize = $item['penalize'];
         $this->featured = $item['featured'];
         
         $this->keywords = '';
         foreach( explode(',', $item['keywords']) as $kw )
            $this->add_keyword($kw);
         
         $this->related_id = $item['related_id'];
         $this->edition_id = $item['edition_id'];
         $this->num_editions = $item['num_editions'];
         $this->num_feeds = $item['num_feeds'];
         $this->num_comments = $item['num_comments'];
      }
      else
      {
         $this->id = NULL;
         $this->name = '';
         $this->date = time();
         $this->published = NULL;
         $this->title = NULL;
         $this->description = NULL;
         $this->link = NULL;
         $this->clics = 0;
         $this->tweets = 0;
         $this->meneos = 0;
         $this->likes = 0;
         $this->plusones = 0;
         $this->popularity = 0;
         $this->native_lang = TRUE;
         $this->parody = FALSE;
         $this->penalize = FALSE;
         $this->featured = FALSE;
         $this->keywords = '';
         $this->related_id = NULL;
         $this->edition_id = NULL;
         $this->num_editions = 0;
         $this->num_feeds = 0;
         $this->num_comments = 0;
      }
   }
   
   public function install_indexes()
   {
      $this->collection->ensureIndex( array('popularity' => -1) );
      $this->collection->ensureIndex( array('date' => -1) );
      $this->collection->ensureIndex( array('published' => -1) );
      $this->collection->ensureIndex('link');
      $this->collection->ensureIndex('name');
   }
   
   public function url($w3c = TRUE)
   {
      if( is_null($this->id) )
         return FS_PATH.'index.php';
      else if($this->name != '')
         return FS_PATH.'show_story/'.$this->name;
      else if($w3c)
         return FS_PATH.'index.php?page=show_story&amp;id='.$this->id;
      else
         return FS_PATH.'index.php?page=show_story&id='.$this->id;
   }
   
   public function link()
   {
      if( is_null($this->id) )
         return $this->url();
      else
         return $this->link;
   }
   
   public function edit_url()
   {
      if( is_null($this->id) )
         return FS_PATH.'index.php';
      else
         return FS_PATH.'edit_story/'.$this->id;
   }
   
   public function show_date($iso=FALSE)
   {
      if($iso)
         return Date('c', $this->date);
      else
         return Date('Y-m-d H:m', $this->date);
   }
   
   public function timesince($published=FALSE)
   {
      if($published)
         return $this->time2timesince($this->published);
      else
         return $this->time2timesince($this->date);
   }
   
   public function popularity()
   {
      return number_format($this->popularity, 2, ',', ' ');
   }
   
   public function feed_links()
   {
      $feed_story = new feed_story();
      $feed_links = $feed_story->all4story($this->id);
      
      if($this->num_feeds != count($feed_links))
      {
         $this->num_feeds = count($feed_links);
         $this->save();
      }
      
      return $feed_links;
   }
   
   public function editions()
   {
      $edition = new story_edition();
      $editions = $edition->all4story($this->id);
      
      if($this->num_editions != count($editions))
      {
         $this->num_editions = count($editions);
         $this->save();
      }
      
      return $editions;
   }
   
   public function comments()
   {
      $comment = new comment();
      $comments = array_reverse($comment->all4thread($this->id));
      
      if($this->num_comments != count($comments))
      {
         $this->num_comments = count($comments);
         $this->save();
      }
      
      return $comments;
   }
   
   public function related_story()
   {
      if( isset($this->related_id) )
         return $this->get($this->related_id);
      else
         return FALSE;
   }
   
   public function pre_related_story()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $data = $this->collection->findone( array('related_id' => $this->var2str($this->id)) );
      if($data)
         return new story($data);
      else
         return FALSE;
   }
   
   public function add_keyword($key)
   {
      $nkey = trim($key);
      
      if($nkey != '')
      {
         if($this->keywords == '')
            $this->keywords = $nkey;
         else if( strstr($this->keywords, $nkey) === FALSE )
            $this->keywords .= ', '.$nkey;
      }
   }
   
   public function description($width=250)
   {
      return $this->true_text_break($this->description, $width);
   }
   
   private function calculate_popularity()
   {
      $tclics = $this->clics;
      
      if($this->native_lang AND !$this->penalize AND mb_strlen($this->description) > 0)
      {
         $tclics += $this->num_editions + $this->num_feeds + $this->num_comments + count( explode(',', $this->keywords) );
         
         if($this->related_id)
            $tclics++;
         
         if( mb_strlen($this->description) > 250 )
            $tclics++;
         else if( mb_strlen($this->description) < 150 )
            $tclics -= 2;
         
         if($this->tweets > 1000)
            $tclics += min( array($this->tweets, 10 + $this->clics) );
         else
            $tclics += min( array($this->tweets, 1 + $this->clics) );
         
         if($this->likes > 1000)
            $tclics += min( array($this->likes, 10 + $this->clics) );
         else
            $tclics += min( array($this->likes, 1 + $this->clics) );
         
         if($this->meneos > 150)
            $tclics += min( array($this->meneos, 10 + $this->clics) );
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
      if( isset($this->link) AND $this->native_lang AND !$this->penalize )
      {
         switch( mt_rand(0, 4) )
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
            
            case 3:
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
         {
            $data = $this->collection->findone( array('name' => $id) );
            if($data)
               return new story($data);
            else
            {
               /// buscamos la raiz
               $parts = explode('-', substr($id, 0, -5));
               $new_name = '';
               for($i = 0; $i < count($parts)-1; $i++)
                  $new_name .= $parts[$i].'-';
               
               $data = $this->collection->findone( array('name' => new MongoRegex('/'.$new_name.'/')) );
               if($data)
                  return new story($data);
               else
                  return FALSE;
            }
         }
         else
         {
            $data = $this->collection->findone( array('_id' => new MongoId($id)) );
            if($data)
               return new story($data);
            else
               return FALSE;
         }
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
      $this->description = $this->true_text_break($this->description, 999, 25);
      $this->related_id = $this->var2str($this->related_id);
      $this->edition_id = $this->var2str($this->edition_id);
      $this->calculate_popularity();
      
      $data = array(
          'name' => $this->name,
          'date' => $this->date,
          'published' => $this->published,
          'title' => $this->title,
          'description' => $this->description,
          'link' => $this->link,
          'clics' => $this->clics,
          'tweets' => $this->tweets,
          'meneos' => $this->meneos,
          'likes' => $this->likes,
          'plusones' => $this->plusones,
          'popularity' => $this->popularity,
          'native_lang' => $this->native_lang,
          'parody' => $this->parody,
          'penalize' => $this->penalize,
          'featured' => $this->featured,
          'keywords' => $this->keywords,
          'related_id' => $this->related_id,
          'edition_id' => $this->edition_id,
          'num_editions' => $this->num_editions,
          'num_feeds' => $this->num_feeds,
          'num_comments' => $this->num_comments
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
         
         if($this->name == '')
            $data['name'] = $this->new_name();
         
         $this->collection->insert($data);
         $this->id = $data['_id'];
      }
   }
   
   public function new_name()
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
      
      foreach($this->editions() as $edi)
         $edi->delete();
      
      foreach($this->feed_links() as $fs)
         $fs->delete();
      
      foreach($this->comments() as $com)
         $com->delete();
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
   
   public function published_stories()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      foreach($this->collection->find()->sort(array('published'=>-1))->limit(FS_MAX_STORIES) as $s)
      {
         if( isset($s['published']) )
            $stlist[] = new story($s);
      }
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
      $search = array( 'title' => new MongoRegex('/'.$query.'/iu') );
      foreach($this->collection->find($search)->sort(array('popularity'=>-1))->limit(FS_MAX_STORIES) as $s)
      {
         /// parece ser que las expresiones regulares no funciona muy bien en mongodb
         if( preg_match('/\b'.$query.'\b/iu', $s['title']) )
            $stlist[] = new story($s);
      }
      return $stlist;
   }
   
   public function cron_job()
   {
      echo "\nActualizamos los artículos populares y publicamos...";
      $j = 0;
      $keywords = array();
      $keywords2 = array();
      $publish = 1; /// máximo de artículos publicados cada vez
      foreach($this->popular_stories(FS_MAX_STORIES * 4) as $ps)
      {
         /// obtenemos las menciones del artículo
         $ps->random_count();
         
         /// extraemos las keywords
         if($ps->keywords != '')
         {
            $aux = explode(',', $ps->keywords);
            $keyword = trim($aux[0]);
            if( !in_array($keyword, $keywords) )
               $keywords[] = $keyword;
            
            for($i = 1; $i < count($aux); $i++)
            {
               $keyword = trim($aux[$i]);
               if( !in_array($keyword, $keywords2) )
                  $keywords2[] = $keyword;
            }
         }
         
         /// si la noticia alcanza el TOP FS_MAX_STORIES, entonces la publicamos
         if($j < FS_MAX_STORIES AND is_null($ps->published) AND $publish > 0 AND $ps->popularity > 1)
         {
            $ps->published = time();
            $publish--;
         }
         
         $ps->save();
         $j++;
         
         echo '.';
      }
      
      /// necesitamos más keywords
      foreach($this->random_stories(FS_MAX_STORIES * 4) as $rs)
      {
         if($rs->keywords != '')
         {
            $aux = explode(',', $rs->keywords);
            $keyword = trim($aux[0]);
            if( !in_array($keyword, $keywords) )
               $keywords[] = $keyword;
            
            for($i = 1; $i < count($aux); $i++)
            {
               $keyword = trim($aux[$i]);
               if( !in_array($keyword, $keywords2) )
                  $keywords2[] = $keyword;
            }
         }
      }
      
      echo "\nInterconectamos los artículos...\n";
      foreach( array_merge($keywords, $keywords2) as $keyword )
      {
         if($keyword != '')
         {
            $relateds = $this->search($keyword);
            for($i = 0; $i < count($relateds); $i++)
            {
               $relateds[$i]->add_keyword($keyword);
               
               for($j = 0; $j < count($relateds); $j++)
               {
                  if( !isset($relateds[$i]->related_id) AND $relateds[$j]->date < $relateds[$i]->date AND $relateds[$j]->native_lang AND !$relateds[$j]->penalize AND !$relateds[$j]->parody )
                  {
                     $relateds[$i]->related_id = $relateds[$j]->get_id();
                     break;
                  }
               }
               
               $relateds[$i]->save();
            }
         }
         
         echo '.';
      }
   }
}

?>