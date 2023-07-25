<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\Search;

final class SearchTest extends TestCase
{
  private readonly Search $search;

  public function setUp(): void
  {
    $this->search = new Search();
  }

  public function tearDown(): void
  {
    $_GET = null;
  }

  public function testDefaultConstruct()
  {
    $this->assertEquals($this->search->config, (new \Simoa\Config));
    $this->assertEquals($this->search->response, (new \Simoa\Response));
  }

  public function testSetQWithValue()
  {
    $_GET['q'] = 'test';

    $this->search->q();

    $this->assertEquals('test', $this->search->q);
  }

  public function testSetQWithoutValue()
  {
    $this->search->q();

    $this->assertNull($this->search->q);
  }

  public function testSetFQWithValue()
  {
    $_GET['fq'] = 'test';

    $this->search->fq();

    $this->assertEquals('test', $this->search->fq);
  }

  public function testSetFQWithoutValue()
  {
    $this->search->fq();

    $this->assertNull($this->search->fq);
  }

  public function testSetFLWithValue()
  {
    $_GET['fl'] = 'test';

    $this->search->fl();

    $this->assertEquals('test,', $this->search->fl);
  }

  public function testSetFLWithoutValue()
  {
    $this->search->fl();

    $this->assertNull($this->search->fl);
  }

  public function testSortWithValue()
  {
    $_GET['sort'] = 'test';

    $this->search->sort();

    $this->assertEquals('test', $this->search->sort);
  }

  public function testSortWithoutValue()
  {
    $this->search->sort();

    $this->assertEquals('sortDate DESC', $this->search->sort);
  }

  public function testSortWhereSortAttributeIsSet()
  {
    $this->search->sort = 'attribute $sort is set';

    $_GET['sort'] = 'test';

    $this->search->sort();

    $this->assertNotEquals($_GET['sort'], $this->search->sort);
    $this->assertEquals('attribute $sort is set', $this->search->sort);
  }

  public function testStartWithValue()
  {
    $_GET['start'] = 'test';

    $this->search->start();

    $this->assertEquals('test', $this->search->start);
  }

  public function testStartWithoutValue()
  {
    $this->search->start();

    $this->assertNull($this->search->start);
  }

  public function testStartWhereStartAttributeIsSet()
  {
    $this->search->start = 'attribute $start is set';

    $_GET['start'] = 'test';

    $this->search->start();

    $this->assertNotEquals($_GET['start'], $this->search->start);
    $this->assertEquals('attribute $start is set', $this->search->start);
  }

  public function testRowsWithValue()
  {
    $_GET['rows'] = 'test';

    $this->search->rows();

    $this->assertEquals('test', $this->search->rows);
  }

  public function testRowsWithoutValue()
  {
    $this->search->rows();

    $this->assertNull($this->search->rows);
  }

  public function testRowsWhereRowsAttributeIsSet()
  {
    $this->search->rows = 'attribute $rows is set';

    $_GET['rows'] = 'test';

    $this->search->rows();

    $this->assertNotEquals($_GET['rows'], $this->search->rows);
    $this->assertEquals('attribute $rows is set', $this->search->rows);
  }

  public function testSetCoreWithDefaultValue()
  {
    $this->search->setCore();

    $this->assertEquals($this->search->config->solr->default, $this->search->core);
  }

  public function testSetCoreWithCustomValue()
  {
    $this->search->config->core = 'test';

    $this->search->setCore();

    $this->assertNotEquals($this->search->config->solr->default, $this->search->core);
    $this->assertEquals('test', $this->search->core);
  }

  public function test_keyOr()
  {
    $result = $this->search->_keyOr('test', [1, 2, 3]);

    $this->assertEquals('(test:1 OR test:2 OR test:3)', $result);
  }

  
  public function test_keyOrWithDuplicateValues()
  {
    $result = $this->search->_keyOr('test', [1, 2, 3, 3, 1, 2]);

    $this->assertEquals('(test:1 OR test:2 OR test:3)', $result);
  }

  public function test_keyOrWithNullValues()
  {
    $result = $this->search->_keyOr('', []);

    $this->assertEmpty($result);
  }

  public function testReset()
  {
    $this->search->q = 'test';
    $this->search->fq = 'test';
    $this->search->fl = 'test';
    $this->search->hint = true;

    $this->search->reset();

    $this->assertEmpty($this->search->q);
    $this->assertEmpty($this->search->fq);
    $this->assertEmpty($this->search->fl);
    $this->assertEmpty($this->search->hint);
  }
}
