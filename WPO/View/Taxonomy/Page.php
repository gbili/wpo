<?php
namespace WPO\View\Taxonomy;

use WPO\Option as Option;//option constants may be used in views;

class Page
{
    public $defaultPageOptions;
    protected $_page;
    protected $_loader;
    protected $_plugin;
    
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param array $info contains the default options info, 
     * specific to the View. If you need the current used options
     * they are available through member $this->getOptionsInUse().
     * @param string $pathToView
     */
    public function __construct(\WPO\Plugin $plugin , $page)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        
        $this->_page = $page;
        
        $dO = $this->_plugin->getOptionDefaultsNormalizedData()->getOptions();
        $this->defaultPageOptions = $dO[$this->_page];
        
        $this->_loader= $this->_plugin->getViewsNormalizedData();
        
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesNormalizedData()->getOptions();
        
        $pagePaths = $this->_loader->getPages();
        require_once $pagePaths[$this->_page];
    }
    
    public function renderSections()
    {
        require_once WPO_DIR . '/Map/AbstractMap.php';
        if (\WPO\Map\AbstractMap::TAXONOMY_PAGE === $this->_loader->getHighestTaxonomyLevel()) {
            throw new \Exception('You cannot call renderSections because there are no files for them.');
        }
        require_once __DIR__. '/Section.php';
        foreach ($this->defaultPageOptions as $section => $options) {
            new Section($this->_plugin, $options, $this->_loader, $this->_page, $section);
        }
    }
}