<?php
/**
 * WPT (http://wpt.onfigs.com/)
 *
 * @link       http://github.com/gbili/wpt for the canonical source repository
 * @copyright Copyright (c) 2012-2013 http://wpt.onfigs.com
 * @license   New BSD License
 * @package   WPT
 */

namespace WPO;

require_once __DIR__ . '/Option.php';

/**
 * Allow WordPress Plugin developers to have an easier task.
 * I don't like wordpress code style. This is an attempt to
 * create a better code interface. And to automate some
 * repetitive tasks. Also helps keeping files more organized.
 * 
 * @package WPT
 * @author g
 *
 */
class Plugin
{
    const VERSION                 = '1.0.1';
    
    const VIEWS_DIR_NAME          = 'view';
    const VIEW_SUFFIX             = 'phtml';
    const OPTIONS_FILE_NAME       = 'options.php';
    
    /**
     * That is only if namelen > 2 letters
     * 
     * @var integer
     */
    const SHORTNAME_MIN_LETTERS   = 3;
    
    const INCLUDES_DIR_IDENTIFIER = 'includes';
    
    const ADMIN_PAGES_DIRNAME     = 'AdminPages';
    const DEFAULT_SECTION_NAME    = 'General';
    const DEFAULT_PAGE_NAME       = 'Options';
    
    /**
     * Keys used in customize array on construction
     * @var unknown_type
     */
    const K_ADMIN_PAGES_DIRNAME   = 'adminPagesDirName';
    const K_DEFAULT_SECTION_NAME  = 'defaultSectionName';
    const K_DEFAULT_PAGE_NAME     = 'defaultPageName';
    const K_IDENTIFIER            = 'identifier';
    
    /**
     * 
     * @var WPO\View\ND\Views
     */
    private $_viewsND = null;
    
    /**
     * 
     * @var WPO\Option\ND\Values
     */
    private $_optionValuesND = null;
    
    /**
     * 
     * @var WPO\Option\ND\Defaults
     */
    private $_optionDefaultsND = null;
    
    /**
     * 
     * @var WPO\Integrate
     */
    private $_integrate = null;
    
    /**
     * Contains all plugin instances
     * 
     * @var array of identifier => $this
     */
    static private $_singletons = array();

    /**
     * The user is not supposed to set the identifier. The plugin takes care
     * of generating one. However if he prefers to set a custom one, then the
     * first param is considered the identifier, and all params meaning is 
     * moved right.
     * 
     * @param string $identifierOrName user should set this to plugin name OR identifier
     * @param string $nameOrVersion    user should set this to version OR if first para is identifier, then this one is considered plugin name
     * @param string $versionOrIncPath user should set this to version OR ^
     * @param string $includesPath     parent directory path of self::ADMIN_PAGES_DIRNAME directory
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
            case 1;
                $identifier = $identifierOrName;
                break;
            /*
             * From here it's user instantiated: name, version, includes path will be set
             */
            case 3;
                $identifier = self::nameToIdentifier($identifierOrName);
                $name = $identifierOrName;
                $version = $nameOrVersion;
                $includesPath = $versionOrIncPath;
                break;
            case 4;
                //name and custom array
                if (is_array($includesPathOrCustomizeArray)) {
                    $identifier = ($customizeArray[self::K_IDENTIFIER])? $customizeArray[self::K_IDENTIFIER] : self::nameToIdentifier($identifierOrName);
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
         * Dont allow more than one constructor call
         * We have to make this available to getInstance before Integrate instantiation
         * because intergrate constructor will call Plugin::getInstance(); if installed
         */
        if (isset(self::$_singletons[$identifier])) {
            require_once __DIR__ . '/Exception.php';
            throw new Exception(
                'You are not allowed to create more than one \\WPO\\Plugin object with this name, call getInstance() instead'
            );
        }
        /*
         * -------------- Code below here is executed only once ------------------------
         */
        self::$_singletons[$identifier] = $this;
        if (!defined('WPO_DIR')) {
            define('WPO_DIR', __DIR__);
            define('WPO_PARENT_DIR', realpath(__DIR__ . '/..'));//both are defined sequentially so no need for double test
        }
        
        /*
         * Either way we have the identifier now.
         */
        require_once WPO_DIR . '/Integrate.php';
        $this->_integrate = new Integrate($identifier);
        
        /*
         * If user instantiated it, set the version to check if it's still up to date (next if, integrate uses this)
         */
        if ($isUserInstantiated) {
            $this->setVersion($version);//version is made available here up
        }
        
        /*
         * Only set meta if not installed, because it would already be available otherwise from the Integrate object (any call to Plugin::get... will succeed)
         */
        if (!$this->_integrate->isUpToDate()) { //also false if not installed
            
            $this->setIdentifier($identifier);
            $this->setName($name);
            $this->setIncludesPath($includesPath);
            
            //set plugin values (either default or customized array)
            if (isset($customizeArray[self::K_ADMIN_PAGES_DIRNAME])) {
                $this->setAdminPagesDirName($customizeArray[self::K_ADMIN_PAGES_DIRNAME]);
            } else {
                $this->setAdminPagesDirName(self::ADMIN_PAGES_DIRNAME);
            }
            if (isset($customizeArray[self::K_DEFAULT_PAGE_NAME])) {
                $this->setDefaultPageName($customizeArray[self::K_DEFAULT_PAGE_NAME]);
            } else {
                $this->setDefaultPageName(self::DEFAULT_PAGE_NAME);
            }
            if (isset($customizeArray[self::K_DEFAULT_SECTION_NAME])) {
                $this->setDefaultSectionName($customizeArray[self::K_DEFAULT_SECTION_NAME]);
            } else {
                $this->setDefaultSectionName(self::DEFAULT_SECTION_NAME);
            }
            /*
             * All plugin values are now set even if not installed
             */
            $this->_integrate->install($this);
        }
        
        /*
         * Only init _values_ in construction because it will be faster than checking if null
         * inside getOptionValuesND every time its called
         */
        require_once WPO_DIR . '/Option/ND/Loader/Values.php';
        $this->_optionValuesND = new \WPO\Option\ND\Loader\Values($this);
    }
    
