<?php
declare(strict_types=1);
/**
 * @author     Laurent Jouanneau
 * @copyright  2024 Laurent Jouanneau
 * @link       https://jelix.org
 * @licence    MIT
 */

namespace Jelix\LocaleTools;


use Gettext\Generator\PoGenerator;
use Gettext\Translation;
use Gettext\Translations;
use Jelix\PropertiesFile\Parser;

class PropertiesToPoConverter
{

    protected $projectId;

    protected $locale;

    /**
     * @var Translations
     */
    protected $translations;

    public function __construct(string $projectId, string $locale, string $date)
    {
        $this->projectId = $projectId;
        $this->locale = $locale;

        $this->translations = Translations::create(null, $locale);
        $this->translations->getHeaders()
            ->set('Project-Id-Version', $projectId)
            ->set('Report-Msgid-Bugs-To', '')
            ->set('POT-Creation-Date', $date)
            ->set('PO-Revision-Date', $date)
            ->set('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>')
            ->set('Language-Team', $locale)
            ->set('MIME-Version', '1.0')
            ->set('Content-Type', 'text/plain; charset=UTF-8')
            ->set('Content-Transfer-Encoding', '8bit');
    }

    public function importFile(string $propFile, string $enPropFile, $msgctxtPrefix, string $originalPropFile = '')
    {
        $propertiesReader = new Parser();

        $localeProperties = new \Jelix\PropertiesFile\Properties();
        if (file_exists($propFile)) {
            $propertiesReader->parseFromFile($propFile, $localeProperties);
        }

        // if the locale file is not the original one in the module,
        // let's read the original one
        $originalProperties = new \Jelix\PropertiesFile\Properties();
        if ($originalPropFile && $originalPropFile !== $propFile && file_exists($originalPropFile)) {
            $propertiesReader->parseFromFile($originalPropFile, $originalProperties);
        }

        // read the en_US properties file.
        $USProperties = new \Jelix\PropertiesFile\Properties();
        $propertiesReader->parseFromFile($enPropFile, $USProperties);

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
            $this->translations->add($translation);
        }
    }

    public function savePoFile(string $poFile)
    {
        $poGen = new PoGenerator();
        $poGen->generateFile($this->translations, $poFile);
    }

    public function getPoContent(): string
    {
        $poGen = new PoGenerator();
        return $poGen->generateString($this->translations);
    }

}