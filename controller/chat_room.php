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

require_once 'base/fs_cache.php';
require_once 'model/chat_msg.php';

class chat_room extends fs_controller
{
   private $cache;
   private $chats;
   
   public function __construct()
   {
      parent::__construct('chat_room', 'Foro de '.FS_NAME, 'chat_room');
   }
   
   protected function process()
   {
      $this->cache = new fs_cache();
      $this->chats = $this->get_chats();
      
      if( isset($_POST['text']) )
      {
         if( $this->visitor->human() )
         {
            $chatmsg = new chat_msg();
            $chatmsg->nick = $this->visitor->get_key();
            $chatmsg->set_text($_POST['text']);
            $this->chats[] = $chatmsg;
            $this->save_chat();
         }
         else
            $this->new_error_msg("No eres humano.");
      }
   }
   
   private function get_chats($reverse=FALSE)
   {
      return $this->cache->get_array('chat_room');
   }
   
   private function save_chat()
   {
      /// reducimos
      $j = 0;
      foreach($this->chats as $i => $value)
      {
         if($j >= 50)
            unset($this->chats[$i]);
         $j++;
      }
      $this->cache->set('chat_room', $this->chats, 86400);
   }
   
   public function reverse_chats()
   {
      return array_reverse( $this->chats );
   }
}

?>
