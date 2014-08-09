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
 * Elimina todos los artículos y fuentes que pertenezcan a medios de AEDE.
 */
class chan7
{
   public function __construct()
   {
      $feed = new feed();
      $story = new story();
      
      $num = 0;
      foreach($feed->all() as $f0)
      {
         echo '.';
         
         if( $this->aede($f0->url) )
         {
            $f0->delete();
            
            $num++;
            echo '-';
         }
      }
      echo ' '.$num.' fuentes eliminadas. ';
      
      $num = 0;
      /// ¿Hacemos una pasada completa por los artículos?
      if( mt_rand(0, 47) == 0 )
      {
         foreach($story->all() as $sto)
         {
            echo '.';
            
            if( $this->aede($sto->link) )
            {
               $sto->delete();
               
               $num++;
               echo '-';
            }
         }
      }
      else
      {
         foreach($story->last_stories(1000) as $sto)
         {
            echo '.';
            
            if( $this->aede($sto->link) )
            {
               $sto->delete();
               
               $num++;
               echo '-';
            }
         }
      }
      
      echo ' '.$num.' artículos eliminados.';
   }
   
   /**
    * Devuelve TRUE si el enlace pertenece a un medio de AEDE
    * @param type $link
    * @return boolean
    */
   private function aede($link)
   {
      $aede_domains = array(
          'abc.es', 'aede.es', 'as.com', 'canarias7.es', 'cincodias.com', 'deia.com', 'diaridegirona.cat',
          'diaridetarragona.com', 'diarideterrassa.es', 'diariocordoba.com', 'diariodeavila.es', 'diariodeavisos.com',
          'diariodecadiz.es', 'diariodeibiza.es', 'diariodejerez.es', 'diariodelaltoaragon.es', 'diariodeleon.es',
          'diariodemallorca.es', 'diariodenavarra.es', 'diariodenoticias.org', 'diariodesevilla.es', 'diarioinformacion.com',
          'diariojaen.es', 'diariopalentino.es', 'diariovasco.com', 'diariovasco.com', 'eladelantado.com', 'elalmeria.es',
          'elcomercio.es', 'elcorreo.com', 'elcorreoweb.es', 'eldiadecordoba.es', 'eldiariomontanes.es', 'eleconomista.es',
          'elmundo.es', 'elpais.com', 'elpais.es', 'elperiodico.com', 'elperiodicodearagon.com', 'elperiodicoextremadura.com',
          'elperiodicomediterraneo.com', 'elprogreso.es', 'europasur.es', 'expansion.com', 'farodevigo.es', 'granadahoy.com',
          'heraldo.es', 'heraldodesoria.es', 'hoy.es', 'ideal.es', 'intereconomia.com/la-gaceta', 'lagacetadesalamanca.es',
          'laopinion.es', 'laopinioncoruna.es', 'laopiniondemalaga.es', 'laopiniondemurcia.es', 'laopiniondezamora.es',
          'laprovincia.es', 'larazon.es', 'larioja.com', 'lasprovincias.es', 'latribunadealbacete.es', 'latribunadeciudadreal.es',
          'latribunadetalavera.es', 'latribunadetoledo.es', 'lavanguardia.com', 'laverdad.es', 'laverdad.es', 'lavozdealmeria.es',
          'lavozdegalicia.es', 'lavozdigital.es', 'levante-emv.com', 'lne.es', 'majorcadailybulletin.es', 'malagahoy.es',
          'marca.com', 'mundodeportivo.com', 'noticiasdealava.com', 'noticiasdegipuzkoa.com', 'regio7.cat', 'sport.es',
          'superdeporte.es', 'ultimahora.es'
      );
      
      $parts = explode('/', $link);
      if( count($parts) >= 3 )
      {
         $result = FALSE;
         
         foreach($aede_domains as $dom)
         {
            if( strpos($parts[2], '.'.$dom) !== FALSE OR $parts[2] == $dom )
            {
               $result = TRUE;
               break;
            }
         }
         
         return $result;
      }
      else
         return FALSE;
   }
}

new chan7();