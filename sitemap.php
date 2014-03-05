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

require_once 'config.php';
require_once 'base/fs_mongo.php';
require_once 'model/story.php';

header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$mongo = new fs_mongo();
$story = new story();
foreach($story->published_stories() as $s)
{
   if($s->native_lang)
   {
      echo '<url><loc>http://',$_SERVER["SERVER_NAME"],$s->url(TRUE),'</loc><lastmod>',
         Date('Y-m-d', $s->date),'</lastmod><changefreq>always</changefreq><priority>0.8</priority></url>';
   }
}
$mongo->close();

echo '</urlset>';

?>