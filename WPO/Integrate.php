<?php
/**
 * WPO (http://wpo.onfigs.com/)
 *
 * @link       http://github.com/gbili/wpt for the canonical source repository
 * @copyright Copyright (c) 2012-2013 http://wpt.onfigs.com
 * @license   New BSD License
 * @package   WPT_Data
 */
namespace WPO;

require_once __DIR__ . '/Dispatcher.php';

/**
 * Detect Admin Pages option files and view files
 *
 * package   WPT_Data
 * @author g
 *
 */
class Integrate
{   
    /**
     * DBR_... identifies the row name
     * of some data set in db options.
     * if a new row is to be added, a DBR_
     * must be appended to avoid row conflicts
     * Furthermore, I've red that options arrays
     * can only be two-dimensional. Ours is
     * deeper (at least four levels...to 6 levels:
     * 1. array(pagename->
     * 2.  array(section->
     * 3.    array(field->
     * 4.      array(data->
     * //optional if validator !data
     * 5.        array(validValues->
     * 6.          array(additionalData)
     *
     * @see DB_ROW_SUFFIX
     * @var string
     */
    const DBR_META                    = 'meta';
    const DBK_META_PLUGINIDENTIFIER   = 'id';
    const DBK_META_PLUGINNAME         = 'name';
    const DBK_META_ADMINPAGESDIRNAME  = 'adminPDirBasename';
    const DBK_META_INCLUDESPATH       = 'incPath';
    const DBK_META_DEFAULTSECTIONNAME = 'defaultSectionName';
    const DBK_META_DEFAULTPAGENAME    = 'defaultPageName';
    const DBK_META_ISUPTODATE         = 'utd';
    const DBK_META_PLUGINVERSION      = 'pluginVersion';
    const DBK_META_WPOVERSION         = 'WPOVersion';
    const DBK_META_VIEWTAXONOMYLEVEL  = 'viewTaxonomyLevel';
    
    /**
     * WPO__plugin_identifier|some_key:maybe_someOther:andSoOn
     * @var unknown_type
     */
    const DBROW_PREFIX               = 'WPO';
    const DBROW_PREPID_SEP           = '__';
    const DBROW_PREFIX_SEP           = '|';
    const DBROW_KEY_SEP              = ':';
    
    const OPTIONS_GROUP_SEP            = '_';
    const CAPABILITY_EDITTHEMEOPTIONS  = 'edit_theme_options';
    
    static private $_appendToPageTitle = ' Plugin Options';
    
    /**
     * 
     * @var unknown_type
     */
    private $_pluginIdentifier = null;
    
    /**
     * 
     * @var WPO\Option\NormalizedData\Values
     */
    private $_optionsValuesND = null;

    /**
     * Contains the meta information needed to start the manager
     * The meta is automatically populated by getMeta()
     * @var array
     */
    private $_meta = array();
    
    /**
     * Contains the meta that has no equivalent in the database
     * It will have once it is saved, but then it will be removed
     * from tempMeta, and set in meta
     * 
     * @var unknown_type
     */
    private $_tempMeta = array();
    
    /**
     * This is made available from _init() (not before..)
     * after _plugin is set
     * 
     * @var bool
     */
    private $_isTheme;
    
    /**
     * This is made available from _init() (not before..)
     * 
     * @var \WPO\Plugin
     */
    private $_plugin;
    
    /**
     * 
     * @param string $pluginIdentifier
     */
    public function __construct($pluginIdentifier)
    {
        $this->_pluginIdentifier = $pluginIdentifier;
        //if no meta can be loaded, it's not installed ($this->_meta is needed by isInstalled)
        $this->_loadMeta();//meta is loaded from pluginIdentifier
        
        /*
         * If installed, all meta is available
         */
        if ($this->isUpToDate()) {
            $this->_init();
        }
        /*
         * If not installed, plugin is in charge of installing itself (call Integrate::install($plugin)) once all plugin data is set
         */
    }
    
