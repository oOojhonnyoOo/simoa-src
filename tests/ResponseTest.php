<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
  public function testLogWithObject()
  {
    $response = new \Simoa\Response();
    $response->log((object)['name' => 'John', 'age' => 30]);

    $this->assertEquals('{"name":"John","age":30}', json_encode($response->response));
  }

  public function testLogWithScalarValue()
  {
    $response = new \Simoa\Response();
    $response->log('Something happened.');

    $this->assertEquals('["Something happened."]', json_encode($response->response->log));
  }

  public function testAddKeyValuePair()
  {
    $response = new \Simoa\Response();
    $response->add('name', 'John');

    $this->assertEquals('{"name":"John"}', json_encode($response->response));
  }

  // public function testAddHttpHeader()
  // {
  //   $response = new \Simoa\Response();

  //   ob_start();
  //   $response->addHttpHeader('Content-Type: application/json');
  //   $headers = xdebug_get_headers();
  //   ob_end_clean();

  //   $this->assertContains('Content-Type: application/json', $headers);
  // }

  // public function testErrors()
  // {
  //   $response = new \Simoa\Response();
  //   $response->errors(['field1' => 'Error message 1', 'field2' => 'Error message 2']);

  //   $this->assertFalse($response->success);
  //   $this->assertEquals(['field1' => 'Error message 1', 'field2' => 'Error message 2'], $response->errors);
  //   $this->assertEquals(200, $response->code);
  // }

  // public function testError()
  // {
  //   $response = new \Simoa\Response();
  //   $response->error('Error message');

  //   $this->assertFalse($response->success);
  //   $this->assertEquals(['Error message'], $response->errors);
  //   $this->assertEquals(200, $response->code);
  // }
}
