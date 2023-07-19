<?php

namespace Simoa;

class Parse
{
  var $hasErrors = false;
  var $errors = [];
  var $Index;
  var $_current = [];

  public $class;
  public $config;
  public $response;
  public $headers;
  public $request;
  
  function __construct($o)
  {
    foreach ($o as $k => $v) {
      $this->$k = $v;
    }

    $this->Index = (object)[];
    $this->parse();
  }

  function schema()
  {
    $schema = false;
    $method = $this->headers->method . '()';

    if (isset($this->config->$method->data)) {
      if ($this->config->$method->data == "schema") {
        if (isset($this->config->schema)) {
          $schema = $this->config->schema;
        }
      } else {
        $schema = $this->config->$method->data;
      }
    }

    return $schema;
  }

  function parse()
  {
    $schema = $this->schema();

    if ($schema) {
      foreach ($schema as $k => $v) {
        $type = '_' . gettype($v);
        if (strpos($k, '[{}]') !== false) {
          $type = "_array";
        } else {
          if (strpos($k, '{}') !== false) {
            $type = "_object";
          }
        }
        $this->$type($k, $v, 'data');
      }
    }
  }

  function _array($key, $value, $parent)
  {
    $_key = str_replace('[{}]', '', $key);
    if (isset($this->data->{$_key})) {
      for ($i = 0; $i < count($this->data->$_key); $i++) {
        foreach ($value as $k => $v) {
          $this->_string($k, $v, $_key, $i);
        }
      }
    }
  }

  function _object($key, $value, $parent)
  {
    $_key = str_replace('{}', '', $key);
    foreach ($value as $k => $v) {
      $type = '_' . gettype($v);
      $this->$type($k, $v, $_key);
    }
  }

  function _string($k, $v, $parent, $i = null)
  {
    $this->_current = [
      'k' => $k,
      'p' => $parent,
      'i' => $i
    ];

    $validate = explode(' ', $v);

    // antes de mais nada, valida se vieram os required.
    if (in_array('required', $validate)) {
      $prop = $this->prop($k, $v, $parent);
      $this->result($parent, $k, 'required', $this->required($prop));
    }

    foreach ($validate as $item) {
      // required was previously verified
      if ($item == 'required') {
        continue;
      }

      // 'key:value' 
      if (strpos($item, ':') !== false) {
        preg_match('/(\w+):(\S+\[?\]?)/', $item, $matches);
        if (count($matches) > 2) {

          $fn = $matches[1];
          $param = $matches[2];
          $prop = $this->prop($k, $v, $parent);

          $params = explode(',', $param);
          foreach ($params as $param) {
            if ($prop) {
              $result = $this->$fn($prop, $param);
              $this->result($parent, $k, $item, $result);
            }
          }
        }
      }

      if (strpos($item, '(') !== false) {
        // função
        preg_match('/(\w+)\((\S+)\)/', $item, $matches);
        if (count($matches) > 2) {
          $fn = $matches[1];
          $param = $matches[2];
          $prop = $this->prop($k, $v, $parent);

          if ($prop) {

            $result = $this->$fn($prop, $param);

            $this->result($parent, $k, $item, $result);
          }
        }
      }

      if (method_exists($this, $item)) {
        $prop = $this->prop($k, $v, $parent);
        $this->result($parent, $k, $item, $this->$item($prop));
      }
    }

    $this->_current = [];
  }

  function index($prop, $param)
  {
    if (strpos($param, '[]') !== false) {
      $param = str_replace('[]', '', $param);
      $this->Index->{$param}[] = $prop;
    } else {
      $this->Index->$param = $prop;
    }

    return true;
  }

  /**
   * $parent é o nó anteior, exemplo data e data.score
   * $k é a propriedade exemplo: score de data.score
   * $item é uma string escrita required ou sei lá, 
   * $valid é booleano, vem false quando deu errado
   */
  function result($parent, $k, $item, $valid)
  {
    if (!$valid) {
      $c = (object) $this->_current;
      if ($c->i !== null) {
        $error = $parent . '[' . $c->i . '].' . $k . ':' . $item;
      } else {
        $error = $parent . '.' . $k . ':' . $item;
      }
      $this->hasErrors = true;
      $this->errors[] = $error;
    }
  }

