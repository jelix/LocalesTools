<?php
/**
 * @author     Laurent Jouanneau
 * @copyright  2021 Laurent Jouanneau
 * @link       http://jelix.org
 * @licence    MIT
 */
namespace Jelix\LocaleTools;

use Jelix\FileUtilities\Path;

class LocalesConfig
{
    protected $applicationPath = '.';

    protected $mainLocale = 'en_US';

    protected $translationLocation = '@module';

    protected $projectId = 'My Project';

    protected $ini;

    protected $iniDir;


    protected $propertiesFileHeader = '';


    public function __construct($pathToIni)
    {
        if (!file_exists($pathToIni)) {
            throw new \Exception("$pathToIni does not exists");
        }

        $ini = parse_ini_file($pathToIni, true);
        $this->iniDir = dirname(realpath($pathToIni));

        $this->applicationPath = $this->iniDir;

        if (isset($ini['applicationPath']) && $ini['applicationPath'] != '') {
            $this->applicationPath = Path::normalizePath($ini['applicationPath'], Path::NORM_ADD_TRAILING_SLASH, $this->iniDir);
        }

        if (isset($ini['projectId']) && $ini['projectId'] != '') {
            $this->projectId = $ini['projectId'];
        }

        if (isset($ini['propertiesFileHeader']) && $ini['propertiesFileHeader'] != '') {
            $this->propertiesFileHeader = $ini['propertiesFileHeader'];
        }

        if (isset($ini['default']['mainLocale']) && $ini['default']['mainLocale'] != '') {
            $this->mainLocale = $ini['default']['mainLocale'];
        }

        if (isset($ini['default']['translationLocation']) && $ini['default']['translationLocation'] != '') {
            $this->translationLocation = $ini['default']['translationLocation'];
        }

        //echo "applicationPath: ".$this->applicationPath ."\n";
        //echo "projectId: ". $this->projectId."\n";
        //echo "mainLocale: ".$this->mainLocale ."\n";
        //echo "translationLocation: ".$this->translationLocation ."\n";


        $this->ini = $ini;
    }

    public function setMainLocale($locale)
    {
        $this->mainLocale = $locale;
        $this->moduleProperties = array();
    }

    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    public function setTranslationLocation($path)
    {
        $this->translationLocation = $path;
        $this->moduleProperties = array();
    }

    public function getApplicationPath()
    {
        return $this->applicationPath;
    }

    public function getPropertiesFileHeader()
    {
        return $this->propertiesFileHeader;
    }

    protected $moduleProperties = array();


    protected function getModuleProperties($module)
    {
        if (isset($this->moduleProperties[$module])) {
            return $this->moduleProperties[$module];
        }


        if (!isset($this->ini['module:'.$module])) {
            throw new \Exception('unknown module');
        }

        $prop = $this->ini['module:'.$module];

        if (!isset($prop['path']) || $prop['path'] == '') {
            throw new \Exception('unknown module path');
        }

        if (!isset($prop['mainLocale']) || $prop['mainLocale'] == '') {
            $prop['mainLocale'] = $this->mainLocale;
        }

        if (!isset($prop['translationLocation']) || $prop['translationLocation'] == '') {
            $prop['translationLocation'] = $this->translationLocation;
        }

        if (!isset($prop['excludePropertiesFiles']) || $prop['excludePropertiesFiles'] == '') {
            $prop['excludePropertiesFiles'] = array();
        } else {
            $prop['excludePropertiesFiles'] = preg_split("/\s*,\s*/", $prop['excludePropertiesFiles']);
            $prop['excludePropertiesFiles'] = array_map(function ($file) {
                if (substr($file, -17) == '.UTF-8.properties') {
                    return $file;
                } else {
                    return $file.'.UTF-8.properties';
                }
            }, $prop['excludePropertiesFiles']);
        }

        $prop['path'] = Path::normalizePath($prop['path'], Path::NORM_ADD_TRAILING_SLASH, $this->applicationPath);

        $this->moduleProperties[$module] = $prop;
        return $prop;
    }

    public function getModulePath($module)
    {
        $prop = $this->getModuleProperties($module);
        return $prop['path'];
    }

    public function getModuleOriginalTranslationPath($module, $locale)
    {
        $prop = $this->getModuleProperties($module);
        return $prop['path'].'locales/'.$locale.'/';
    }

    public function getModuleOriginalMainTranslationPath($module)
    {
        $prop = $this->getModuleProperties($module);
        return $prop['path'].'locales/'.$this->mainLocale.'/';
    }

    public function getModuleTranslationPath($module, $locale)
    {
        $prop = $this->getModuleProperties($module);

        $translationPath = $prop['translationLocation'];
        if ($translationPath == '@module') {
            $path = $prop['path'].'locales/:locale/';
        } elseif ($translationPath == '@app-overloads') {
            $path =  $this->applicationPath.'app/overloads/:module/locales/:locale/';
        } elseif ($translationPath == '@app-locales') {
            $path =  $this->applicationPath.'app/locales/:locale/:module/';
        } elseif ($translationPath == '@var-overloads') {
            $path =  $this->applicationPath.'var/overloads/:module/locales/:locale/';
        } elseif ($translationPath == '@var-locales') {
            $path =  $this->applicationPath.'var/locales/:locale/:module/';
        } elseif (strpos($translationPath, '@app:') === 0) {
            $path = str_replace('@app:', $this->applicationPath, $translationPath);
        } else {
            $path = Path::normalizePath($translationPath, Path::NORM_ADD_TRAILING_SLASH, $this->iniDir);
        }

        $path = str_replace(
            array(':locale', ':module'),
            array($locale, $module),
            $path
        );
        return $path;
    }

    public function getModuleLocaleFiles($module)
    {
        $prop = $this->getModuleProperties($module);
        $path = $prop['path'].'locales/'.$this->mainLocale.'/';
        if (!file_exists($path)) {
            throw new \Exception('Locales directory does not exists into the module '.$module.' ('.$path.')');
        }

        $files = array();
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if (substr($file, -17) == '.UTF-8.properties'
                    && !in_array($file, $prop['excludePropertiesFiles'])) {
                    $files[] = $file;
                }
            }
            closedir($dh);
        }

        sort($files);

        return $files;
    }


    public function getProjectId()
    {
        return $this->projectId;
    }
}
