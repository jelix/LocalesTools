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

class PropertiesToPotConverter
{

    protected $projectId;

    /**
     * @var Translations
     */
    protected $translations;

    public function __construct(string $projectId, string $date)
    {
        $this->projectId = $projectId;

        $this->translations = Translations::create();
        $this->translations->getHeaders()
            ->set('Project-Id-Version', $projectId)
            ->set('Report-Msgid-Bugs-To', '')
            ->set('POT-Creation-Date', $date)
            ->set('PO-Revision-Date', $date)
            ->set('Last-Translator', 'FULL NAME <EMAIL@ADDRESS>')
            ->set('MIME-Version', '1.0')
            ->set('Content-Type', 'text/plain; charset=UTF-8')
            ->set('Content-Transfer-Encoding', '8bit');
    }

    public function importFile(string $enPropFile, $msgctxtPrefix)
    {
        $propertiesReader = new Parser();

        $properties = new \Jelix\PropertiesFile\Properties();
        $propertiesReader->parseFromFile($enPropFile, $properties);

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