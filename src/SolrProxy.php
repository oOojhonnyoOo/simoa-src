<?php

namespace Simoa;

class SolrProxy{

  /**
   * $core (string) - solr core name
   */
  function __construct($core = "default")
  {
    $this->core = $core;

    $this->config();

    $this->response();
  }

  /**
   * request query solr
   */
  public function request()
  {
    if($this->checkAPIKey()){
      $q = $_SERVER['QUERY_STRING'];
      echo file_get_contents($this->config->solr->{$this->core} . "/select/?" . $q);
    }else{
      $this->response->error('forbidden',403);
      $this->response->echo();
    }
  }

  /**
   * set configs (config.yml)
   */
  private function config()
  {
    $this->config = new Config();
  }

  /**
   * set response object
   */
  private function response()
  {
    $this->response = new Response();
  }

  /**
   * check solr publicapi key in config
  */
  private function checkAPIKey()
  {
    //obter os headers da requisição 
    $headers = Helper::getallheaders();
 
    return ( !empty($headers["key"]) && $headers["key"] == $this->config->keys->publicsolr );
  }

}