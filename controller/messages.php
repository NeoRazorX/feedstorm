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

require_once 'model/message.php';

class messages extends fs_controller
{
   public $messages;
   public $msg_txt;
   public $msg_to;
   public $msg_to_nick;
   
   public function __construct()
   {
      parent::__construct('messages', 'Mensajes &lsaquo; '.FS_NAME);
      
      $msg = new message();
      
      $this->msg_to = NULL;
      if( isset($_GET['to']) )
         $this->msg_to = $_GET['to'];
      else if( isset($_POST['to']) )
         $this->msg_to = $_POST['to'];
      
      $this->msg_to_nick = NULL;
      if( isset($this->msg_to) AND $this->msg_to != '' )
      {
         $vi0 = $this->visitor->get($this->msg_to);
         if($vi0)
            $this->msg_to_nick = '@'.$vi0->nick;
         else
            $this->new_error_msg('Usuario no encontrado.');
      }
      
      if( isset($_POST['text']) )
      {
         $this->msg_txt = trim($_POST['text']);
         
         if( is_null($this->msg_to_nick) AND !isset($_POST['broadcast']) )
         {
            $this->new_error_msg('Tienes que hacer clic sobre el nick de un usuario para enviarle un mensaje.');
         }
         else if( mb_strlen($this->msg_txt) <= 1 )
         {
            $this->new_error_msg('Tienes que escribir más.');
         }
         else if($this->visitor->admin OR $_POST['human'] == '')
         {
            $msg->from = $this->visitor->get_id();
            $msg->from_nick = $this->visitor->nick;
            $msg->text = $this->msg_txt;
            $msg->to = $this->msg_to;
            $msg->to_nick = str_replace('@', '', $this->msg_to_nick);
            
            if($this->visitor->admin AND isset($_POST['broadcast']))
               $msg->broadcast = TRUE;
            
            $msg->save();
            
            $this->new_message('Mensaje enviado correctamente.');
            $this->msg_to = NULL;
            $this->msg_to_nick = NULL;
            $this->msg_txt = '';
         }
         else
            $this->new_error_msg('Tienes que borrar el número para demostrar que eres humano.');
      }
      
      $this->messages = $msg->all2visitor( $this->visitor->get_id() );
      
      /// marcamos los mensajes como leídos
      foreach($this->messages as $m)
      {
         $m->readed = TRUE;
         $m->save();
      }
   }
   
   public function get_description()
   {
      return 'Mensajes de '.FS_NAME;
   }
}
