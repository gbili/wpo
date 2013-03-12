<?php
namespace WPO\View\Taxonomy;

class Option
{
    protected $_page;
    protected $_section;
    protected $_option;
    
    protected $_loader;
    
    public $defaultPageOptions;
    public $optionsInUse;
    public $current;
    
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
    public function __construct(\WPO\Plugin $plugin, $page, $section, $option)
    {
        $this->_plugin = $plugin;
        $this->pluginIdentifier = $this->themeIdentifier = $plugin->getIdentifier();
        
        $this->_page = $page;
        $this->_section = $section;
        $this->_option = $option;
        
        $dO = $this->_plugin->getOptionDefaultsND()->getOptions();
        $this->defaultPageOptions = $dO[$this->_page];
        
        $this->_loader= $this->_plugin->getViewsND();

        /*
         * Allow both variants, store as array and as object
         */
        $this->array = $this->defaultPageOptions[$this->_section][$this->_option];
        //@todo what happens when array keys have spaces?(are not valid variable names)
        $enc = json_encode($this->array);
        $this->object = json_decode($enc);
        
        //you can use _init() to narrow the scope of the current option
        $this->optionsInUse = $plugin->getOptionValuesND()->getOptions();
        $this->current = $this->optionsInUse[$this->_page][$this->_section][$this->_option];
        
        //require my view
        $optionPaths = $this->_loader->getOptions();
        require_once $optionPaths[$this->_page][$this->_section][$this->_option];
    }
    
    public function __get($item)
    {
        if (!isset($this->array[$item])) {
            throw new \Exception("The option element with name $item is not set");
        }
        return $this->array[$item];
    }
}