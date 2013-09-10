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

class GooglePageRank {
 
	var $_GOOGLE_MAGIC = 0xE6359A60;
	var $_url = '';
	var $_checksum = '';
 
	function GooglePageRank($url)
	{
		$this->_url = $url;
	}
 
	function _strToNum($Str, $Check, $Magic)
	{
		$Int32Unit = 4294967296;
 
		$length = strlen($Str);
		for ($i = 0; $i < $length; $i++) {
			$Check *= $Magic;    
 
			if ($Check >= $Int32Unit) {
				$Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
				$Check = ($Check < -2147483647) ? ($Check + $Int32Unit) : $Check;
			}
			$Check += ord($Str{$i});
		}
		return $Check;
	}
 
	function _hashURL($String)
	{
		$Check1 = $this->_strToNum($String, 0x1505, 0x21);
		$Check2 = $this->_strToNum($String, 0, 0x1003F);
 
		$Check1 >>= 2;
		$Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
		$Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
		$Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);   
 
		$T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
		$T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
 
		return ($T1 | $T2);
	}
 
	function checksum()
	{
		if($this->_checksum != '') return $this->_checksum;
 
		$Hashnum = $this->_hashURL($this->_url);
 
		$CheckByte = 0;
		$Flag = 0;
 
		$HashStr = sprintf('%u', $Hashnum) ;
		$length = strlen($HashStr);
 
		for ($i = $length - 1;  $i >= 0;  $i --) {
			$Re = $HashStr{$i};
			if (1 == ($Flag % 2)) {
				$Re += $Re;
				$Re = (int)($Re / 10) + ($Re % 10);
			}
			$CheckByte += $Re;
			$Flag ++;
		}
 
		$CheckByte %= 10;
		if (0 !== $CheckByte) {
			$CheckByte = 10 - $CheckByte;
			if (1 === ($Flag%2) ) {
				if (1 === ($CheckByte % 2)) {
					$CheckByte += 9;
				}
				$CheckByte >>= 1;
			}
		}
 
		$this->_checksum = '7'.$CheckByte.$HashStr;
		return $this->_checksum;
	}
 
	function pageRankUrl($dcchosen)
	{
		return $dcchosen . 'tbr?client=navclient-auto&features=Rank:&q=info:'.$this->_url.'&ch='.$this->checksum();
	}
 
	function getPageRank($dcchosen)
	{
		$fh = @fopen($this->pageRankUrl($dcchosen), "r");
		if($fh)
		{
			$contenido = '';
			while (!feof($fh)) {
			  $contenido .= fread($fh, 8192);
			}
			fclose($fh);
			ltrim($contenido);
			rtrim($contenido);
			$contenido=str_replace("Rank_1:1:","",$contenido);
			$contenido=str_replace("Rank_1:2:","",$contenido);
			//$contenido=intval($contenido);
			$contenido=intval($contenido);
 
			if(is_numeric($contenido))
				return $contenido;
			else
				return -2;
		}
		return -1;
	}
 
}

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
   
   public $showing;
   
   public function __construct()
   {
      parent::__construct('stats', 'Estadísticas', 'Estadísticas &lsaquo; '.FS_NAME, 'stats');
      
      $this->feed = new feed();
      $this->feed_story = new feed_story();
      $this->media_item = new media_item();
      $this->story = new story();
      $this->story_edition = new story_edition();
      $this->story_media = new story_media();
      $this->story_visit = new story_visit();
      $this->suscription = new suscription();
      
      $this->showing = 'visits';
      if( isset($_GET['showing']) )
         $this->showing = $_GET['showing'];
   }
   
   public function get_description()
   {
      return 'Estadísticas de '.FS_NAME.'. Número de fuentes, de historias, visitas, usuarios
         y un largo etcétera.';
   }
   
   public function tmp_size($path='tmp', $show_units=TRUE)
   {
      $total_size = 0;
      $files = scandir($path);
      
      foreach($files as $t)
      {
         if(is_dir(rtrim($path, '/') . '/' . $t))
         {
            if($t<>"." && $t<>"..")
            {
               $size = $this->tmp_size( rtrim($path, '/').'/'.$t, FALSE );
               $total_size += $size;
            }
         }
         else
         {
            $size = filesize( rtrim($path, '/').'/'.$t );
            $total_size += $size;
         }
      }
      
      if($show_units)
      {
         $mod = 1024;
         $units = explode(' ','B KB MB GB TB PB');
         
         for($i = 0; $total_size > $mod; $i++)
            $total_size /= $mod;
         
         return round($total_size, 2) . ' ' . $units[$i];
      }
      else
         return $total_size;
   }
   
   public function analyze_visits()
   {
      if( isset($_SERVER['REMOTE_ADDR']) )
         $ip = $_SERVER['REMOTE_ADDR'];
      else
         $ip = 'unknown';
      
      $visits = $this->story_visit->last(FS_MAX_STORIES * 4, $ip);
      $aux = array();
      
      foreach($visits as $i => $value)
      {
         if( array_key_exists($value->story_id, $aux) )
            $aux[$value->story_id]++;
         else
            $aux[$value->story_id] = 1;
      }
      
      arsort($aux);
      
      $stlist = array();
      $n = 0;
      foreach($aux as $i => $value)
      {
         if($n < FS_MAX_STORIES AND $value > 1)
         {
            $stlist[] = array(
                'story' => $this->story->get($i),
                'visits' => $value
            );
         }
         else
            break;
         
         $n++;
      }
      
      return $stlist;
   }
   
   public function pagerank()
   {
      $dc = "http://toolbarqueries.google.com/";
      $gpr =& new GooglePageRank( trim( $this->domain() ) );
      $pagerank = $gpr->getPageRank($dc);
      return $pagerank;
   }
}

?>