<?php

namespace Simoa;

class Helper
{
  public static function requestURI()
  {
    $requestURI = $_SERVER['REQUEST_URI'];
    $requestURI = ltrim($requestURI, '/');
    $requestURI = rtrim($requestURI, '/');
    $requestURI = parse_url($requestURI, PHP_URL_PATH);

    return $requestURI;
  }

  public static function getallheaders()
  {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }

    return $headers;    
  }

  public static function compare($value1, $value2, $operator = "==")
  {
    switch ($operator) {
      case "==":
          return $value1 == $value2;
      case "===":
          return $value1 === $value2;
      case ">=":
          return $value1 >= $value2;
      case "<=":
          return $value1 <= $value2;
      case "<":
          return $value1 < $value2;
      case ">":
          return $value1 > $value2;
      case "!=":
          return $value1 != $value2;
      case "!==":
          return $value1 !== $value2;
      default:
          $operator = strtolower($operator);
          if ($operator == "in" || $operator == "out") {
              $value1 = is_array($value1) ? $value1 : [$value1];
              $value2 = is_array($value2) ? $value2 : [$value2];
              $intersection = array_intersect($value1, $value2);
              return ($operator == "in") ? (count($intersection) > 0) : (count($intersection) === 0);
          }
          return false;
    }
  }  


  function debugg($var, $mode = "default")
  {
    if ($mode == "json") {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
      $json = (object)[];
      $json->file = $trace[1]['file'];
      $json->line = $trace[1]['line'];
      $json->debugg = $var;
  
      if (isset($json->debugg->config)) {
        unset($json->debugg->config);
      }
  
      if (isset($json->debugg->token->config)) {
        unset($json->debugg->token->config);
      }
  
  
      echo json_encode($json);
    } else {
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
      echo $trace[0]['file'] . ":" . $trace[0]['line'];
      echo "
      ";
      echo "<pre>";
      print_r($var);
      echo "</pre>";
    }
  }

  function d($val, $deep = 0)
  {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $r = (object)[
      "value" => print_r(is_bool($val) ? ($val == true) ? "true" : "false" : $val, true),
      "type" => gettype($val),
      "trace" => $trace[$deep]
    ];
    self::debugg($r, "default");
  }  

}

// function object($array, $associative = false)
// {
//   return json_decode(json_encode($array), $associative);
// }

// function jdebugg($var)
// {
//   debugg($var, 'json');
// }

function dd()
{
  $arg_list = func_get_args();
  foreach ($arg_list as $val) {
    d($val, 1);
  }
  die;
}

function dt($title, $val, $deep = 1)
{
  echo "\n-------------------------- [ start of $title  vvvvv ] \n";
  d($val, $deep);
  echo "\n-------------------------- [ end of $title ^^^^^ ] \n";
}

function ddt($title, $val)
{
  dt($title, $val, 2);
  die;
}

function getNowString()
{
  return date("Y:m:d") . "T" . date("H:i:s") . "Z";
}

// function stringToTimestamp($stringTime)
// {
//   $strTime = trim(str_replace(["T", "Z"], " ", $stringTime));
//   return strtotime($strTime);
// }

function debugg($var, $mode = "default")
{
  if ($mode == "json") {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $json = (object)[];
    $json->file = $trace[1]['file'];
    $json->line = $trace[1]['line'];
    $json->debugg = $var;

    if (isset($json->debugg->config)) {
      unset($json->debugg->config);
    }

    if (isset($json->debugg->token->config)) {
      unset($json->debugg->token->config);
    }


    echo json_encode($json);
  } else {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    echo $trace[0]['file'] . ":" . $trace[0]['line'];
    echo "
    ";
    echo "<pre>";
    print_r($var);
    echo "</pre>";
  }
}

function RequestURI()
{
  $requestURI = $_SERVER['REQUEST_URI'];
  $requestURI = preg_replace('/^\//', "", $requestURI);
  $requestURI = preg_replace('/\/$/', "", $requestURI);
  $split = explode("?", $requestURI);
  $requestURI = $split[0];

  return $requestURI;
}

