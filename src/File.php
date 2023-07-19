<?php

namespace Simoa;

class File
{
  var $mode = "w+";
  var $path;
  var $filename;
  var $extension;
  var $file;

  function __construct($props)
  {
    foreach ($props as $k => $v) {
      $this->$k = $v;
    }

    $this->fetchFromFile();
  }

  function fetchFromFile()
  {
    if (!empty($this->file)) {
      preg_match('/(.+\/)(\w+).(\w{3})/', $this->file, $matches);

      if (count($matches) < 4) {
        return false;
      }

      $this->path = $matches[1];
      $this->filename = $matches[2];
      $this->extension = $matches[3];
    }
  }

  function createPath()
  {
    if (!file_exists($this->path)) {
      mkdir($this->path, 0755, true);
    }
  }

  static function mkdir($path)
  {
    if (!file_exists($path)) {
      mkdir($path, 0755, true);
    }
  }

  function save($content)
  {
    $this->createPath();
    $fopen = fopen($this->file, $this->mode);
    $return = (fwrite($fopen, $content)) ? true : false ;
    fclose($fopen);
    return $return;
  }

  static function saveFile($file, $content)
  {
    $fopen = fopen($file, 'w+');
    $return = (fwrite($fopen, $content)) ? true : false ;
    fclose($fopen);
    return $return;
  }

  // content = array de linhas csv
  function csvsave($content)
  {
    $return = null;
    $this->createPath();
    $fopen = fopen($this->file, $this->mode);
    
    foreach ($content as $line) {
      $return = (fputcsv($fopen, $line)) ? true : false;
    }
    
    fclose($fopen);

    return $return;
  }
}
