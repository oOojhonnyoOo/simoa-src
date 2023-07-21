<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\Counter;

final class CounterTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/counters_test';
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

    public function testCreateModuleDirectory()
    {
        $data = $this->tempDir;
        $counter = new Counter();

        $props = (object) [
            'site' => 'example',
            'module' => 'test',
            'data' => $data,
        ];

        $counter->createLastFile($props);

        $path = $props->data . "/counters/" . $props->site . "/" . $props->module;

        $this->assertFileExists($path);
    }

    public function testReset()
    {
        $data = $this->tempDir;
        $counter = new Counter();

        $props = (object) [
            'site' => 'example',
            'module' => 'test',
            'data' => $data,
        ];

        $counter->createLastFile($props);

        $lastFile = $data . "/counters/" . $props->site . "/" . $props->module . "/last";
        $this->assertTrue(file_exists($lastFile));

        $counter->reset((object) $props);
        $this->assertFileExists($lastFile);
        $this->assertEquals('0', file_get_contents($lastFile));
    }
}
