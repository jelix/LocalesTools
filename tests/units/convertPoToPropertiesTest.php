<?php

use Jelix\LocaleTools\PoToPropertiesConverter;

class convertPoToPropertiesTest extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {

    }

    protected $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = __DIR__ . '/tmp/';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir);
        }
    }

    protected function tearDown(): void
    {
    }

    /**
     *
     */
    public function testConvert()
    {
        $converter = new PoToPropertiesConverter(__DIR__.'/po/test2.po', 'myheader');

        $converter->convertToProperties(
            __DIR__.'/locales/test2_en_US1.properties',
            $this->tmpDir.'test2_result1.properties',
            'mymodule~'
        );

        $expected = file_get_contents(__DIR__.'/locales/test2_result1.properties');
        $result = file_get_contents($this->tmpDir.'test2_result1.properties');
        $this->assertEquals($expected, $result);

        $converter->convertToProperties(
            __DIR__.'/locales/test2_en_US2.properties',
            $this->tmpDir.'test2_result2.properties',
            'mymodule2~'
        );

        $expected = file_get_contents(__DIR__.'/locales/test2_result2.properties');
        $result = file_get_contents($this->tmpDir.'test2_result2.properties');
        $this->assertEquals($expected, $result);
    }
}
