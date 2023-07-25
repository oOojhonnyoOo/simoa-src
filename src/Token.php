<?php

namespace Simoa;

use \Firebase\JWT\JWT;
use Simoa\Helper;

class Token
{
  var $config;
  var $data;

  var $allows = [];
  var $objAllows = [];
  var $parsedAllows = false;

  function __construct($config)
  {
    $this->config = $config;
  }

  protected function hashString($str)
  {
    return hash_hmac(
      'SHA1',
      $str,
      $this->config->keys->default
    );
  }

  protected function getExtraInfo($tokenId)
  {
    if (!$tokenId) {
      return [];
    }

    $hashedToken = $this->hashString($tokenId);
    //server cached info
    $url = $this->config->server->conta . "/token/$hashedToken.json";

    return getUrlContent($url, []);
  }

  function populateExtraInfo()
  {
    $dataToken = $this->getExtraInfo(Helper::filterData($this->data, "data.tokenId"));
    $this->data->data->extraInfo = [];

    if ($dataToken && isset($dataToken->extraInfo)) {
      $this->data->data->extraInfo = $dataToken->extraInfo;
    }

    return true;
  }

  function gen($data)
  {
    $this->data = $data;

    $time = time();
    $array = [
      'iss' => $this->config->sitename,
      'aud' => $this->config->aud,
      'iat' => $time,
      'nbf' => $time,
      'exp' => $time + (60 * $this->config->minutes),
      'sub' => $this->data->username,
      'data' => [
        'id' => $this->data->uid,
        'cpf' => $this->data->cpf,
        'username' => $this->data->username,
        'roles' => $this->data->roles,
        'fullname' => $this->data->fullname
      ]
    ];

    if (isset($this->data->tokenId)) {
      $array['data']['tokenId'] = $this->data->id;
    }
    
    return JWT::encode(
      $array,
      $this->config->keys->token,
      "HS256"
    );
  }

  function data($token)
  {
    $this->data = $this->read($token);
    return ($this->data !== false) ? true : false;
  }

  function read($token)
  {
    if (count(explode(".", $token)) != 3) {
      return false;
    }

    try {
      $response = JWT::decode(
        $token,
        $this->config->keys->token,
        ['HS256']
      );
    } catch (\Firebase\JWT\ExpiredException $e) {
      return false;
    }

    return $response;
  }

  function allowed()
  {
    if (in_array('all', $this->config->allow)) {
      return true;
    }

    if (in_array('root', $this->data->data->roles)) {
      return true;
    }

    $this->asterisks();
    $this->parseAllows();
    $count = count(array_intersect(
      $this->data->data->roles,
      $this->allows
    ));

    if ($count > 0) {
      return true;
    }

    $allows = Helper::filterData($this->objAllows, "role", function ($d) {
      //transforma o que veio em um regex para que o asterisco sirva pra qualquer curso
      $allowRegex = "|" . str_replace("/", "\/", str_replace("*", "[0-9a-zA-Z_-]+?", $d)) . "|";

      foreach ($this->data->data->roles as $r) {
        if (preg_match($allowRegex, $r)) {
          return $d;
        }
      }

      return null;
    });

    if (count((array)$allows) <= 0) {
      //não tem permissão dentre os tipos objeto tb
      return false;
    }

    return true;
  }

  /**
   *  Atualmente isso está:
   *    adicionando ao token->config->allow
   *    todos os cursos que o usuário tenha
   *    quando o método tem uma permissão do tipo \/site\/*\/role
   *  
   */
  function asterisks()
  {

    // todo: "^*/"
    // todo: "/*$"

    // "/*/"
    $allow = $this->config->allow;

    foreach ($allow as $p) {
      if (!is_string($p)) {
        continue;
      }

      if (strpos($p, "/*/") !== false) {
        $a = explode('/', $p);
        $s = $a[0];
        $r = $a[2];

        foreach ($this->data->data->roles as $up) {
          $ua = explode('/', $up);
          if (count($ua) > 2) {
            $us = $ua[0];
            $um = $ua[1];
            $ur = $ua[2];

            if ($s == $us && $r == $ur) {
              $this->config->allow[] = $s . "/" . $um . "/" . $r;
            }
          }
        }
      }
    }
  }

  protected function parseValue($API, $path, $class)
  {
    $valResult = Helper::filterData($API, $path);

    if (empty($valResult)) {
      $valResult = Helper::parseValue($path);
      if ($valResult == $path) {
        if (preg_match("/([a-zA-Z0-9_]+\(\))\.?([a-zA-Z0-9_.]*)/i", $path, $matchMethod)) {
          $m = str_replace("()", "", $matchMethod[1]);
          $valResult = call_user_func_array(array($class, $m), array());
          if (isset($matchMethod[2]) && !empty($matchMethod[2])) {
            $valResult = Helper::filterData($valResult, $matchMethod[2]);
          }
        }
      }
    }

    return $valResult;
  }

  function dataAllowed($API = null, $class = null, $method = null)
  {
    $result = (object)[
      "success" => false,
      "errors" => []
    ];
    //root pass

    if (array_intersect(['root', 'ava/admin'], $this->data->data->roles)) {
      $result->success = true;
      return $result;
    }
    if (in_array('all', $this->config->allow)) {
      $result->success = true;
      return $result;
    }
    $this->parseAllows();
    //verificando se tem dados a se comparar 
    $allowRules = Helper::filterData($this->objAllows, "rules", function ($d) {
      return ($d != "") ? $d : null;
    });

    if (count((array)$allowRules) <= 0) {
      //criou o allow e passou, mas não tem comparação a fazer é true
      $result->success = true;
      return $result;
    }

    $API->_get = $_GET;
    $API->_post = $_POST;
    $result->success = true;

    //agora verificando se os dados batem
    foreach ($this->objAllows as $ruleInfo) {
      $allowRule = $ruleInfo->rules;
      $labelOnError = isset($ruleInfo->error) ? $ruleInfo->error : "notAllowedFromData";
      //fazendo os testes que estao no rules
      preg_match("/([\"'\[\]a-zA-Z0-9,\(\)_.]+) +?(in|out|[!=><]+) +?([\"'\[\]a-zA-Z0-9\(\),_.]+)/i", $allowRule, $match);
      if (count($match) < 4) {
        continue;
      }

      $path1 = trim($match[1]);
      $operator = $match[2] == "=" ? "==" : $match[2];
      $path2 = trim($match[3]);

      $val1 = $this->parseValue($API, $path1, $class);
      $val2 = $this->parseValue($API, $path2, $class);
      $compareResult = Helper::compare($val1, $val2, $operator);
      if (!$compareResult) {
        $result->success = false;
        $result->errors[] = "error:$labelOnError";
      }
    }
    
    return $result;
  }

  protected function parseAllows()
  {
    if ($this->parsedAllows) {
      return;
    }
    foreach ($this->config->allow as $item) {
      if (is_string($item)) {
        $this->allows[] = $item;
        continue;
      }
      $this->objAllows[] = $item;
    }
    // chegou até aqui é pq não tem a permissão direta
    // verificando permisão de objetos com funções
    $this->parsedAllows = true;
  }

  function renew()
  {
  }

  function valid()
  {
  }
}
