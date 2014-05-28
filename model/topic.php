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
require_once 'model/story.php';
require_once 'model/topic_story.php';

class topic extends fs_model
{
   public $parent;
   public $name;
   public $title;
   public $description;
   public $keywords;
   public $importance;
   public $num_stories;
   public $num_children;
   public $icon;
   
   public function __construct($t=FALSE)
   {
      parent::__construct('topics');
      
      $this->parent = NULL;
      $this->name = NULL;
      $this->title = 'Desconocido';
      $this->description = 'Sin descripción';
      $this->keywords = '';
      $this->importance = 0;
      $this->num_stories = 0;
      $this->num_children = 0;
      $this->icon = '';
      
      if($t)
      {
         $this->id = $t['_id'];
         $this->parent = $t['parent'];
         $this->name = $t['name'];
         $this->title = $t['title'];
         $this->description = $t['description'];
         $this->keywords = $t['keywords'];
         $this->importance = $t['importance'];
         $this->num_stories = $t['num_stories'];
         $this->num_children = $t['num_children'];
         
         if( isset($t['icon']) )
            $this->icon = $t['icon'];
      }
   }
   
   public function install_indexes()
   {
      
   }
   
   public function description($width=250)
   {
      return $this->true_text_break($this->description, $width);
   }
   
   public function keywords()
   {
      $keys = array();
      
      foreach( explode(',', $this->keywords) as $key )
         $keys[] = trim($key);
      
      return $keys;
   }
   
   public function url()
   {
      if( is_null($this->id) )
         return FS_PATH.'topic_list';
      else if($this->name != '')
         return FS_PATH.'show_topic/'.$this->name;
      else
         return FS_PATH.'index.php?page=show_topic&id='.$this->id;
   }
   
   public function stories()
   {
      $topic_story = new topic_story();
      $story = new story();
      $stories = array();
      
      $best_tss = array();
      $current_ts = FALSE;
      foreach($topic_story->best4topic($this->get_id()) as $ts)
      {
         if(!$current_ts)
         {
            $current_ts = $ts;
         }
         else if( Date('W-Y', $ts->date) != Date('W-Y', $current_ts->date) )
         {
            $best_tss[] = $current_ts;
            $current_ts = $ts;
         }
         else if($ts->popularity > $current_ts->popularity)
         {
            $current_ts = $ts;
         }
      }
      
      if($current_ts)
         $best_tss[] = $current_ts;
      
      foreach($best_tss as $ts)
      {
         if( $ts->popularity > 0 )
         {
            $st0 = $story->get($ts->story_id);
            if($st0)
               $stories[] = $st0;
            else
               $ts->delete();
         }
      }
      
      return $stories;
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
   
   public function get($id)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         if( substr($id, -5) == '.html' )
         {
            $data = $this->collection->findone( array('name' => $id) );
            if($data)
               return new topic($data);
            else
            {
               /// buscamos la raiz
               $parts = explode('-', substr($id, 0, -5));
               $new_name = '';
               for($i = 0; $i < count($parts)-1; $i++)
                  $new_name .= $parts[$i].'-';
               
               $data = $this->collection->findone( array('name' => new MongoRegex('/'.$new_name.'/')) );
               if($data)
                  return new topic($data);
               else
                  return FALSE;
            }
         }
         else
         {
            $data = $this->collection->findone( array('_id' => new MongoId($id)) );
            if($data)
               return new topic($data);
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
   
   public function get_by_title($title)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      try
      {
         $data = $this->collection->findone( array('title' => $this->true_text_break($title)) );
         if($data)
            return new topic($data);
         else
            return FALSE;
      }
      catch(Exception $e)
      {
         $this->new_error($e);
         return FALSE;
      }
   }
   
   public function exists()
   {
      if( isset($this->id) )
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
         $data = $this->collection->findone( array('_id' => $this->id) );
         if($data)
            return TRUE;
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function save()
   {
      $this->parent = $this->var2str($this->parent);
      $this->title = $this->true_text_break($this->title);
      $this->description = $this->true_text_break($this->description, 999);
      
      $data = array(
          'parent' => $this->parent,
          'name' => $this->name,
          'title' => $this->title,
          'description' => $this->description,
          'keywords' => $this->keywords,
          'importance' => $this->importance,
          'num_stories' => $this->num_stories,
          'num_children' => $this->num_children,
          'icon' => $this->icon
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
   
   public function delete()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $this->collection->remove( array('_id' => $this->id) );
      
      $ts = new topic_story();
      $ts->delete4topic($this->id);
   }
   
   public function all()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      foreach($this->collection->find()->sort(array('importance'=>-1)) as $t)
         $tlist[] = new topic($t);
      
      return $tlist;
   }
   
   public function all_from($parent=NULL)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      
      $tlist = array();
      foreach($this->collection->find(array('parent'=>$this->var2str($parent)))->sort(array('name'=>1)) as $t)
         $tlist[] = new topic($t);
      
      return $tlist;
   }
   
   public function count_from($parent=NULL)
   {
      if( is_null($parent) )
      {
         return 0;
      }
      else
      {
         $this->add2history(__CLASS__.'::'.__FUNCTION__);
         return $this->collection->find( array('parent' => $this->var2str($parent)) )->count();
      }
   }
   
   public function search($query)
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      $stlist = array();
      $search = array( 'title' => new MongoRegex('/'.$query.'/iu') );
      foreach($this->collection->find($search)->sort(array('title'=>1))->limit(FS_MAX_STORIES) as $s)
      {
         /// parece ser que las expresiones regulares no funciona muy bien en mongodb
         if( preg_match('/\b'.$query.'\b/iu', $s['title']) )
            $stlist[] = new topic($s);
      }
      return $stlist;
   }
   
