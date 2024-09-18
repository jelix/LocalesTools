<?php

use Jelix\LocaleTools\Command\ConvertPropertiesToPo;
use Jelix\LocaleTools\PropertiesToPoConverter;
use Symfony\Component\Console\Tester\CommandTester;

class convertPropertiesToPoTest extends \PHPUnit\Framework\TestCase
{

    public static function setUpBeforeClass(): void
    {

    }

    protected function setUp(): void
    {

    }

    protected function tearDown(): void
    {
    }

    /**
     *
     */
    public function testSimpleConvert()
    {
        $propertiesToPo = new PropertiesToPoConverter("MyProject", "fr_FR", "2024-09-18 10:19++0000");
        $propertiesToPo->importFile(__DIR__.'/locales/test1.properties',
            __DIR__.'/locales/test1_en_US.properties',
            'mymodule~'
        );
        $content = $propertiesToPo->getPoContent();
        $this->assertEquals(file_get_contents(__DIR__ . '/po/test1_simpleconvert.po'), $content);
    }

    public function testRedefinedLocale()
    {
        $propertiesToPo = new PropertiesToPoConverter("MyProject", "fr_FR", "2024-09-18 10:19++0000");
        $propertiesToPo->importFile(__DIR__.'/locales/test1.properties',
            __DIR__.'/locales/test1_en_US.properties',
            'mymodule~',
            __DIR__.'/locales/test1_original.properties'
        );
        $content = $propertiesToPo->getPoContent();
        $this->assertEquals(file_get_contents(__DIR__ . '/po/test1_redefinedlocale.po'), $content);
    }


    public function testLocaleNotTranslated()
    {
        $propertiesToPo = new PropertiesToPoConverter("MyProject", "fr_FR", "2024-09-18 10:19++0000");
        $propertiesToPo->importFile(__DIR__.'/locales/test1_original.properties',
            __DIR__.'/locales/test1_en_US.properties',
            'mymodule~'
        );
        $content = $propertiesToPo->getPoContent();
        $this->assertEquals(file_get_contents(__DIR__ . '/po/test1_localenottranslated.po'), $content);
    }

}
