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

class show_story extends fs_controller
{
   public $story;
   public $popular;
   
   public function __construct()
   {
      parent::__construct('show_story', 'historia...', 'Historia...', 'show_story');
      
      /// seleccionamos la plantilla adecuada
      if( isset($_GET['redir']) )
         $this->template = 'redir';
      else if( !isset($_POST['popup']) )
         $this->set_template('show_story_fp');
      
      $story = new story();
      
      if( isset($_GET['id']) )
         $this->story = $story->get($_GET['id']);
      else
         $this->story = FALSE;
      
      if($this->story)
      {
         $this->title = $this->story->title;
         
         if( !$this->story->readed() AND $this->visitor->human() AND  isset($_SERVER['REMOTE_ADDR']) )
         {
            $this->story->read();
            
            $story_visit = new story_visit();
            $sv0 = $story_visit->get_by_params($this->story->get_id(), $_SERVER['REMOTE_ADDR']);
            if( !$sv0 )
            {
               $story_visit->visitor_id = $this->visitor->get_id();
               $story_visit->story_id = $this->story->get_id();
               $story_visit->save();
               $this->story->clics++;
               $this->story->save();
            }
         }
      }
      else
         $this->new_error_msg('Historia no encontrada. <a href="'.FS_PATH.'/index.php?page=search">Usa el buscador</a>.');
      
      if( isset($_POST['popup']) OR $this->visitor->mobile() )
         $this->popular = array();
      else
      {
         $this->popular = $story->popular_stories();
         
         if($this->story)
         {
            /// excluimos la historia actual
            foreach($this->popular as $i => $value)
            {
               if( $value->get_id() == $this->story->get_id() )
                  unset($this->popular[$i]);
            }
         }
      }
   }
   
   public function get_description()
   {
      if($this->story)
         return $this->story->description;
      else
         return parent::get_description();
   }
   
   public function twitter_url()
   {
      if($this->story)
         return 'https://twitter.com/share?url='.urlencode($this->story->link).
              '&text='.urlencode($this->story->title);
      else
         return 'https://twitter.com/share';
   }
   
   public function facebook_url()
   {
      if($this->story)
         return 'http://www.facebook.com/sharer.php?s=100&p[title]='.urlencode($this->story->title).
              '&p[url]='.urlencode($this->story->link);
      else
         return 'http://www.facebook.com/sharer.php';
   }
}

?>