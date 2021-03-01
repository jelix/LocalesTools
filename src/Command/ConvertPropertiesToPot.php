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

class ConvertPropertiesToPot extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('convert:properties:pot')
            ->setDescription('Convert properties files to POT files')
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                ''
            )
            ->addArgument(
                'pot-path',
                InputArgument::REQUIRED,
                'Path to POT files'
            )
            ->addOption(
                'config',
                '',
                InputOption::VALUE_REQUIRED,
                'Path to the configuration file. Default is ./.jelixlocales.ini'
            )
            ->addOption(
                'main-locale',
                '',
                InputOption::VALUE_REQUIRED,
                'locale to use to generate pot files. Default is indicated into the .jelixlocales.ini file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module = $input->getArgument('module');
        $config = $this->getLocalesConfig($input->getOption('config'));
        $modulePath = $config->getModulePath($module);
        $originalLocalesPath = $config->getModuleOriginalMainTranslationPath($module);

        $files = $config->getModuleLocaleFiles($module);

        $output->writeln("================");
        $output->writeln($module);
        $output->writeln($modulePath);
        $output->writeln($originalLocalesPath);

        $dt = new \DateTime('NOW');
        $projectId = $config->getProjectId(). ' ' . $module;

        $translations = new Translations();
        $translations->setHeader('Project-Id-Version', $projectId);
        $translations->setHeader('Report-Msgid-Bugs-To', '');
        $translations->setHeader('POT-Creation-Date', $dt->format('Y-m-d H:i+O'));
        $translations->setHeader('PO-Revision-Date', $dt->format('Y-m-d H:i+O'));
        $translations->setHeader('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>');
        $translations->setHeader('MIME-Version', '1.0');
        $translations->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $translations->setHeader('Content-Transfer-Encoding', '8bit');

        $propertiesReader = new Parser();
        foreach ($files as $f) {
            $output->writeln($f);
            $fileId = str_replace('.UTF-8.properties', '', $f);
            $properties = new \Jelix\PropertiesFile\Properties();
            $propertiesReader->parseFromFile($originalLocalesPath.$f, $properties);
            $msgctxtPrefix = $module.'~'.$fileId.'.';
            $propertiesArray = array();
            foreach ($properties->getIterator() as $key => $value) {
                $propertiesArray[$key] = $value;
            }
            ksort($propertiesArray);
            foreach ($propertiesArray as $key => $value) {
                $msgctxt = $msgctxtPrefix.$key;
                $translation = $translations->insert($msgctxt, $value);
                $translation->addReference($msgctxt);
                $translation->setTranslation('');
            }
        }

        $potPath = $input->getArgument('pot-path');
        $potPath = Path::normalizePath($potPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($potPath);
        $poFile = $potPath.$module.'.pot';
        $output->writeln("save to: ${poFile}");
        \Gettext\Generators\Po::toFile($translations, $poFile);

        $output->writeln("================");
        return 0;
    }
}
