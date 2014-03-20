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

date_default_timezone_set('Europe/Madrid');

require_once 'config.php';
require_once 'base/fs_mongo.php';
require_once 'model/feed.php';
require_once 'model/story.php';
require_once 'model/suscription.php';

header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<suscriptions>';

$mongo = new fs_mongo();

$suscription = new suscription();
foreach($suscription->all() as $sus)
{
   echo '<item><user>'.$sus->visitor_id.'</user><feed>'.base64_encode($sus->feed_url()).'</feed></item>';
}

$feed = new feed();
foreach($feed->all() as $f)
{
   echo '<item><user>-</user><feed>'.base64_encode($f->url).'</feed></item>';
}

$story = new story();
foreach($story->popular_stories(1000, TRUE) as $ps)
{
   $native = 'TRUE';
   if( !$ps->native_lang )
      $native = 'FALSE';
   
   echo '<story><title>'.base64_encode($ps->title).'</title><description>'.base64_encode($ps->description).'</description>'
      . '<date>'.$ps->date.'</date><link>'.base64_encode($ps->link).'</link><clics>'.$ps->clics.'</clics>'
      . '<keywords>'.base64_encode($ps->keywords).'</keywords><native>'.$native.'</native>'
      . '<name>'.base64_encode($ps->name).'</name></story>';
}

$mongo->close();

echo '</suscriptions>';

?>