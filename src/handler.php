<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler('api_error_handler');

function api_error_handler($errno, $errstr, $errfile, $errline)
{
  return api_error($errstr, $errno, $errfile, $errline, 500);    
}

// Set uncaught exceptions handler    
set_exception_handler('api_exception_handler');

function api_exception_handler($exception)
{
    return api_error(
      $exception->getMessage(), 
      $exception->getCode(), 
      $exception->getFile(), 
      $exception->getLine(), 
      500)
    ;
}

// Error/Exception helper
function api_error($error, $errno, $errfile, $errline, $code)
{
  $die = (defined("CLI") && CLI === true) ? false : true ;
  // $die = true;
  
  if ($die) {
    http_response_code($code);
    
    echo json_encode([
      'success' => false,
      'response' => (object)[],
      'errors' => [
        'no'   => $errno,
        'error'   => $error,
        'file'   => $errfile,
        'line'   => $errline
      ]
    ]);
  }

  exit;
}