    /**
     * All the meta data that was set in Plugin::_construct will be
     * saved to the wpdb options table. The option files and view files
     * will also be saved to an array in a file (normalized_).
     */
    public function install(\WPO\Plugin $pluginToInstall)
    {
        if ($this->isUpToDate()) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception('Trying to install/update an already up to date plugin, with name : ' . $pluginToInstall->getIdentifier());
        }
        require_once WPO_DIR . '/Installer.php';
        $installer = new \WPO\Installer($pluginToInstall);
        $installer->install();
        $this->_saveMeta();//save meta to db
        $this->_reloadMeta();//meta is in tempMeta, swap it to meta
        $this->_init();
    }
    
    /**
     * Checks wpdb to see if it has been installed
     * by getting self::DBR_META key from db. This means
     * that DBR_META key has to be inserted in db on install
     *
     * @see _install()
     * @return boolean
     */
    public function isInstalled()
    {
        return !empty($this->_meta);//meta is loaded on construction
    }
    
    /**
     * The version is set on each construction
     * of the plugin. But it is only saved to temp
     * meta, if it is not available in $this->_meta
     * 
     * @return boolean 
     */
    public function isUpToDate()
    {
        return $this->isInstalled() && !isset($this->_tempMeta[self::DBK_META_PLUGINVERSION]);
    }
    
    /**
     * Initialize options keep the normalized array
     * in _optionsND
     * Register wordpress admin_init
     */
    private function _init()
    {
        //we are keeping a reference to the plugin here, but note that construction
        //is not finished in \WPO\Plugin
        $this->_plugin = \WPO\Plugin::getInstance($this->_pluginIdentifier);
        /*
         * allow for theme or plugin specific function calls
         */
        $this->_isTheme = (false !== strpos(get_class($this->_plugin), 'Theme'));
        /*
         * (The options pages are registered
         * at wp admin init, but we still need to tell wp
         * to call the registration method)
         * call add_action(admin_menu
         *      add_action(admin_init
         */
        add_action('admin_menu', \WPO\Dispatcher::getIntegrateCallback($this->_pluginIdentifier, 'addPagesToAdminMenu'));
        add_action('admin_init', \WPO\Dispatcher::getIntegrateCallback($this->_pluginIdentifier, 'registerOptions'));
        //@todo implement styles and scripts enqueueing. they should be located in each page's folder under styles.css scirpt.js or conversely under AdminPages/styles/pageName.js etc.
        //@todo implement options help, they should be located in each page's folder in help.phtml, resolve view .phtml discover conflict. Do the same for sidebar help
    }
    
    /**
     * 
     */
    public function getPluginIdentifier()
    {
        return $this->_pluginIdentifier;
    }
    
    /**
     * Use add_theme_page | add_options_page
     * 
     * @param array $optsArray
     */
    public function addPagesToAdminMenu()
    {
        $wpfunction = ($this->_isTheme)? '\\add_theme_page' : '\\add_options_page';
        foreach ($this->_plugin->getOptionValuesNormalizedData()->getOptions() as $page => $sections) {
            $options = current($sections);
            $info    = current($options);
            $wpfunction(
                $info[Option::PAGE_TITLE] . self::$_appendToPageTitle, //page title
                $info[Option::PAGE_TITLE],                             //page title in menu
                self::CAPABILITY_EDITTHEMEOPTIONS,                     //capability to view page
                $page,                                                 //slug in menu
                \WPO\Dispatcher::getRenderCallback($this->_pluginIdentifier, $page) //function wp must call for rendering
            );
        }
    }
    
    /**
     * Use wordpress functions to add options
     * 
     * @param array $optsArray
     */
    public function registerOptions()
    {
        foreach ($this->_plugin->getOptionValuesNormalizedData()->getOptions() as $page => $sections) {
            \register_setting(
                $this->getPageOptionsGroup($page),         //options group
                $this->getPageOptionsDbKey($page),
                \WPO\Dispatcher::getValidateCallback($this->_pluginIdentifier, $page)
            );
            foreach ($sections as $section => $options) {
                $info = current($options);//get the first option, to have a grasp on section's title
               \add_settings_section(
                    $section,                     //section unique identifier
                    $info[Option::SECTION_TITLE], //section's title
                    '__return_false',             //no section callback
                    $page                         //menu slug used to uniquely identify page
                );
                foreach ($options as $option => $info) {
                   \add_settings_field(
                        $option, //unique identifier for the field
                        __( $info[Option::TITLE], $this->_pluginIdentifier), //settings field label
                        \WPO\Dispatcher::getRenderCallback($this->_pluginIdentifier, $page),//function to render the section
                        $page, //menu slug used to uniquely identify page
                        $section //settings section, same as the first argument in add_settings_section
                    );
                }
            }
        }
    }
    
    /**
     * WPO__themename|append:apend
     * 
     * @param string $append
     * @return mixed string|array of strings
     */
    private function _getIdentifyingSlug($append)
    {
        if (is_array($append)) {
            $append = implode(self::DBROW_KEY_SEP, $append);
        }
        
        //WPO__themename|append0:append1:append2
        return self::DBROW_PREFIX 
               . self::DBROW_PREPID_SEP 
               . $this->_pluginIdentifier 
               . self::DBROW_PREFIX_SEP 
               . $append;
    }
    
    /**
     * WPO__themename|pageName
     * @param string $page
     */
    public function getPageOptionsGroup($page)
    {
        return $this->_getIdentifyingSlug($page);
    }
    
    /**
     * WPO__themename|pageName
     * @param string $page
     */
    public function getPageOptionsDbKey($page)
    {
        return $this->_getIdentifyingSlug($page);
    }
    
    /**
     * 
     * @return string
     */
    public function getDbKey($name)
    {
        return $this->_getIdentifyingSlug($name);
    }
    
    /**
     * Note that $this->_meta is loaded on construction
     * so, if there is something in db, by now $this->_meta
     * should have it.
     * 
     * @param unknown_type $key
     * @param unknown_type $value
     */
    public function addMeta($key, $value, $now=false)
    {
        if (isset($this->_meta[$key])) {
            if ($value === $this->_meta[$key]) {
                return;//the meta already is the same in db
            }
        }
        
        //save to temp meta the new value
        $this->_tempMeta[$key] = $value;
        
        if (true === $now) {
            $this->_saveMeta();
        }
    }
    
    /**
     * 
     */
    private function _loadMeta()
    {
        //make sure meta from db is available
        if (empty($this->_meta)) {
            //if no meta, get from database
            $dbKey = $this->getDbKey(self::DBR_META);
            $this->_meta = get_option($dbKey, array());
        }
        return empty($this->_meta);
    }
    
    /**
     * Swap temp meta to meta, this is called after
     * install (the first time data is inserted into db)
     */
    private function _reloadMeta()
    {
        $this->_meta = $this->_tempMeta = array();
        $this->_loadMeta();
    }
    
    /**
     * Get the meta from database or if already
     * fetched, get the value from meta member
     * 
     * @param unknown_type $key
     * @return multitype:
     */
    public function getMeta($key = null)
    {
        if (null === $key) {
            return array_merge($this->_meta, $this->_tempMeta);//this is used for saving and not loosing data
        }
        
        //return from tempMeta if no meta
        if (isset($this->_meta[$key])) {
            return $this->_meta[$key];
        } else if (isset($this->_tempMeta[$key])) {
            return $this->_tempMeta[$key];
        }
        require_once WPO_DIR . '/Exception.php';
        throw new Exception('No meta with this key has been set yet, key : ' . $key );
    }
    
    /**
     * Save meta to wordpress db
     */
    private function _saveMeta()
    {
        if (empty($this->_tempMeta)) {
            return;
        }
        $k = $this->getDbKey(self::DBR_META);
        update_option($k, $this->getMeta());
    }
    
    /**
     * Make sure no meta is lost
     */
    public function __destruct()
    {
        $this->_saveMeta();
    }
}