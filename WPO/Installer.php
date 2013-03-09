<?php
/**
 * WPT (http://wpt.onfigs.com/)
 *
 * @link       http://github.com/gbili/wpt for the canonical source repository
 * @copyright Copyright (c) 2012-2013 http://wpt.onfigs.com
 * @license   New BSD License
 * @package   WPT_Data
 */
namespace WPO;

require_once WPO_DIR . '/Option/Map/Test/Option.php';
require_once WPO_DIR . '/View/Map/Test/View.php';
require_once WPO_DIR . '/Option/ND/Normalizer/Defaults.php';
require_once WPO_DIR . '/Option/ND/Normalizer/Values.php';
require_once WPO_DIR . '/View/ND/Normalizer/Views.php';

use WPO\Option\Map\Test\Option                        as OptionTest,
    WPO\View\Map\Test\View                            as ViewTest,
    WPO\Option\NormalizedData\Defaults\DataNormalizer as OptionDataDefaultsNormalizer,
    WPO\Option\NormalizedData\Values\DataNormalizer   as OptionValuesDataNormalizer,
    WPO\View\NormalizedData\Views\DataNormalizer      as ViewViewsDataNormalizer;

/**
 * Detect Admin Pages option files and view files.
 * Moved away from WPO\Integrate to load less files if not installing
 *
 * package   WPT_Data
 * @author g
 *
 */
class Installer
{
    
    /**
     * When generating views, use this map as default
     *
     * @var unknown_type
     */
    static public $defaultViewMap = 'StandardP1S1V1v1';
    
    /**
     *
     * @var WPO\Plugin
     */
    private $_plugin;
    
    /**
     * 
     * @param Integrate $integrate
     * @param unknown_type $adminPagesPath
     * @throws Exception
     */
    public function __construct(\WPO\Plugin $plugin)
    {
        if ($plugin->getIntegrate()->isInstalled()
          && $plugin->getIntegrate()->isUpToDate()) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception('The software is already installed and uptodate, so you should not reinstall');
        }
        $this->_plugin = $plugin;
    }
    
    /**
     * Important, the no array files have been saved yet, so we cannot
     * get the NormalizedData arrays from the plugin, they would not
     * be able to load the file (It doesn't exist yet). 
     * 
     * Parse all admin pages directory, create standard arrays that can
     * be used by options and views, then write the arrays to files.
     * Add the version of wpo to meta
     */
    public function install()
    {
        require_once WPO_DIR . '/Map/AbstractMap.php';
        \WPO\Map\AbstractMap::setSubPathToRemoveFromPath($this->_plugin->getAdminPagesPath());
        /*
         * Look into the filesystem to see how the theme developer
         * stored the option files. Use tests to discover path maps
         * Then create a file that holds the whole data in a centralized way
         */
        $adminPagesPath = $this->_plugin->getAdminPagesPath();
        
        /*
         * Did the user create an admin pages dir?
         * @todo it makes no sense to create the admin pages dir, because
         * if there is no such dir, the use of wpo is pointless... options file is stored in admin pages dir..
         */
        if (!is_dir($adminPagesPath)) {
            echo "You have not created the admin pages dir, I'm creating it for you.";
            self::createIntermediteDirectories($adminPagesPath);
            if (!is_dir($adminPagesPath)) {
                require_once WPO_DIR . '/Exception.php';
                throw new \WPO\Exception('Could not create the admin pages dir');
            }
        }
        
        /*
         * --- DEFAULT OPTIONS
         */
        $otest = new \WPO\Option\Map\Test\Option($adminPagesPath);//go through file system
        $otest->findMap();
        //get the loaded data (from the ND\Writer)
        $oDNDWriter = $otest->getMap()->getNDWriter();//the maps uses an WPO\ND\Writer to store the loaded user data
        //normalize
        $oDNDWriter->setPlugin($this->_plugin);
        $oDNDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Defaults');
        $oDNDWriter->writeFile();
        
        /*
         * --- OPTION VALUES
         */
        require_once WPO_DIR . '/ND/Writer.php';
        $oVNDWriter = new \WPO\ND\Writer();
        $oVNDWriter->setPlugin($this->_plugin);
        $oVNDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Values');
        $oVNDWriter->writeFile($oDNDWriter->getArray());//use defaults writer array as input for this one
        
        /*
         * --- VIEWS paths and taxonomy
         */
        $vtest = new ViewTest($this->_plugin->getAdminPagesPath());//go through file system 
        
        /*
         * If no map was found it is either because the user did something wrong
         * when creating the view files, or because he wants us to create them
         * @todo allow for partial view files generation, if some are missing.
         * Currently we will only attempt to create views if no views are set by
         * user. Ultimately if no map is found, it will throw up on next getMap()
         */
        if (!$vtest->findMap()) {
            require_once WPO_DIR . '/Dev/Plugin.php';
            //create a mock plugin
            $p = new \WPO\Dev\Plugin($this->_plugin->getIdentifier(), $this->_plugin->getVersion(), $this->_plugin->getIncludesPath());
            $p->generateViewFiles(self::$defaultViewMap, true);
            //try to find the map again, wiht the new created view files
            $vtest->findMap();
        }
        
        $vmap = $vtest->getMap();
        $vNDWriter = $vmap->getNDWriter();
        $vNDWriter->setPlugin($this->_plugin);
        //at which level are views defined (one per option, one per section, one per page?)
        $vNDWriter->registerNormalizer('\\WPO\\View\\ND\\Normalizer\\Views', $vmap->getHighestFileTaxonomy());
        $vNDWriter->writeFile();
        
        /*
         * Add some meta: WPO plugin version to the database
         * Views Taxonomy level is used by the view to determine
         * how to request and buffer the view files. 
         */
        $this->_plugin->getIntegrate()->addMeta(Integrate::DBK_META_WPOVERSION, \WPO\Plugin::VERSION);
        $this->_plugin->getIntegrate()->addMeta(Integrate::DBK_META_VIEWTAXONOMYLEVEL, $vmap->getHighestFileTaxonomy());
    }
    
    /**
     * Mkdir -p
     * @param string $path
     */
    static public function createIntermediteDirectories($path)
    {
        $pathParts = explode('/', $path);
        //does the path point to a file? ..because we only want to create the directories
        //does the part contain a "name.something" (name dot something)
        if (1 < count($ptParts = preg_split('#\\.#', end($pathParts))) && '' !== current($ptParts)) {//dont pop hidden folders
            array_pop($pathParts);
        }
    
        $path = implode('/', $pathParts);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);//true == -p
        }
    
        if (!file_exists($path)) {
            trigger_error('folder not created',E_NOTICE);
        }
    }
}