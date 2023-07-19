<?php

namespace Simoa;

class Crop
{

  public function getImageResourceFromUrl($url)
  {
    return imagecreatefromstring(file_get_contents($url));
  }

  public function createCrop($url, $filename, $options,  $tmp_path = "/tmp")
  {
    return $this->create_scaled_image($url, $filename, $options, $tmp_path);
  }

  //verifica os metadados da imagem e a rotaciona de acordo com a orientação (se necessário) 
  private function exifReadData($url, $image)
  {
    $exif = exif_read_data($url);
    
    if ($image && $exif && isset($exif['Orientation'])) {
      $orientation = $exif['Orientation'];

      if ($orientation == 6 || $orientation == 5) {
        $image = imagerotate($image, 270, null);
      }
      if ($orientation == 3 || $orientation == 4) {
        $image = imagerotate($image, 180, null);
      }
      if ($orientation == 8 || $orientation == 7) {
        $image = imagerotate($image, 90, null);
      }
      if ($orientation == 5 || $orientation == 4 || $orientation == 7) {
        imageflip($image, IMG_FLIP_HORIZONTAL);
      }
    }
    return $image;
  }

  protected function create_scaled_image($url, $filename, $options, $tmp_path)
  {

    if (!function_exists('imagecreatetruecolor')) {
      error_log('Function not found: imagecreatetruecolor');
      return false;
    }

    //image resource
    $image = $this->getImageResourceFromUrl($url);
    
    if (function_exists('exif_read_data') && preg_match("/\.(jpe?g|jpg)$/i", $filename)) {
      $image = $this->exifReadData($url, $image);
    }

    //tmp 
    $new_file_path = $tmp_path . "/" . $filename;
    
    $max_width = $img_width = imagesx($image);
    $max_height = $img_height = imagesy($image);

    if (!empty($options['max_width'])) {
      $max_width = $options['max_width'];
    }

    if (!empty($options['max_height'])) {
      $max_height = $options['max_height'];
    }
    
    if (!isset($options["cropModal"])) {
      $scale = min(
        $max_width / $img_width,
        $max_height / $img_height
      );
      // if ($scale >= 1) {
      //   if ($file_path !== $new_file_path) {
      //     return copy($file_path, $new_file_path);
      //   }
      //   return true;
      // }
    }

    //Initialize x and y source image
    $src_x = $src_y = 0;

    if (isset($options["cropModal"])) {
      $new_width  = $options["max_width"];
      $new_height = $options["max_height"];
      $new_img    = imagecreatetruecolor($new_width, $new_height);

      $dst_x = $dst_y = 0;

      $src_x = $options["x"];
      $src_y = $options["y"];

      $img_width  = $options["width"];
      $img_height = $options["height"];

    }else{
      if (empty($options['crop'])) {
        $new_width = $img_width * $scale;
        $new_height = $img_height * $scale;
        $dst_x = 0;
        $dst_y = 0;
        $new_img = imagecreatetruecolor($new_width, $new_height);
      } else {
        if (($img_width / $img_height) >= ($max_width / $max_height)) {
            $new_width = $img_width / ($img_height / $max_height);
            $new_height = $max_height;
        } else {
            $new_width = $max_width;
            $new_height = $img_height / ($img_width / $max_width);
        }
        $dst_x = 0 - ($new_width - $max_width) / 2;
        $dst_y = 0 - ($new_height - $max_height) / 2;
        $new_img = imagecreatetruecolor($max_width, $max_height);
      }
    }

    switch (strtolower(substr(strrchr($filename, '.'), 1))) {
      case 'jpg':
      case 'jpeg':
        // $src_img = imagecreatefromjpeg($file_path);
        $src_img = $image;
        $write_image = 'imagejpeg';
        $image_quality = isset($options['jpeg_quality']) ?
            $options['jpeg_quality'] : 90;
        break;
      case 'gif':
        imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
        //$src_img = imagecreatefromgif($file_path);
        $src_img = $image;
        $write_image = 'imagegif';
        $image_quality = null;
        break;
      case 'png':
        imagecolortransparent($new_img, imagecolorallocate($new_img, 0, 0, 0));
        imagealphablending($new_img, false);
        imagesavealpha($new_img, true);
        // $src_img = imagecreatefrompng($file_path);
        $src_img = $image;
        $write_image = 'imagepng';
        $image_quality = isset($options['png_quality']) ?
            $options['png_quality'] : 9;
        break;
      default:
        imagedestroy($new_img);
        return false;
    }

    $success = imagecopyresampled(
      $new_img,
      $src_img,
      $dst_x,
      $dst_y,
      $src_x,
      $src_y,
      $new_width,
      $new_height,
      $img_width,
      $img_height
    ) && $write_image($new_img, $new_file_path, $image_quality);
    // Free up memory (imagedestroy does not delete files):

    imagedestroy($src_img);
    imagedestroy($new_img);

    return $success;
  }
}
