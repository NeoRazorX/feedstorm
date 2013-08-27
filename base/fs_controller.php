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

require_once 'base/fs_mongo.php';
require_once 'model/visitor.php';

abstract class fs_controller
{
   private $uptime;
   private $errors;
   private $messages;
   private $mongo;
   public $page;
   public $page_title;
   public $title;
   public $template;
   public $visitor;
   
   public function __construct($name, $ptitle, $title, $template)
   {
      if( !defined('FS_MASTER_KEY') )
         define('FS_MASTER_KEY', '');
      
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      $this->page = $name;
      $this->page_title = $ptitle;
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
            $this->visitor->login();
         }
         else
            $this->new_error_msg('No se encuentra el usuario.');
      }
      $this->visitor->save();
      setcookie('key', $this->visitor->get_id(), time()+31536000);
      
      $this->set_template($template);
   }
   
   public function __destruct()
   {
      $this->mongo->close();
   }
   
   public function version()
   {
      return '1.0b8';
   }
   
   public function php_version()
   {
      return PHP_VERSION;
   }
   
   public function mongo_version()
   {
      return $this->mongo->version();
   }
   
   protected function set_template($tpl='main')
   {
      if( $this->visitor->mobile() )
         $this->template = 'mobile/'.$tpl;
      else
         $this->template = 'desktop/'.$tpl;
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
   
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
   
   public function url()
   {
      return 'index.php?page='.$this->page;
   }
   
   public function domain()
   {
      if( mb_substr($_SERVER["SERVER_NAME"], 0, 4) == 'www.')
         return 'http://'.$_SERVER["SERVER_NAME"].FS_PATH;
      else
         return 'http://www.'.$_SERVER["SERVER_NAME"].FS_PATH;
   }
}

?>