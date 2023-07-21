<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\TemplateCache;

final class TemplateCacheTest extends TestCase
{
    public function testVarsWithValidHeaders()
    {
        $object = (object) [
            'config' => (object) [
                'formats' => (object) [
                    'data' => 'Hello [name], welcome to [city]!',
                    'extension' => '.html',
                ],
            ],
            'headers' => (object) [
                'name' => 'John',
                'city' => 'New York',
            ],
            'data' => (object) [
                'some_key' => 'some_value',
            ],
        ];

        $templateCache = new TemplateCache($object);

        $templateCache->vars();

        $expectedUniqueId = 'Hello John, welcome to New York!';
        $expectedFilename = 'Hello John, welcome to New York!.html';
        $this->assertEquals($expectedUniqueId, $templateCache->uniqueId);
        $this->assertEquals($expectedFilename, $templateCache->filename);
    }

    public function testVarsWithMissingHeaders()
    {
        $object = (object) [
            'config' => (object) [
                'formats' => (object) [
                    'data' => 'Hello [name], welcome to [city]!',
                    'extension' => '.html',
                ],
            ],
            'headers' => (object) [],
            'data' => (object) [
                'name' => 'John',
                'city' => 'New York',
            ],
        ];

        $templateCache = new TemplateCache($object);

        $templateCache->vars();

        $expectedUniqueId = 'Hello John, welcome to New York!';
        $expectedFilename = 'Hello John, welcome to New York!.html';
        $this->assertEquals($expectedUniqueId, $templateCache->uniqueId);
        $this->assertEquals($expectedFilename, $templateCache->filename);
    }

    public function testVarsWithMissingData()
    {
        $object = (object) [
            'config' => (object) [
                'formats' => (object) [
                    'data' => 'Hello [name], welcome to [city]!',
                    'extension' => '.html',
                ],
            ],
            'headers' => (object) [
                'name' => 'John',
                'city' => 'New York',
            ],
            'data' => (object) [],
        ];

        $templateCache = new TemplateCache($object);

        $templateCache->vars();

        $expectedUniqueId = 'Hello John, welcome to New York!';
        $expectedFilename = 'Hello John, welcome to New York!.html';
        $this->assertEquals($expectedUniqueId, $templateCache->uniqueId);
        $this->assertEquals($expectedFilename, $templateCache->filename);
    }
}
