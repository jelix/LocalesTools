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

class ConvertPropertiesToPo extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('convert:properties:po')
            ->setDescription('Convert properties files to PO files')
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                ''
            )
            ->addArgument(
                'locale',
                InputArgument::REQUIRED,
                ''
            )
            ->addArgument(
                'po-root-path',
                InputArgument::REQUIRED,
                'Root path of PO files to write'
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
                'Path to access to translated properties files'
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
        $modulePath = $config->getModulePath($module);
        $localesPath = $config->getModuleTranslationPath($module, $locale);
        $originalLocalesPath = $config->getModuleOriginalTranslationPath($module, $locale);
        $enLocalesPath = $config->getModuleOriginalMainTranslationPath($module);

        $files = $config->getModuleLocaleFiles($module);

        $output->writeln("================ $module $locale");
        //$output->writeln($modulePath);
        //$output->writeln($localesPath);
        //$output->writeln($originalLocalesPath);
        //$output->writeln($enLocalesPath);

        $dt = new \DateTime('NOW');
        $projectId = $config->getProjectId(). ' ' . $module;

        $translations = new Translations();
        $translations->setHeader('Project-Id-Version', $projectId);
        $translations->setHeader('Report-Msgid-Bugs-To', '');
        $translations->setHeader('POT-Creation-Date', $dt->format('Y-m-d H:i+O'));
        $translations->setHeader('PO-Revision-Date', $dt->format('Y-m-d H:i+O'));
        $translations->setHeader('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>');
        $translations->setHeader('Language-Team', $locale);
        $translations->setHeader('MIME-Version', '1.0');
        $translations->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $translations->setHeader('Content-Transfer-Encoding', '8bit');
        $translations->setHeader('Language', $locale);
        Translations::$options['headersSorting'] = false;

        $propertiesReader = new Parser();

        foreach ($files as $f) {
            $output->writeln($f);

            // read locale file
            $localeProperties = new \Jelix\PropertiesFile\Properties();
            if (file_exists($localesPath.$f)) {
                $propertiesReader->parseFromFile($localesPath.$f, $localeProperties);
            }

            // if the locale file is not the original one in the module,
            // let's read the original one
            $originalProperties = new \Jelix\PropertiesFile\Properties();
            if ($originalLocalesPath !== $localesPath && file_exists($originalLocalesPath.$f)) {
                $propertiesReader->parseFromFile($originalLocalesPath.$f, $originalProperties);
            }

            // read the en_US properties file.
            $USProperties = new \Jelix\PropertiesFile\Properties();
            $propertiesReader->parseFromFile($enLocalesPath.$f, $USProperties);

            $fileId = str_replace('.UTF-8.properties', '', $f);
            $msgctxtPrefix = $module.'~'.$fileId.'.';

            foreach ($USProperties->getIterator() as $key => $value) {
                $msgctxt = $msgctxtPrefix.$key;
                $translation = $translations->insert($msgctxt, $value);
                $translation->addReference($msgctxt);
                $localeValue = $localeProperties[$key];
                if ($localeValue === null) {
                    $localeValue = $originalProperties[$key];
                }
                if ($localeValue !== null && $localeValue != $value) {
                    $translation->setTranslation($localeValue);
                } else {
                    $translation->setTranslation('');
                }
            }
        }

        $poPath = $input->getArgument('po-root-path');
        $poPath = Path::normalizePath($poPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($poPath);
        $poFile = $poPath.$module.'.po';

        $output->writeln("save to: ${poFile}");
        \Gettext\Generators\Po::toFile($translations, $poFile);
        return 0;
    }
}
