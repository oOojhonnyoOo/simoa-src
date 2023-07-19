<?php

namespace Simoa;

class Module extends Controller
{
  var $config;
  var $headers;
  var $data;

  public $class;
  public $response;
  public $request;
  public $Index;

  // (object) $API  OR  (string) $module
  function __construct($init = null)
  {
    if ($init === null) {
      preg_match('/\w+\\\\(\w+)/', get_called_class(), $matches);
      if (count($matches) > 1) {
        $init = strtolower($matches[1]);
      }
    }

    if (is_object($init)) {
      $this->fill($init);
      $this->formats();
    } else {
      if (is_string($init)) {
        $this->module($init);
        if (empty($this->data)) {
          $this->data = (object)[];
        }
      }
    }
  }

  public function forceUniqueSlug()
  {
    // slug único
    if (empty($this->data->id)) { // primeira vez que tá salvando, cria o slug
      $result = parent::save();
      // campo é readOnly e não veio na request OU foi criado id e não está no slug enviado.
      if (!isset($this->data->slug) || $result->success && !preg_match('/^\d+?_/', $this->data->slug)) {
        $slug = slugify($this->data->title);
        $this->data->slug = "{$result->response->id}_{$slug}";
      }
    }

    if (!isset($this->data->slug) || empty($this->data->slug)) {
      $slug = slugify($this->data->title);
      $this->data->slug = "{$this->data->id}_{$slug}";
    }
  }

  function save()
  {
    if (isset($this->config->forceUniqueSlug)) {
      ($this->config->forceUniqueSlug) && $this->forceUniqueSlug();
    }

    return parent::save();
  }

  function fill($o)
  {
    foreach ($o as $k => $v) {
      $this->$k = $v;
    }
  }

  function module($m)
  {
    $this->config = new Config();
    $this->config->module($m);

    $this->headers = (object)[
      'site' => $this->config->sitename,
      'module' => $m,
      'method' => ''
    ];

    // CLI
    if (defined('METHOD')) {
      $this->method(METHOD);
    }

    $this->response();

    parent::__construct($this);
  }

  function parse($method)
  {
    $this->method($method);
    $Parse = new Parse($this);
    $this->Index = $Parse->Index;

    if ($Parse->hasErrors) {
      $this->response->errors($Parse->errors);
    }
  }

  function response()
  {
    $this->response = new Response();
  }

  function data($data)
  {
    if (empty($this->data)) {
      $this->data = (object)[];
    }
    foreach ($data as $k => $v) {
      $this->data->$k = $v;
    }
  }

  function method($method)
  {
    $this->headers->method = $method;
    $this->config->method($method);
    $this->formats();
  }

  function formats()
  {
    $this->config->formats($this->headers, $this->data);
  }

  function info()
  {
    $methods = [];
    foreach ($this->config as $k => $v) {
      if (strpos($k, '()')) {
        $k = str_replace('()', '', $k);
        if (strpos($k, '__') !== 0) {
          $methods[$k] = $v;
        }
      }
    }

    // replacing when data = schema
    foreach ($methods as $k => $item) {
      if (is_object($item) && isset($item->data) && $item->data == "schema") {
        if (isset($this->config->schema)) {
          $methods[$k] = $this->config->schema;
        }
      }
    }

    return $this->response->add('API', $methods);
  }
}
