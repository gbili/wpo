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
require_once WPO_DIR . '/Installer.php';//using method: createIntermediateDirectories()

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
     * Where are templates stored relative to wpo dir?
     * means : true === is_dir(WPO_DIR . VIEW_TEMPLATES_DIR_WPORELATIVE)
     * @var unknown_type
     */
    const VIEW_TEMPLATES_DIR_WPORELATIVE = '/prepacked/templates/view';
    
    /**
     *
     * @var WPO\Plugin
     */
    private $_plugin;
    
    private $_viewMap;
    private $_viewMapBaseClassName;
    
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
     * This will generate all the view files
     * @todo ???description is wrong? only view files? I see normalized options
     * @param unknown_type $overwrite
     */
    public function generateViews($viewMapBaseClassName, $overwrite = false)
    {
        $this->_overwrite = $overwrite;
        $this->_viewMapBaseClassName = $viewMapBaseClassName;

        /*
         * Depr//Test and write a normalized Defaults options array!
         * We have to get the options map to generate compatible views
         * 
         */
        $otest = new \WPO\Option\Map\Test\Option($this->_plugin->getAdminPagesPath());
        //maybe the test has been done elswere and we can use that result
        if(!$otest->isAlreadyTested()) {
            $otest->findMap();
        }
        $omap = $otest->getMap();//used later to see if views map is compatible with this options map
        //get the loaded data (from the ND\Writer)
        $nDWriter = $omap->getNDWriter();//the maps uses an WPO\ND\Writer to store the loaded user data
        //normalize
        /*$nDWriter->setPlugin($this->_plugin);
        $nDWriter->registerNormalizer('\\WPO\\Option\\ND\\Normalizer\\Defaults');
        $nDWriter->writeFile();*/
        //we will use the writer internal loader later
        
        /*
         * === Views
         * Make sure the param $viewMapBaseClassName exists and it's a subclass of AbstractMap
         * For that we create a maps iterator of the view type, and see if there is some file
         * containing the class with base name $viewMapBaseClassName
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
        
        /*
         * Load view map class file, create an instance and check type
         * Then make sure that the view map is compatible with the already
         * used options map
         */
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
        $this->_recursivelyCreateViewFiles($omap->getNDWriter()->getOptions(), \WPO\Map\AbstractMap::getOrderedTaxonomies());
    }
    
    /**
     * Loop through options to generate the view files names
     * Some view maps may not support some taxonomy level of view files
     *
     * @param array $data options array
     * @param array $taxonomies
     * @param array $keys
     */
    protected function _recursivelyCreateViewFiles(array $data, array $taxonomies, array $keys = array())
    {   
        //go one level deeper
        $taxonomy = array_shift($taxonomies);
        foreach ($data as $key => $values) {//closer to leaf...
            //keys need to be stacked, we pass then to getPath
            $keys[$taxonomy] = $key;
            if ($path = $this->_plugin->getAdminPagesPath() . $this->_viewMap->getPath($keys)) {
                \WPO\Installer::createIntermediteDirectories($path);
                if (!file_exists($path) || true === $this->_overwrite) {
                    file_put_contents($path, $this->_getViewFileContents($taxonomy));
                }
            }
            if (count($taxonomies) > 0) {
                $this->_recursivelyCreateViewFiles($values, $taxonomies, $keys);
            }
        }
    }
    
    /**
     * 
     * @param string $viewMapViewTempaltesDir where are the maps view templates stored
     * @param string $taxonomy what is the taxonomy level of the view? option, page or section
     */
    private function _getViewFileContents($taxonomy)
    {
        $viewMapViewTempaltesDir = WPO_DIR . self::VIEW_TEMPLATES_DIR_WPORELATIVE . '/'. $this->_viewMapBaseClassName;
        if (!is_dir($viewMapViewTempaltesDir)) {
            return '';//there are no default templates for this map type
        }
        $viewTemplate = $viewMapViewTempaltesDir . '/' . strtolower($taxonomy) . '.' . \WPO\Plugin::VIEW_SUFFIX;
        //@todo when the taxonomy is options, then we have to allow for further refinement of the view template
        //for example when the option wants tu use a multicheck, load path/option_multicheck.phtml
        if (!file_exists($viewTemplate)) {
            throw new \Exception("Your map does not seem to have a view template for taxonomy: $taxonomy, create one as $viewTemplate");
        }
        return file_get_contents($viewTemplate);
    }
}