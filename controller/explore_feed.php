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

require_once 'model/feed.php';
require_once 'model/suscription.php';

class explore_feed extends fs_controller
{
   public $feed;
   public $stories;
   public $suscribe_url;
   public $suscribe_text;
   public $unsuscribe;
   
   public function __construct()
   {
      parent::__construct('explore_feed', 'Fuente', FS_NAME, 'explore_feed');
      
      $feed = new feed();
      $this->stories = array();
      
      if( isset($_GET['id']) )
      {
         $this->feed = $feed->get($_GET['id']);
         
         if( isset($_GET['delete']) )
         {
            if($_GET['delete'] == FS_MASTER_KEY AND FS_MASTER_KEY != '')
            {
               $this->feed->delete();
               $this->new_message('Fuente eliminada correctamente.');
               $this->feed = FALSE;
            }
            else
               $this->new_error_msg('Clave incorrecta.');
         }
         else if( isset($_GET['native_lang']) )
         {
            $this->feed->native_lang = ($_GET['native_lang'] == 'TRUE');
            $this->feed->save();
            $this->new_message("Fuente modificada correctamente.");
         }
      }
      else
         $this->feed = FALSE;
      
      if($this->feed)
      {
         $this->title = $this->feed->name;
         $this->stories = $this->feed->stories();
         
         $suscription = new suscription();
         $suscription->visitor_id = $this->visitor->get_id();
         $suscription->feed_id = $this->feed->get_id();
         if( $suscription->exists() )
         {
            $this->suscribe_url = 'index.php?page=suscriptions&unsuscribe='.$suscription->get_id();
            $this->suscribe_text = 'Anular suscripción';
            $this->unsuscribe = TRUE;
         }
         else
         {
            $this->suscribe_url = 'index.php?page=suscriptions&suscribe='.$this->feed->get_id();
            $this->suscribe_text = 'Suscribirse';
            $this->unsuscribe = FALSE;
         }
      }
      else
         $this->new_error_msg('Fuente no encontrada.');
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
   
   public function twitter_url()
   {
      if($this->feed)
         return 'https://twitter.com/share?url='.urlencode( $this->domain().'/'.$this->feed->url() ).
              '&text='.urlencode($this->feed->name);
      else
         return 'https://twitter.com/share';
   }
   
   public function facebook_url()
   {
      if($this->feed)
         return 'http://www.facebook.com/sharer.php?s=100&p[title]='.urlencode($this->feed->name).
              '&p[url]='.urlencode( $this->domain().'/'.$this->feed->url() );
      else
         return 'http://www.facebook.com/sharer.php';
   }
}

?>