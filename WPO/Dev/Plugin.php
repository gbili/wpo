<?php
namespace WPO\Dev;

require_once __DIR__ . '/../Plugin.php';
/**
 * This is a mock plugin that does not have any Integrate instance,
 * we store the data locally. It can be thus accessed, and used as
 * a normal plugin.
 * 
 * @author g
 *
 */
class Plugin
extends \WPO\Plugin
{
    private $_adminPagesDirName;
    private $_defaultPageName;
    private $_defaultSectionName;
    private $_identifier;
    private $_includesPath;
    private $_name;
    private $_version;
    
    /**
     * 
     * @var unknown_type
     */
    private $_optionDefaultsND;
    
    /**
     * 
     * @var \WPO\Dev\ViewsGenerator;
     */
    private $_viewsGenerator;
    
    /**
     * The user is not supposed to set the identifier. The plugin takes care
     * of generating one. However if he prefers to set a custom one, then the
     * first param is considered the identifier, and all params meaning is
     * moved right.
     *
     * @param string $identifierOrName user should set this to plugin name OR identifier
     * @param string $nameOrVersion    user should set this to version OR if first para is identifier, then this one is considered plugin name
     * @param string $versionOrIncPath user should set this to version OR ^
     * @param string $includesPath     parent directory path of parent::ADMIN_PAGES_DIRNAME directory
     * @param array  $customizeArray   customize what you want see the constants starting with K_
     * @throws Exception if user tries to create more than one instance per script run
     */
    public function __construct($identifierOrName, $nameOrVersion = null, $versionOrIncPath = null, $includesPathOrCustomizeArray = null, $customizeArrayOrNull = null)
    {
        /*
         * What type of instantiation is it? from the user, or from us (once installed)?
        */
        $isUserInstantiated = (null !== $nameOrVersion); //case 1
        $customizeArray = array();
        //$identifier is set in any case
        switch(func_num_args()) { //Java where are you????
            /*
             * From here it's user instantiated: name, version, includes path will be set
            */
            case 3;
            $identifier = parent::nameToIdentifier($identifierOrName);
            $name = $identifierOrName;
            $version = $nameOrVersion;
            $includesPath = $versionOrIncPath;
            break;
            case 4;
            //name and custom array
            if (is_array($includesPathOrCustomizeArray)) {
                $identifier = ($customizeArray[parent::K_IDENTIFIER])? $customizeArray[parent::K_IDENTIFIER] : parent::nameToIdentifier($identifierOrName);
                $name = $identifierOrName;
                $version = $nameOrVersion;
                $includesPath = $versionOrIncPath;
                $customizeArray = $includesPathOrCustomizeArray;
            } else { // no name, no custom array
                $identifier = $identifierOrName;
                $name = $nameOrVersion;
                $version = $versionOrIncPath;
                $includesPath = $includesPathOrCustomizeArray;
            }
            break;
            case 5;
            list($identifier, $name, $version, $includesPath, $customizeArray) = func_get_args();
            break;
            default;
            require_once __DIR__ . '/Exception.php';
            throw new Exception('Not supported param count.');
            break;
        }
        
        /*
         * -------------- Code below here is executed only once ------------------------
         */
        define('WPO_DIR', realpath(__DIR__ . '/..'));//only defined once ^
        define('WPO_PARENT_DIR', realpath(WPO_DIR . '/..'));
    
        /*
         * Either way we have the identifier now.
        */
        $this->setVersion($version);//version is made available here    
        $this->setIdentifier($identifier);
        $this->setName($name);
        $this->setIncludesPath($includesPath);
    
        //set plugin values (either default or customized array)
        if (isset($customizeArray[parent::K_ADMIN_PAGES_DIRNAME])) {
            $this->setAdminPagesDirName($customizeArray[parent::K_ADMIN_PAGES_DIRNAME]);
        } else {
            $this->setAdminPagesDirName(parent::ADMIN_PAGES_DIRNAME);
        }
        if (isset($customizeArray[parent::K_DEFAULT_PAGE_NAME])) {
            $this->setDefaultPageName($customizeArray[parent::K_DEFAULT_PAGE_NAME]);
        } else {
            $this->setDefaultPageName(parent::K_DEFAULT_PAGE_NAME);
        }
        if (isset($customizeArray[parent::K_DEFAULT_SECTION_NAME])) {
            $this->setDefaultSectionName($customizeArray[parent::K_DEFAULT_SECTION_NAME]);
        } else {
            $this->setDefaultSectionName(parent::K_DEFAULT_SECTION_NAME);
        }
        /*
         * All plugin values are now set even if not installed
         */
        require_once WPO_DIR . '/Map/AbstractMap.php';
        \WPO\Map\AbstractMap::setSubPathToRemoveFromPath($this->getAdminPagesPath());
        require_once WPO_DIR . '/Dev/ViewsGenerator.php';
        $this->_viewsGenerator = new ViewsGenerator($this);
    }
    
    /**
     * 
     * @param unknown_type $overwrite
     */
    public function generateViewFiles($viewMapsBaseClassName='StandardP1S1V1v1', $overwrite=false)
    {
        return $this->_viewsGenerator->generateViews($viewMapsBaseClassName, $overwrite);
    }
    
    /**
     * Moved here for the benefit of front end
     *
     * @return \WPO\Option\NormalizedData\Defaults
     */
    public function getOptionDefaultsNormalizedData()
    {
        if (null === $this->_optionDefaultsND) {
            require_once __DIR__ . '/Option/NormalizedData/Defaults.php';
            $this->_optionDefaultsND = new \WPO\Option\ND\Loader\Defaults($this);
        }
        return $this->_optionDefaultsND;
    }
    
    /**
     * Set the theme name.
     *
     * @param string $name
     * @throws Exception
     */
    public function setName($name)
    {
        parent::_throwIfNotIsString($name);
        $this->_name = $name;
    }
    
    /**
     * Get methods need to check if the member
     * they are to return is set or not. This
     * is done here by guessing the member name
     * from the calling method name
     * 
     * @param string $funcName
     * @throws \WPO\Exception
     */
    protected function _throwIfNotSet($funcName)
    {
        $funcPart = end(explode('::get', $funcName));
        $member = '_' . lcfirst($funcPart);
        if (null === $this->{$member}) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception('the member ' . $member . ' has not been set');
        }
    }
    
    /**
     *
     * @throws Exception
     * @return string
     */
    public function getName()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_name;
    }
    
    /**
     * Short name can be guessed on from name
     *
     * @param string $shortName
     */
    public function setIdentifier($shortName)
    {
        $this->_identifier = $shortName;
    }
    
    /**
     * Short name can be guessed on from name
     */
    static public function nameToIdentifier($name)
    {
        $words = explode(' ', preg_replace('/[^a-z]/', ' ', strtolower($name)));
        $shortName = '';
        $substrLen = ceil(parent::SHORTNAME_MIN_LETTERS / count($words));
        foreach ($words as $word) {//cut words if wlen > sunstrlen
            if (strlen($word) > $substrLen) {
                $shortName .= substr($word, 0,  $substrLen);
            } else {
                $shortName .= $word;
            }
        }
        return $shortName;
    }
    
    /**
     * Plugin short name, guessed if not already set
     *
     * @return string
     */
    public function getIdentifier()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_identifier;
    }
    
    /**
     *
     * @return string path
     */
    public function setSubPathToRemoveFromPath()
    {
        return $this->getIncludesPath() . DIRECTORY_SEPARATOR . $this->getAdminPagesDirName();
    }
    
    /**
     * Allow users to change the default admin pages directory name
     *
     * @param string $name
     * @throws Exception
     */
    public function setAdminPagesDirName($name)
    {
        parent::_throwIfNotIsString($name);
    
        $this->_adminPagesDirName = $name;
    }
    
    /**
     * Admin pages are stored under this directory
     *
     * @return string
     */
    public function getAdminPagesDirName()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_adminPagesDirName;
    }
    
    /**
     * The directory iterator starts from crawling from here
     *
     * @param string $path
     * @throws Exception
     */
    public function setIncludesPath($path)
    {
        parent::_throwIfNotIsString($path);
    
        $path = realpath($path);
    
        if (! is_dir($path)) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception(
                    '$path is not a valid path. Given : ' . print_r($path, true)
            );
        }
        $this->_includesPath = $path;
    }
    
    /**
     *
     * @throws \Exception
     * @return string
     */
    public function getIncludesPath()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_includesPath;
    }
    
    /**
     * Pages with no sections are named after this
     * section
     *
     * @return string
     */
    public function getDefaultSectionName()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_defaultSectionName;
    }
    
    /**
     *
     * @param unknown_type $name
     */
    public function setDefaultSectionName($name)
    {
        parent::_throwIfNotIsString($name);
    
        $this->_defaultSectionName = $name;
    }
    
    /**
     * Pages with no sections are named after this
     * section
     *
     * @return string
     */
    public function getDefaultPageName()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_defaultPageName;
    }
    
    /**
     *
     * @param unknown_type $name
     */
    public function setDefaultPageName($name)
    {
        parent::_throwIfNotIsString($name);
    
        $this->_defaultPageName = $name;
    }
    
    /**
     *
     * @param unknown_type $version
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }
    
    /**
     *
     * @return \WPO\multitype:
     */
    public function getVersion()
    {
        $this->_throwIfNotSet(__METHOD__);
        return $this->_version;
    }
}