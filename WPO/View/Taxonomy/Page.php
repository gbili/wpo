<?php
namespace WPO\View\Taxonomy;

use WPO\Option as Option;//option constants may be used in views;

class Page
{
    protected $_page;
    protected $_loader;
    protected $_plugin;
    
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
     * @param \WPO\Plugin $plugin
     * @param unknown_type $page
     */
    public function __construct(\WPO\Plugin $plugin , $page)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        $this->_page = $page;
        
        $dO = $this->_plugin->getOptionDefaultsND()->getOptions();
        $this->defaultPageOptions = $dO[$this->_page];
        
        /*
         * Allow both variants, store as array and as object
        */
        $this->array = $this->defaultPageOptions;
        //@todo what happens when array keys have spaces?(are not valid variable names)
        $enc = json_encode($this->array);
        $this->object = json_decode($enc);
        
        $this->_loader= $this->_plugin->getViewsND();
        
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesND()->getOptions();
        
        $pagePaths = $this->_loader->getPages();
        require_once $pagePaths[$this->_page];
    }
    
    public function renderSections()
    {
        foreach ($this->defaultPageOptions as $section => $options) {
            $this->renderSection($section);
        }
    }
    
    public function renderSection($section)
    {
        //Make sure the taxonomy is higher than page, otherwise, there will be no section views to render
        require_once WPO_DIR . '/Map/AbstractMap.php';
        if (\WPO\Map\AbstractMap::TAXONOMY_PAGE === $this->_loader->getHighestTaxonomyLevel()) {
            throw new \Exception('You cannot call renderSections because there are no files for them.');
        }
        
        require_once __DIR__. '/Section.php';
        new Section($this->_plugin, $this->array[$section], $this->_loader, $this->_page, $section);
    }
}