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
require_once 'model/feed_story.php';
require_once 'model/media_item.php';
require_once 'model/story.php';
require_once 'model/story_edition.php';
require_once 'model/story_media.php';
require_once 'model/story_visit.php';
require_once 'model/suscription.php';
require_once 'model/visitor.php';

class stats extends fs_controller
{
   public $feed;
   public $feed_story;
   public $media_item;
   public $story;
   public $story_edition;
   public $story_media;
   public $story_visit;
   public $suscription;
   
   public function __construct()
   {
      parent::__construct('stats', 'Estadísticas', 'stats@'.FS_NAME, 'stats');
   }
   
   protected function process()
   {
      $this->feed = new feed();
      $this->feed_story = new feed_story();
      $this->media_item = new media_item();
      $this->story = new story();
      $this->story_edition = new story_edition();
      $this->story_media = new story_media();
      $this->story_visit = new story_visit();
      $this->suscription = new suscription();
   }
}

?>