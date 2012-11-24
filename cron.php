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

date_default_timezone_set('Europe/Madrid');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once 'config.php';
require_once 'model/feed.php';

echo "+ -> PreProcesar, * -> Procesar, - -> Imágen, D -> Descargar, R -> Redimensionar.\n";

$feed = new feed();
foreach($feed->all() as $f)
{
   echo "\nProcesando ".$f->name."...\n";
   $f->read(TRUE);
}

?>
