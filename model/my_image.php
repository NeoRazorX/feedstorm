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

class my_image
{
   public $image;
   public $image_type;
   public $path;
   
   function load($filename)
   {
      $this->path = $filename;
      
      try
      {
         $image_info = @getimagesize($filename);
         
         if($image_info[0] == 0 AND $image_info[1] == 0)
         {
            $this->image = NULL;
            $this->image_type = NULL;
         }
         else
         {
            $this->image_type = $image_info[2];
            
            if( $this->image_type == IMAGETYPE_JPEG )
               $this->image = @imagecreatefromjpeg($filename);
            else if( $this->image_type == IMAGETYPE_GIF )
               $this->image = @imagecreatefromgif($filename);
            else if( $this->image_type == IMAGETYPE_PNG )
               $this->image = @imagecreatefrompng($filename);
            else
            {
               $this->image = NULL;
               $this->image_type = NULL;
            }
         }
      }
      catch(Exception $e)
      {
         $this->image = NULL;
         $this->image_type = NULL;
      }
   }
   
   function save($filename=FALSE, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null)
   {
      if( isset($this->image) )
      {
         if( !$filename )
            $filename = $this->path;
         
         if( $image_type == IMAGETYPE_JPEG )
            imagejpeg($this->image,$filename,$compression);
         else if( $image_type == IMAGETYPE_GIF )
            imagegif($this->image,$filename);
         else if( $image_type == IMAGETYPE_PNG )
            imagepng($this->image,$filename);
         
         if( $permissions != null)
            chmod($filename,$permissions);
      }
   }
   
   function output($image_type=IMAGETYPE_JPEG)
   {
      if( isset($this->image) )
      {
         if( $image_type == IMAGETYPE_JPEG )
            imagejpeg($this->image);
         else if( $image_type == IMAGETYPE_GIF )
            imagegif($this->image);
         else if( $image_type == IMAGETYPE_PNG )
            imagepng($this->image);
      }
   }
   
   function getWidth()
   {
      if( is_null($this->image) OR is_bool($this->image) )
         return 0;
      else
         return imagesx($this->image);
   }
   
   function getHeight()
   {
      if( is_null($this->image) OR is_bool($this->image) )
         return 0;
      else
         return imagesy($this->image);
   }
   
   function resizeToHeight($height)
   {
      if( isset($this->image) )
      {
         $ratio = $height / $this->getHeight();
         $width = $this->getWidth() * $ratio;
         $this->resize($width, $height);
      }
   }
   
   function resizeToWidth($width)
   {
      if( isset($this->image) )
      {
         $ratio = $width / $this->getWidth();
         $height = $this->getheight() * $ratio;
         $this->resize($width, $height);
      }
   }
   
   function scale($scale)
   {
      if( isset($this->image) )
      {
         $width = $this->getWidth() * $scale/100;
         $height = $this->getheight() * $scale/100;
         $this->resize($width, $height);
      }
   }
   
   function resize($width, $height)
   {
      if( isset($this->image) )
      {
         $new_image = imagecreatetruecolor($width, $height);
         
         if($this->image_type == IMAGETYPE_GIF OR $this->image_type == IMAGETYPE_PNG)
         {
            $current_transparent = imagecolortransparent($this->image);
            
            if($current_transparent != -1)
            {
               $transparent_color = @imagecolorsforindex($this->image, $current_transparent);
               $current_transparent = imagecolorallocate($new_image, $transparent_color['red'],
                       $transparent_color['green'], $transparent_color['blue']);
               imagefill($new_image, 0, 0, $current_transparent);
               imagecolortransparent($new_image, $current_transparent);
            }
            else if($this->image_type == IMAGETYPE_PNG)
            {
               imagealphablending($new_image, false);
               $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
               imagefill($new_image, 0, 0, $color);
               imagesavealpha($new_image, true);
            }
         }
         
         imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height,
                 $this->getWidth(), $this->getHeight());
         $this->image = $new_image;
      }
   }
}

?>