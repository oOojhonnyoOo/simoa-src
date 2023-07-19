<?php

namespace Simoa;

use Solr;

class SolrIndex
{
  var $doc;

  function __construct($doc, $core = null)
  {
    $this->doc = $doc;
    $this->config();
    $this->core($core);
  }

  function config()
  {
    if (isset($this->doc->config)) {
      $this->config = $this->doc->config;
    } else {
      $this->config = new Config();
      $this->config->module($this->doc->headers->module);
      $this->config->formats($this->doc->headers, $this->doc->data);
    }
  }

  function core($core = null)
  {
    $this->core = ($core !== null) ? $core : $this->config->solr->default;
  }


  function setup()
  {
    $doc = new Solr\Document($this->core);

    $s = $this->doc->headers->site;
    $m = $this->doc->headers->module;
    $id = $this->doc->headers->id;

    $solrId = (isset($this->doc->solrId)) ? $this->doc->solrId : $s . '/' . $m . '/' . $id;

    $_data =
      $this->config->data
      . "/" . $this->config->formats->data
      . $this->config->formats->extension;

    $doc->addField('solrId', $solrId);
    $doc->addField('site', $s);
    $doc->addField('module', $m);
    $doc->addField('id', $id);
    $doc->addField('_data', $_data);

    isset($this->doc->data->cache) && $doc->addField('cache', $this->doc->data->cache);
    isset($this->doc->data->url) && $doc->addField('url', $this->doc->data->url);
    isset($this->doc->data->request) && $doc->addField('request', $this->doc->data->request);
    isset($this->doc->data->_status) && $doc->addField('_status', $this->doc->data->_status);
    isset($this->doc->data->preview) && $doc->addField('preview', $this->doc->data->preview);
    isset($this->doc->data->previewRequest) && $doc->addField('previewRequest', $this->doc->data->previewRequest);

    foreach ($this->config->index as $item) {
      (isset($this->doc->data->$item)) && $doc->addField($item, $this->doc->data->$item);
    }

    if (isset($this->doc->Index)) {
      foreach ($this->doc->Index as $k => $v) {
        $doc->addField($k, $v);
      }
    }

    if (isset($this->doc->data->sortDate) && (isset($this->doc->data->keepSortDate) && $this->doc->data->keepSortDate)) {
      $doc->addField('sortDate', $this->doc->data->sortDate);
    } else {
      $doc->setSortDate();
    }

    return $doc;
  }

  function commit()
  {
    $doc = $this->setup();
    return $doc->commit();
  }
}
