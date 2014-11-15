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

require_once 'base/fs_mongo.php';
require_once 'model/visitor.php';

abstract class fs_controller
{
   private $uptime;
   private $errors;
   private $messages;
   private $mongo;
   public $page;
   public $title;
   public $visitor;
   public $template;
   public $noindex;
   
   public function __construct($name, $title)
   {
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      $this->page = $name;
      $this->title = $title;
      $this->errors = array();
      $this->messages = array();
      $this->mongo = new fs_mongo();
      
      $this->visitor = new visitor();
      if( isset($_COOKIE['key']) )
      {
         $visitor = $this->visitor->get($_COOKIE['key']);
         if($visitor)
         {
            $this->visitor = $visitor;
         }
         else
            $this->new_error_msg('No se encuentra el usuario.');
      }
      
      $this->visitor->login();
      if( isset($_GET['cookies_ok']) )
      {
         $this->visitor->cookies_ok = TRUE;
         $this->visitor->need_save = TRUE;
         $this->visitor->save();
      }
      else if( $this->visitor->save() )
      {
         setcookie('key', $this->visitor->get_id(), time()+FS_MAX_AGE, FS_PATH);
      }
      
      $this->template = $name;
      $this->noindex = TRUE;
   }
   
   public function __destruct()
   {
      $this->mongo->close();
   }
   
   public function version()
   {
      return '2.2';
   }
   
   public function php_version()
   {
      return PHP_VERSION;
   }
   
   public function mongo_version()
   {
      return $this->mongo->version();
   }
   
   public function new_error_msg($msg)
   {
      if( $msg )
         $this->errors[] = (string)$msg;
   }
   
   public function get_errors()
   {
      return array_merge($this->errors, $this->visitor->get_errors());;
   }
   
   public function new_message($msg)
   {
      if( $msg )
         $this->messages[] = (string)$msg;
   }
   
   public function get_messages()
   {
      return array_merge($this->messages, $this->visitor->get_messages());
   }
   
   public function get_mongo_history()
   {
      return $this->mongo->get_history();
   }
   
   public function get_description()
   {
      return FS_DESCRIPTION.' Exploramos la web para mostrarte los temas de actualidad.';
   }
   
   public function get_keywords()
   {
      return '';
   }
   
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
   
   public function url()
   {
      return FS_PATH.$this->page;
   }
   
   public function domain()
   {
      if( mb_substr($_SERVER["SERVER_NAME"], 0, 4) == 'www.')
         return 'http://'.$_SERVER["SERVER_NAME"];
      else
         return 'http://www.'.$_SERVER["SERVER_NAME"];
   }
   
   public function split_stories(&$stories, $cols=3, $col=1)
   {
      $cut = max( array(1, ceil( count($stories)/$cols) ) );
      $list = array();
      
      foreach($stories as $i => $value)
      {
         if($i >= $cut*($col-1) AND $i < $cut*$col)
            $list[] = $value;
      }
      
      return $list;
   }
   
   public function add2header()
   {
      return '';
   }
}
