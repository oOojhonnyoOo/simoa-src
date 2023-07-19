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

  public function testEqual()
  {
      $this->assertTrue(Helper::compare(5, 5, "=="));
  }

  public function testIdentical()
  {
      $this->assertTrue(Helper::compare(5, 5, "==="));
  }

  public function testNonIdentical()
  {
      $this->assertFalse(Helper::compare(6, 5, "==="));
  }

  public function testGreaterThanOrEqual()
  {
      $this->assertTrue(Helper::compare(5, 3, ">="));
  }

  public function testNotGreaterThanOrEqual()
  {
      $this->assertFalse(Helper::compare(3, 5, ">="));
  }

  public function testLessThanOrEqual()
  {
      $this->assertTrue(Helper::compare(5, 5, "<="));
  }

  public function testNotLessThanOrEqual()
  {
      $this->assertFalse(Helper::compare(6, 5, "<="));
  }

  public function testLessThan()
  {
      $this->assertTrue(Helper::compare(3, 5, "<"));
  }

  public function testNotLessThan()
  {
      $this->assertFalse(Helper::compare(5, 3, "<"));
  }

  public function testGreaterThan()
  {
      $this->assertTrue(Helper::compare(5, 3, ">"));
  }

  public function testNotEqual()
  {
      $this->assertTrue(Helper::compare(5, 3, "!="));
  }

  public function testNotIdentical()
  {
      $this->assertTrue(Helper::compare(5, "5", "!=="));
  }

  public function testInArray()
  {
      $this->assertTrue(Helper::compare(2, [1, 2, 3], "in"));
  }

  public function testErrorInArray()
  {
      $this->assertFalse(Helper::compare(5, [1, 2, 3], "in"));
  }

  public function testNotInArray()
  {
      $this->assertTrue(Helper::compare(4, [1, 2, 3], "out"));
  }

  public function testErrorNotInArray()
  {
      $this->assertFalse(Helper::compare(2, [1, 2, 3], "out"));
  }

  public function testInvalidComparison()
  {
      $this->assertFalse(Helper::compare(5, 5, "invalid"));
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