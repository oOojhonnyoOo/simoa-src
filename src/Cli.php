<?php

/**
 *  Usage
 * 
 *  Chamando Arbitrariamente: -x
 *  php cli -x 'Namespace\Class.method(param1, param2)'
 *  php cli -x 'Namespace\Class.method()'
 *  php cli -x 'Class.method()'
 *  
 *  Chamando como módulo do Simoa: -S
 *  php cli -S 'Site\Module.method(param1, param2)'
 *  php cli -S 'Site\Module.method()'
 *  php cli -S 'Module.method()'
 * 
 */


namespace Simoa;

class Cli
{
  var $namespace;
  var $class;
  var $method;
  var $params=[];

  function __construct($namespace, $argv, $config)
  {
    $this->namespace = $namespace;
    $this->argv = $argv;
    $this->config = $config;
    
    if (isset($argv[1])) {
      $method = str_replace("-", "", $argv[1]);
      if (method_exists($this, $method)) {
        $this->$method();
      } else {
        echo "method not found \n";
      }
    }
  }

  function fetch($cmd){
    preg_match('/(\\\\?\w+?\\\\?\w+)\.?(\w+)\((.+)?\)/', $cmd, $matches);
    $pos = strpos($matches[1], '\\');
    if ($pos) {
      $split = explode('\\', $matches[1]);
      $this->namespace = $split[0];
      $class = $split[1];
    } else {
      $class = $matches[1];
    }

    $this->site = strtolower($this->namespace);
    $this->module = strtolower($class);

    $this->class = $this->namespace.'\\'.$class;
    $this->method = trim($matches[2]);

    if (isset($matches[3])) {
      foreach(explode(",", $matches[3]) as $param){
        $this->params[] = trim($param);
      }
    }
  }

  function S()
  {
    if (!isset($this->argv[2])) {
      echo "Something is missing.. \n";
      exit;
    }

    $this->fetch($this->argv[2]);

    $c = $this->class;
    $m = $this->method;
    $p = $this->params;

    if (!class_exists($c)) {
      echo "Class $c not found \n";
      return ;
    }

    define("CLI", true);
    define("SITE", $this->site);
    define("MODULE", $this->module);
    define("METHOD", $this->method);

    $C = new $c($this->module);

    if (!method_exists($C, $m)) {
      echo "method $c::$m not found \n";
      return ;
    }

    $response = call_user_func_array([$C, $m], $p);

    debugg($response);
  }

  function x()
  {
    if (!isset($this->argv[2])) {
      echo "Something is missing.. \n";
      exit;
    }

    // if true, no orders will be executed
    $test = false;

    if (isset($this->argv[3])) {
      $test = (in_array($this->argv[3], array("test", "setup"))) ? true : $test ;
    }
    define("TEST", $test);

    if (TEST) {
      echo "TEST !! \n";
    }

    $this->fetch($this->argv[2]);

    $c = $this->class;
    $m = $this->method;
    $p = $this->params;

    if (!class_exists($c)) {
      echo "Class $c not found \n";
      return;
    }

    if ($this->config !== null) {
      $C = new $c((object)[
        "config" => $this->config
      ]);
    } else {
      $C = new $c();
    }

    if (!method_exists($C, $m)) {
      echo "method $c::$m not found \n";
      return;
    }

    call_user_func_array([$C, $m], $p);
  }
}
