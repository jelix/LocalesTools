<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021-2024 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools\Command;

use Jelix\FileUtilities\Directory;
use Jelix\FileUtilities\Path;
use Jelix\LocaleTools\PropertiesToPoConverter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $projectId = $config->getProjectId(). ' ' . $module;

        $dt = new \DateTime('NOW');
        $converter = new PropertiesToPoConverter($projectId, $locale, $dt->format('Y-m-d H:i+O'));

        foreach ($files as $f) {
            $output->writeln($f);

            $fileId = str_replace('.UTF-8.properties', '', $f);
            $msgctxtPrefix = $module.'~'.$fileId.'.';

            $converter->importFile($localesPath.$f,
                $enLocalesPath.$f,
                $msgctxtPrefix,
                $originalLocalesPath.$f);
        }

        $poPath = $input->getArgument('po-root-path');
        $poPath = Path::normalizePath($poPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($poPath);
        $poFile = $poPath.$module.'.po';

        $output->writeln("save to: ${poFile}");
        $converter->savePoFile($poFile);

        return 0;
    }
}
