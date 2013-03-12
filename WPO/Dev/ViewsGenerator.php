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
    const WPOREL_VIEW_TEMPLATES_DIR = '/prepacked/templates/view';
    
    const WPOREL_FORM_ELEM_TEMPLATES_DIR = '/prepacked/templates/form/element';
    
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
     * @param array $taxonomies array('Page', 'Section', 'Option') on every recursion there is one less
     * @param array $keys
     */
    protected function _recursivelyCreateViewFiles(array $data, array $taxonomies, array $keys = array())
    {   
        //go one level deeper
        $taxonomy = array_shift($taxonomies);
        foreach ($data as $key => $values) {//closer to leaf...
            //keys need to be stacked, we pass them to getPath
            $keys[$taxonomy] = $key;
            if ($path = $this->_plugin->getAdminPagesPath() . $this->_viewMap->getPath($keys)) {
                \WPO\Installer::createIntermediateDirectories($path);
                if (!file_exists($path) || true === $this->_overwrite) {
                    file_put_contents($path, $this->_getViewFileContents($taxonomy, ((isset($values[\WPO\Option::VIEW_TYPE]))? $values[\WPO\Option::VIEW_TYPE] : null)));
                }
            }
            if (count($taxonomies) > 0) {
                $this->_recursivelyCreateViewFiles($values, $taxonomies, $keys);
            }
        }
    }
    
    /**
     * Get the map specific prepacked views for each taxonomy level.
     * However when in option taxonomy, the user can specify to use
     * a specific form element for the view. Try to get it or use
     * the default map specific view.
     * 
     * @param const $taxonomy for what taxonomy level is the view
     * @param string $viewType file basename no suffix. Does the user want to use a prepacked form element?
     * @throws \Exception
     * @return string
     */
    private function _getViewFileContents($taxonomy, $viewType=null)
    {
        //try to get the form element if user wants to
        $viewPath = '';
        if (null !== $viewType) {
            $viewPath = WPO_DIR . self::WPOREL_FORM_ELEM_TEMPLATES_DIR . "/$viewType." . \WPO\Plugin::VIEW_SUFFIX;
        }
        
        //Did not get the form element specified in viewType
        if (!file_exists($viewPath)) {
            $viewMapViewTempaltesDir = WPO_DIR . self::WPOREL_VIEW_TEMPLATES_DIR . '/'. $this->_viewMapBaseClassName;
            if (!is_dir($viewMapViewTempaltesDir)) {
                return '';//there are no default templates for this map type, return an empty string used as view content
            }
            
            $viewPath = $viewMapViewTempaltesDir . '/' . strtolower($taxonomy) . '.' . \WPO\Plugin::VIEW_SUFFIX;
            
            if (!file_exists($viewPath)) {
                throw new \Exception("Your map does not seem to have a view template for taxonomy: $taxonomy, create one as $viewPath");
            }
        }
        
        return file_get_contents($viewPath);
    }
    
    
}