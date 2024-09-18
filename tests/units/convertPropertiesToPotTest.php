<?php

use Jelix\LocaleTools\Command\ConvertPropertiesToPo;
use Jelix\LocaleTools\PropertiesToPoConverter;
use Jelix\LocaleTools\PropertiesToPotConverter;
use Symfony\Component\Console\Tester\CommandTester;

class convertPropertiesToPotTest extends \PHPUnit\Framework\TestCase
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
    public function testConvertToPot()
    {
        $propertiesToPo = new PropertiesToPotConverter("MyProject", "2024-09-18 10:19++0000");
        $propertiesToPo->importFile(__DIR__.'/locales/test1_en_US.properties',
            'mymodule~'
        );
        $content = $propertiesToPo->getPoContent();
        $this->assertEquals(file_get_contents(__DIR__ . '/po/test1_convert.pot'), $content);
    }
}
