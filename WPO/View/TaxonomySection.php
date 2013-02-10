<?php
namespace WPO\View;

use WPO\Option as Option;//option constants may be used in views;

class TaxonomySection
extends \WPO\View\AbstractTaxonomy
{
    private $_viewPathsloader = null;
    public $page = null;
    public $section = null;
     
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param array $info contains the default options info, 
     * specific to the View. If you need the current used options
     * they are available through member $this->getOptionsInUse().
     * @param string $pathToView
     */
    public function __construct(\WPO\Plugin $plugin, array $info, $myViewPath, $myOptionsViewPaths)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        $this->_optionsViewPaths = $myOptionsViewPaths;
        $this->defaultSectionOptions = $info;
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesNormalizedData()->getArray();
        $this->_init();
        require_once $myViewPath;
    }
    
    public function renderOptions()
    {
        foreach ($this->defaultSectionOptions as $option => $info) {
            $optionView = new TaxonomyOption($this->_plugin, $info, $this->_optionsViewPaths[$option]);
        }
    }
}