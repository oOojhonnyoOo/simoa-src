<?php

namespace Simoa;

use Solr;

abstract class Controller
{

  var $fs;
  var $cache;
  var $keepStatusOnSave = true;

  function __construct()
  {
  }

  public function __solrDeleteByQuery($query)
  {
    return (new Solr\Document($this->config->solr->default))->deleteByQuery($query);
  }

  # useful when changing a field type
  # running that avoid DOCS_AND_FREQS_AND_POSITIONS solr exception
  function __solrReindexByQuery($q)
  {
    $Solr = new Solr\Query($this->config->solr->default);
    $Solr->q = $q;
    $response = $Solr->query()->response;

    $_docs = [];
    $tmp = $this->config->data . "/tmp/" . $this->headers->module;

    foreach ($response->docs as $doc) {

      unset($doc->_version_);
      $_docs[] = $doc;

      if (count($_docs) == 10000) {
        $Batch = new Solr\Batch($this->config->solr->default);
        $Batch->index($_docs, $tmp);
        $_docs = [];
      }
    }

    $Batch = new Solr\Batch($this->config->solr->default);
    $Batch->index($_docs, $tmp);
  }

  public function __reset()
  {
    $this->__solrDeleteByQuery('module:' . $this->headers->module);
    $path = $this->config->data
      . "/" . $this->headers->site
      . "/" . $this->headers->module;
    $files = glob($path . "/*.json");
    if (count($files) > 0) {
      shell_exec('find ' . $path . '/ -name "*.json" -print0 | xargs -0 rm');
    }
    $Counter = new Counter();
    return $Counter->reset([
      'site' => $this->headers->site,
      'module' => $this->headers->module,
      'data' => $this->config->data
    ]);
  }

  /**
   *  WIP
   *  query solr with preset defaults core, site and module
   */
  public function query($props = [])
  {
    $props = (object) $props;
    $Solr = new Solr\Query($this->config->solr->default);

    if (property_exists($props, 'group')) {
      $Solr->group =  $props->group;
    }

    if (property_exists($props, 'groupField')) {
      $Solr->groupField =  $props->groupField;
    }

    if (property_exists($props, 'groupLimit')) {
      $Solr->groupLimit =  $props->groupLimit;
    }

    if (property_exists($props, 'groupSort')) {
      $Solr->groupSort =  $props->groupSort;
    }

    $Solr->q = '(site:' . $this->headers->site . ')';
    $Solr->q .= ' AND (module:' . $this->headers->module . ')';

    if (property_exists($props, 'q')) {
      $Solr->q .= ' AND (' . $props->q . ')';
    }

    if (property_exists($props, 'fq')) {
      $Solr->fq .= $props->fq;
    }

    if (property_exists($props, 'start')) {
      $Solr->start = $props->start;
    }

    if (property_exists($props, 'rows')) {
      $Solr->rows = $props->rows;
    }

    if (property_exists($props, 'fl')) {
      $Solr->fl = $props->fl;
    }

    if (property_exists($props, 'sort')) {
      $Solr->sort = $props->sort;
    }

    if (property_exists($props, '_or')) {
      $_or = [];

      foreach ($props->_or['array'] as $v) {
        $_or[] = $props->_or['key'] . ":" . $v;
      }

      $Solr->q .= ' AND (' . $Solr->_or($_or) . ')';
    }

    // debugg($Solr); exit;

    // return $Solr;

    return $Solr->query();
  }

  public function hint()
  {
    $Search = new Search($this);
    $Search->hint = true;
    return $this->response->log($this->filter($Search->query()));
  }

  public function search()
  {
    $Search = new Search($this);
    return $this->response->log($this->filter($Search->query()));
  }

  public function list()
  {
    $Search = new Search($this);
    return $this->response->add('response', $this->filter($Search->query()));
  }

  public function downloadCsv()
  {
    header('Cache-Control: max-age=0');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $this->headers->module . '.csv";');
    $output = fopen('php://output', 'w');

    $hasItems = true;

    $props = (object)[
      "start" => 0,
      "rows" => 10000
    ];

    $Search = new Search($this);
    $result = $this->filter($Search->query($props));

    if (isset($result->docs[0])) {
      $header = array_keys((array)$result->docs[0]);
      fputcsv($output, $header, ',');
    }