function getSlug()
{
  $a = explode("/", RequestURI());
  return end($a);
}

function slugify($str)
{

  $slugRegExp = '/([^a-z0-9]|-)+/';
  $slugSeparator = "-";
  $slugRules = [
    // Numeric characters
    '¹' => 1,
    '²' => 2,
    '³' => 3,

    // Latin
    '°' => 0,
    'æ' => 'ae',
    'ǽ' => 'ae',
    'À' => 'A',
    'Á' => 'A',
    'Â' => 'A',
    'Ã' => 'A',
    'Å' => 'A',
    'Ǻ' => 'A',
    'Ă' => 'A',
    'Ǎ' => 'A',
    'Æ' => 'AE',
    'Ǽ' => 'AE',
    'à' => 'a',
    'á' => 'a',
    'â' => 'a',
    'ã' => 'a',
    'å' => 'a',
    'ǻ' => 'a',
    'ă' => 'a',
    'ǎ' => 'a',
    'ª' => 'a',
    '@' => 'at',
    'Ĉ' => 'C',
    'Ċ' => 'C',
    'ĉ' => 'c',
    'ċ' => 'c',
    '©' => 'c',
    'Ð' => 'Dj',
    'Đ' => 'Dj',
    'ð' => 'dj',
    'đ' => 'dj',
    'È' => 'E',
    'É' => 'E',
    'Ê' => 'E',
    'Ë' => 'E',
    'Ĕ' => 'E',
    'Ė' => 'E',
    'è' => 'e',
    'é' => 'e',
    'ê' => 'e',
    'ë' => 'e',
    'ĕ' => 'e',
    'ė' => 'e',
    'ƒ' => 'f',
    'Ĝ' => 'G',
    'Ġ' => 'G',
    'ĝ' => 'g',
    'ġ' => 'g',
    'Ĥ' => 'H',
    'Ħ' => 'H',
    'ĥ' => 'h',
    'ħ' => 'h',
    'Ì' => 'I',
    'Í' => 'I',
    'Î' => 'I',
    'Ï' => 'I',
    'Ĩ' => 'I',
    'Ĭ' => 'I',
    'Ǐ' => 'I',
    'Į' => 'I',
    'Ĳ' => 'IJ',
    'ì' => 'i',
    'í' => 'i',
    'î' => 'i',
    'ï' => 'i',
    'ĩ' => 'i',
    'ĭ' => 'i',
    'ǐ' => 'i',
    'į' => 'i',
    'ĳ' => 'ij',
    'Ĵ' => 'J',
    'ĵ' => 'j',
    'Ĺ' => 'L',
    'Ľ' => 'L',
    'Ŀ' => 'L',
    'ĺ' => 'l',
    'ľ' => 'l',
    'ŀ' => 'l',
    'Ñ' => 'N',
    'ñ' => 'n',
    'ŉ' => 'n',
    'Ò' => 'O',
    'Ô' => 'O',
    'Õ' => 'O',
    'Ō' => 'O',
    'Ŏ' => 'O',
    'Ǒ' => 'O',
    'Ő' => 'O',
    'Ơ' => 'O',
    'Ø' => 'O',
    'Ǿ' => 'O',
    'Œ' => 'OE',
    'ò' => 'o',
    'ô' => 'o',
    'õ' => 'o',
    'ō' => 'o',
    'ŏ' => 'o',
    'ǒ' => 'o',
    'ő' => 'o',
    'ơ' => 'o',
    'ø' => 'o',
    'ǿ' => 'o',
    'º' => 'o',
    'œ' => 'oe',
    'Ŕ' => 'R',
    'Ŗ' => 'R',
    'ŕ' => 'r',
    'ŗ' => 'r',
    'Ŝ' => 'S',
    'Ș' => 'S',
    'ŝ' => 's',
    'ș' => 's',
    'ſ' => 's',
    'Ţ' => 'T',
    'Ț' => 'T',
    'Ŧ' => 'T',
    'Þ' => 'TH',
    'ţ' => 't',
    'ț' => 't',
    'ŧ' => 't',
    'þ' => 'th',
    'Ù' => 'U',
    'Ú' => 'U',
    'Û' => 'U',
    'Ũ' => 'U',
    'Ŭ' => 'U',
    'Ű' => 'U',
    'Ų' => 'U',
    'Ư' => 'U',
    'Ǔ' => 'U',
    'Ǖ' => 'U',
    'Ǘ' => 'U',
    'Ǚ' => 'U',
    'Ǜ' => 'U',
    'ù' => 'u',
    'ú' => 'u',
    'û' => 'u',
    'ũ' => 'u',
    'ŭ' => 'u',
    'ű' => 'u',
    'ų' => 'u',
    'ư' => 'u',
    'ǔ' => 'u',
    'ǖ' => 'u',
    'ǘ' => 'u',
    'ǚ' => 'u',
    'ǜ' => 'u',
    'Ŵ' => 'W',
    'ŵ' => 'w',
    'Ý' => 'Y',
    'Ÿ' => 'Y',
    'Ŷ' => 'Y',
    'ý' => 'y',
    'ÿ' => 'y',
    'ŷ' => 'y',

    // Russian
    'Ъ' => '',
    'Ь' => '',
    'А' => 'A',
    'Б' => 'B',
    'Ц' => 'C',
    'Ч' => 'Ch',
    'Д' => 'D',
    'Е' => 'E',
    'Ё' => 'E',
    'Э' => 'E',
    'Ф' => 'F',
    'Г' => 'G',
    'Х' => 'H',
    'И' => 'I',
    'Й' => 'J',
    'Я' => 'Ja',
    'Ю' => 'Ju',
    'К' => 'K',
    'Л' => 'L',
    'М' => 'M',
    'Н' => 'N',
    'О' => 'O',
    'П' => 'P',
    'Р' => 'R',
    'С' => 'S',
    'Ш' => 'Sh',
    'Щ' => 'Shch',
    'Т' => 'T',
    'У' => 'U',
    'В' => 'V',
    'Ы' => 'Y',
    'З' => 'Z',
    'Ж' => 'Zh',
    'ъ' => '',
    'ь' => '',
    'а' => 'a',
    'б' => 'b',
    'ц' => 'c',
    'ч' => 'ch',
    'д' => 'd',
    'е' => 'e',
    'ё' => 'e',
    'э' => 'e',
    'ф' => 'f',
    'г' => 'g',
    'х' => 'h',
    'и' => 'i',
    'й' => 'j',
    'я' => 'ja',
    'ю' => 'ju',
    'к' => 'k',
    'л' => 'l',
    'м' => 'm',
    'н' => 'n',
    'о' => 'o',
    'п' => 'p',
    'р' => 'r',
    'с' => 's',
    'ш' => 'sh',
    'щ' => 'shch',
    'т' => 't',
    'у' => 'u',
    'в' => 'v',
    'ы' => 'y',
    'з' => 'z',
    'ж' => 'zh',

    // German characters
    'Ä' => 'AE',
    'Ö' => 'OE',
    'Ü' => 'UE',
    'ß' => 'ss',
    'ä' => 'ae',
    'ö' => 'oe',
    'ü' => 'ue',

    // Turkish characters
    'Ç' => 'C',
    'Ğ' => 'G',
    'İ' => 'I',
    'Ş' => 'S',
    'ç' => 'c',
    'ğ' => 'g',
    'ı' => 'i',
    'ş' => 's',

    // Latvian
    'Ā' => 'A',
    'Ē' => 'E',
    'Ģ' => 'G',
    'Ī' => 'I',
    'Ķ' => 'K',
    'Ļ' => 'L',
    'Ņ' => 'N',
    'Ū' => 'U',
    'ā' => 'a',
    'ē' => 'e',
    'ģ' => 'g',
    'ī' => 'i',
    'ķ' => 'k',
    'ļ' => 'l',
    'ņ' => 'n',
    'ū' => 'u',

    // Ukrainian
    'Ґ' => 'G',
    'І' => 'I',
    'Ї' => 'Ji',
    'Є' => 'Ye',
    'ґ' => 'g',
    'і' => 'i',
    'ї' => 'ji',
    'є' => 'ye',

    // Czech
    'Č' => 'C',
    'Ď' => 'D',
    'Ě' => 'E',
    'Ň' => 'N',
    'Ř' => 'R',
    'Š' => 'S',
    'Ť' => 'T',
    'Ů' => 'U',
    'Ž' => 'Z',
    'č' => 'c',
    'ď' => 'd',
    'ě' => 'e',
    'ň' => 'n',
    'ř' => 'r',
    'š' => 's',
    'ť' => 't',
    'ů' => 'u',
    'ž' => 'z',

    // Polish
    'Ą' => 'A',
    'Ć' => 'C',
    'Ę' => 'E',
    'Ł' => 'L',
    'Ń' => 'N',
    'Ó' => 'O',
    'Ś' => 'S',
    'Ź' => 'Z',
    'Ż' => 'Z',
    'ą' => 'a',
    'ć' => 'c',
    'ę' => 'e',
    'ł' => 'l',
    'ń' => 'n',
    'ó' => 'o',
    'ś' => 's',
    'ź' => 'z',
    'ż' => 'z',

    // Greek
    'Α' => 'A',
    'Β' => 'B',
    'Γ' => 'G',
    'Δ' => 'D',
    'Ε' => 'E',
    'Ζ' => 'Z',
    'Η' => 'E',
    'Θ' => 'Th',
    'Ι' => 'I',
    'Κ' => 'K',
    'Λ' => 'L',
    'Μ' => 'M',
    'Ν' => 'N',
    'Ξ' => 'X',
    'Ο' => 'O',
    'Π' => 'P',
    'Ρ' => 'R',
    'Σ' => 'S',
    'Τ' => 'T',
    'Υ' => 'Y',
    'Φ' => 'Ph',
    'Χ' => 'Ch',
    'Ψ' => 'Ps',
    'Ω' => 'O',
    'Ϊ' => 'I',
    'Ϋ' => 'Y',
    'ά' => 'a',
    'έ' => 'e',
    'ή' => 'e',
    'ί' => 'i',
    'ΰ' => 'Y',
    'α' => 'a',
    'β' => 'b',
    'γ' => 'g',
    'δ' => 'd',
    'ε' => 'e',
    'ζ' => 'z',
    'η' => 'e',
    'θ' => 'th',
    'ι' => 'i',
    'κ' => 'k',
    'λ' => 'l',
    'μ' => 'm',
    'ν' => 'n',
    'ξ' => 'x',
    'ο' => 'o',
    'π' => 'p',
    'ρ' => 'r',
    'ς' => 's',
    'σ' => 's',
    'τ' => 't',
    'υ' => 'y',
    'φ' => 'ph',
    'χ' => 'ch',
    'ψ' => 'ps',
    'ω' => 'o',
    'ϊ' => 'i',
    'ϋ' => 'y',
    'ό' => 'o',
    'ύ' => 'y',
    'ώ' => 'o',
    'ϐ' => 'b',
    'ϑ' => 'th',
    'ϒ' => 'Y',

    /* Arabic */
    'أ' => 'a',
    'ب' => 'b',
    'ت' => 't',
    'ث' => 'th',
    'ج' => 'g',
    'ح' => 'h',
    'خ' => 'kh',
    'د' => 'd',
    'ذ' => 'th',
    'ر' => 'r',
    'ز' => 'z',
    'س' => 's',
    'ش' => 'sh',
    'ص' => 's',
    'ض' => 'd',
    'ط' => 't',
    'ظ' => 'th',
    'ع' => 'aa',
    'غ' => 'gh',
    'ف' => 'f',
    'ق' => 'k',
    'ك' => 'k',
    'ل' => 'l',
    'م' => 'm',
    'ن' => 'n',
    'ه' => 'h',
    'و' => 'o',
    'ي' => 'y'
  ];


  $str = strtolower(strtr($str, $slugRules));
  $str = preg_replace($slugRegExp, $slugSeparator, $str);
  $str = strtolower($str);

  return trim($str, $slugSeparator);
}

