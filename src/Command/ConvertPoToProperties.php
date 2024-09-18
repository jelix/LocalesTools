<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021-2024 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Gettext\Loader\PoLoader;
use Jelix\FileUtilities\Directory;
use Jelix\FileUtilities\Path;
use Jelix\LocaleTools\PoToPropertiesConverter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Gettext\Translations;
use Jelix\PropertiesFile\Parser;
use Jelix\PropertiesFile\Writer;

class ConvertPoToProperties extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('convert:po:properties')
            ->setDescription('Convert PO files to properties files')
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                'The module name'
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                ''
            )
            ->addArgument(
                'po-root-path',
                InputArgument::REQUIRED,
                'Root path of PO files to read'
            )
            ->addOption(
                'config',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to the configuration file. Default is ./.jelixlocales.ini'
            )
            ->addOption(
                'properties-path',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to store translated properties files. Default is path indicated into the .jelixlocales.ini file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $locale = $input->getArgument('locale');
        $config = $this->getLocalesConfig($input->getOption('config'));

        $translationPath = $input->getOption('properties-path');
        if ($translationPath) {
            $config->setTranslationLocation($translationPath);
        }

        $localesPath = $config->getModuleTranslationPath($module, $locale);
        $originalLocalesPath = $config->getModuleOriginalMainTranslationPath($module);

        $files = $config->getModuleLocaleFiles($module);

        $output->writeln("================");

        // read the PO file
        $poPath = $input->getArgument('po-root-path');
        $poPath = Path::normalizePath($poPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());

        $poFile = $poPath.$module.'.po';
        if (!is_readable($poFile)) {
            throw new \Exception($poFile.' is not readable !!');
        }

        if (!is_dir($localesPath)) {
            Directory::create($localesPath);
        }

        $converter = new PoToPropertiesConverter($poFile, $config->getPropertiesFileHeader());

        foreach ($files as $f) {
            $output->writeln($f);
            $fileId = str_replace('.UTF-8.properties', '', $f);
            $msgctxtPrefix = $module.'~'.$fileId.'.';

            $converter->convertToProperties($originalLocalesPath.$f,
                $localesPath.$f, $msgctxtPrefix);
        }
        return 0;
    }
}
