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

abstract class fs_model
{
   private static $mongo;
   private static $errors;
   private static $messages;
   
   protected $collection_name;
   protected $collection;
   
   protected $id;
   
   public function __construct($cname='test')
   {
      if( !isset(self::$mongo) )
         self::$mongo = new fs_mongo();
      
      if( !isset(self::$errors) )
         self::$errors = array();
      
      if( !isset(self::$messages) )
         self::$messages = array();
      
      $this->collection_name = $cname;
      $this->collection = self::$mongo->select_collection($cname);
   }
   
   public function get_id()
   {
      return $this->id;
   }
   
   protected function new_error($msg=FALSE)
   {
      if($msg)
         self::$errors[] = (string)$msg;
   }
   
   protected function new_message($msg=FALSE)
   {
      if($msg)
         self::$messages[] = (string)$msg;
   }
   
   public function get_errors()
   {
      return self::$errors;
   }
   
   public function get_messages()
   {
      return self::$messages;
   }
   
   public function clean_errors()
   {
      self::$errors = array();
   }
   
   public function clean_messages()
   {
      self::$messages = array();
   }
   
   protected function add2history($comm)
   {
      self::$mongo->add2history($comm);
   }
   
   /// functión auxiliar para facilitar el uso de fechas
   public function time2timesince($v)
   {
      if( isset($v) )
      {
         $time = time() - $v;
         
         if($time <= 60)
            return 'hace '.$time.' segundos';
         else if(60 < $time && $time <= 3600)
            return 'hace '.round($time/60,0).' minutos';
         else if(3600 < $time && $time <= 86400)
            return 'hace '.round($time/3600,0).' horas';
         else if(86400 < $time && $time <= 604800)
            return 'hace '.round($time/86400,0).' dias';
         else if(604800 < $time && $time <= 2592000)
            return 'hace '.round($time/604800,0).' semanas';
         else if(2592000 < $time && $time <= 29030400)
            return 'hace '.round($time/2592000,0).' meses';
         else if($time > 29030400)
            return 'hace más de un año';
      }
      else
         return 'fecha desconocida';
   }
   
   /*
    * Esta función devuelve una copia del string $str al que se le ha
    * añadido un caracter de espacio vacío &#8203; cada $max_width
    * caracteres a cada palabra de más de $max_width caracteres.
    * Las caracteres escapados de HTML se cuentan como uno solo.
    */
   public function true_word_break($str, $max_width=30)
   {
      /// Eliminamos cualquier rastro de &#8203;
      $str = str_replace('&#8203;', '', $str);
      
      $pos0 = 0;
      $width = 0;
      $special_char = FALSE;
      while( $pos0 < strlen($str) )
      {
         $char = substr($str, $pos0, 1);
         
         if($special_char)
         {
            if($char == ';')
            {
               $special_char = FALSE;
               $width++;
            }
         }
         else
         {
            if($char == ' ')
               $width = 0;
            else if($width >= $max_width)
            {
               $str = substr($str, 0, $pos0).'&#8203;'.substr($str, $pos0, strlen($str) - $pos0 );
               $pos0 += 6;
               $width = 0;
            }
            else if($char == '&')
               $special_char = TRUE;
            else
               $width++;
         }
         
         $pos0++;
      }
      return $str;
   }
   
   /*
    * Esta función devuelve una copia de $str con una longitud máxima de
    * $max_t_width caracteres, pero sin cortar la última palabra.
    * 
    * Además añade un caracter de espacio vacío cada $max_w_width
    * caracteres usando la functión fs_model::true_word_break().
    * 
    * Las elementos HTML son escapados mediante fs_model::no_html().
    */
   public function true_text_break($str, $max_t_width=500, $max_w_width=30)
   {
      $description = '';
      $desc = $this->true_word_break( $this->no_html($str), $max_w_width );
      
      foreach(explode(' ', $desc) as $aux)
      {
         if( strlen($description.' '.$aux) < $max_t_width-3 )
         {
            if($description == '')
               $description = $aux;
            else
               $description .= ' ' . $aux;
         }
         else
            break;
      }
      if( strlen($description) < strlen($desc) )
         $description .= '...';
      
      return $description;
   }
   
   public function random_string($length = 10)
   {
      return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
   }
   
   public function var2str($var)
   {
      if( is_null($var) )
         return NULL;
      else
         return (string)$var;
   }
   
   /*
    * Esta función convierte:
    * < en &lt;
    * > en &gt;
    * " en &quot;
    * ' en &#39;
    * 
    * Además elimina los espacios extra.
    * 
    * No tengas la tentación de sustiturla por htmlentities o htmlspecialshars
    * porque te encontrarás con muchas sorpresas desagradables.
    */
   public function no_html($t)
   {
      $newt = trim( preg_replace('/\s+/', ' ', $t) );
      $newt = preg_replace('/</', '&lt;', $newt);
      $newt = preg_replace('/>/', '&gt;', $newt);
      $newt = preg_replace('/"/', '&quot;', $newt);
      $newt = preg_replace("/'/", '&#39;', $newt);
      return $newt;
   }
   
   abstract public function get($id);
   abstract public function exists();
   abstract public function save();
   abstract public function delete();
   abstract public function all();
   
   public function count()
   {
      $this->add2history(__CLASS__.'::'.__FUNCTION__);
      return $this->collection->count();
   }
   
   public function show_count()
   {
      return number_format($this->count(), 0, ',', '.');
   }
}

?>
