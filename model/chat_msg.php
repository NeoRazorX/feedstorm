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

require_once 'base/fs_model.php';

class chat_msg extends fs_model
{
   public $date;
   public $ip;
   public $user_agent;
   public $nick;
   private $text;
   
   public function __construct()
   {
      parent::__construct();
      
      $this->date = time();
      
      if( isset($_SERVER['REMOTE_ADDR']) )
      {
         $ip4 = explode('.', $_SERVER['REMOTE_ADDR']);
         $ip6 = explode(':', $_SERVER['REMOTE_ADDR']);
         if( count($ip4) == 4 )
            $this->ip = $ip4[0].'.'.$ip4[1].'.'.$ip4[2].'.X';
         else if( count($ip6) == 8 )
            $this->ip = $ip6[0].':'.$ip6[1].':'.$ip6[2].':'.$ip6[3].':X:X:X:X';
      }
      
      if( isset($_SERVER['HTTP_USER_AGENT']) )
         $this->user_agent = $_SERVER['HTTP_USER_AGENT'];
   }
   
   public function get_text()
   {
      return $this->text;
   }
   
   public function set_text($text='')
   {
      $this->text = htmlspecialchars($text);
   }
   
   public function show_date()
   {
      return Date('Y-m-d H:m', $this->date);
   }
   
   public function timesince()
   {
      return $this->time2timesince($this->date);
   }
}

?>
