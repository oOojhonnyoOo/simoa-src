<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Simoa\Config;

final class ConfigTest extends TestCase
{
    public function testDefaultInitValues()
    {
        $config = new Config();

        $this->assertSame($config->path, getenv("PATH_FOLDER"));
        $this->assertSame($config->public, $config->path . "/public");
        $this->assertSame($config->private, $config->path . "/private");
        $this->assertSame($config->preview, $config->path . "/preview");
        $this->assertSame($config->data, $config->path . "/.simoa/.data");
        $this->assertSame($config->history, $config->path . "/.simoa/.history");
    }

    public function testServerAttributeWithTestEnv() 
    {
        $config = new Config();
        $env = trim(file_get_contents($config->path . "/.env"));

        $this->assertSame($config->server, $config->env->$env->server);
    }


    public function testClientAttributeWithTestEnv() 
    {
        $config = new Config();
        $env = trim(file_get_contents($config->path . "/.env"));
        
        $this->assertSame($config->client, $config->env->$env->client);
    }


    public function testGetDefaultSolr() 
    {
        $config = new Config();

        $this->assertSame(
            $config->__defaultSolr(), 
            $config->yaml->default->solr
        );
    }
}