// enquanto não tenho tempo pra refatorar os códigos do Renato
// criei uma função rápida q resolve o meu problema pontual.
// fazer revisão geral depois.
function _solrToNumberDate($string)
{
  $Y = substr($string, 0, 4);
  $m = substr($string, 5, 2);
  $d = substr($string, 8, 2);

  $H = substr($string, 11, 2);
  $i = substr($string, 14, 2);
  $s = substr($string, 17, 2);

  return $Y . $m . $d . $H . $i . $s;
}

function solrToNumberDate($string)
{
  return numberDate(convertDateToBrDate($string));
}

// function uiDatedmYHisToNumberYmdHis($string){
function numberDate($string)
{
  if (empty($string)) {
    return false;
  }
  $a = explode(" ", $string);
  $date = _filterItem($a, 0);
  $hourString  = _filterItem($a, 1);
  $hour = ($hourString) ? $hourString : "00:00:00";

  $b = explode("/", $date);
  $d = _filterItem($b, 0);
  $m = _filterItem($b, 1);
  $Y = _filterItem($b, 2);
  $c = explode(":", $hour);
  $H = _filterItem($c, 0);
  $i = _filterItem($c, 1);
  $s = _filterItem($c, 2);
  return (int) "$Y$m$d$H$i$s";
}

