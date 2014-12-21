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

/**
 * Genera infografías sobre un mes/año.
 */
class chan11
{
   public function __construct()
   {
      if( Date('m') != Date('m', strtotime('+2 hours')) AND !file_exists('tmp/special/'.$this->mes(Date('m')).'_'.Date('Y').'.json') )
      {
         $data = array(
             'title' => 'Resumen de '.$this->mes(Date('m')).' de '.Date('Y'),
             'stories' => array(
                 'popular' => array(),
                 'total' => 0,
                 'clics' => 0,
                 'tweets' => 0,
                 'likes' => 0,
                 'plusones' => 0,
                 'meneos' => 0
             ),
             'topics' => array(
                 'popular' => array(),
                 'total' => 0,
                 'stories' => 0
             ),
             'feeds' => array(
                 'popular' => array(),
                 'total' => 0,
                 'stories' => 0,
                 'suscriptors' => 0
             )
         );
         
         $story = new story();
         $popular_stories = array();
         $popular_feeds = array();
         $popular_topics = array();
         $continuar = TRUE;
         $offset = 0;
         $stories = $story->published_stories($offset);
         while($stories AND $continuar)
         {
            foreach($stories as $sto)
            {
               if($sto->date > strtotime(Date('1-m-Y')))
               {
                  $offset++;
                  $data['stories']['total']++;
                  $data['stories']['clics'] += $sto->clics;
                  $data['stories']['tweets'] += $sto->tweets;
                  $data['stories']['likes'] += $sto->likes;
                  $data['stories']['plusones'] += $sto->plusones;
                  $data['stories']['meneos'] += $sto->meneos;
                  
                  /// buscamos los artículos más populares
                  if( count($popular_stories) < 5 )
                  {
                     $popular_stories[] = $sto;
                  }
                  else
                  {
                     foreach($popular_stories as $i => $value)
                     {
                        if( $sto->max_popularity() > $value->max_popularity() )
                        {
                           $popular_stories[$i] = $sto;
                           break;
                        }
                     }
                  }
                  
                  /// calculamos las fuentes más populares
                  foreach( array_reverse($sto->feed_links()) as $fl)
                  {
                     if( isset($popular_feeds[$fl->feed_id]) )
                     {
                        $popular_feeds[$fl->feed_id]++;
                     }
                     else
                        $popular_feeds[$fl->feed_id] = 1;
                     
                     $data['feeds']['stories']++;
                     break;
                  }
                  
                  /// calculamos los temas más populares
                  foreach($sto->topics as $tid)
                  {
                     if( isset($popular_topics[(string)$tid]) )
                     {
                        $popular_topics[(string)$tid]++;
                     }
                     else
                        $popular_topics[(string)$tid] = 1;
                     
                     $data['topics']['stories']++;
                  }
               }
               else
                  $continuar = FALSE;
            }
            
            $stories = $story->published_stories($offset);
         }
         
         /// metemos por fin los artículos más populares
         foreach($popular_stories as $ps)
         {
            $data['stories']['popular'][] = (string)$ps->get_id();
         }
         
         /// ahora ordenamos y sacamos las fuentes más populares
         arsort($popular_feeds);
         $feed0 = new feed();
         $num = 5;
         foreach($popular_feeds as $key => $value)
         {
            $data['feeds']['total']++;
            
            $feed = $feed0->get($key);
            if($feed)
            {
               $data['feeds']['suscriptors'] += $feed->suscriptors;
               
               if($num > 0)
               {
                  $data['feeds']['popular'][] = (string)$feed->get_id();
                  $num--;
               }
            }
            else
               '-ERROR-';
         }
         
         /// ordenamos y sacamos los temas más populares
         arsort($popular_topics);
         $topic0 = new topic();
         $num = 5;
         foreach($popular_topics as $key => $value)
         {
            $data['topics']['total']++;
            
            if($num > 0)
            {
               $topic = $topic0->get($key);
               if($topic)
               {
                  $data['topics']['popular'][] = (string)$topic->get_id();
               }
               else
                  '-ERROR-';
               
               $num--;
            }
         }
         
         if( !file_exists('tmp/special') )
         {
            mkdir('tmp/special');
         }
         
         file_put_contents('tmp/special/'.$this->mes(Date('m')).'_'.Date('Y').'.json', json_encode($data) );
         $this->share_special_page($this->mes(Date('m')).'_'.Date('Y'), $data);
      }
   }
   
   private function mes($num)
   {
      $meses = array(
          '1' => 'Enero',
          '2' => 'Febrero',
          '3' => 'Marzo',
          '4' => 'Abril',
          '5' => 'Mayo',
          '6' => 'Junio',
          '7' => 'Julio',
          '8' => 'Agosto',
          '9' => 'Septiembre',
          '10' => 'Octubre',
          '11' => 'Noviembre',
          '12' => 'Diciembre'
      );
      
      return $meses[$num];
   }
   
   private function share_special_page($name, &$data)
   {
      $story = new story();
      $story->link = 'http://'.FS_DOMAIN.FS_PATH.'special/'.$name;
      $story->featured = TRUE;
      $story->published = time();
      $story->title = $data['title'];
      $story->description = $data['stories']['total'].' artículos publicados en este periodo, ';
      $story->description .= $data['feeds']['total'].' fuentes y ';
      $story->description .= $data['topics']['total'].' temas. Este es el '.$data['title'].'. ';
      $story->description .= 'Haz clic en el enlace para ver con detalle este resumen elaborado por chan11 ;-)';
      $story->save();
      
      $comm = new comment();
      $comm->thread = $story->get_id();
      $comm->nick = __CLASS__;
      switch ( mt_rand(0,2) )
      {
         case 0:
            $comm->text = "Se admiten sugerencias para el próximo resumen ;-)";
            break;
         
         case 1:
            $comm->text = "Para los que creen que este resumen es sectario, aquí tenéis mi código fuente "
                 . "https://github.com/NeoRazorX/feedstorm/blob/master/bots/chan11/cron.php :D";
            break;
         
         default:
            $comm->text = "Este es el mejor resumen del mes, y si no ¡Desmiéntemelo!";
            break;
      }
      $comm->save();
   }
}

new chan11();