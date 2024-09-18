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
use Jelix\LocaleTools\PropertiesToPotConverter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $projectId = $config->getProjectId(). ' ' . $module;

        $dt = new \DateTime('NOW');
        $converter = new PropertiesToPotConverter($projectId, $dt->format('Y-m-d H:i+O'));

        foreach ($files as $f) {
            $output->writeln($f);
            $fileId = str_replace('.UTF-8.properties', '', $f);
            $msgctxtPrefix = $module.'~'.$fileId.'.';
            $converter->importFile($originalLocalesPath.$f, $msgctxtPrefix);
        }

        $potPath = $input->getArgument('pot-path');
        $potPath = Path::normalizePath($potPath, Path::NORM_ADD_TRAILING_SLASH, getcwd());
        Directory::create($potPath);
        $poFile = $potPath.$module.'.pot';
        $output->writeln("save to: ${poFile}");
        $converter->savePoFile($poFile);

        $output->writeln("================");
        return 0;
    }
}
