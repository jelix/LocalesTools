<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Jelix\LocaleTools\LocalesConfig;
use Symfony\Component\Console\Command\Command;


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