   public function cron_job()
   {
      $story = new story();
      $topic_story = new topic_story();
      $max_importance = 0;
      
      echo "\nProcesamos los temas...";
      foreach($this->all() as $topic)
      {
         echo '.';
         
         $story_ids = array();
         $stories = array();
         
         /// usamos las keywords para buscar artículos relacionados
         foreach($topic->keywords() as $key)
         {
            if( mt_rand(0, 2) != 2 )
               $relateds = $story->search($key, TRUE);
            else
               $relateds = $story->search($key, FALSE);
            
            for($i = 0; $i < count($relateds); $i++)
            {
               if( !in_array($topic->get_id(), $relateds[$i]->topics) )
               {
                  if($relateds[$i]->keywords == '')
                     $relateds[$i]->keywords = $key;
                  else if( strpos($relateds[$i]->keywords, $key) === FALSE )
                     $relateds[$i]->keywords .= ', '.$key;
                  
                  $relateds[$i]->topics[] = $topic->get_id();
                  $relateds[$i]->save();
                  
                  $ts0 = new topic_story();
                  $ts0->topic_id = $topic->get_id();
                  $ts0->story_id = $relateds[$i]->get_id();
                  $ts0->date = $relateds[$i]->date;
                  $ts0->popularity = $relateds[$i]->max_popularity();
                  $ts0->save();
               }
               else
               {
                  /// ¿Actualizamos la popularidad?
                  $ts0 = $topic_story->get2($topic->get_id(), $relateds[$i]->get_id());
                  if($ts0)
                  {
                     if( $ts0->popularity != $relateds[$i]->max_popularity() )
                     {
                        $ts0->popularity = $relateds[$i]->max_popularity();
                        $ts0->save();
                     }
                  }
                  else
                  {
                     $relateds[$i]->topics = array();
                     $relateds[$i]->keywords = '';
                     $relateds[$i]->save();
                  }
               }
               
               if( !in_array($relateds[$i]->get_id(), $story_ids) )
               {
                  $story_ids[] = $relateds[$i]->get_id();
                  $stories[] = $relateds[$i];
               }
            }
         }
         
         if( count($stories) > 0 )
         {
            /// ordenamos los artículos por popularidad
            usort($stories, function($a, $b) {
               if($a->popularity == $b->popularity)
                  return 0;
               else if($a->popularity > $b->popularity)
                  return -1;
               else
                  return 1;
            } );
            
            /// interrelacionamos los artículos
            for($i = 0; $i < count($stories); $i++)
            {
               if( !isset($stories[$i]->related_id) )
               {
                  for($j = 0; $j < count($stories); $j++)
                  {
                     if( $stories[$j]->date < $stories[$i]->date AND $stories[$j]->native_lang AND !$stories[$j]->penalize AND !$stories[$j]->parody )
                     {
                        $stories[$i]->related_id = $stories[$j]->get_id();
                        $stories[$i]->save();
                        break;
                     }
                  }
               }
            }
         }
         
         /*
          * Ahora vamos a comprobar la importancia del tema.
          * Los "hijos de un tema" o "subtemas" tienen más importancia que su padre,
          * así podemos destacar estos subtemas. Pero todos los temas o subtemas sin hijos
          * tienen la máxima importancia.
          */
         if($topic->importance > $max_importance)
            $max_importance = $topic->importance;
         
         $topic->num_children = $topic->count_from( $topic->get_id() );
         if($topic->num_children == 0)
         {
            $topic->importance = $max_importance;
         }
         else if( is_null($topic->parent) )
         {
            $topic->importance = 0;
         }
         else
         {
            $parent = $this->get($topic->parent);
            if($parent)
            {
               $topic->importance = $parent->importance + 1;
            }
            else
            {
               $topic->parent = NULL;
               
               if($topic->num_children == 0)
                  $topic->importance = $max_importance;
               else
                  $topic->importance = 0;
            }
         }
         
         $topic->num_stories = $topic_story->count4topic( $topic->get_id() );
         $topic->save();
      }
   }
}
