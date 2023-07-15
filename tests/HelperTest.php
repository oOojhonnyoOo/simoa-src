<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Simoa\Helper;

final class HelperTest extends TestCase
{
  /**
   * @dataProvider rightRequestURIMethodProvider
   */
  public function testRightValuesFromRequestURIMethod(
    string $requestURIExample, 
    string $expectedResult
  ): void
  {
    $_SERVER['REQUEST_URI'] = $requestURIExample;
    $result = Helper::requestURI();

    $this->assertSame($result, $expectedResult, "Test right values from requestURI method");
  }

  /**
   * @dataProvider wrongRequestURIMethodProvider
   */
  public function testWrongValuesFromRequestURIMethod(
    string $requestURIExample, 
    string $unexpectedResult
  ): void
  {
    $_SERVER['REQUEST_URI'] = $requestURIExample;
    $result = Helper::requestURI();

    $this->assertFalse($result === $unexpectedResult, 'Test wrong values from RequestURI method');
  }

  public function rightRequestURIMethodProvider(): array
  {
    return [
      ['/api/conta/teste/list?rows=100&start=0', 'api/conta/teste/list'],
      ['/api/conta/teste/save', 'api/conta/teste/save'],
      ['/api/conta/teste/save?rows=100&start=0', 'api/conta/teste/save'],
      ['/api/conta/teste/delete/3', 'api/conta/teste/delete/3'],
      ['/api/conta/teste/delete/3?test=test', 'api/conta/teste/delete/3'],
      ['/api/conta/teste/populate/3', 'api/conta/teste/populate/3'],
    ];
  }

  public function wrongRequestURIMethodProvider(): array
  {
    return [
      ['/api/conta/teste/list?rows=100&start=0', '/api/conta/teste/list'],
      ['/api/conta/teste/save', '/api/conta/teste/save'],
      ['/api/conta/teste/save?rows=100&start=0', 'api/conta/teste/save?rows=100&start=0'],
      ['/api/conta/teste/delete/3', 'api/conta/teste/delete/3/'],
      ['/api/conta/teste/delete/3?test=test', '/api/conta/teste/delete/3?test=test'],
      ['/api/conta/teste/populate/3', 'api/conta/teste/populate/3?'],
    ];
  }

}