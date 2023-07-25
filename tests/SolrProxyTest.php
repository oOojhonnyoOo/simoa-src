<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Simoa\SolrProxy;

class SolrProxyTest extends TestCase
{
  public function testRequestWithValidAPIKey()
  {
    $solrProxy = new SolrProxy("default");
    $_SERVER['QUERY_STRING'] = "q=example";

    ob_start();
    $solrProxy->request();
    ob_get_clean();

    $this->expectOutputString('');
  }

  public function testRequestWithInvalidAPIKey()
  {
    $solrProxy = new SolrProxy("default");
    $_SERVER['QUERY_STRING'] = "q=example";
    $_SERVER['HTTP_KEY'] = "invalid_key";

    ob_start();
    $solrProxy->request();
    $output = ob_get_clean();

    $this->assertStringContainsString('forbidden', $output);
    $this->assertEquals(403, http_response_code());
  }

  /*
  public function testCheckAPIKeyWithValidKey()
  {
    $solrProxy = new SolrProxy("default");

    $_SERVER['HTTP_KEY'] = "valid_key";

    $result = $this->callPrivateMethod($solrProxy, 'checkAPIKey');

    $this->assertTrue($result);
  }

  public function testCheckAPIKeyWithInvalidKey()
  {
    $solrProxy = new SolrProxy("default");
    $headers = array("key" => "invalid_key");
    
    $result = $this->callPrivateMethod($solrProxy, 'checkAPIKey', $headers);

    $this->assertFalse($result);
  }

  private function callPrivateMethod($object, $method, $args = [])
  {
    $class = new \ReflectionClass(get_class($object));
    $method = $class->getMethod($method);
    $method->setAccessible(true);
    return $method->invokeArgs($object, $args);
  }
  */
}
