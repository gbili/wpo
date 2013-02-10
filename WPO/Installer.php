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
        $otest = new \WPO\Option\Map\Test\Option($adminPagesPath);//go through file system
        $omap = $otest->findMap();//used later to see if views map is compatible with this options map
        //get the loaded data (from the ND\Writer)
        $oDNDWriter = $omap->getNDWriter();//the maps uses an WPO\ND\Writer to store the loaded user data
        //normalize
        $oDNDWriter->setPlugin($this->_plugin);
        $oDNDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Defaults');
        $oDNDWriter->writeFile();
        
        //option values
        require_once WPO_DIR . '/ND/Writer.php';
        $oVNDWriter = new \WPO\ND\Writer();
        $oVNDWriter->setPlugin($this->_plugin);
        $oVNDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Values');
        $oVNDWriter->writeFile($oDNDWriter->getArray());//use defaults writer array as input for this one
        
        //view paths and taxonomy
        $vtest = new ViewTest($this->_plugin->getAdminPagesPath());//go through file system 
        $vmap = $vtest->findMap();
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
}