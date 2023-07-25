<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\Csv;

final class CsvTest extends TestCase
{
  private $tempDir;

  protected function setUp(): void
  {
    $this->tempDir = sys_get_temp_dir() . '/csv_test';
    mkdir($this->tempDir, 0755);
  }

  protected function tearDown(): void
  {
    $this->deleteDirectory($this->tempDir);
  }

  private function deleteDirectory($dir)
  {
    if (!file_exists($dir)) {
      return;
    }

    foreach (scandir($dir) as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $path = $dir . '/' . $item;
      if (is_dir($path)) {
        $this->deleteDirectory($path);
      } else {
        unlink($path);
      }
    }
    rmdir($dir);
  }

  public function testAddDataAsLine()
  {
    $data = [
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'age' => 30,
    ];

    $file = $this->tempDir . '/test.csv';
    $csv = new Csv();

    $result = $csv->addDataAsLine($data, $file);

    $this->assertTrue($result);

    $expectedContent = "name,email,age\n" . '"John Doe"' . ",john.doe@example.com,30\n";
    $actualContent = file_get_contents($file);
    $this->assertEquals($expectedContent, $actualContent);
  }

  public function testAddMultipleLines()
  {
    $data1 = [
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'age' => 30,
    ];

    $data2 = [
      'name' => 'Jane Smith',
      'email' => 'jane.smith@example.com',
      'age' => 25,
    ];

    $file = $this->tempDir . '/test.csv';
    $csv = new Csv();

    $result1 = $csv->addDataAsLine($data1, $file);
    $result2 = $csv->addDataAsLine($data2, $file);

    $this->assertTrue($result1);
    $this->assertTrue($result2);

    $expectedContent = "name,email,age\n" . '"John Doe"' . ",john.doe@example.com,30\n" . '"Jane Smith"' . ",jane.smith@example.com,25\n";
    $actualContent = file_get_contents($file);
    $this->assertEquals($expectedContent, $actualContent);
  }

  public function testSpecialCharacters()
  {
    $data = [
      'name' => 'James, "Jim" Brown',
      'email' => "james@example.com\nnew_line",
      'age' => 40,
    ];

    $file = $this->tempDir . '/test.csv';
    $csv = new Csv();

    $result = $csv->addDataAsLine($data, $file);

    $this->assertTrue($result);

    $expectedContent = "name,email,age\n\"James, \"\"Jim\"\" Brown\",\"james@example.com\nnew_line\",40\n";
    $actualContent = file_get_contents($file);
    $this->assertEquals($expectedContent, $actualContent);
  }

  public function testFileNotExist()
  {
    $data = [
      'name' => 'John Doe',
      'email' => 'john.doe@example.com',
      'age' => 30,
    ];

    $file = $this->tempDir . '/nonexistent.csv';
    $csv = new Csv();

    $result = $csv->addDataAsLine($data, $file);

    $this->assertTrue($result);

    $expectedContent = "name,email,age\n" . '"John Doe"' . ",john.doe@example.com,30\n";
    $actualContent = file_get_contents($file);
    $this->assertEquals($expectedContent, $actualContent);
  }
}
