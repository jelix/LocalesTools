<?php
declare(strict_types=1);
/**
 * @author     Laurent Jouanneau
 * @copyright  2024 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */

namespace Jelix\LocaleTools;

use Gettext\Loader\PoLoader;
use Gettext\Translations;
use Jelix\PropertiesFile\Parser;
use Jelix\PropertiesFile\Writer;

class PoToPropertiesConverter
{

    /**
     * @var Translations
     */
    protected $translations;

    protected $propertiesFileHeaders = '';

    public function __construct($poFilePath, $propertiesFileHeaders = '')
    {
        $loader = new PoLoader();
        $this->translations = $loader->loadFile($poFilePath);
        $this->propertiesFileHeaders = $propertiesFileHeaders;
    }

    /**
     * convert all string from the context having the given context prefix
     * to the given properties file.
     */
    public function convertToProperties($defaultLocalePropFile, $propFile, $contextPrefix)
    {
        $propertiesReader = new Parser();
        $propertiesWriter = new Writer();

        $localeProperties = new \Jelix\PropertiesFile\Properties();
        $USProperties = new \Jelix\PropertiesFile\Properties();
        $propertiesReader->parseFromFile($defaultLocalePropFile, $USProperties);

        $sameAsUs = true;
        foreach ($USProperties->getIterator() as $key => $usString) {
            $msgctxt = $contextPrefix.$key;
            $translation = $this->translations->find($msgctxt, $usString);
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
                $propFile,
                array(
                    'lineLength' => 500,
                    'spaceAroundEqual' => false,
                    'removeTrailingSpace' => true,
                    'cutOnlyAtSpace' => true,
                    'headerComment' => $this->propertiesFileHeaders,
                )
            );
        } elseif (file_exists($propFile)) {
            unlink($propFile);
        }
    }
}