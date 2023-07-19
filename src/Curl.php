<?php

/**
 * Usage:
 * 
 * $Curl = new Curl([
 *   'url' => 'http://foo.com/bar'   
 *   'request' => 'GET',             (optional)
 *   'headers' => [                  (optional)
 *     'token: foo123'
 *   ],
 *   'props' => [                    (optional)
 *     '-k',
 *     '-s'
 *   ]
 *   'dataRaw' => (object)[          (optional)
 *     "data" => (object)[]
 *   ]
 * ]);
 */

namespace Simoa;

class Curl
{
  var $url;
  var $request = 'POST';
  var $props = ['-k'];
  var $headers = [];
  var $dataRaw;

  function __construct($options = [])
  {
    foreach ($options as $k => $v) {
      $this->$k = $v;
    }
  }

  function props($props = [])
  {
    foreach ($props as $prop) {
      $this->props = $prop;
    }
  }

  function fetchProps()
  {
    $str = "";
    foreach ($this->props as $prop) {
      (!empty($str)) && ($str .= " ");
      $str .= $prop;
    }
    return $str;
  }

  function fetchRequest()
  {
    return '--request ' . $this->request;
  }

  function url($url = null)
  {
    $this->url = ($url === null) ? $this->url : $url;
    return $this->url;
  }

  function fetchHeaders()
  {
    $str = "";
    foreach ($this->headers as $k => $v) {
      if (!empty($v)) {
        (!empty($str)) && ($str .= " ");
        $str .= "--header '$k: $v'";
      }
    }
    return $str;
  }

  function fetchDataRaw()
  {
    (is_object($this->dataRaw)) && ($data = json_encode($this->dataRaw));
    return "--data-raw '$data'";
  }

  function fetch()
  {
    $str = $this->add('curl', $this->fetchProps());
    $str = $this->add($str, $this->fetchRequest());
    $str = $this->add($str, "'" . $this->url() . "'");
    $str = $this->add($str, $this->fetchHeaders());
    $str = $this->add($str, $this->fetchDataRaw());

    return $str;
  }

  function add($str, $add)
  {
    if (!empty($add)) {
      (!empty($str)) && ($str .= " ");
      $str .= $add;
    }
    return $str;
  }

  function exec($string = null)
  {
    if ($string !== null) {
      return shell_exec($string);
    }

    return shell_exec($this->fetch());
  }
}
