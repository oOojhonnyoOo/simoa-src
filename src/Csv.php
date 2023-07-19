<?php

namespace Simoa;

class Csv
{
  var $delimiter = ',';

  function addDataAsLine($data, $file)
  {
    $header = [];
    $values = [];
    foreach ($data as $k => $v) {
      $header[] = $k;
      $values[] = $v;
    }

    $mode = 'a+';
    $content = [];

    if (!file_exists($file)) {
      $content[] = $header;
    }

    $content[] = $values;

    $File = new File([
      'file' => $file,
      'mode' => $mode
    ]);

    return $File->csvsave($content);
  }

  private function header($array)
  {
		return $this->line($array);
	}

	private function addLine($origin, $array)
  {
		$origin .= $this->line($array, $this->delimiter);
		return $origin;
	}

	private function line($array)
  {
		$line = "";

		for ($i=0; $i<count($array); $i++) {
			if ($i > 0) {
				$line .= $this->delimiter;
			}

			$line .= $array[$i];
		}

		$line .= "\n";

		return $line;
	}
}
