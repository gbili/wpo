<?php
/**
 * WPT (http://wpt.onfigs.com/)
 *
 * @link       http://github.com/gbili/wpt for the canonical source repository
 * @copyright Copyright (c) 2012-2013 http://wpt.onfigs.com
 * @license   New BSD License
 * @package   WPT_Data
 */
namespace WPO\Dev;

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
class ViewsGenerator
{
    /**
     *
     * @var WPO\Plugin
     */
    private $_plugin;
    
    private $_viewMap;
    
    private $_overwrite;
    
    /**
     * 
     * @param Integrate $integrate
     * @param unknown_type $adminPagesPath
     * @throws Exception
     */
    public function __construct(\WPO\Plugin $plugin)
    {
        $this->_plugin = $plugin;
    }
    
    /**
     * 
     * @param unknown_type $overwrite
     */
    public function generateViews($viewMapBaseClassName, $overwrite = false)
    {
        $this->_overwrite = $overwrite;
        /*
         * Look for all files 
         */
        $adminPagesPath = $this->_plugin->getAdminPagesPath();
        $otest = new \WPO\Option\Map\Test\Option($adminPagesPath);//go through file system
        $omap = $otest->findMap();//used later to see if views map is compatible with this options map
        //get the loaded data (from the ND\Writer)
        $nDWriter = $omap->getNDWriter();//the maps uses an WPO\ND\Writer to store the loaded user data
        //normalize
        $nDWriter->setPlugin($this->_plugin);
        $nDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Defaults');
        $nDWriter->writeFile();
        //we will use the writer internal loader later
        
        /*
         * Make sure the passed view map exists and it's a subclass of AbstractMap
         */
        require_once WPO_DIR . '/Map/Test/MapFilesIterator.php';
        $mapsIterator = new \WPO\Map\Test\MapFilesIterator(WPO_DIR . '/View/Map');
        $mapExists = false;
        foreach ($mapsIterator as $mapFile) {
            if ($viewMapBaseClassName === $mapFile->getBasename('.php')) {
                $mapExists = true;
                break;
            }
        }
        if (!$mapExists) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception(
                'The map passed to generateViews does not exist: ' . $viewMapBaseClassName
            );
        }
        
        require_once $mapFile->getPathname();
        $viewMapClassName = '\\WPO\\View\\Map\\' . $viewMapBaseClassName;
        $this->_viewMap = new $viewMapClassName();
        $abstractClassName = '\\WPO\\Map\\AbstractMap';
        require_once WPO_PARENT_DIR . preg_replace('#\\\#', '/', $abstractClassName) . '.php';
        if (!($this->_viewMap instanceof $abstractClassName)) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception(
                'The map "' . $viewMapClassName . '" passed to generateViews does not extend "' . $abstractClassName . '"'
            );
        }
        if (!$this->_viewMap->isCompatibleWithMap($omap)) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception(
                'The map "' . $viewMapClassName . '" passed to generateViews is not compatible with options map "' . get_class($omap) . '"'
            );
        }
        
        /* @todo there should be a possibility to specify the view type from within the options info array,
         * triggering the views generator to use prepacked templates, (color picker, drop
         * down, textarea, password etc.)
         */
        //generate view files from options
        require_once WPO_DIR . '/Map/AbstractMap.php';
        $this->_recursivelyCreateViewFiles($nDWriter->getLoader()->getOptions(), \WPO\Map\AbstractMap::getOrderedTaxonomies());
        
        /*
         * Test our mapping and save the array
         * @todo do not overwrite the views normalized array on install
         */
        //view paths and taxonomy
        $vtest = new ViewTest($this->_plugin->getAdminPagesPath());//go through file system 
        $vmap = $vtest->findMap();
        $vNDWriter = $vmap->getNDWriter();
        $vNDWriter->setPlugin($this->_plugin);
        //at which level are views defined (one per option, one per section, one per page?)
        $vNDWriter->registerNormalizer('\\WPO\\View\\ND\\Normalizer\\Views', $vmap->getHighestFileTaxonomy());
        $vNDWriter->writeFile();
    }
    
    /**
     * Loop through options to generate the view files names
     * Some view maps may not support some taxonomy level of view files
     *
     * @param array $options
     * @param array $taxonomies
     * @param array $keys
     */
    protected function _recursivelyCreateViewFiles(array $options, array $taxonomies, array $keys = array())
    {
        //go one level deeper
        $taxonomy = array_shift($taxonomies);
        foreach ($options as $key => $values) {//closer to leaf...
            //keys need to be stacked, we pass then to getPath
            $keys[$taxonomy] = $key;
            if ($path = $this->_plugin->getAdminPagesPath() . $this->_viewMap->getPath($keys)) {
                $this->createIntermediteDirectories($path);
                if (!file_exists($path) || true === $this->_overwrite) {
                    file_put_contents($path, "");//write an empty file
                }
            }
            if (count($taxonomies) > 0) {
                $this->_recursivelyCreateViewFiles($values, $taxonomies, $keys);
            }
        }
    }
    
    /**
     * Mkdir -p
     * @param string $path
     */
    public function createIntermediteDirectories($path)
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