<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021 Laurent Jouanneau
 * @link       http://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Jelix\FileUtilities\Directory;
use Jelix\FileUtilities\Path;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use Gettext\Translations;
use Jelix\PropertiesFile\Parser;
use Jelix\PropertiesFile\Properties;
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

        $translations = new Translations();
        \Gettext\Extractors\Po::fromFile($poFile, $translations);

        if (!is_dir($localesPath)) {
            Directory::create($localesPath);
        }

        $propertiesReader = new Parser();
        $propertiesWriter = new Writer();

        foreach ($files as $f) {
            $output->writeln($f);

            $localeProperties = new \Jelix\PropertiesFile\Properties();
            $USProperties = new \Jelix\PropertiesFile\Properties();
            $propertiesReader->parseFromFile($originalLocalesPath.$f, $USProperties);

            $fileId = str_replace('.UTF-8.properties', '', $f);
            $msgctxtPrefix = $module.'~'.$fileId.'.';

            $sameAsUs = true;
            foreach ($USProperties->getIterator() as $key => $usString) {
                $msgctxt = $msgctxtPrefix.$key;
                $translation = $translations->find($msgctxt, $usString);
                if ($translation === false) {
                    $localeString = '';
                } else {
                    $localeString = $translation->getTranslation();
                }
                if (trim($localeString) == '') {
                    $localeString = $usString;
                }
                if ($localeString != $usString) {
                    $sameAsUs = false;
                }
                $localeProperties[$key] = $localeString;
            }
            if (!$sameAsUs) {
                $propertiesWriter->writeToFile(
                    $localeProperties,
                    $localesPath.$f,
                    array(
                        'lineLength' => 500,
                        'spaceAroundEqual' => false,
                        'removeTrailingSpace' => true,
                        'cutOnlyAtSpace' => true,
                        'headerComment' => $config->getPropertiesFileHeader(),
                    )
                );
            } elseif (file_exists($localesPath.$f)) {
                unlink($localesPath.$f);
            }
        }
        return 0;
    }
}
