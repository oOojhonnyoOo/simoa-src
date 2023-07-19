<?php

namespace Simoa;

use Exception;
use Error;

class API
{

  public $class;
  public Config $config;
  public Response $response;
  public $headers;
  public $request;

  function __construct()
  {
    if (!defined("CLI")) {
      define("CLI", false);
    }

    $this->config = new Config();
    $this->response = new Response();

    // site/module/method
    $this->getRequest();

    // http request headers
    $this->request();

    // load module configs 
    // this overrides site configs if they've got the same keys
    $this->config->module($this->headers->module);

    // load method configs
    // this overides module configs
    // also, this avoid to overried 'data' sent in request.
    $this->config->method($this->headers->method);

    $this->auth();

    // ecosystem call
    $this->system();

    // data parser
    $this->data();

    // parse data: validate, set indexing fields
    $this->parse();

    //parsing class info
    $this->parseClass();

    //auth based in content data
    $this->contentAuth();
  }

  function getRequest()
  {
    $request = Helper::requestURI();

    // backlog: substituir /(?:\/?api)?/ por verificação automática do host path da api
    preg_match(
      '/(?:\/?api)?\/?(\w+)\/(\w+)\/(\w+)\/?(\w+)?\/?(\w+)?\/?(\d+)?/',
      $request,
      $matches
    );

    if (empty($matches) || count($matches) < 4) {
      die("service unknown");
    }

    $this->headers = (object)[];
    $this->headers->site   = $matches[1];
    $this->headers->module = $matches[2];
    $this->headers->method = $matches[3];
    (isset($matches[4])) && ($this->headers->id = $matches[4]);
  }

  function request()
  {
    $this->request = (object)[];
    $http = (object)[];

    foreach (Helper::getallheaders() as $k => $v) {
      $key = str_replace('-', '', strtolower($k));
      $http->$key = $v;
    }

    (isset($http->token)) && ($this->request->token = $http->token);
    (isset($http->origin)) && ($this->request->origin = $http->origin);
    (isset($http->referer)) && ($this->request->referer = $http->referer);
    (isset($http->contenttype)) && $this->contentType($http->contenttype);
    (isset($http->key)) && ($this->request->key = $http->key);

    $this->config->env();
  }

  private function contentType($ct)
  {
    if (strpos($ct, "multipart/form-data") !== false) {
      $ct = "multipart/form-data";
    }
    $this->request->contenttype = $ct;
  }

  function auth()
  {
    if (!isset($this->config->auth)) {
      return true;
    }

    if ($this->config->auth) {
      if (!isset($this->request->token)) {
        $this->response->addHttpHeader('WWW-Authenticate: Bearer error="invalid_token", error_description="The access token was not provided"');
        $this->response->error('missingToken', 401);
        $this->response->echo();
        exit;
      }
      $this->token = new Token($this->config);

      if (!$this->token->data($this->request->token)) {
        $this->response->addHttpHeader('WWW-Authenticate: Bearer error="invalid_token", error_description="The access token provided is expired"');
        $this->response->error('expiredToken', 401);
        $this->response->echo();
        exit;
      }
      if (!$this->token->allowed()) {
        $this->response->error('denied', 403);
        $this->response->echo();
        exit;
      }
      $this->token->populateExtraInfo();
    }
  }

  function system()
  {
    if (isset($this->config->system)) {
      if ($this->config->system) {
        if (!isset($this->request->key)) {
          $this->response->addHttpHeader('WWW-Authenticate: Bearer error="invalid_key", error_description="The system key was not provided"');
          $this->response->error('missingKey', 401);
          $this->response->echo();
          exit;
        }

        if ($this->config->keys->system != $this->request->key) {
          $this->response->addHttpHeader('WWW-Authenticate: Bearer error="invalid_token", error_description="The access token was not provided"');
          $this->response->error('invalidKey', 401);
          $this->response->echo();
          exit;
        }
      }
    }
  }

  function parse()
  {
    $parse = new Parse($this);
    $this->Index = $parse->Index;

    if ($parse->hasErrors) {
      $this->response->errors($parse->errors);
      $this->response->echo();
      exit;
    }
  }

  function data()
  {
    if (isset($this->request->contenttype)) {
      if ($this->request->contenttype == "application/json") {

        $request = json_decode(file_get_contents("php://input"));
        if (empty($request)) {
          $request = (object)[
            "data" => (object)[]
          ];
        }

        $this->data = $request->data;
      } else {
        if (isset($_POST['data'])) {
          $request = json_decode($_POST['data']);
          $this->data = $request;
        } else {
          $this->response->error('unknowContentType')->echo();
          exit;
        }
      }
    }
  }

  function parseClass()
  {
    $n = ucfirst($this->headers->site);
    $c = ucfirst($this->headers->module);
    $m = $this->headers->method;
    $nc = $n . '\\' . $c;

    // Simoa \\ Class
    $sc = 'Simoa\\' . $c;
    $C =
      (class_exists($nc))
      ? new $nc($this)
      : ((class_exists($sc))
        ? new $sc($this)
        : new Module($this));

    $this->class = $C;
  }

  function contentAuth()
  {
    if (!$this->config->auth) {
      return;
    }

    $result = $this->token->dataAllowed($this, $this->class, $this->headers->method);

    if (!$result->success) {
      foreach ($result->errors as $error) {
        $this->response->error($error, 403);
      }
      $this->response->echo();
      exit;
    }
  }

  function execute()
  {
    try {
      $this->response = call_user_func_array([$this->class, $this->headers->method], []);
    } catch (Exception $e) {
      $this->response->errors(print_r($e, true), 503);
    } catch (Error $e) {
      $this->response->errors(print_r($e, true), 503);
    }

    $this->response->echo();
  }
}
