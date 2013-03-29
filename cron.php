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

date_default_timezone_set('Europe/Madrid');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once 'config.php';

if( !defined('FS_MAX_AGE') )
   define('FS_MAX_AGE', 2592000);

require_once 'base/fs_mongo.php';
require_once 'model/feed.php';
require_once 'model/feed_story.php';
require_once 'model/media_item.php';
require_once 'model/story.php';
require_once 'model/story_edition.php';
require_once 'model/story_media.php';
require_once 'model/story_visit.php';
require_once 'model/suscription.php';
require_once 'model/visitor.php';

$mongo = new fs_mongo();
$feed = new feed();
$feed_story = new feed_story();
$media_item = new media_item();
$story = new story();
$story_edition = new story_edition();
$story_media = new story_media();
$story_visit = new story_visit();
$suscription = new suscription();
$visitor = new visitor();

echo "Comprobamos los índices... ";
$feed->install_indexes();
$feed_story->install_indexes();
$media_item->install_indexes();
$story->install_indexes();
$story_edition->install_indexes();
$story_media->install_indexes();
$story_visit->install_indexes();
$suscription->install_indexes();
$visitor->install_indexes();

echo "\nComprobamos los modelos... ";
$feed->cron_job();
$feed_story->cron_job();
$media_item->cron_job();
$story->cron_job();
$story_edition->cron_job();
$story_media->cron_job();
$story_visit->cron_job();
$suscription->cron_job();
$visitor->cron_job();

$mongo->close();

echo "\n";

?>