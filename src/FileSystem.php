<?php

namespace Simoa;

class FileSystem
{
  var $mode = 'w+';
  var $path; // /path/to/data/site/module
  var $filename; // site/module/file.json
  var $file; // /path/to/data/site/module/file.json
  var $extension; // set in config.yml
  var $history; // /path/to/history/site/module/[id]/ 
  var $historySaved; // history saved full path
  var $log;

  function __construct($object)
  {
    foreach ($object as $k => $v) {
      $this->$k = $v;
    }

    $this->mode();
    $this->filename();
    $this->path();
    $this->file();

    $this->log = (object)[];
  }

  function save()
  {
    $ext = str_replace('.', '', $this->config->formats->extension);
    $method = $ext . "Save";
    return $this->$method();
  }

  function jsonSave()
  {
    if (file_exists($this->file)) {
      if ($this->config->formats->history) {
        if (!$this->createHistory()) {
          return (object)[
            'success' => false,
            'message' => 'history'
          ];
        }
      }
    }

    if (!$this->createFile()) {
      return (object)[
        'success' => false,
        'message' => 'fs'
      ];
    }

    return (object)[
      'success' => true,
      '_data' => $this->file,
      '_history' => $this->historySaved
    ];
  }

  function csvSave()
  {
    $Csv = new Csv();
    return $Csv->addDataAsLine($this->data, $this->file);
  }

  function createFile()
  {
    $content = (object)[
      "headers" => $this->headers,
      "data" => $this->data
    ];

    $content->log = $this->log();

    $i = 0;
    $tries = 100;
    $success = false;

    do {
      //open and lock file
      $handle = fopen($this->file, $this->mode);
      $locked = flock($handle, LOCK_EX);

      if ($locked) {
        
        //truncate file
        ftruncate($handle, 0);

        //volta pro começo
        rewind($handle);

        //begin of file
        $success = 
          (fwrite($handle, json_encode($content)))
          ? true
          : false;

        //unlock file
        flock( $handle, LOCK_UN ); 
        
        //close file
        fclose($handle);
      } else {
        usleep(1000);
        $i++;
      }
    } while (!$locked  && $i <= $tries);

    return $success;
  }

  function delete()
  {
    if (file_exists($this->file)) {
      if (!$this->createHistory()) {
        return (object)[
          'success' => false,
          'message' => 'history'
        ];
      }

      // criando arquivo para armazenar log de delete
      $save = $this->createFile();
      $move = $this->createHistory();

      return (object)[
        'success' => ($save && $move)
      ];
    }

    return (object)[
      'success' => true,
      'message' => 'no file'
    ];
  }

  function log()
  {
    (isset($_SERVER['REMOTE_ADDR'])) && $this->addLog('ip', $_SERVER['REMOTE_ADDR']);
    (isset($_SERVER['SCRIPT_NAME'])) && $this->addLog('script', $_SERVER['SCRIPT_NAME']);
    (isset($this->token->data->data)) && $this->addLog('user', $this->token->data->data);
    $this->addLog('date', date("YmdHis"));

    return $this->log;
  }

  function addLog($k, $v)
  {
    if ($k == "script") {
      $v = (strpos($v, 'cli') > -1) ? 'CLI' : 'API';
    }
    $this->log->$k = $v;
  }


  // move current file to history if it does exist.
  function createHistory()
  {
    // mover atual para history
    if (file_exists($this->file)) {
      $this->history();
      $this->historySaved = $this->history . "/" . $this->getLastDate() . $this->config->formats->extension;
      $cmd = 'mv ' . $this->file . ' ' . $this->historySaved;
      // echo $cmd; exit;
      $return = shell_exec($cmd);
    }
    return true;
  }

  function getLastDate()
  {
    $json = json_decode(file_get_contents($this->file));
    if (isset($json->log) && isset($json->log->date) && !empty(isset($json->log->date))) {
      return $json->log->date;
    }
    return date("YmdHis") . "-";
  }

  function filename()
  {
    $data = $this->config->formats->data;
    $extension = $this->config->formats->extension;
    $this->filename = $data . $extension;
  }

  // set path
  // create, if it doesn't exists
  function path()
  {
    preg_match('/(\S+\/)/', $this->filename, $matches);
    $path = $this->config->data . "/" . $matches[1];
    if (!file_exists($path)) {
      mkdir($path, 0755, true);
    }
    $this->path = $path;
  }

  function history()
  {
    preg_match('/(\S+\/)/', $this->filename, $matches);
    $history = $this->config->history . "/" . $matches[1] . $this->headers->id;
    
    if (!file_exists($history)) {
      mkdir($history, 0755, true);
    }

    $this->history = $history;
  }

  // set the full path of file
  function file()
  {
    $this->file = $this->config->data . "/" . $this->filename;
  }

  function mode($mode = null)
  {
    if ($mode !== null) {
      $this->mode = $mode;
      return true;
    }

    if (isset($this->config->formats->mode)) {
      $this->mode = $this->config->formats->mode;
      return true;
    }

    return true;
  }
}
