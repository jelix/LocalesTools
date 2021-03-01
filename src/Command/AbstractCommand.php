<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021 Laurent Jouanneau
 * @link       http://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Jelix\LocaleTools\LocalesConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use Gettext\Translations;
use Jelix\PropertiesFile\Parser;
use Jelix\PropertiesFile\Properties;
use Jelix\PropertiesFile\Writer;

class AbstractCommand extends Command
{
    protected function getLocalesConfig($path)
    {
        if ($path == '') {
            $path = './jelixlocales.ini';
        }

        return new LocalesConfig($path);
    }
}
