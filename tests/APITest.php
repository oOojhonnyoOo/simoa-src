<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Simoa\API;

final class APITest extends TestCase
{

  protected function setUp(): void
  {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

    $_SERVER['HTTP_HOST'] = 'localhost:8080';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_COOKIE'] = '_conasems=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb250YSIsImF1ZCI6ImNvbmFzZW1zIiwiaWF0IjoxNjg4OTk0NDAzLCJuYmYiOjE2ODg5OTQ0MDMsImV4cCI6MTY4OTU5OTIwMywic3ViIjoiMTE1NDM5Mjc0MDgiLCJkYXRhIjp7ImlkIjoiMiIsImNwZiI6IjExNTQzOTI3NDA4IiwidXNlcm5hbWUiOiIxMTU0MzkyNzQwOCIsInJvbGVzIjpbInJvb3QiLCJjb250YVwvdXNlciJdLCJmdWxsbmFtZSI6IkpvXHUwMGUzbyBKb3NlIGRlIFNvdXNhIE5ldG8iLCJ0b2tlbklkIjoxMX19.uN0d0RVnWLGwwHRWo7mWvVOKgfiTKbEo5tBdS3gBJGc';

  }

  /**
   * @dataProvider uriProvider
   */
  public function testMandatoryHeadersParameters(
    string $uriExample, 
    string $expectedSite,
    string $expectedModule,
    string $expectedMethod,
    string|null $expectedId
  ): void
  {
    $_SERVER['REQUEST_URI'] = $uriExample;

    $api = new API();

    $this->assertSame(
      $api->headers->site, 
      $expectedSite, 
      "Test expected site header parameter from REQUEST_URI"
    );

    $this->assertSame(
      $api->headers->module, 
      $expectedModule, 
      "Test expected module header parameter from REQUEST_URI"
    );

    $this->assertSame(
      $api->headers->method, 
      $expectedMethod, 
      "Test expected method header parameter from REQUEST_URI"
    );
  }

  /**
   * @dataProvider uriProvider
   */
  public function testIdHeaderParameter(
    string $uriExample, 
    string $expectedSite,
    string $expectedModule,
    string $expectedMethod,
    string|null $expectedId
  ): void
  {
    $_SERVER['REQUEST_URI'] = $uriExample;

    $api = new API();
    $thereIsId = $expectedId != null;

    if ($thereIsId) {
      $this->assertSame(
        $api->headers->id,
        $expectedId,
        "Test expected ID header parameter from REQUEST_URI"
      );
    } else {
      $this->assertObjectNotHasAttribute(
        'id',
        $api->headers,
        "Test headers object doesn't have id attribute"
      );
    }

  }

  public function testRequestParameter() 
  {
    $token = 'test';
    $origin = 'test';
    $referer = 'test';
    $key = 'test';
    $apiWithoutRequest = new API();

    $this->assertObjectNotHasAttribute('token', $apiWithoutRequest->request);
    $this->assertObjectNotHasAttribute('origin', $apiWithoutRequest->request);
    $this->assertObjectNotHasAttribute('referer', $apiWithoutRequest->request);
    $this->assertObjectNotHasAttribute('key', $apiWithoutRequest->request);

    $_SERVER['HTTP_TOKEN'] = $token;
    $_SERVER['HTTP_ORIGIN'] = $origin;
    $_SERVER['HTTP_REFERER'] = $referer;
    $_SERVER['HTTP_KEY'] = $key;

    $apiWithRequest = new API();

    $this->assertSame(
      $apiWithRequest->request->token, 
      $token, 
      "Test token request parameter from header"
    );

    $this->assertSame(
      $apiWithRequest->request->origin, 
      $origin, 
      "Test origin request parameter from header"
    );

    $this->assertSame(
      $apiWithRequest->request->referer, 
      $referer, 
      "Test referer request parameter from header"
    );

    $this->assertSame(
      $apiWithRequest->request->key,
      $key,
      "Test key request parameter from header"
    );
  }

  public function testContentTypeWithoutRequestParameter()
  {
    $apiWithoutRequest = new API();
    $this->assertObjectNotHasAttribute('contenttype', $apiWithoutRequest->request);
  }

  public function testContentTypeTextPlainRequestParameter()
  {
    $expectedContentType = 'application/json';
    $_SERVER['HTTP_CONTENT_TYPE'] = $expectedContentType;

    $api = new API();

    $this->assertEquals(
      $api->request->contenttype, 
      $expectedContentType,
      "Test application/json content type header parameter"
    );
  }

  public function testContentTypeMultipartFormDataRequestParameter()
  {
    $expectedContentType = 'multipart/form-data';
    $_SERVER['HTTP_CONTENT_TYPE'] = $expectedContentType;
    $_POST['data'] = json_encode(['test' => 'test']);
    $api = new API();

    $this->assertEquals(
      $api->request->contenttype, 
      $expectedContentType,
      "Test multipart/form-data content type header parameter"
    );
  }

  public function testAuth() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testSystem() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testParse() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testData() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testParseClass() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function testContentAuth() 
  {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  public function uriProvider(): array
  {
    return [
      // ['/api/conta/teste/list?rows=100&start=0', 'conta', 'teste', 'list', null],
      // ['/api/conta/teste/save', 'conta', 'teste', 'save', null],
      ['/api/conta/teste/delete/3', 'conta', 'teste', 'delete', '3'],
      ['/api/conta/teste/populate/3', 'conta', 'teste', 'populate', '3']
    ];
  }

}
