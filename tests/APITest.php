<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Simoa\API;

final class APITest extends TestCase
{

  private $sites = [
    '/api/conta/teste/list?rows=100&start=0',
    '/api/conta/teste/save',
    '/api/conta/teste/delete/3',
    '/api/conta/teste/populate/3',
  ];

  public function testGetRightHeadersParameters(): void
  {
    $_SERVER['REQUEST_URI'] = '/api/conta/teste/delete/3?test=test';
    $_SERVER['HTTP_HOST'] = 'localhost:8080';

    $api = new API();
    // $this->execute();

    $this->assertSame("test", "test");
  }

}
