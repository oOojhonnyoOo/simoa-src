<?php

namespace Simoa;

use Solr;

class Search
{
  var $core;
  var $site;
  var $module;
  var $params;
  var $hint = false;

  var $fq;
  var $q;
  var $fl;
  var $sort = null;
  var $rows = null;
  var $start = null;

  var $facetField;

  function __construct($init = null) // Module Object or string
  {
    if ($init === null) {
      $this->config = new Config();
      $this->response = new Response();
    } else {
      if (is_string($init)) {
        $init = new Module($init);
      }
      foreach ($init as $k => $v) {
        $this->$k = $v;
      }
    }
  }

  function q()
  {
    if (isset($_GET['q']) && !empty($_GET['q'])) {
      $_q = $_GET['q'];

      if (empty($this->q)) {
        $this->q = $_q;
      } else {
        $this->q .= $_q . " AND " . $this->q;
      }
    }
  }

  function fq()
  {
    // se veio fq por GET
    if (isset($_GET['fq']) && !empty($_GET['fq'])) {
      $fq = "";
      // &fq[foo]=bar
      // &fq[fu]=ba
      // se veio fq por get e ele é um array
      if (is_array($_GET['fq'])) {
        foreach ($_GET['fq'] as $k => $v) {
          if ($this->hint) {
            $v .= "*";
          }
          if (empty($fq) && !empty($v)) {
            $fq = "($k:$v)";
          } else {
            if (!empty($v)) {
              $fq .= " AND ($k:$v)";
            }
          }
        }
      } else {
        // se veio fq por get e ele é uma string
        if (is_string($_GET['fq'])) {
          $fq = $_GET['fq'];
        }
      }

      if (!empty($fq) && !empty($this->fq)) {
        $fq .= ' AND ' . $this->fq;
      }

      $this->fq = $fq;
    }
  }

  function fl()
  {
    if (isset($_GET['fl']) && !empty($_GET['fl'])) {
      $this->fl = $_GET['fl'] . "," . $this->fl;
    } else {
      $this->fl = $this->fl;
    }
  }

  function sort()
  {
    if ($this->sort === null) {
      if (isset($_GET['sort'])) {
        $this->sort = $_GET['sort'];
      } else {
        $this->sort = "sortDate DESC";
      }
    }
  }

  function start()
  {
    if ($this->start === null) {
      if (isset($_GET['start'])) {
        $this->start = $_GET['start'];
      }
    }
  }

  function rows()
  {
    if ($this->rows === null) {
      if (isset($_GET['rows'])) {
        $this->rows = $_GET['rows'];
      }
    }
  }

  function setCore()
  {
    $this->core =
      (isset($this->config->core))
      ? $this->config->core
      : $this->config->solr->default;
  }

  // recebe uma chave<string> e um array
  // e monta uma string "chave:array[0] OR chave:array[1] OR chave:array[2] ..."
  function _keyOr($key, $array)
  {
    $array = array_unique($array);
    $or = "";
    foreach ($array as $item) {
      if ($item === reset($array)) {
        if (strlen($or) == 0) {
          $or .= "(" . $key . ":" . $item;
        }
      } else {
        $or .= " OR " . $key . ":" . $item;
      }
      if ($item === end($array)) {
        if (strlen($or) > 0) {
          $or .= ")";
        }
      }
    }

    return $or;
  }

