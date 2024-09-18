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


        $translations = Translations::create(null, $locale);
        $translations->getHeaders()
            ->set('Project-Id-Version', $projectId)
            ->set('Report-Msgid-Bugs-To', '')
            ->set('POT-Creation-Date', $dt->format('Y-m-d H:i+O'))
            ->set('PO-Revision-Date', $dt->format('Y-m-d H:i+O'))
            ->set('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>')
            ->set('Language-Team', $locale)
            ->set('MIME-Version', '1.0')
            ->set('Content-Type', 'text/plain; charset=UTF-8')
            ->set('Content-Transfer-Encoding', '8bit');

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

                $translation = Translation::create($msgctxt, $value);
                $translation->getReferences()->add($msgctxt);

                $localeValue = $localeProperties[$key];
                if ($localeValue === null) {
                    $localeValue = $originalProperties[$key];
                }
                if ($localeValue !== null && $localeValue != $value) {
                    $translation->translate($localeValue);
                } else {
                    $translation->translate('');
                }
                $translations->add($translation);
            }
        }

        $poPath = $input->getArgument('po-root-path');
        $poPath = Path::normalizePath($poPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($poPath);
        $poFile = $poPath.$module.'.po';

        $output->writeln("save to: ${poFile}");
        $poGen = new PoGenerator();
        $poGen->generateFile($translations, $poFile);

        return 0;
    }
}
