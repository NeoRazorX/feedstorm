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

require_once 'config.php';
require_once 'model/story.php';

class cron_job
{
   public function __construct()
   {
      if( FS_FEEDS )
      {
         $stories = array();
         foreach(explode(',', FS_FEEDS) as $feed)
            $stories = array_merge($stories, $this->read_feed( trim($feed) ));
         if( count($stories) > 0 )
         {
            $story = new story();
            $story->save_all($stories);
            echo "Total: ".count( $stories ) . " stories.\n";
         }
         else
            echo "Total: 0 stories.\n";
      }
   }
   
   public function read_feed($feed)
   {
      $stories = array();
      echo 'Reading ' . $feed . " ...\n";
      $ch = curl_init( $feed );
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      $html = curl_exec($ch);
      $xml = simplexml_load_string( $html );
      if( $xml )
      {
         foreach($xml->channel->item as $item)
            $stories[] = new story($item);
      }
      curl_close($ch);
      return $stories;
   }
}

$cj = new cron_job();

?>