function isCpf($str)
{
  // elimina caracteres não numéricos
  $cpf = preg_replace('/[^0-9]/is', '', $str);

  // checa se só tem caracteres não numéricos
  if (!ctype_digit($cpf)) {
    return false;
  }

  if (!length($cpf, 11)) {
    return false;
  }

  // checa se é válido
  if (preg_match('/(\d)\1{10}/', $cpf)) {
    return false;
  }

  // checa se é válido
  for ($t = 9; $t < 11; $t++) {
    for ($d = 0, $c = 0; $c < $t; $c++) {
      $d += $cpf[$c] * (($t + 1) - $c);
    }
    $d = ((10 * $d) % 11) % 10;
    if ($cpf[$c] != $d) {
      return false;
    }
  }

  return true;
}

function length($prop, $param)
{
  return (is_string($prop)) ? (strlen($prop) == $param) : (count($prop) == $param);
}


function convertDateToSolr($date)
{
  $date = trim($date);
  if (preg_match_all("/([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9][0-9]{2}) ([0-2]?[0-9]):([0-6]?[0-9]):([0-6]?[0-9])/", $date, $arrayDate)) {
    return $arrayDate[3][0] . "-" . $arrayDate[2][0] . "-" . $arrayDate[1][0] . "T" . $arrayDate[4][0] . ":" . $arrayDate[5][0] . ":" . $arrayDate[6][0] . "Z";
  }
  if (preg_match_all("/([0-9]?[0-9][0-9]{2})[\/\.\\\-]([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9]) ([0-2]?[0-9]):([0-6]?[0-9]):([0-6]?[0-9])/", $date, $arrayDate)) {
    return $arrayDate[1][0] . "-" . $arrayDate[2][0] . "-" . $arrayDate[3][0] . "T" . $arrayDate[4][0] . ":" . $arrayDate[5][0] . ":" . $arrayDate[6][0] . "Z";
  }
  if (preg_match_all("/([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9][0-9]{2})/", $date, $arrayDate)) {
    return $arrayDate[3][0] . "-" . $arrayDate[2][0] . "-" . $arrayDate[1][0] . "T00:00:00Z";
  }
  if (preg_match_all("/([0-9]?[0-9][0-9]{2})[\/\.\\\-]([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9])/", $date, $arrayDate)) {
    return $arrayDate[1][0] . "-" . $arrayDate[2][0] . "-" . $arrayDate[3][0] . "T00:00:00Z";
  }
  if (preg_match_all("/([0-9]?[0-9][0-9]{2})[\/\.\\\-]([0-9]?[0-9])[\/\.\\\-]([0-9]?[0-9])T([0-2]?[0-9]):([0-6]?[0-9]):([0-6]?[0-9])Z/", $date, $arrayDate)) {
    return $arrayDate[3][0] . "-" . $arrayDate[2][0] . "-" . $arrayDate[1][0] . "T" . $arrayDate[4][0] . ":" . $arrayDate[5][0] . ":" . $arrayDate[6][0] . "Z";
  }
  return null;
}