  function required($prop)
  {
    return ($prop || is_bool($prop) || is_numeric($prop)) ? true : false;
  }

  function string($prop)
  {
    return ($prop) ? is_string($prop) : true;
  }

  function int($prop)
  {
    return ($prop) ? is_int($prop) : true;
  }

  function boolean($prop)
  {
    return ($prop) ? is_bool($prop) : true;
  }

  function double($prop)
  {
    return $this->float($prop);
  }

  function float($prop)
  {
    return ($prop) ? is_numeric($prop) : true;
  }

  function object($prop)
  {
    return ($prop) ? is_object($prop) : true;
  }

  function array($prop)
  {
    return ($prop) ? is_array($prop) : true;
  }

  function inArray($prop, $param)
  {
    if (strpos($param, '[') === false) {
      return false;
    }

    $param = str_replace('[', '', $param);
    $param = str_replace(']', '', $param);

    $array = explode(",", $param);

    return ($prop) ? in_array($prop, $array) : true;
  }

  function min($prop, $param)
  {
    // verificar se propriedade é string ou número
    switch (gettype($prop)) {
      case 'integer':
        return ($prop >= $param);
        break;

      case 'array':
        return (count($prop) >= $param);
        break;

      case 'string':
        return (strlen($prop) >= $param);
        break;
    }
  }

  function max($prop, $param)
  {
    switch (gettype($prop)) {
      case 'integer':
        return ($prop <= $param);
        break;

      case 'array':
        return (count($prop) <= $param);
        break;

      case 'string':
        return (strlen($prop) <= $param);
        break;
    }
  }

  function length($prop, $param)
  {
    return (is_string($prop)) ? (strlen($prop) == $param) : (count($prop) == $param);
  }

  function toStringNumbers($string)
  {
    return preg_replace('/[^0-9]/is', '', $string);
  }

  function cpf($prop)
  {
    // elimina caracteres não numéricos
    $cpf = $this->toStringNumbers($prop);

    if (!$this->length($cpf, 11)) {
      return false;
    }

    // checa se só tem caracteres não numéricos
    if (!ctype_digit($cpf)) {
      return false;
    }

    // checa se é válido
    for ($t = 9; $t < 11; $t++) {
      for ($d = 0, $c = 0; $c < $t; $c++) {
        $d += $cpf[$c] * (($t + 1) - $c);
      }
      $d = ((10 * $d) % 11) % 10;
      if ($cpf[$c] != $d) {
        return false;
      }
    }

    $c = (object) $this->_current;
    $this->changeProp($c->k, $c->p, $cpf);

    return true;
  }

  function key($prop)
  {
    $c = (object) $this->_current;
    $this->changeProp($c->k, $c->p, str_replace(' ', '', $prop));
    return true;
  }

  function email($prop)
  {
    return (filter_var($prop, FILTER_VALIDATE_EMAIL)) ? true : false;
  }

  // obtém o dado enviado na requisição
  // referente à propriedade declarada no config
  function prop($k, $v, $p)
  {
    if ($p == 'data') {
      if (isset($this->data->$k)) {
        return $this->data->$k;
      }
    } else {
      if (isset($this->data->$p->$k)) {
        return $this->data->$p->$k;
      }

      if (is_array($this->data->$p)) {
        $c = (object) $this->_current;
        if (isset($this->data->$p[$c->i]->$k)) {
          return $this->data->$p[$c->i]->$k;
        }
      }
    }
    return false;
  }

  function changeProp($k, $p, $value)
  {
    if ($p == 'data') {
      if (isset($this->data->$k)) {
        $this->data->$k = $value;
      }
    } else {
      if (isset($this->data->$p->$k)) {
        $this->data->$p->$k = $value;
      }
    }
    
    return true;
  }
}
