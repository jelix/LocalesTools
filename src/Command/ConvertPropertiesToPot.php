<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021-2024 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Jelix\FileUtilities\Directory;
use Jelix\FileUtilities\Path;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Gettext\Translations;
use Jelix\PropertiesFile\Parser;

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

        $translations = Translations::create();
        $translations->getHeaders()
            ->set('Project-Id-Version', $projectId)
            ->set('Report-Msgid-Bugs-To', '')
            ->set('POT-Creation-Date', $dt->format('Y-m-d H:i+O'))
            ->set('PO-Revision-Date', $dt->format('Y-m-d H:i+O'))
            ->set('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>')
            ->set('MIME-Version', '1.0')
            ->set('Content-Type', 'text/plain; charset=UTF-8')
            ->set('Content-Transfer-Encoding', '8bit');

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
                $translation = Translation::create($msgctxt, $value);
                $translation->getReferences()->add($msgctxt);
                $translation->translate('');
                $translations->add($translation);
            }
        }

        $potPath = $input->getArgument('pot-path');
        $potPath = Path::normalizePath($potPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($potPath);
        $poFile = $potPath.$module.'.pot';
        $output->writeln("save to: ${poFile}");
        $poGen = new PoGenerator();
        $poGen->generateFile($translations, $poFile);

        $output->writeln("================");
        return 0;
    }
}
