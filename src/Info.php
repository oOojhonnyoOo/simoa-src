<?php

namespace Simoa;

use Symfony\Component\Yaml\Yaml;

class Info extends Module
{

  // private $headers;
  private $yaml;
  var $module;

  function __construct($API)
  {
    parent::__construct($API);
    $this->init();
    $this->default();
  }

  function init()
  {
    $this->path = dirname(__DIR__, 3);
  }

  function default()
  {
    $this->yaml = Yaml::parseFile(
      $this->path . '/config.yml',
      Yaml::PARSE_OBJECT_FOR_MAP
    );

    if (isset($this->yaml->includes)) {
      foreach ($this->yaml->includes as $mt) {
        $this->yaml = (object) array_merge((array) $this->yaml, (array) Yaml::parseFile(
          $this->path . '/mt/' . $mt . '.yml',
          Yaml::PARSE_OBJECT_FOR_MAP
        ));
      }
    } else {
      if (is_dir($this->path . '/mt')) {
        $files = glob($this->path . '/mt/*.yml');

        foreach ($files as $mt) {
          $this->yaml = (object) array_merge((array) $this->yaml, (array) Yaml::parseFile(
            $mt,
            Yaml::PARSE_OBJECT_FOR_MAP
          ));
        }
      }
    }

    foreach ($this->yaml as $k => $v) {
      $this->$k = $v;
    }
  }

  function list($props = [])
  {
    $modules = [];
    $skip = ["info", "config"];
    $label = !empty($this->config->label) ? $this->config->label : $this->config->sitename;
    $modules['siteName'] = (object)["label" => $label];
    foreach ($this as $k => $v) {
      if (in_array($k, $skip)) {
        continue;
      }
      if (gettype($v) == 'object') {
        if (property_exists($v, "list()")) {
          $list = $v->{'list()'};

          $auth = (isset($list->auth)) ? $list->auth : $this->yaml->default->auth;
          $allow = (isset($list->allow)) ? $list->allow : $this->yaml->default->allow;
          $label = (isset($list->label)) ? $list->label : "";

          $modules[$k] = (object)[
            'module' => $k,
            'auth' => $auth,
            'allow' => $allow,
            'label' => $label
          ];
        }
      }
    }
    return $this->response->add('response', $modules);
  }
}
