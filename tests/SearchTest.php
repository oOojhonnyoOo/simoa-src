<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\Search;

class SearchTest extends TestCase
{
    public function testReset()
    {
        $search = new Search();
        $search->q = 'foo';
        $search->fq = 'bar';
        $search->fl = 'baz';
        $search->hint = true;

        $search->reset();

        $this->assertEquals('', $search->q);
        $this->assertEquals('', $search->fq);
        $this->assertEquals('', $search->fl);
        $this->assertEquals(false, $search->hint);
    }
}
