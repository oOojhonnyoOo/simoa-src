<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Simoa\Config;
use Symfony\Component\Yaml\Yaml;

final class ConfigTest extends TestCase
{
    private readonly Config $config;

    public function setUp(): void
    {
        $this->config = new Config();   
    }

    public function testDefaultInitValues()
    {
        $this->assertSame($this->config->path, getenv("PATH_FOLDER"));
        $this->assertSame($this->config->public, $this->config->path . "/public");
        $this->assertSame($this->config->private, $this->config->path . "/private");
        $this->assertSame($this->config->preview, $this->config->path . "/preview");
        $this->assertSame($this->config->data, $this->config->path . "/.simoa/.data");
        $this->assertSame($this->config->history, $this->config->path . "/.simoa/.history");
    }

    public function testServerAttributeWithTestEnv() 
    {
        $env = trim(file_get_contents($this->config->path . "/.env"));

        $this->assertSame($this->config->server, $this->config->env->$env->server);
    }

    public function testClientAttributeWithTestEnv() 
    {
        $env = trim(file_get_contents($this->config->path . "/.env"));

        $this->assertSame($this->config->client, $this->config->env->$env->client);
    }

    public function testGetDefaultSolr() 
    {
        $this->assertSame(
            $this->config->__defaultSolr(), 
            $this->config->yaml->default->solr
        );
    }

    public function testYamlHasFormatsAttribute()
    {
        $yaml = Yaml::parseFile(
            $this->config->path . '/config.yml',
            Yaml::PARSE_OBJECT_FOR_MAP
        );

        $this->assertObjectHasAttribute('formats', $yaml->default);
    }
    
    public function testYamlNotHasFormatsAttribute()
    {
        $yaml = Yaml::parseFile(
            $this->config->path . '/config.yml',
            Yaml::PARSE_OBJECT_FOR_MAP
        );

        unset($yaml->default->formats);

        $this->assertObjectNotHasAttribute('formats', $yaml->default);
    }
}
