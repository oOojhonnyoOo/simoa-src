<?php

namespace Simoa;

class SiteAPI
{

  var $site;
  var $endpoint;
  var $token;
  var $key;
  var $type = 'POST';
  var $config;

  function __construct($props = [])
  {
    $props = (object) $props;
    foreach ($props as $k => $v) {
      $this->$k = $v;
    }
    (empty($this->config)) && $this->config();
  }

  function config()
  {
    $this->config = new Config();
  }

  function call($site = null, $endpoint = null, $token = null, $key = null, $type = null, $data = [])
  {
    $site = ($site === null) ? $this->site : $site;
    $endpoint = ($endpoint === null) ? $this->endpoint : $endpoint;
    $token = ($token === null) ? $this->token : $token;
    $key = ($key === null) ? $this->key : $key;
    $type = ($type === null) ? $this->type : $type;
    $data = ($data === []) ? ((isset($this->data) ? $this->data : [])) : $data;

    $endpoint = str_replace('[', urlencode('['), $endpoint);
    $endpoint = str_replace(']', urlencode(']'), $endpoint);

    $url = $this->config->server->$site;
    $url .= "/$endpoint";

    $Curl = new Curl([
      'url' => $url,
      'headers' => [
        'token' => $token,
        'key' => $key,
        'Content-Type' => 'application/json'
      ],
      'dataRaw' => (object)[
        "data" => (object) $data
      ]
    ]);
    $decoded =  json_decode($Curl->exec());
    if (json_last_error()) {
      return (object)["success" => false, "message" => json_last_error_msg(), "response" => null];
    }
    return $decoded;
  }





  // $Curl = new Simoa\Curl();
  // $login = json_decode($Curl->exec(
  //   "curl -k --location --request GET 'https://ava-nda2.simoa.dev:8300/check_password/?cpf={$this->data->cpf}&password={$this->data->password}' \
  //   --header 'token: abc321'")
  // );

  // $url = $this->config->server->$site."/api/$site/users/tokenInfo";

  // //url: http://ava.conasems.local:8204
  // $curlString = "curl --location --request POST '".$url."' \
  // --header 'token: ".$token."' \
  // --header 'Content-Type: application/json' \
  // --data-raw '{
  //     \"data\": {}
  // }'" ;
  // $result = shell_exec($curlString) ;
}
