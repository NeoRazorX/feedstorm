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

require_once 'model/story.php';
require_once 'model/story_edition.php';
require_once 'model/story_visit.php';

class edit_story extends fs_controller
{
   public $story;
   public $story_edition;
   public $story_visit;
   
   public function __construct()
   {
      parent::__construct('edit_story', 'Editar artículo');
      
      $this->story_edition = new story_edition();
      $this->story_visit = new story_visit();
      
      if( isset($_GET['id']) )
      {
         $story = new story();
         $this->story = $story->get($_GET['id']);
      }
      else
         $this->story = FALSE;
      
      
      if($this->story AND isset($_POST['delete']) AND $this->visitor->admin)
      {
         $this->story->delete();
         $this->story = FALSE;
         $this->new_message('Artículo eliminado correctamente.');
      }
      else if($this->story)
      {
         if( $this->visitor->human() )
         {
            $new_edition = FALSE;
            $fake_edition = TRUE;
            
            $se0 = $this->story_edition->get_by_params($this->story->get_id(), $this->visitor->get_id());
            if( $se0 )
               $this->story_edition = $se0;
            else
            {
               $this->story_edition->nick = $this->visitor->nick;
               $this->story_edition->description = $this->story->description;
               $this->story_edition->points = $this->visitor->points;
               $this->story_edition->story_id = $this->story->get_id();
               $this->story_edition->title = $this->story->title;
               $this->story_edition->visitor_id = $this->visitor->get_id();
               $new_edition = TRUE;
            }
            
            if( isset($_POST['title']) AND isset($_POST['description']) AND isset($_POST['human']) )
            {
               if($_POST['title'] != $this->story_edition->title AND $_POST['description'] != $this->story_edition->description)
                  $fake_edition = FALSE;
               
               $this->story_edition->title = $_POST['title'];
               $this->story_edition->description = $_POST['description'];
               
               /// otra comprobación más para evitar el spam
               if( strstr($_POST['description'], '<a href=') )
                  $this->new_error_msg('De eso nada, aquí no se permite HTML.');
               else if( $_POST['human'] != '' AND !$this->visitor->admin )
                  $this->new_error_msg('Tienes que borrar el número para demostrar que eres humano, y si no eres
                     humano no puedes editar artículos. Y si, ya sé que esto es nazismo puro,
                     pero es una forma sencilla de atajar el SPAM.');
               else
               {
                  $this->story_edition->save();
                  $this->new_message('Artículo editado correctamente.');
                  
                  if($this->visitor->admin)
                  {
                     $this->story->edition_id = $this->story_edition->get_id();
                     $this->story->title = $this->story_edition->title;
                     $this->story->description = $this->story_edition->description;
                     
                     $nkeywords = mb_strtolower( trim($_POST['keywords']), 'utf8' );
                     if($nkeywords != $this->story->keywords)
                     {
                        $this->story->keywords = $nkeywords;
                        if($this->story->keywords != '')
                        {
                           /// añadimos las keyword a todas las noticias de la búsqueda
                           $kwlist = explode(',', $this->story->keywords);
                           $relateds = $this->story->search($kwlist[0]);
                           for($i = 0; $i < count($relateds); $i++)
                           {
                              if( $relateds[$i]->get_id() != $this->story->get_id() )
                              {
                                 foreach($kwlist as $kw)
                                 {
                                    if( preg_match('/\b'.$kw.'\b/iu', $relateds[$i]->title) )
                                       $relateds[$i]->add_keyword($kw);
                                 }
                                 
                                 $relateds[$i]->save();
                              }
                           }
                        }
                     }
                     
                     $this->story->native_lang = isset($_POST['native_lang']);
                     $this->story->parody = isset($_POST['parody']);
                     
                     if( isset($_POST['featured']) )
                     {
                        $this->story->featured = TRUE;
                        $this->story->penalize = FALSE;
                        $this->story->published = time();
                     }
                     else if( isset($_POST['penalize']) )
                     {
                        $this->story->penalize = TRUE;
                        $this->story->featured = FALSE;
                        $this->story->published = NULL;
                     }
                     
                     if($new_edition)
                        $this->story->num_editions++;
                     
                     $this->story->save();
                  }
                  else if( !$this->story->native_lang AND !$fake_edition ) /// ¿La noticia está en otro idioma?
                  {
                     $this->story->edition_id = $this->story_edition->get_id();
                     $this->story->title = $this->story_edition->title;
                     $this->story->description = $this->story_edition->description;
                     $this->story->native_lang = TRUE;
                     
                     if($new_edition)
                        $this->story->num_editions++;
                     
                     $this->story->save();
                  }
                  else
                     $this->set_best_edition();
                  
                  /// actualizamos al visitante
                  if($new_edition)
                  {
                     $this->visitor->num_editions++;
                     $this->visitor->need_save = TRUE;
                     $this->visitor->save();
                  }
               }
            }
            
            $sv0 = $this->story_visit->get_by_params($this->story->get_id(), $_SERVER['REMOTE_ADDR']);
            if( $sv0 )
            {
               $sv0->edition_id = $this->story_edition->get_id();
               $sv0->save();
            }
            else
            {
               $this->story_visit->story_id = $this->story->get_id();
               $this->story_visit->edition_id = $this->story_edition->get_id();
               $this->story_visit->save();
               $this->story->clics++;
               $this->story->save();
            }
         }
      }
      else
         $this->new_error_msg('Artículo no encontrado.');
   }
   
   public function url()
   {
      if( $this->story )
         return $this->story->edit_url();
      else
         return parent::url();
   }
   
   public function get_description()
   {
      if( $this->story )
         return $this->story->description();
      else
         return parent::get_description();
   }
   
   private function set_best_edition()
   {
      $edition = NULL;
      $num_editions = 0;
      
      foreach($this->story->editions() as $edi)
      {
         if( is_null($edition) )
            $edition = $edi;
         else if($edi->points > $edition->points)
            $edition = $edi;
         
         $num_editions++;
      }
      
      if( isset($edition) )
      {
         $this->story->edition_id = $edition->get_id();
         $this->story->title = $edition->title;
         $this->story->description = $edition->description;
         $this->story->num_editions = $num_editions;
         $this->story->save();
      }
   }
}

?>