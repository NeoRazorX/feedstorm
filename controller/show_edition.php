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

require_once 'model/story.php';
require_once 'model/story_visit.php';

class show_edition extends fs_controller
{
   public $edition;
   public $editions;
   
   public function __construct()
   {
      parent::__construct('show_edition', 'edición...', 'Edición...', 'show_edition');
      
      /// seleccionamos la plantilla adecuada
      if( !isset($_POST['popup']) )
         $this->set_template('show_edition_fp');
      
      $se = new story_edition();
      
      if( isset($_GET['id']) )
         $this->edition = $se->get($_GET['id']);
      else
         $this->edition = FALSE;
      
      if($this->edition AND $this->edition->story)
      {
         $this->title = $this->edition->title . ' (edición)';
         
         if( !$this->edition->story->readed() AND $this->visitor->human() AND  isset($_SERVER['REMOTE_ADDR']) )
         {
            $this->edition->story->read();
            
            if( isset($_GET['vote']) )
            {
               $story_visit = new story_visit();
               $sv0 = $story_visit->get_by_params($this->edition->story_id, $_SERVER['REMOTE_ADDR']);
               if( $sv0 )
               {
                  if( is_null($sv0->edition_id) )
                  {
                     $sv0->edition_id = $this->edition->get_id();
                     $sv0->save();
                     $this->edition->votes++;
                     $this->edition->save();
                  }
               }
               else
               {
                  $story_visit->visitor_id = $this->visitor->get_id();
                  $story_visit->story_id = $this->edition->story_id;
                  $story_visit->edition_id = $this->edition->get_id();
                  $story_visit->save();
                  $this->edition->story->clics++;
                  $this->edition->story->save();
                  $this->edition->votes++;
                  $this->edition->save();
               }
            }
         }
      }
      else
         $this->new_error_msg('Edición no encontrada. <a href="'.FS_PATH.'/index.php?page=search">Usa el buscador</a>.');
      
      if( isset($_POST['popup']) OR $this->visitor->mobile() )
         $this->editions = array();
      else
      {
         $this->editions = $se->last_editions();
         
         if($this->edition)
         {
            /// excluimos la edición actual
            foreach($this->editions as $i => $value)
            {
               if( $value->get_id() == $this->edition->get_id() )
                  unset($this->editions[$i]);
            }
         }
      }
   }
   
   public function url()
   {
      if($this->edition)
         return $this->edition->edit_url();
      else
         return parent::url();
   }
   
   public function get_description()
   {
      if($this->edition)
         return $this->edition->description;
      else
         return parent::get_description();
   }
   
   public function twitter_url()
   {
      if($this->edition)
         return 'https://twitter.com/share?url='.urlencode( $this->domain().'/'.$this->edition->url(FALSE) ).
              '&amp;text='.urlencode($this->edition->title);
      else
         return 'https://twitter.com/share';
   }
   
   public function facebook_url()
   {
      if($this->edition)
         return 'http://www.facebook.com/sharer.php?s=100&amp;p[title]='.urlencode($this->edition->title).
              '&amp;p[url]='.urlencode( $this->domain().'/'.$this->edition->url(FALSE) );
      else
         return 'http://www.facebook.com/sharer.php';
   }
}

?>