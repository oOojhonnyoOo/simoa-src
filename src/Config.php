<?php

namespace Simoa;

use Symfony\Component\Yaml\Yaml;

class Config
{
  private $yaml;
  public $module;

  public string $path;
  public string $public;
  public string $private;
  public string $preview;
  public string $data;
  public string $history;
  public string $url;

  // comes from default
  public $sitename = null;
  public $aud = null;
  public $label = null;
  public $env = null;
  public $solr = null;
  public $formats = null;
  public $cache = null;
  public $auth = null;
  public $allow = null;
  public $fl = null;
  public $index = null;
  public $keys = null;
  public $smtp = null;
  public $s3 = null;

  // comes from paths
  public $server;
  public $client;
  public $_env;
  public $config;

  public $schema;
  public $status;

  function __construct()
  {
    $this->init();
    $this->default();
    $this->env();
    $this->paths();
  }

  function init()
  {
    $this->path = getenv("PATH_FOLDER") ?? dirname(__DIR__, 3);
    $this->public = $this->path . "/public";
    $this->private = $this->path . "/private";
    $this->preview = $this->path . "/preview";
    $this->data = $this->path . "/.simoa/.data";
    $this->history = $this->path . "/.simoa/.history";
    $this->url = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : "";
  }

  function env()
  {
    $env = trim(file_get_contents($this->path . "/.env"));
    $this->server = $this->env->$env->server;
    $this->client = $this->env->$env->client;
    $this->_env = $env;
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

    foreach ($this->yaml->default as $k => $v) {
      $this->$k = $v;
    }
  }

  function paths()
  {
    $e = $this->_env;
    if (isset($this->paths->{$e})) {
      foreach ($this->paths->{$e} as $k => $v) {
        $this->$k = $v;
      }
    }
  }

  function module($m)
  {
    $this->module = $m;
    if (isset($this->yaml->$m)) {
      foreach ($this->yaml->$m as $k => $v) {
        $this->$k = $v;
      }
    }
  }

  // tá mais ou menos duplicado com relação
  // ao Module::formats(). Estudar refactory
  function formats($headers, $data)
  {
    if (isset($this->formats)) {
      $fd = $this->formats->data;

      preg_match_all('/\[(\w+)]/', $fd, $matches);

      foreach ($matches[1] as $i => $k) {

        $v = (isset($headers->$k))
          ? $headers->$k
          : (
            (isset($data->$k))
            ? ($data->$k)
            : $matches[0][$i]
          );

        $fd = str_replace($matches[0][$i], $v, $fd);
      }

      $this->formats->data = $fd;
    }
  }

  function method($method)
  {
    $m = $this->module;
    $method = $method . '()';
    if (isset($this->yaml->$m->$method)) {
      foreach ($this->yaml->$m->$method as $k => $v) {
        if ($k != 'data') {
          $this->$k = $v;
        }
      }
    }
  }

  public function __defaultSolr()
  {
    return $this->yaml->default->solr;
  }
}
