<?php
namespace WPO\View\Taxonomy;

use WPO\Option as Option;//option constants may be used in views;

class Section
{
    protected $_page;
    protected $_section;
    
    protected $_loader;
    
    public $defaultPageOptions;
    
    /**
     * Contains the option info as members of an object
     * 
     * @var unknown_type
     */
    public $object;
    
    /**
     * Contains option info as key vals of an array
     * 
     * @var unknown_type
     */
    public $array;
    
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param array $info contains the default options info, 
     * specific to the View. If you need the current used options
     * they are available through member $this->getOptionsInUse().
     * @param string $pathToView
     */
    public function __construct(\WPO\Plugin $plugin, array $info,\WPO\ND\Loader $loader , $page, $section)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        
        $this->_page = $page;
        $this->_section = $section;
        
        $dO = $this->_plugin->getOptionDefaultsNormalizedData()->getOptions();
        $this->defaultPageOptions = $dO[$this->_page];
        
        $this->_loader= $this->_plugin->getViewsNormalizedData();
        
        /*
         * Allow both variants, store as array and as object
         */
        $this->array = $this->defaultPageOptions[$this->_section];
        //@todo what happens when array keys have spaces?(are not valid variable names)
        $enc = json_encode($this->array);
        $this->object = json_decode($enc);
        
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesNormalizedData()->getOptions();
        
        //require my view
        $sectionPaths = $this->_loader->getSections();
        require_once $sectionPaths[$this->_page][$this->_section];
    }
    
    public function renderOptions()
    {
        require_once WPO_DIR . '/Map/AbstractMap.php';
        if (\WPO\Map\AbstractMap::TAXONOMY_OPTION !== $this->_loader->getHighestTaxonomyLevel()) {
            throw new \Exception('You cannot call renderOptions because there are no files for them.');
        }
        require_once __DIR__. '/Option.php';
        foreach ($this->defaultPageOptions as $section => $options) {
            new Option($this->_plugin, $this->_page, $this->_section, $option);
        }
    }
}