/**
 * @param $date_time
 * @param $noTime
 * @return string data
 */
function convertDateToBrDate($date_time, $noTime = FALSE)
{
  $date_time = trim($date_time);
  //@TODO: Upgrade p/ usar o DataMask  ( esse metodo vai sumir)
  # ($date_time, $output_string, $utilizar_funcao_date = false) {
  // Verifica se a string está num formato válido de data ("aaaa-mm-dd" ou "aaaa-mm-dd hh:mm:ss")
  if (preg_match("/^(\d{4}(-\d{2})-\d{2})$/", $date_time)) {
    $valor['d'] = substr($date_time, 8, 2);
    $valor['m'] = substr($date_time, 5, 2);
    $valor['Y'] = substr($date_time, 0, 4);
    // Verifica se a string está num formato válido de horário ("hh:mm:ss")
  } else if (preg_match("/^(\d{4})-(\d{2})-(\d{2})[ T]((\d{2}):(\d{2}):(\d{2}))?Z?$/", $date_time, $match)) {
    $valor['d'] = $match[3];
    $valor['m'] = $match[2];
    $valor['Y'] = $match[1];
    $valor['y'] = $match[1];
    $valor['H'] = $match[5];
    $valor['i'] = $match[6];
    $valor['s'] = $match[7];
    // Verifica se a string está num formato válido de horário ("hh:mm:ss")
  } else if (preg_match("/^(\d{2}(:\d{2}){2})?$/", $date_time)) {
    //se não tinha hora na data enviada, vai sem data
    $noTime = TRUE;
    $valor['d'] = NULL;
    $valor['m'] = NULL;
    $valor['Y'] = NULL;
    $valor['y'] = NULL;
    $valor['H'] = substr($date_time, 0, 2);
    $valor['i'] = substr($date_time, 3, 2);
    $valor['s'] = substr($date_time, 6, 2);
  } else {
    return $date_time;
  }
  if ($valor['d'] == "") {
    return "";
  }
  if ($noTime || !isset($valor['H'])) {
    return $valor['d'] . "/" . $valor['m'] . "/" . $valor['Y'];
  }
  return  $valor['d'] . "/" . $valor['m'] . "/" . $valor['Y'] . " " . $valor['H'] . ":" . $valor['i'] . ":" . $valor['s'];
}

