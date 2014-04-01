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

require_once 'model/feed.php';
require_once 'model/story_preview.php';
require_once 'model/suscription.php';

class explore_feed extends fs_controller
{
   public $feed;
   public $preview;
   public $stories;
   public $suscribe_url;
   public $suscribe_text;
   public $unsuscribe;
   
   public function __construct()
   {
      parent::__construct('explore_feed', FS_NAME);
      
      $feed = new feed();
      $this->preview = new story_preview();
      $this->stories = array();
      
      if( isset($_POST['modify']) AND $this->visitor->admin )
      {
         $this->feed = $feed->get($_GET['id']);
         $this->feed->native_lang = isset($_POST['native_lang']);
         $this->feed->parody = isset($_POST['parody']);
         $this->feed->penalize = isset($_POST['penalize']);
         $this->feed->save();
         $this->new_message('Fuente modificada correctamente.');
      }
      else if( isset($_GET['id']) )
         $this->feed = $feed->get($_GET['id']);
      else
         $this->feed = FALSE;
      
      
      if($this->feed AND isset($_POST['delete']) AND $this->visitor->admin)
      {
         $this->feed->delete();
         $this->feed = FALSE;
         $this->new_message('Fuente eliminada correctamente.');
      }
      else if($this->feed)
      {
         $this->title = $this->feed->name.' &lsaquo; '.FS_NAME;
         $this->stories = $this->feed->stories();
         
         $suscription = new suscription();
         $suscription->visitor_id = $this->visitor->get_id();
         $suscription->feed_id = $this->feed->get_id();
         if( $suscription->exists() )
         {
            $this->suscribe_url = FS_PATH.'index.php?page=suscriptions&unsuscribe='.$suscription->get_id();
            $this->suscribe_text = 'Anular suscripción';
            $this->unsuscribe = TRUE;
         }
         else
         {
            $this->suscribe_url = FS_PATH.'index.php?page=suscriptions&suscribe='.$this->feed->get_id();
            $this->suscribe_text = 'Suscribirse';
            $this->unsuscribe = FALSE;
         }
      }
      else
      {
         $this->new_error_msg('Fuente no encontrada.');
         header("HTTP/1.0 404 Not Found");
      }
   }
   
   public function url()
   {
      if($this->feed)
         return $this->feed->url();
      else
         return parent::url();
   }
   
   public function get_description()
   {
      if( $this->feed )
         return $this->feed->description;
      else
         return parent::get_description();
   }
}

?>