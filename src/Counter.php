<?php

namespace Simoa;

use Exception;

class Counter
{

  function __construct()
  {
  }


  // props
  /* 
    [
      'site' => $this->headers->site,
      'module' => $this->headers->module,
      'data' => $this->config->data
    ]
  */

  function createLastFile($props)
  {
    $path = $props->data . "/counters/" . $props->site . "/" . $props->module;
    if (!file_exists($path)) {
      mkdir($path, 0755, true);

      echo "\n criou $path";
    } else {
      echo "\n não criou $path";
    }

    $this->reset($props);
  }

  function reset($props)
  {
    $props = (object)$props;
    $last = $props->data . "/counters/" . $props->site . "/" . $props->module . "/last";
    $fopen = fopen($last, 'w+');
    fwrite($fopen, 0);
    fclose($fopen);
  }

  function next($props)
  {
    $props = (object)$props;

    $path = $props->data . "/counters/" . $props->site . "/" . $props->module;
    if (!file_exists($path)) {
      mkdir($path, 0755, true);
    }

    $last = $path . "/last";

    $current = $this->getCurrentAndIncrement($last);

    if (empty($current)) {
      return false;
    }

    return $current;
  }

  function getCurrentAndIncrement($file, $tries = 100)
  {
    $current = "";
    $i = 0;

    do {
      //open and lock file
      $handle = fopen($file, 'r+');
      $locked = flock($handle, LOCK_EX);

      if ($locked) {
        $current = fgets($handle);

        //work with data
        if (empty($current)) {
          continue;
        }

        $current = (int)$current;
        $current++;

        //truncate file
        ftruncate($handle, 0);

        //begin of file
        rewind($handle);
        
        //write content
        fwrite($handle, $current);

        //unlock file
        flock( $handle, LOCK_UN ); 
        
        //close
        fclose($handle);
      } else {
        usleep(1000);
        $i++;
      }
    } while (!$locked  && $i <= $tries);

    return $current;
  }
}
