<?php

namespace Simoa;

class Proxy
{
  public $headers;
  public $request;
  public $data;
  public $url;
  public $headersCurl;

  function __construct()
  {
    $this->config();
    $this->request();
    $this->getRequest();
    $this->response();
    $this->url();
    $this->data();
    $this->curl();
  }

  private function data()
  {

    if(isset($this->request->contenttype)){
      if($this->request->contenttype == "application/json"){
        $request = file_get_contents("php://input"); 
        $this->data = $request;
      } else {
        if (isset($_POST)) {
          $this->data = $_POST;
        }      

        if (!empty($_FILES)) {
          $key = key($_FILES);
          $curlFile = new \CURLFile($_FILES[$key]['tmp_name'],$_FILES[$key]['type'],$_FILES[$key]['name']);
          $this->data[$key] = $curlFile;
        }
      }
    }
  }

  private function getRequest()
  {
    $request = Helper::requestURI();

    preg_match(
      '/(?:\/?proxy)?\/?(\w+)\/(\w+)\/(\w+)\/?(\w+)?\/?(\w+)?\/?(\d+)?/',
      $request,
      $matches
    );

    if (empty($matches) || count($matches) < 4) {
      die("service unknown");
    }

    $this->headers = (object)[];
    $this->headers->site   = $matches[1];
  }

  private function request()
  {
    $this->request = (object)[];

    $this->headersCurl = [];
    foreach (Helper::getallheaders() as $k => $v) {
      //content-type é formatado depois
      if ($k != "Content-Length" && $k != "Content-Type") {
        $this->headersCurl[] = $k . ": " . $v;
      }

      if ($k == "Content-Type") {
        $this->contentType($v);
      }
    }
  }

  private function contentType($ct)
  {

    if (strpos($ct, "multipart/form-data") !== false) {
      $ct = "multipart/form-data";
    }

    $this->request->contenttype = $ct;
    $this->headersCurl[] = "Content-Type: ". $ct;
  }

  private function config()
  {
    $this->config = new Config();
    $this->config->env();
  }

  private function url()
  {
    $s = $this->headers->site;

    if (isset($this->config->server->$s)) {
      $this->url = $this->config->server->$s . str_replace("proxy/", "/api/", Helper::requestURI());
    } else {
      $this->response->error('invalidUrl')->echo();
      exit;
    }

    //env local docker network
    if ($this->config->_env == "local") {
      $this->url = str_replace(".local:", ":", $this->url);
    }

    $this->url.= (!empty($_SERVER['QUERY_STRING'])) ? "?". $_SERVER['QUERY_STRING'] : "";
  }
  
  private function response()
  {
    $this->response = new Response();
  }

  private function curl()
  {
    $curl = curl_init();

    curl_setopt_array($curl, 
      array(
        CURLOPT_URL => $this->url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $this->data,
        CURLOPT_HTTPHEADER => $this->headersCurl,

        CURLOPT_HEADERFUNCTION => 
          function($curl, $header) use (&$responseHeaders)
          {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
              return $len;

            $responseHeaders[trim($header[0])][] = trim($header[1]);

            return $len;
          }
    ));

    $response = curl_exec($curl);
  
    if (curl_errno($curl)) {
      $this->response->error('proxy: ' . curl_error($curl))->echo();
      exit;
    }

    $info = curl_getinfo($curl);

    //add headers to response
    foreach ($responseHeaders as $k => $v) {
      if ($k != "Transfer-Encoding") {
        header($k . ": '" . $v[0]."'");
      }
    }

    //set response status code
    http_response_code($info['http_code']);

    curl_close($curl);
    echo $response;
  }
}
