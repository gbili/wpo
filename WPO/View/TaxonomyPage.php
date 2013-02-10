<?php
namespace WPO\View;

use WPO\Option as Option;//option constants may be used in views;

class TaxonomyPage
extends \WPO\View\AbstractTaxonomy
{
    protected $defaultPageOptions;
    
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param array $info contains the default options info, 
     * specific to the View. If you need the current used options
     * they are available through member $this->getOptionsInUse().
     * @param string $pathToView
     */
    public function __construct(\WPO\Plugin $plugin, array $info, $myViewPath, \WPO\ND\Loader $loader)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        
        $this->defaultPageOptions = $info;
        
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesNormalizedData()->getArray();
        $this->_init();
        require_once $myViewPath;
    }
    
    public function renderSections()
    {
        foreach ($this->defaulPagetOptions as $section => $options) {
            $sectionView = new TaxonomySection($this->_plugin, $options, $this->_viewPaths);
        }
    }
}