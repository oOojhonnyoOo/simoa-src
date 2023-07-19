<?php

namespace Simoa;

class TemplateCache{
  var $mode = 'w+';
  var $url;
  var $cache;
  var $asset;

  function __construct($object)
  {
    foreach ($object as $k => $v) {
      $this->$k = $v;
    }
  }

  // replace bracket config.yml instructions by this.headers or this_data info.
  function vars()
  {
    $uniqueId = $this->config->formats->data;
    $this->extension = $extension = $this->config->formats->extension;
    preg_match_all('/\[(\w+)]/', $uniqueId, $matches);

    foreach ($matches[1] as $i => $k) {
      $v = (isset($this->headers->$k))
        ? $this->headers->$k
        : (
            (isset($this->data->$k)) 
            ? ($this->data->$k) 
            : $matches[0][$i]
          ); 
      
      $uniqueId = str_replace($matches[0][$i], $v, $uniqueId);
    }

    $this->uniqueId = $uniqueId;
    $this->filename = $uniqueId.$extension;
  }
}
