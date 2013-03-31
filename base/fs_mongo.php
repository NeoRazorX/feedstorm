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

class fs_mongo
{
   private static $link;
   private static $db;
   private static $history;
   
   public function __construct()
   {
      if( !isset(self::$link) )
      {
         self::$link = new Mongo( FS_MONGO_HOST );
         self::$db = self::$link->selectDB( FS_MONGO_DBNAME );
         self::$db->command(array('profile' => 1, 'slowms' => 50));
         self::$history = array();
      }
   }
   
   public function close()
   {
      if(self::$link)
         self::$link->close();
   }
   
   public function select_collection($cname)
   {
      if(self::$link)
         return self::$db->selectCollection($cname);
      else
         return FALSE;
   }
   
   public function get_history()
   {
      if(self::$link)
         return self::$history;
      else
         return array();
   }
   
   public function add2history($comm)
   {
      if(self::$link)
         self::$history[] = $comm;
   }
   
   public function version()
   {
      if(self::$link)
         return Mongo::VERSION;
      else
         return 'Desconocida';
   }
}

?>