<?php

/** 
 *  Usage:
 * 
 *  $this->log(mixed); 
 *  $this->add(key, value);
 *  $this->error(string, code);
 *    error pattern: 
 *      object.field:message
 *      object.child.field:message
 *      object.array[i]:message 
 *      object.array[i].field:message 
 *      etc...
 * 
 * */

namespace Simoa;

class Response
{
  private $code = 200;
  private $data;
  public $success = true;
  public $response;
  public $errors = [];

  function __construct()
  {
    $this->response = (object)[];
  }

  function log($value)
  {
    switch (gettype($value)) {
      case 'object':
        foreach ($value as $k => $v) {
          if ($k == 'response') {
            $this->response = $v;
          } else {
            $this->response->$k = $v;
          }
        }
        break;

      default:
        if (!isset($this->response->log)) {
          $this->response->log = [];
        }
        $this->response->log[] = $value;
        break;
    }

    return $this;
  }

  function add($k, $v)
  {
    if ($k == 'response') {
      foreach ($v as $key => $item) {
        $this->response->$key = $item;
      }
    } else {
      $this->response->$k = $v;
    }

    return $this;
  }

  function errors($array, $code = 200)
  {
    $this->success = false;
    $this->errors = $array;
    $this->code = $code;
    return $this;
  }

  function error($error, $code = 200)
  {
    $this->success = false;
    $this->errors[] = $error;
    $this->code = $code;
    return $this;
  }

  function addHttpHeader($string)
  {
    header($string);
  }

  function echo()
  {
    http_response_code($this->code);
    echo json_encode($this);
  }

  function __toString()
  {
    return json_encode($this);
  }
}