    /**
     * 
     * @param string $pluginIdentifierOrName name if first time, identifier otherwise
     * @throws Exception
     * @return \WPO\Plugin
     */
    static public function getInstance($identifier)
    {
        self::_throwIfNotIsString($identifier);
        
        //first call create the instance
        if (!isset(self::$_singletons[$identifier])) {
            new self($identifier);//the constructor stores itself in self::$_singletons
        }
        
        return self::$_singletons[$identifier];
    }
    
    /**
     * Moved here for the benefit of front end
     * 
     * @return \WPO\View\ND\Views
     */
    public function getViewsND()
    {
        if (null === $this->_viewsND) {
            require_once __DIR__ . '/View/ND/Loader/Views.php';
            $this->_viewsND = new \WPO\View\ND\Loader\Views($this);
        }
        return $this->_viewsND;
    }
    
    /**
     * Moved to construction for the benefit of front end
     * 
     * @return \WPO\Option\ND\Values
     */
    public function getOptionValuesND()
    {
        return $this->_optionValuesND;
    }
    
    /**
     * Moved here for the benefit of front end
     * 
     * @return \WPO\Option\ND\Defaults
     */
    public function getOptionDefaultsND()
    {
        if (null === $this->_optionDefaultsND) {
            require_once __DIR__ . '/Option/ND/Loader/Defaults.php';
            $this->_optionDefaultsND = new \WPO\Option\ND\Loader\Defaults($this);
        }
        return $this->_optionDefaultsND;
    }
    
    /**
     * Set on construction
     * 
     * @return \WPO\Integrate
     */
    public function getIntegrate()
    {
        return $this->_integrate;
    }
    
    /**
     * Set the theme name.
     * 
     * @param string $name
     * @throws Exception
     */
    public function setName($name)
    {
        self::_throwIfNotIsString($name);
        $this->_integrate->addMeta(Integrate::DBK_META_PLUGINNAME, $name);
    }
    
    /**
     * 
     * @throws Exception
     * @return string
     */
    public function getName()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_PLUGINNAME);
    }
    
    /**
     * Short name can be guessed on from name
     * 
     * @param string $shortName
     */
    public function setIdentifier($shortName)
    {
        $this->_integrate->addMeta(Integrate::DBK_META_PLUGINIDENTIFIER, $shortName, true);//identifier has to be saved right away (when not installed)
    }
    
    /**
     * Short name can be guessed on from name
     */
    static public function nameToIdentifier($name)
    {
        $words = explode(' ', preg_replace('/[^a-z]/', ' ', strtolower($name)));
        $shortName = '';
        $substrLen = ceil(self::SHORTNAME_MIN_LETTERS / count($words));
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
        return $this->_integrate->getMeta(Integrate::DBK_META_PLUGINIDENTIFIER);
    }
    
    /**
     *
     * @return string path
     */
    public function getAdminPagesPath()
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
        self::_throwIfNotIsString($name);
    
        $this->_integrate->addMeta(Integrate::DBK_META_ADMINPAGESDIRNAME, $name);
    }
    
    /**
     * Admin pages are stored under this directory
     *
     * @return string
     */
    public function getAdminPagesDirName()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_ADMINPAGESDIRNAME);
    }
    
    /**
     * The directory iterator starts crawling from here
     *
     * @param string $path
     * @throws Exception
     */
    public function setIncludesPath($path)
    {
        self::_throwIfNotIsString($path);
    
        $path = realpath($path);
    
        if (! is_dir($path)) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception(
                    '$path is not a valid path. Given : ' . print_r($path, true)
            );
        }
        $this->_integrate->addMeta(Integrate::DBK_META_INCLUDESPATH, $path);
    }
    
    /**
     *
     * @throws \Exception
     * @return string
     */
    public function getIncludesPath()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_INCLUDESPATH);
    }
    
    /**
     * Pages with no sections are named after this
     * section
     *
     * @return string
     */
    public function getDefaultSectionName()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_DEFAULTSECTIONNAME);
    }
    
    /**
     *
     * @param unknown_type $name
     */
    public function setDefaultSectionName($name)
    {
        self::_throwIfNotIsString($name);
    
        $this->_integrate->addMeta(Integrate::DBK_META_DEFAULTSECTIONNAME, $name);
    }
    
    /**
     * Pages with no sections are named after this
     * section
     *
     * @return string
     */
    public function getDefaultPageName()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_DEFAULTPAGENAME);
    }
    
    /**
     *
     * @param unknown_type $name
     */
    public function setDefaultPageName($name)
    {
        self::_throwIfNotIsString($name);
    
        $this->_integrate->addMeta(Integrate::DBK_META_DEFAULTPAGENAME, $name);
    }
    
    /**
     * 
     * @param unknown_type $version
     */
    public function setVersion($version)
    {
        $this->_integrate->addMeta(Integrate::DBK_META_PLUGINVERSION, $version);
    }
    
    /**
     * 
     * @return \WPO\multitype:
     */
    public function getVersion()
    {
        return $this->_integrate->getMeta(Integrate::DBK_META_PLUGINVERSION);
    }
    
    /**
     * Code reuse?
     *
     * @param string $str
     * @throws \Exception
     */
    static protected function _throwIfNotIsString($str)
    {
        if (! is_string($str)) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception(
                    '$param must be a string. Given : ' . print_r($str, true)
            );
        }
    }
}