/**
 * Pega um array de array e transforma só em array de 1 dimensão
 */
function _flatArray($array)
{
  if (!is_array($array)) {
    return $array;
  }
  //se é array de array, vira array só
  $res = [];
  $k = 0;
  $kV = 0;
  foreach ($array as $key => $value) {
    if (!is_array($value)) {
      $res[is_int($key) ? $k++ : $key] = $value;
      continue;
    }
    foreach ($value as $keyV => $valueV) {
      $res[is_int($keyV) ? $kV++ : $keyV] = $valueV;
    }
  }
  return $res;
}

/**
 * filtra itens em 1 dimensão
 */
function _filterItem($item, $property)
{
  if (is_array($item)) {
    if (array_key_exists($property, $item)) {
      return _flatArray($item[$property]);
    }
    //se a propriedade enviada não está no nome da chave da array, deve significar uma propriedade do objeto em questao
    $res = [];
    $hasItens = false;
    foreach ($item as $key => $value) {
      if (isset($value->$property)) {
        $res[] = $value->$property;
        $hasItens = true;
      }
    }
    if (!$hasItens) {
      return null;
    }
    return _flatArray($res);
  }
  if (is_object($item) && isset($item->$property)) {
    return $item->$property;
  }
  return null;
}

/**
 * //onde $array = [{f:[{name:'a'}]},{f:[{name:'b'}]}];
 * filterData($array, "f.name")
 * //retorna ['a', 'b']
 * @return array||null
 */
