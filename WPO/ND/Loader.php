<?php
namespace WPO\ND;

/**
 * This class is used to write the normalized array to a file,
 * and get the options normalized data. It will avoid using
 * maps on every script load. It simply includes the normalized
 * data arrays, and makes it available to subclasses.
 * 
 * @author g
 *
 */
class Loader
{

    /**
     * the normalizedArray array is saved to a file that is prefixed with this
     * @var unknown_type
     */
    const FILE_PREFIX = 'normalized__';
    
    const PAGES    = 0;
    const SECTIONS = 1;
    const OPTIONS  = 2;
    
    /**
     * Avoid loading data twice
     * data is stored with the class name as key
     * This way there can be one array per class
     * 
     * @var unknown_type
     */
    static private $_classToDataArray = array();
    
    /**
     * 
     * @var unknown_type
     */
    protected $_dataArray = null;
    
    private $_dataFilePath = null;
    
    /**
     * Is needed to avoid confiltcts between plugins
     * 
     * @var WPO\Plugin
     */
    private $_plugin;
    
    /**
     * Do not override constructor, use hooks like _afterLoad();
     * @param boolean $load load array from file
     */
    public function __construct(\WPO\Plugin $plugin, $load = true)
    {
        $this->_className = get_class($this);
        $this->_plugin = $plugin;
        
        if (true === $load) {
            $this->loadArray();
        }
    }
    
    /**
     * The file in which the normalized array is written
     */
    public function getArrayFilePath()
    {
        if (null === $this->_dataFilePath) {
            $nspts = explode('\\', $this->_className);
            $ns = $nspts[1];
            $type = end($nspts);
            $this->_dataFilePath = $this->_plugin->getIncludesPath() . '/' . $this->_plugin->getAdminPagesDirName() . '/' . self::FILE_PREFIX . $ns . $type . '.php';
        }
        return $this->_dataFilePath;
    }

    /**
     * Options array has been normalized and centralized, written to one single file
     */
    public function loadArray()
    {
        //include only once
        if (!isset(self::$_classToDataArray[$this->_plugin->getIdentifier()][$this->_className])) {
            self::$_classToDataArray[$this->_plugin->getIdentifier()][$this->_className] = include $this->getArrayFilePath();
        }
        //load from static
        $this->_dataArray = self::$_classToDataArray[$this->_plugin->getIdentifier()][$this->_className];
        //allow subclasses to do whatever they like with the data array after it loads
        $this->_afterLoad();
    }
    
    /**
     * allow subclasses to do whatever they like with the data array after it loads.
     * 
     * Do not put anything here, meant for subclasses to override
     */
    protected function _afterLoad()
    {
        //subclasses can add some code here by overriding
    }
    
    /**
     * Returns the data array
     * 
     * @throws Exception
     */
    public function getArray($key=null)
    {
        if (null === $this->_dataArray) {
            $this->loadArray();
        }
        if (null !== $key) {
            if (!isset($this->_dataArray[$key])) {
                throw new \Exception("The key:$key is not set in \$this->_dataArray");
            }
            return $this->_dataArray[$key];
        }
        return $this->_dataArray;
    }
    
    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->getArray(self::OPTIONS);
    }
    
    /**
     * @return array
     */
    public function getSections()
    {
        return $this->getArray(self::SECTIONS);
    }
    
    /**
     * @return array
     */
    public function getPages()
    {
        return $this->getArray(self::PAGES);
    }
}