  function query($props = [])
  {
    $props = (object)$props;
    $this->setCore();

    $this->q();
    $this->fq();
    $this->fl();
    $this->sort();
    $this->start();
    $this->rows();

    $Solr = new Solr\Query($this->core);
    $Solr->start  = 0;
    $Solr->rows   = 30;
    $Solr->sort   = (isset($props->sort)) ? $props->sort : $this->sort;
    $Solr->q  = "";
    $Solr->fl  = (isset($props->fl)) ? $props->fl : "*";
    $Solr->fq  = (isset($props->fq)) ? $props->fq : "";

    if (isset($this->headers->site)) {
      $Solr->q = "(site:" . $this->headers->site . ")";
    }

    if (isset($this->headers->module)) {
      if (empty($Solr->q)) {
        $Solr->q = "module:" . $this->headers->module;
      } else {
        $Solr->q .= ' AND (module:' . $this->headers->module . ')';
      }
    }


    if (!empty($props->q)) {
      $Solr->q .= ' AND (' . $props->q . ')';
    } else {
      if (isset($this->q) && !empty($this->q)) {
        $_q = $this->q;
        if ($this->hint) {
          $_q .= "*";
        }
        if (empty($Solr->q)) {
          $Solr->q = $_q;
        } else {
          $Solr->q .= ' AND (' . $_q . ')';
        }
      }
    }


    if (isset($this->fq) && !empty($this->fq)) {
      $fq = "";

      if (is_array($this->fq)) {
        foreach ($this->fq as $k => $v) {
          if ($this->hint) {
            $v .= "*";
          }
          if (empty($fq) && !empty($v)) {
            $fq = "($k:$v)";
          } else {
            if (!empty($v)) {
              $fq .= " AND ($k:$v)";
            }
          }
        }
      } else {
        if (is_string($this->fq)) {
          $fq = $this->fq;
        }
      }

      $Solr->fq = $fq;
    }

    // filter date
    if (isset($_GET['calendar'])) {
      if (!empty($_GET['calendar'])) {
        $c = $_GET['calendar'];

        $field =
          (isset($_GET['calendarField']) && !empty($_GET['calendarField']))
          ? $_GET['calendarField']
          : "sortDate";

        $calendar = (object)[
          'lastweek' => $field . ':[' . date('Y-m-d', strtotime(' -7 days')) . 'T00:00:00Z TO *]',
          'lastmonth' => $field . ':[' . date('Y-m-d', strtotime(' -1 month')) . 'T00:00:00Z TO *]'
        ];

        if (isset($calendar->$c)) {
          if (empty($Solr->fq)) {
            $Solr->fq = $calendar->$c;
          } else {
            $Solr->fq .= ' AND (' . $calendar->$c . ')';
          }
        }
      }
    }

    if ($this->sort !== null) {
      $Solr->sort = (isset($props->sort)) ? $props->sort : $this->sort;
    }

    if ($this->start !== null) {
      $Solr->start = $this->start;
    }

    if ($this->rows !== null) {
      $Solr->rows = $this->rows;
    }

    if (isset($this->fl) && !empty($this->fl)) {
      $Solr->fl = $this->fl;
    }

    if (isset($_GET['sort'])) {
      $Solr->sort = $_GET['sort'];
    }

    if (isset($_GET['group'])) {
      $Solr->group = 'true';
      $Solr->groupField = $_GET['group'];

      if (isset($_GET['groupLimit'])) {
        $Solr->groupLimit = $_GET['groupLimit'];
      }
      if (isset($_GET['groupOffset'])) {
        $Solr->groupOffset = $_GET['groupOffset'];
      }
    }

    //props
    if (isset($props->group)) {
      $Solr->group = 'true';
    }
    if (isset($props->groupField)) {
      $Solr->groupField = $props->groupField;
    }
    if (isset($props->groupLimit)) {
      $Solr->groupLimit = $props->groupLimit;
    }
    if (isset($props->groupLimit)) {
      $Solr->groupLimit = $props->groupLimit;
    }
    if (isset($props->groupOffset)) {
      $Solr->groupOffset = $props->groupOffset;
    }

    if (!empty($this->facetField)) {
      $Solr->facet = 'true';
      $Solr->facetField = $this->facetField;
    }

    if (isset($props->start)) {
      $Solr->start = $props->start;
    }
    if (isset($props->rows)) {
      $Solr->rows = $props->rows;
    }

    $result = $Solr->query();
    $this->reset();
    return $result;
  }

  function reset()
  {
    $this->q = "";
    $this->fq = "";
    $this->fl = "";
    $this->hint = "";
  }
}
