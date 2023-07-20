<?php declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Simoa\File;

class FileTest extends TestCase
{
    public function testFetchFromFile()
    {
        $props = [
            'file' => '/path/to/file.txt'
        ];

        $file = new File($props);

        $this->assertEquals('/path/to/', $file->path);
        $this->assertEquals('file', $file->filename);
        $this->assertEquals('txt', $file->extension);
    }

    public function testCreatePath()
    {
        $props = [
            'path' => '/path/to/directory/'
        ];

        $file = new File($props);

        $file->createPath();

        $this->assertDirectoryExists('/path/to/directory');
    }

    public function testSave()
    {
        $props = [
            'file' => '/path/to/file.txt'
        ];

        $file = new File($props);

        $content = 'This is a test content';

        $result = $file->save($content);

        $this->assertTrue($result);
        $this->assertFileExists('/path/to/file.txt');
        $this->assertEquals($content, file_get_contents('/path/to/file.txt'));

        unlink('/path/to/file.txt');
    }

    public function testSaveFile()
    {
        $file = '/path/to/file.txt';
        $content = 'This is a test content';

        $result = File::saveFile($file, $content);

        $this->assertTrue($result);
        $this->assertFileExists($file);
        $this->assertEquals($content, file_get_contents($file));

        unlink($file);
    }

    public function testCsvSave()
    {
        $props = [
            'file' => '/path/to/file.csv'
        ];
    
        $file = new File($props);
    
        $content = [
            ['Name', 'Email'],
            ['John Doe', 'john@example.com'],
            ['Jane Smith', 'jane@example.com']
        ];
    
        $result = $file->csvsave($content);
    
        $expectedContent = "Name,Email\n\"John Doe\",\"john@example.com\"\n\"Jane Smith\",\"jane@example.com\"\n";
    
        $expectedLines = explode("\n", $expectedContent);
        $actualLines = explode("\n", file_get_contents('/path/to/file.csv'));
    
        $expectedLines = array_filter($expectedLines);
        $actualLines = array_filter($actualLines);
    
        $this->assertTrue($result);
        $this->assertFileExists('/path/to/file.csv');
        $this->assertEquals(count($expectedLines), count($actualLines));
    
        foreach ($expectedLines as $index => $expectedLine) {
            $expectedFields = str_getcsv($expectedLine);
            $actualFields = str_getcsv($actualLines[$index]);
    
            $this->assertEquals($expectedFields, $actualFields);
        }
    
        unlink('/path/to/file.csv');
    }
}
