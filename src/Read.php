<?php

namespace Simoa;

use Exception;
use Error;

class Read extends API{

  public function __construct()
  {
    parent::__construct() ; 
  }
  function getRequest(){
    $request = RequestURI();
    
    // backlog: substituir /(?:\/?api)?/ por verificação automática do host path da api
    preg_match(
      '/(\w+)\/(\w+)\/(\S+)?\/?(\w+)?\/?(\w+)?\/?(\d+)?/',
      $request,
      $matches
    );
    
    if(empty($matches) || count($matches) < 3){
      die("service unknown");
    }
    
    $this->headers = (object)[];
    $this->headers->site   = $matches[1];
    $this->headers->module = $matches[2];
    $this->headers->method = "read";
    // $this->headers->asset = $matches[3];
    $this->headers->asset = (isset($matches[3])) ? $matches[3] : "index";
    //  && ($this->headers->method = $matches[3]);
    // (isset($matches[4])) && ($this->headers->id = $matches[4]);
    // (isset($matches[5])) && ($this->headers->status = $matches[5]);
    // (isset($matches[6])) && ($this->headers->time = $matches[6]);

    //read preview
    if($matches[1] == "preview"){
      $matches = explode("/", $request);

      $this->headers->site   = $matches[1];
      $this->headers->module = $matches[2];
      $this->headers->method = "readPreview";
      $this->headers->asset = (isset($matches[3])) ? $matches[3] : "index";
    }
    
    ($this->public()) && exit;
    
  }

  function public(){
    $s = $this->headers->site;
    $m = $this->headers->module;
    $asset = $this->headers->asset;
    $public = $this->config->public."/$m/$asset";
    foreach(['.json', '.html', '.php'] as $ext){
      $file = $public."$ext";
      if(file_exists($file)){
        include($file);
        return true;
      }

      if (file_exists($this->config->path . "/public/$m/".$asset.$ext)) {
        include($this->config->path . "/public/$m/".$asset.$ext);
        return true;
      }
    }
    return false;
  }
}

?>