    while ($hasItems) {
      foreach ($result->docs as $doc) {
        $line = [];
        foreach ($doc as $k => $v) {
          if (is_array($doc->{$k})) {
            $line[] = implode(";", $v);
          } else {
            $line[] = $v;
          }
        }
        fputcsv($output, $line, ',');
      }

      if ($result->numFound < $props->start) {
        $hasItems = false;
      } else {
        $props->start += $props->rows;
        $result = $this->filter($Search->query($props));
      }
    }
    exit();
  }

  public function solrFindOne($q, $fl = "*")
  {
    $response = $this->query([
      'q' => $q,
      'rows' => 1,
      'fl' => $fl
    ])->response;

    if ($response->numFound > 0) {
      return $response->docs[0];
    }

    return false;
  }

  // public function list($props=[]){
  //   // vai entrar o código aqui
  //   return $this->response->log(
  //     $this->filter(
  //       $this->query($props)->response
  //     )
  //   );
  // }

  function findLast($q = "id:[* TO *]")
  {
    $response = $this->query([
      'q' => $q,
      'rows' => 1,
      'fl' => '_data',
      'sort' => 'sortDate DESC'
    ])->response;

    if ($response->numFound > 0) {
      return json_decode(file_get_contents($response->docs[0]->_data))->data;
    }

    return false;
  }

  // encontra um registro por query.
  // recebe q de solr
  // retorna o doc encontrado, ou false.
  protected function findOne($q)
  {
    $response = $this->query([
      'q' => $q,
      'rows' => 1,
      'fl' => '_data'
    ])->response;

    if ($response->numFound > 0) {
      return json_decode(file_get_contents($response->docs[0]->_data))->data;
    }

    return false;
  }

  function arrayAdd($k, $v)
  {
    if (is_array($this->data->$k)) {
      $this->data->$k[] = $v;
    }
  }

  /**
   * Recebe um objeto ou array e retorna um solr query string sendo key1:value1 key2:value2
   */
  private function objectToSolrQuery($obj)
  {
    $query = "";
    if ($obj != null && (is_object($obj) || is_array($obj))) {
      $array = [];
      foreach ($obj as $key => $value) {
        $array[] = $key . ":" . $value;
      }
      $query = implode(" AND ", $array);
    }
    return $query;
  }
  /**
   * obtém o registro anterior e só atualiza as propriedades que forem enviadas
   * mantendo as anteriores.
   * $S
   *
   */
  protected function findOneAndUpdate($solrQueryOrObject, $props = null)
  {
    $q = $solrQueryOrObject;

    if (!is_string($solrQueryOrObject)) {
      $q = $this->objectToSolrQuery($solrQueryOrObject);
    }
    if ($props === null) {
      $props = $this->data;
    }

    // acha o doc e atualiza nele primeiro
    $doc = $this->findOne($q);
    if ($doc) {
      foreach ($props as $k => $v) {
        $doc->$k = $v;
      }
    } else {
      $doc = $props;
    }

    // pra depois trazer pro this->data
    foreach ($doc as $k => $v) {
      $this->data->$k = $v;
    }
  }



  public function populate()
  {
    $id = null;
    if (isset($this->headers->id)) {
      $id = $this->headers->id;
    }
    if (!$id && isset($this->data->id)) {
      $id = $this->data->id;
    }

    if(isset($this->config->saveS3)){
      $bucket = $this->config->saveS3->bucket;
      $region = $this->config->saveS3->region;
      $bucketUrl = "https://".$bucket.".s3.".$region.".amazonaws.com/";
      $path = $bucketUrl.$this->headers->site."/".$this->headers->module."/".$id.".json";
      $path = str_replace($bucketUrl, "", $path);

      $UploadS3 = new UploadS3((object)[
        "profile" => "s3",
        "region" => $region,
        "bucket" => $bucket
      ]);
      $publicUrl = $UploadS3->getPrivateObjectS3($path);

      $headers = get_headers($publicUrl);
      $status_code = $headers[0];
      if(strpos($status_code,"200")){
        $data = json_decode(file_get_contents($publicUrl));
        unset($data->data->password);
        return $this->response->add('response', $data);
      }
    }

    $response = $this->query([
      'q' => 'id:' . $id,
      'rows' => 1,
      'fl' => '_data'
    ])->response;

    if ($response->numFound > 0) {
      $file = $response->docs[0]->_data;

      if(file_exists($file)){
        $data = json_decode(file_get_contents($file));
        unset($data->data->password);
        return $this->response->add('response', $data);
      }
    }

    return $this->response->error('assetNotFound');
  }

  protected function setStartDate()
  {
    if (isset($this->data)) {
      if (!isset($this->data->startDate) || empty($this->data->startDate)) {
        $this->data->startDate = date("Y-m-d") . "T" . date("H:i:s") . "Z";
      }
    }
  }

  protected function keepStatus()
  {
    if (!$this->keepStatusOnSave) {
      return;
    }
    $id = isset($this->headers->id) ? $this->headers->id : null;
    if (!$id && isset($this->data->id)) {
      $id = $this->data->id;
    }
    if (!isset($this->data->_status)) {
      $this->data->_status = [];
    }
    if ($id) {
      $tempData = $this->findOne('id:' . $id);
      if (isset($tempData->cache) && !isset($this->data->cache)) {
        $this->data->cache = $tempData->cache;
      }
      if (isset($tempData->request) && !isset($this->data->request)) {
        $this->data->request = $tempData->request;
      }

      if (isset($tempData->preview) && !isset($this->data->preview)) {
        $this->data->preview = $tempData->preview;
      }

      if (isset($tempData->previewRequest) && !isset($this->data->previewRequest)) {
        $this->data->previewRequest = $tempData->previewRequest;
      }

      if (isset($tempData->url) && !isset($this->data->url)) {
        $this->data->url = $tempData->url;
      }
      if (isset($tempData->_status)) {
        //apenas para retrocompatibilidade
        if (is_string($tempData->_status)) {
          $this->data->_status = [$tempData->_status];
        }
        if (is_array($tempData->_status)) {
          $this->data->_status = array_merge($tempData->_status, $this->data->_status);
        }
      }
    }
    if (!is_array($this->data->_status)) {
      $this->data->_status = (array) $this->data->_status;
    }
    $this->data->_status[] = "draft";
    $this->data->_status = array_values(array_unique($this->data->_status));
  }

  public function save()
  {
    // se não tem id, atribui.
    // $this->id(); // está sendo executado dentro de fileSystemSave();

    //adicionar status draft
    $this->keepStatus();
    $this->setStartDate();

    $this->fs = $this->fileSystemSave();

    //salva no s3 e apaga do local
    if(isset($this->config->saveS3)){
      $profile = 'saveS3';
      $environment = isset($this->config->{$profile}->environment) ? $this->config->{$profile}->environment : '';
      if (($environment == $this->config->_env) && !$this->saveDataToS3($this->fs->_data, $profile)) {
        $this->response->error('saveS3Error', 500);
      }
    }

    if(isset($this->fs->_history) && !empty($this->fs->_history)){
      $profile = 'historyS3';
      $environment = isset($this->config->{$profile}->environment) ? $this->config->{$profile}->environment : '';
      if (($environment == $this->config->_env) && !$this->saveDataToS3($this->fs->_history, $profile)) {
        $this->response->error('historyS3Error', 500);
      }
    }

    if (!$this->fs->success) {
      return $this->response->error('fs', 500);
    }

    if (!$this->solrIndex()) {
      return $this->response->error('solr', 500);
    }

    return $this->response->add('id', $this->headers->id);
  }

  function saveDataToS3($path, $profile)
  {
    $peacesfilePath = explode('/',$path);
    $filename = end($peacesfilePath);
    $result = $this->fileuploadByCli($path, $filename, "private", $profile);
    if($result->success){
      $json = json_decode(file_get_contents($result->response->publicUrl));
      if (json_last_error()) {
        return $this->response->error('S3UploadError', 500);
      }

      // apaga arquivo no local
      $cmd = 'rm '. $path;
      $return = shell_exec($cmd);
      unset($this->response->response->filename);
      unset($this->response->response->url);
      unset($this->response->response->publicUrl);
    }
    return true;
  }

  public function fssave()
  {
    return $this->fileSystemSave();
  }

  public function reindexOne($id)
  {
    $path = $this->config->data
      . "/" . $this->headers->site
      . "/" . $this->headers->module;

    $item = $path . "/" . $id . $this->config->formats->extension;
    $doc = json_decode(file_get_contents($item));
    $SolrIndex = new SolrIndex($doc);
    $response = $SolrIndex->commit();

    return $this->response->add('response', $response);
  }

  public function reindex($start = 0)
  {
    $path = $this->config->data
      . "/" . $this->headers->site
      . "/" . $this->headers->module;


    $files = glob($path . "/*.json");

    $docs = [];
    $batchLimit = 1000;
    $tmp = $this->config->data . "/tmp/" . $this->headers->module;

    for ($i = $start; $i < count($files); $i++) {
      $item = $files[$i];
      $doc = json_decode(file_get_contents($item));

      if (!isset($doc->headers)) {
        $doc->headers = $this->headers;
        $doc->headers->id = $doc->data->id;
      }

      $_doc = new Module($doc->headers->module);
      $_doc->headers->id = $doc->headers->id;
      $_doc->headers->method = 'save';
      $_doc->data = $doc->data;
      $_doc->parse('save');

      $SolrIndex = new SolrIndex($_doc);
      $docs[] = $SolrIndex->setup();

      if (count($docs) == $batchLimit) {
        $Batch = new Solr\Batch($this->config->solr->default);
        $Batch->index($docs, $tmp);
        $docs = [];
      }
    }

    $Batch = new Solr\Batch($this->config->solr->default);
    $Batch->index($docs, $tmp);
  }

  public function publish()
  {
    $this->data = $this->findOne('id:' . $this->headers->id);

    $this->data->_status[] = 'published';

    // template cache
    $this->templateCache();

    $this->data->_status = array_unique($this->data->_status);
    $key = array_search('preview', $this->data->_status);

    if ($key !== false) {
      unset($this->data->_status[$key]);
      $this->data->_status = array_values($this->data->_status);
    }

    if (isset($this->data->preview)) {
      if (file_exists($this->data->preview)) {
        unlink($this->data->preview);
      }
      unset($this->data->preview);
    }

    if (isset($this->data->previewRequest)) {
      unset($this->data->previewRequest);
    }

    // then save
    $this->parse('save');
    $this->keepStatusOnSave = false;
    return $this->save();
  }

  public function unpublish()
  {
    /**
     * remover:
     *  cache
     *  url
     *  request
     *
     * deletar o arquivo físico do cache - done
     *
     * remover o status published - done
     *
     * save ao final
     *
     */
    $this->data = $this->findOne("id:" . $this->headers->id);

    if (!isset($this->data->cache)) {
      return (object)["success" => true];
    }
    if (file_exists($this->data->cache)) {
      unlink($this->data->cache);
    }

    $this->data->cache = "";

    if (isset($this->data->url)) {
      $this->data->url = "";
    }
    if (isset($this->data->request)) {
      $this->data->request = "";
    }
    $this->data->_status = ["draft"];
    $this->keepStatusOnSave = false;
    //then save
    $this->parse('save');
    return $this->save();
  }
  abstract function parse($fnName);

  public function preview()
  {

    $this->data = $this->findOne('id:' . $this->headers->id);

    $this->data->_status[] = 'preview';
    $this->data->_status = array_unique($this->data->_status);

    // template preview
    $this->templatePreview();

    // then save
    $this->parse('save');
    return $this->save();
  }



  protected function templatePreview()
  {
    $this->configCache();

    if ($this->config->cache->path == "!") {
      return $this->response->add('cache', 'avoided');
    }

    $p = explode("preview/", $this->config->cache->preview);
    $finalPath = $this->config->preview . "/" . $p[1];

    $_path = $finalPath;
    $_asset = $this->config->path . "/" . $this->config->cache->asset;

    $_preview = $finalPath . "/" . $this->config->cache->filename . $this->config->cache->extension;

    $this->data->preview = $_preview;
    $this->data->previewRequest = $this->config->cache->previewRequest;

    File::mkdir($_path);

    ob_start();
    $_config = $this->config;
    $_data = $this->data;
    $_token = isset($this->request->token) ? $this->request->token : null;

    include($_asset);
    $output = ob_get_clean();
    File::saveFile($_preview, $output);
  }

  protected function templateCache($objRef = null)
  {
    $this->configCache($objRef);

    if ($this->config->cache->path == "!") {
      return $this->response->add('cache', 'avoided');
    }

    $rootPath = $this->config->path;

    if (preg_match("/^public\//", $this->config->cache->path)) {
      $folder = "public";
      $p = explode("$folder/", $this->config->cache->path);
      $finalPath = $this->config->public . "/" . $p[1];
      $rootPath = str_replace($folder, "", $this->config->public);
    }

    if (preg_match("/^private\//", $this->config->cache->path)) {
      $folder = "private";
      $p = explode("$folder/", $this->config->cache->path);
      $finalPath = $this->config->private . "/" . $p[1];
      $rootPath = str_replace($folder, "", $this->config->private);
    }

    $_path = $finalPath;
    $_asset = $this->config->path . "/" . $this->config->cache->asset;
    $_cache = $rootPath . "/" . $this->cacheFile();

    $this->data->cache = $_cache;

    if ($this->config->cache->url != "!") {
      $this->data->url = $this->config->server->{$this->config->sitename} . "/" . $this->config->cache->url;
    }
    $this->data->request = $this->config->cache->request;

    File::mkdir($_path);

    ob_start();
    $_config = $this->config;
    $_data = $this->data;
    $_token = isset($this->request->token) ? $this->request->token : null;

    include($_asset);
    $output = ob_get_clean();
    File::saveFile($_cache, $output);
  }

  protected function configCache()
  {
    foreach ($this->config->cache as $k => $v) {
      $r = preg_match_all('/(\[\w+\])/', $v, $matches);
      if ((int)$r > 0) {
        foreach ($matches[1] as $var) {
          $key = str_replace(['[', ']'], '', $var);
          if (isset($this->data->$key)) {
            $this->config->cache->$k = str_replace($var, $this->data->$key, $this->config->cache->$k);
          }
          if (isset($this->headers->$key)) {
            $this->config->cache->$k = str_replace($var, $this->headers->$key, $this->config->cache->$k);
          }
        }
      }
    }
  }

  protected function cacheFile()
  {
    $c = $this->config->cache;
    if ($c->path != "!") {
      return $c->path . "/" . $c->filename . $c->extension;
    }
  }


  public function delete()
  {
    // mover este id pro history
    $id = null;
    if (isset($this->headers->id)) {
      $id = $this->headers->id;
    }
    if (!$id && isset($this->data->id)) {
      $id = $this->data->id;
    }
    $this->headers->id = $this->data->id = $id;

    $unpublishResult = $this->unpublish();
    $this->response->add('unpublish', (object)["success" => $unpublishResult->success, "messages" => isset($unpublishResult->messages) ? $unpublishResult->messages : ""]);

    $fs = new FileSystem($this);
    $this->response->add('fs', $fs->delete());
    $this->response->add('solr', $this->solrDelete());
    return $this->response;
  }

  function solrDelete()
  {
    $query = '(module:' . $this->headers->module . ')';
    $query .= ' AND (id:' . $this->headers->id . ')';
    $result = $this->__solrDeleteByQuery($query);
    $response = json_decode($result->response);
    return (object)[
      'success' => (!isset($response->error))
    ];
  }

  function addIndex($array = [])
  { // [{k: v}, ..., {k, v}]
    foreach ($array as $k => $v) {
      $this->Index->$k = $v;
    }
  }

  private function solrIndex()
  {
    $SolrIndex = new SolrIndex($this);
    $commit = $SolrIndex->commit();

    if (property_exists($commit, 'response')) {
      $response = json_decode($commit->response);

      if (empty($response)) {
        return $this->response->error('solr');
      }

      if (property_exists($response, 'error')) {
        if ($this->config->_env == "local") {
          return $this->response->error($response->error->msg);
        }
        return $this->response->error('solr');
      }
    }
    return $commit;
  }

  private function fileSystemSave()
  {
    $this->id();
    if (!$this->headers->id) {
      return (object)[
        'success' => false,
        'message' => 'fs'
      ];
    }
    $fs = new FileSystem($this);
    return $fs->save();
  }

  /**
   *  set id if it doesn't exist
   */
  private function id()
  {
    if (!isset($this->headers->id)) {
      if (!empty($this->data->id)) {
        //invalid id
        if (preg_match('/\W/', $this->data->id)) {
          $this->response->error('invalid_id', 400)->echo();
          exit();
        }

        $this->headers->id = $this->data->id;
      } else {
        $this->headers->id = $this->data->id = $this->next();
      }
    }
    $this->formatsId();
  }

  private function formatsId()
  {
    if (isset($this->config->formats->data)) {
      $this->config->formats->data = str_replace('[id]', $this->headers->id, $this->config->formats->data);
    }
  }

  private function next()
  {
    $Counter = new Counter();
    return $Counter->next([
      'site' => $this->headers->site,
      'module' => $this->headers->module,
      'data' => $this->config->data
    ]);
  }

  function createModule()
  {
    $Counter = new Counter();
    $Counter->createLastFile([
      'site' => $this->headers->site,
      'module' => $this->headers->module,
      'data' => $this->config->data
    ]);
  }

  // filter fields in response according to config
  // object can be either: $query OR $query->response from solr
  function filter($o)
  {
    $docs = [];
    if (isset($o->docs)) {
      if (isset($o->docs)) {
        if (count($o->docs) > 0) {
          foreach ($o->docs as $doc) {
            $docs[] = $this->_doc($doc);
          }
        }
        $o->docs = $docs;
        return $o;
      }
    }

    if (isset($o->response->docs)) {
      if (count($o->response->docs) > 0) {
        foreach ($o->response->docs as $doc) {
          $docs[] = $this->_doc($doc);
        }
      }
      $o->response->docs = $docs;
      return $o->response;
    }

    if (isset($o->grouped)) {
      $r = (object)[];
      foreach ($o->grouped as $k => $v) {
        $r->$k = (object)[
          'groups' => []
        ];
        if (count($v->groups) > 0) {
          foreach ($v->groups as $group) {
            $g = (object)[];
            $g->value = $group->groupValue;
            $g->docs = [];
            if (isset($group->doclist)) {
              $g->numFound = $group->doclist->numFound;
              $g->start = $group->doclist->start;
            }
            foreach ($group->doclist->docs as $doc) {
              $g->docs[] = $this->_doc($doc);
            }
            $r->$k->groups[] = $g;
          }
        }
      }
      return $r;
    }
  }

  function _doc($doc)
  {
    $new = (object)[];
    foreach ($this->config->fl as $name) {
      if (isset($doc->$name)) {
        $new->$name = $doc->$name;
      }
    }
    return $new;
  }

  function password($string)
  {
    return hash_hmac(
      'SHA1',
      $string,
      $this->config->keys->default
    );
  }

  function readPreview()
  {
    $s = $this->headers->site;
    $m = $this->headers->module;
    $asset = $this->headers->asset;
    $preview = $this->config->preview . "/$m/$asset";

    if ($asset != "index") {
      // se tem um asset.php na pasta, dê preferência a ele.
      $_asset = $this->config->preview . "/$m/read.php";
      if (file_exists($_asset)) {
        include($_asset);
        exit;
      }
    }

    foreach (['.json', '.html', '.php'] as $ext) {
      $file = $preview . "$ext";
      if (file_exists($file)) {
        include($file);
        exit;
      }
    }

    return $this->response->error("assetNotFound");
    exit;
  }

  function read()
  {
    if ($this->private()) {
      exit;
    }

    //try to get asset preview
    $this->headers->method = "readPreview";
    if ($this->hasPermission("allow")) {
      return $this->readPreview();
    }

    return $this->response->error("assetNotFound");
  }

  function private()
  {
    $s = $this->headers->site;
    $m = $this->headers->module;
    $asset = $this->headers->asset;
    $private = $this->config->private . "/$m/$asset";

    // TO DO: dá pra refatorar isso aqui, deixando tudo no mesmo foreach ali debaixo.
    if ($asset != "index") {
      // se tem um asset.php na pasta, dê preferência a ele.

      $_asset = $this->config->path . "/private/$m/read.php";

      if (file_exists($_asset)) {
        include($_asset);
        return true;
      }

      //se tem $module/read{$Module}.php e não tem $module/$asset.php
      if (file_exists($this->config->path . "/private/$m/read" . ucfirst($m) . ".php") && !file_exists($this->config->path . "/private/$m/" . $asset . ".php")) {
        include($this->config->path . "/private/$m/read" . ucfirst($m) . ".php");
        return true;
      }
    }

    foreach (['.json', '.html', '.php'] as $ext) {
      $file = $private . "$ext";
      if (file_exists($file)) {
        include($file);
        return true;
      }

      if (file_exists($this->config->path . "/private/$m/" . $asset . $ext)) {
        include($this->config->path . "/private/$m/" . $asset . $ext);
        return true;
      }
    }
    return false;
  }



  function getContent($path, $file)
  {
    $file = $path . "/" . $file;
    if (file_exists($file)) {
      return json_decode(file_get_contents($file));
    }
    return false;
  }


  function crop()
  {

    if (empty($this->data->imageUrl)) {
      return $this->response->error('crop.emptyImageUrl', 400);
    }

    if (empty($this->data->version)) {
      return $this->response->error('crop.emptyVersion', 400);
    }

    $version = $this->data->version;

    //filename
    $name = explode("/", $this->data->imageUrl);
    $filename = $name[count($name) - 1];

    //to save file
    $tmp_path = "/tmp";

    $crop = new Crop();
    $create = $crop->createCrop($this->data->imageUrl, $filename, (array)$this->data, $tmp_path);

    //upload do s3
    if ($create) {
      $upload = $this->fileuploadByCli($tmp_path . "/" . $filename, $version . "/" . $filename);

      //remove tmp file
      unlink($tmp_path . "/" . $filename);

      return $this->response;
    } else {
      return $this->response->error('crop.errorToCreate', 500);
    }
  }

  private function formatsFileupload($filename = false, $profile = 's3')
  {

    if (isset($this->config->{$profile}->format)) {
      $s = $this->headers->site;
      $m = $this->headers->module;
      $id = isset($this->headers->id) ? $this->headers->id : '';

      $format = $this->config->{$profile}->format;
      $format = str_replace('[site]', $s, $format);
      $format = str_replace('[module]', $m, $format);
      $format = str_replace('[id]', $id, $format);

      if (isset($this->data->filename)) {
        $format = str_replace('[filename]', $this->data->filename, $format);
      }

      if ($filename) {
        $format = str_replace('[filename]', $filename, $format);
      }

      $this->config->{$profile}->format = $format;
      return true;
    }
    return false;
  }

  function articleupload()
  {
    return $this->fileupload(true);
  }


  function articlemultipleupload()
  {
    return $this->multipleupload(true);
  }

  function multipleupload($article = false)
  {
    if (empty($_FILES)) {
      return $this->response->error('multipleupload.emptyFile', 400);
    }

    //unset to formats filename
    if (isset($this->data->filename)) {
      unset($this->data->filename);
    }


    if (!$this->formatsFileupload()) {
      return $this->response->error('multipleupload.formatUploadIsNotDefined', 400);
    }

    //formats
    $formats = explode("/", $this->config->s3->format);

    //remove filename of prefix
    $prefix = str_replace("/" . $formats[count($formats) - 1], "", $this->config->s3->format);


    //for each file, set name and check type
    foreach ($_FILES as $key => $file) {

      $filename = is_array($_FILES[$key]['name']) ? $_FILES[$key]['name'][0] : $_FILES[$key]['name'];

      preg_match('/\.([0-9a-z]+$)/i', $filename, $ext);
      $extension = $ext[0];

      $filename = preg_replace('/\.([0-9a-z]+$)/i', '', $filename);
      $filename = slugify($filename) . '-' . time() . $extension;

      //add to files
      $_FILES[$key]['name'] = $filename;

      //validate type files
      if (isset($this->config->s3->accept_file_types)) {
        if (!preg_match($this->config->s3->accept_file_types, $filename)) {
          return $this->response->error('multipleupload.fileTypeNotPermitted', 400);
        }
      }
    }

    //check configs
    $config = (object)[];
    $requiredFields = ["bucket", "profile", "region"];

    foreach ($requiredFields as $field) {
      if (!isset($this->config->s3->{$field})) {
        return $this->response->error("fileupload.{$field}IsNotDefined", 400);
      } else {
        $config->{$field} = $this->config->s3->{$field};
      }
    }


    $upload = new UploadS3($config);
    $response = $upload->handleUploadFile($prefix);

    if ($article) {
      echo json_encode($response);
      exit;
    }

    if (isset($response->error)) {
      $this->response->error($response->error);
      return $this->response->error('fileupload.uploadError', 500);
    } else {
      return $this->response->add('response', $response);
    }
  }


  function fileupload($article = false)
  {


    if (empty($_FILES)) {
      return $this->response->error('fileupload.emptyFile', 400);
    }

    if (!$this->formatsFileupload()) {
      return $this->response->error('fileupload.formatUploadIsNotDefined', 400);
    }

    $formats = explode("/", $this->config->s3->format);

    $filename = $formats[count($formats) - 1];
    $prefix = str_replace("/" . $filename, "", $this->config->s3->format);


    $key = key($_FILES);

    //se não for enviado no data, seta um nome
    if ($filename == "[filename]") {
      $filename = is_array($_FILES[$key]['name']) ? $_FILES[$key]['name'][0] : $_FILES[$key]['name'];

      preg_match('/\.([0-9a-z]+$)/i', $filename, $ext);
      $extension = $ext[0];

      $filename = preg_replace('/\.([0-9a-z]+$)/i', '', $filename);
      $filename = slugify($filename) . '-' . time() . $extension;
    }

    $_FILES[$key]['name'] = $filename;


    //validate type files
    if (isset($this->config->s3->accept_file_types)) {
      if (!preg_match($this->config->s3->accept_file_types, $filename)) {
        return $this->response->error('fileupload.fileTypeNotPermitted', 400);
      }
    }

    $config = (object)[];
    $requiredFields = ["bucket", "profile", "region"];

    foreach ($requiredFields as $field) {
      if (!isset($this->config->s3->{$field})) {
        return $this->response->error("fileupload.{$field}IsNotDefined", 400);
      } else {
        $config->{$field} = $this->config->s3->{$field};
      }
    }


    $upload = new UploadS3($config);
    $response = $upload->handleUploadFile($prefix);

    if ($article) {
      echo json_encode($response);
      exit;
    }

    if (isset($response->error)) {
      $this->response->error($response->error);
      return $this->response->error('fileupload.uploadError', 500);
    } else {

      //data thumbs and image
      if (!empty($this->data->thumbs) && (preg_match("/(gif|jpe?g|jpg|png)$/i", $extension))) {
        $imageUrl = $response->{$key}->url;

        $thumbs = (object)[];

        foreach ($this->data->thumbs as $version => $item) {
          $options = [
            "version" => $version,
            "imageUrl" => $imageUrl,
            "crop" => true,
            "x" => 0,
            "y" => 0,
            "max_width" => $item->maxWidth,
            "max_height" => $item->maxHeight
          ];

          $tmp_path = "/tmp";

          $crop = new Crop();
          $create = $crop->createCrop($imageUrl, $filename, $options, $tmp_path);

          //upload do s3
          if ($create) {
            $UploadS3 = new UploadS3($config);
            $UploadS3->upload($tmp_path . "/" . $filename, $filename, $prefix . "/" . $version);

            //remove tmp file
            unlink($tmp_path . "/" . $filename);
            $thumbs->{$version} = $UploadS3->objectURL;
          } else {
            $this->response->errors('crop.errorToCreateThumb');
          }
        }
        $response->thumbs = $thumbs;
      }

      return $this->response->add('response', $response);
    }
  }

  function fileZipUpload()
  {
    if (empty($_FILES)) {
      return $this->response->error('fileZipUpload.emptyFile', 400);
    }

    if (!$this->formatsFileupload()) {
      return $this->response->error('fileZipUpload.formatUploadIsNotDefined', 400);
    }

    $formats = explode("/", $this->config->s3->format);

    $filename = $formats[count($formats) - 1];
    $prefix = str_replace("/" . $filename, "", $this->config->s3->format);


    $key = key($_FILES);

    //se não for enviado no data, seta um nome
    if ($filename == "[filename]") {
      $filename = is_array($_FILES[$key]['name']) ? $_FILES[$key]['name'][0] : $_FILES[$key]['name'];

      preg_match('/\.([0-9a-z]+$)/i', $filename, $ext);
      $extension = $ext[0];

      if ($extension != '.zip') {
        return $this->response->error('fileZipUpload.fileTypeNotPermitted', 400);
      }

      $filename = preg_replace('/\.([0-9a-z]+$)/i', '', $filename);
      $foldername = slugify($filename) . '-' . time();
    }

    $_FILES[$key]['name'] = $filename;

    $tmp_name = $_FILES[$key]['tmp_name'];
    exec("mkdir -p /tmp/$foldername");
    exec("unzip " . $tmp_name . " -d /tmp/$foldername/");

    if (!file_exists("/tmp/$foldername/index.html")) {
      return $this->response->error('fileZipUpload.thereIsNotIndexInRootFolder', 400);
    }

    $resources = rglob("/tmp/$foldername/*");

    $config = (object)[];
    $requiredFields = ["bucket", "profile", "region"];

    foreach ($requiredFields as $field) {
      if (!isset($this->config->s3->{$field})) {
        return $this->response->error("fileZipUpload.{$field}IsNotDefined", 400);
      } else {
        $config->{$field} = $this->config->s3->{$field};
      }
    }

    foreach ($resources as $sourceFile) {
      $UploadS3 = new UploadS3($config);
      if (!is_dir($sourceFile)) {
        $tmpPath = "/tmp/$foldername/";
        $relativePath = str_replace($tmpPath, '', $sourceFile);
        $relativePathSplit = explode('/', $relativePath);
        $fileName = $relativePathSplit[count($relativePathSplit) - 1];

        $subFolder = '';
        $s3FolderPath = $prefix . '/' . $foldername;
        if ($relativePath != $fileName) {
          $subFolder = str_replace('/' . $fileName, '', $relativePath);
          $s3FolderPath .= '/' . $subFolder;
        }

        $upload = $UploadS3->upload($sourceFile, $fileName, $s3FolderPath);



        $proxy = true;
        $proxy = (isset($_GET['proxy']) && $_GET['proxy'] == 'false') ? false : true;

        //check cloudFront Url
        $awsRequestUrl = (!empty($this->config->s3->cloudFront))
                            ? "https://" . $this->config->s3->cloudFront
                            : "https://" . $this->config->s3->bucket . ".s3.amazonaws.com";

        $url = ($proxy)
          ? $this->config->client->maisconasems . '/api/proxy/?path=' . $s3FolderPath . '&file=' . $fileName
          : $awsRequestUrl . "/" . $s3FolderPath . "/" . $fileName;

        if (empty($subFolder) && $fileName == 'index.html' && $upload) {
          $response = (object)[
            'file' =>
            (object)[
              'url' => $url
            ]
          ];
        }

        if (!$upload) {
          $response = (object)[
            "error" => $UploadS3->messageError
          ];
        }
      }
    }

    if (isset($response->error)) {
      $this->response->error($response->error);
      return $this->response->error('fileZipUpload.uploadError', 500);
    } else {
      return $this->response->add('response', $response);
    }
  }

  //php cli -S "Ava\Users.fileuploadByCli(/var/www/ava.conasems/public/cpf.jpg, cpf-test.jpg)"
  function fileuploadByCli($sourceFile, $filename = "", $acl = "public-read", $profile = 's3')
  {
    if (!$this->formatsFileupload($filename, $profile)) {
      return $this->response->error('fileupload.formatUploadIsNotDefined', 400);
    }

    $formats = explode("/", $this->config->{$profile}->format);

    $filename = $formats[count($formats) - 1];
    $prefix = str_replace("/" . $filename, "", $this->config->{$profile}->format);

    //validate type files
    if (isset($this->config->{$profile}->accept_file_types)) {
      if (!preg_match($this->config->{$profile}->accept_file_types, $filename)) {
        return $this->response->error('fileupload.fileTypeNotPermitted', 400);
      }
    }

    $config = (object)[];
    $requiredFields = ["bucket", "profile", "region"];

    foreach ($requiredFields as $field) {
      if (!isset($this->config->{$profile}->{$field})) {
        return $this->response->error("fileupload.{$field}IsNotDefined", 400);
      } else {
        $config->{$field} = $this->config->{$profile}->{$field};
      }
    }

    $UploadS3 = new UploadS3($config);
    $upload = $UploadS3->upload($sourceFile, $filename, $prefix, $acl);
    if ($upload) {
      $result = (object)[
        "filename" => $filename,
        "url" => $UploadS3->objectURL
      ];
      if($acl == 'private'){
        $bucketUrl = "https://".$this->config->{$profile}->bucket.".s3.sa-east-1.amazonaws.com/";
        $path = str_replace($bucketUrl, "", $UploadS3->objectURL);
        $result->publicUrl = $UploadS3->getPrivateObjectS3($path);
      }

      return $this->response->add('response',$result);
    } else {
      return $this->response->error(
        'response',
        (object)[
          "url" => $UploadS3->messageError
        ]
      );
    }
  }

  function getTokenInPublicRequest()
  {
    $h = Helper::getallheaders();
    if (!empty($h["token"])) {
      $t = $h["token"];
      $this->token = new Token($this->config);
      if (!$this->token->data($t)) {
        $this->response->addHttpHeader('WWW-Authenticate: Bearer error="invalid_token", error_description="The access token provided is expired"');
        $this->response->error('expiredToken', 401);
        $this->response->echo();
        exit;
      }
      $this->token->populateExtraInfo();
    }
  }

  function hasPermission($prop, $headers = null)
  {
    if (!isset($this->token->data->data)) {
      return false;
    }

    $td = $this->token->data->data;

    $s = (isset($headers->site))   ? $headers->site   : $this->headers->site;
    $c = (isset($headers->module)) ? $headers->module : $this->headers->module;
    $m = (isset($headers->method)) ? $headers->method : $this->headers->method;

    // root || {$site}/admin
    if (array_intersect(['root', $s . '/admin'], $td->roles)) {
      return true;
    }

    $config = new Config($c);
    $config->module($c);
    $config->method($m);

    $allow = (isset($config->{"$m()"}->{$prop}))
      ? $config->{"$m()"}->{$prop}
      : [];

    if (array_intersect($td->roles, $allow) || in_array('all', $allow)) {
      return true;
    }

    return false;
  }

  function fsJsonCheck($start = 0, $rows = 1)
  {
    $path = $this->config->data . "/" . $this->headers->site . "/" . $this->headers->module;

    $files = glob($path . "/*.json");
    for ($i = $start; $i < $rows; $i++) {
      $json = json_decode(file_get_contents($files[$i]));
      if (json_last_error() != 0) {
        echo "\n" . $files[$i];
      }
    }
    exit;
  }

  function dateTimeUtc()
  {
    $response = (object)[
      "dateTimeUtc" => gmdate('Y-m-d') . "T" . gmdate('H:i:s') . "Z"
    ];

    return $this->response->add("response", $response);
  }
}

function rglob($pattern, $flags = 0)
{
  $files = glob($pattern, $flags);
  foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
    $files = array_merge($files, rglob($dir . '/' . basename($pattern), $flags));
  }
  return $files;
}