function filterData($data, $path = "", $itemFilter = null)
{
  if (!$path && !$itemFilter) {
    return $data;
  }
  if (!$path) {
    if (is_array($data)) {
      $res = [];
      foreach ($data as $key => $value) {
        if ($itemFilter) $itemRes = $itemFilter($value, $key);
        //se o filtro retornar null, não entra na lista. False entra na lista
        if ($itemRes !== null) {
          $res[] = $itemRes;
        }
      }
      //retorna só passando pelo filtro com indice zero
      return $res;
    }
    if ($itemFilter) return $itemFilter($data, 0);
    return $data;
  }
  if (!is_array($path)) {
    $path = explode(".", $path);
  }
  $current = $data;
  foreach ($path as $prop) {
    $current = _filterItem($current, $prop);
  }
  return filterData($current, null, $itemFilter);
}

/**
 * a ideia é enviar [1,2,3] e entender que é array, por exemplo
 */
function parseValue($valString)
{
  if (empty($valString)) {
    return $valString;
  }
  if (is_string($valString)) {
    //tenta json
    $jsonResult = json_decode($valString);
    if (!json_last_error()) {
      //conseguiu parsear json
      return $jsonResult;
    }
  }
  return $valString;
}

function getUrlContent($url, $defaultValue = null, $isJson = true)
{
  $headers = get_headers($url);
  $headerResult = substr($headers[0], 9, 3);

  if ($headerResult != "200") {
    return $defaultValue;
  }
  $strResult = file_get_contents($url);
  if (!$isJson) {
    return $strResult;
  }
  $json = json_decode($strResult);
  if (json_last_error()) {
    return $defaultValue;
  }
  return $json;
}


function dateAvailable($startDate, $endDate)
{
  $numberNow = date("YmdHis", time());

  if ($endDate !== null && solrToNumberDate($endDate) != "" && solrToNumberDate($endDate) < $numberNow) {
    return "finished";
  }

  if ($startDate !== null && solrToNumberDate($startDate) > $numberNow) {
    return "comingSoon";
  }

  return "opened";
}

function _img($source, $crop)
{
  return preg_replace('/(.+\/)(.+\/)(.+\/)(.+)/', '$1' . '$2' .  $crop . '/$4', $source);
}

function isPreview($_status)
{
  return in_array("preview", $_status);
}

function isPublished($_status)
{
  return in_array("published", $_status);
}

function encrypt($simple_string, $encryption_key)
{   
  // Store the cipher method
  $ciphering = "AES-128-CTR";
      
  // Use OpenSSl Encryption method
  $iv_length = openssl_cipher_iv_length($ciphering);
  $options = 0;
      
  // Non-NULL Initialization Vector for encryption
  $encryption_iv = 'a04c68f2252042e3';
  
  // Use openssl_encrypt() function to encrypt the data
  $encryption = openssl_encrypt($simple_string, $ciphering,
                $encryption_key, $options, $encryption_iv);
  return $encryption;
}

function decrypt($encryption,$decryption_key)
{
  // Store the cipher method
  $ciphering = "AES-128-CTR";

  // Non-NULL Initialization Vector for decryption
  $decryption_iv = 'a04c68f2252042e3';
  $options = 0;
  // Use openssl_decrypt() function to decrypt the data
  $decryption = openssl_decrypt($encryption, $ciphering,$decryption_key, $options, $decryption_iv);
  return $decryption;
}
