<?php
/**
 * 
 * @author g
 *
 */
namespace WPO\View;

require_once WPO_DIR . '/Map/AbstractMap.php';

use WPO\Option as Option;//option constants may be used in views

/**
 * 
 * @author g
 *
 */
abstract class AbstractTaxonomy
{
    /**
     * 
     * @var WPO\Plugin
     */
    private $_plugin;
    
    /**
     * 
     * @var string
     */
    protected $themeIdentifier;
    protected $pluginIdentifier;
    
    /**
     * Options that are currently in use by the plugin
     * 
     * @var array
     */
    protected $optionsInUse;
    
    /**
     * 
     * @var array
     */
    protected $info;
    
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param array $info contains the default options info, 
     * specific to the View. If you need the current used options
     * they are available through member $this->getOptionsInUse().
     * @param string $pathToView
     */
    public function __construct(\WPO\Plugin $plugin, array $info, array $viewPaths)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        
        $this->info = $info;
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesNormalizedData()->getArray();
        $this->_init();
        require_once $pathToView;
    }
    
    /**
     * 
     * @return multitype:
     */
    public function getOptionsInUse()
    {
        return $this->optionsInUse;
    }
    
    /**
     * Outputs something like: WPO__themeIdentifier|pageName[sectionName][optionName]
     * @param string $section
     * @param string $option
     * @return string
     */
    public function getInputName($section, $option)
    {
        return $this->_plugin->getIntegrate()->getPageOptionsGroup($page) . '[' . $section . ']['. $option . ']';
    }
    
    /**
     *
     * @param string $dataKey
     */
    public function __get($key)
    {
        if (!isset($this->_info[$key])) {
            throw new Exception('Member not set for this view. Members are:' . print_r($this->_info, true));
        }
        return $this->_info[$key];
    }
    
    abstract protected function _init();
}