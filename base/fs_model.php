<?php

require_once 'base/fs_cache.php';

class fs_model
{
   protected $cache;
   private static $errors;
   private static $messages;
   
   public function __construct()
   {
      $this->cache = new fs_cache();
      
      if( !isset(self::$errors) )
         self::$errors = array();
      
      if( !isset(self::$messages) )
         self::$messages = array();
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
   
   /// functión auxiliar para facilitar el uso de fechas
   public function var2timesince($v)
   {
      if( isset($v) )
      {
         $time = time() - strtotime($v);
         
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
   
   public function true_word_break($str, $width=40)
   {
      return preg_replace('#(\S{'.$width.',})#e', "chunk_split('$1', ".$width.", '&#8203;')", $str);
   }
}

?>
