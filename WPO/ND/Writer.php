<?php
namespace WPO\ND;

require_once __DIR__ . '/Loader.php';//constants


/**
 * This class is used to write the normalized array to a file,
 * and get the options normalized data. It will avoid using
 * maps on every script load. It simply includes the normalized
 * data arrays, and makes it available to subclasses.
 * 
 * @author g
 *
 */
class Writer
{
    /**
     * 
     * @var unknown_type
     */
    protected $_dataArray = null;
    
    /**
     * Is needed to avoid confiltcts between plugins
     * 
     * @var WPO\Plugin
     */
    private $_plugin;
    
    /**
     * 
     * @var \WPO\ND\Loader
     */
    private $_loader;
    
    /**
     * 
     * @var \WPO\ND\Normalizer\NormalizerInterface
     */
    private $_normalizer;
    
    /**
     * Has _normalize() been called?
     * 
     * @var unknown_type
     */
    private $_isNormalized = false;
    
    /**
     * 
     * @var boolean
     */
    private $_isWritten = false;
    
    /**
     * Do not override constructor, use hooks like _afterLoad();
     * @param boolean $load load array from file
     */
    public function __construct()
    {
        $this->_dataArray = array(
                                \WPO\ND\Loader::PAGES    =>array(), 
                                \WPO\ND\Loader::SECTIONS =>array(), 
                                \WPO\ND\Loader::OPTIONS  =>array()
                            );
    }
    
    /**
     * 
     * @return \WPO\ND\Loader
     */
    public function getLoader()
    {
        if (null === $this->_loader) {//no loader was registered
            if (null === $this->_normalizer) {//can we still create one from the normalizer classname part?
                throw new \Exception('You have to register either a loader classname or a normalizer classname. In the latter we will create the loader for you from part of the normalizer classname');
            }
            $loaderClassName = preg_replace('#Normalizer#', 'Loader', '\\' . get_class($this->_normalizer));
            $this->registerLoader($loaderClassName);
        }
        return $this->_loader;
    }

    
    /**
     * Returns the data array that the map has set 
     * 
     * @throws Exception
     */
    public function getArray()
    {
        //try to normalized but if no normalizer is registered, then it will not normalize, and return
        if (!$this->isNormalized()) {
            $this->_normalize();
        }
        return $this->_dataArray;
    }
    
    /**
     * Call this if you want to know if someone called
     * add...() or set()
     * 
     * @return boolean
     */
    public function hasDataToWrite()
    {
        return !empty($this->_dataArray);
    }
    
    /**
     * 
     * @param unknown_type $normalizerFullClassName
     * @param unknown_type $params
     * @throws \Execption
     * @throws \Exception
     */
    public function registerNormalizer($normalizerFullClassName, $params = null)
    {
        $normalizerPath = WPO_PARENT_DIR . preg_replace('#\\\#', '/', $normalizerFullClassName) . '.php';
        if (!file_exists($normalizerPath)) {
            throw new \Exception('No normalizer with path : ' . $normalizerPath);
        }
        if (!class_exists($normalizerFullClassName)) {
            throw new \Exception('The class does not exist : ' . $normalizerFullClassName);
        }
        $this->_normalizer = new $normalizerFullClassName($params);
        require_once WPO_DIR . '/ND/Normalizer/NormalizerInterface.php';
        if (!($this->_normalizer instanceof \WPO\ND\Normalizer\NormalizerInterface)) {
            throw new \Exception('The normalizer must implement : \\WPO\\ND\\Normalizer\\NormalizerInterface');
        }
    }
    
    /**
     * 
     * @param unknown_type $loaderFullClassName
     * @param \WPO\Plugin $plugin the plugin to be passed as constructor to the loader (if not passed, must call setPlugin() before this)
     * @throws \Exception
     */
    public function registerLoader($loaderFullClassName, \WPO\Plugin $plugin = null)
    {
        $loaderPath = WPO_PARENT_DIR . preg_replace('#\\\#', '/', $loaderFullClassName)  . '.php';
        if (!file_exists($loaderPath)) {
            throw new \Exception('No loader with path : ' . $loaderPath);
        }
        require_once $loaderPath;
        if (!class_exists($loaderFullClassName)) {
            throw new \Exception('The class does not exist : ' . $loaderFullClassName);
        }
        
        if (null === $plugin) {
            $plugin = $this->getPlugin();
        }
        
        $this->_loader = new $loaderFullClassName($plugin, $this->_isWritten);//dont load if not written
        
        require_once WPO_DIR . '/ND/Loader.php';
        if (!($this->_loader instanceof \WPO\ND\Loader)) {
            throw new \Exception('The normalizer must implement : \\WPO\\ND\\Loader');
        }
    }
    
    /**
     * Set whole data array at once as one of the tree taxonomies data
     * 
     * @param const $to taxonomy
     * @param array $data option info
     * @throws \Exception
     */
    public function setTo($to, $data)
    {
        //one view per option
        switch ($to) {
            case \WPO\ND\Loader::OPTIONS;
                foreach ($data as $page => $sections) {
                    foreach ($sections as $section => $options) {
                        foreach ($options as $option => $info) {
                            $this->addOption(array($page, $section, $option, $info));
                        }
                    }
                }
                break;
            case \WPO\ND\Loader::SECTIONS;
                foreach ($data as $page => $sections) {
                    foreach ($sections as $section => $info) {
                        $this->addSection(array($page, $section, $info));
                    }
                }
                break;
            case \WPO\ND\Loader::PAGES;
                foreach ($data as $page => $info) {
                    $this->addPage(array($page, $info));
                }
                break;
            default;
                throw new \Exception('Choose among ND\Loader constants for the second parameter');
                break;
        }
    }
    
    public function getOptions()
    {
        $a = $this->getArray();
        return $a[\WPO\ND\Loader::OPTIONS];
    }
    
    public function getSections()
    {
        $a = $this->getArray();
        return $a[\WPO\ND\Loader::SECTIONS];
    }
    
    public function getPages()
    {
        $a = $this->getArray();
        return $a[\WPO\ND\Loader::PAGES];
    }
    
    /**
     * 
     * @param unknown_type $page
     * @param unknown_type $data
     */
    public function addPage(array $params)
    {
        $this->_dataArray[\WPO\ND\Loader::PAGES][$params[0]] = $params[1];
    }
    
    /**
     * 
     * @param unknown_type $page
     * @param unknown_type $section
     * @param unknown_type $data
     */
    public function addSection(array $params)
    {
                //make sure all keys are set
        if (!isset($this->_dataArray[\WPO\ND\Loader::SECTIONS][$params[0]])) {
            $this->_dataArray[\WPO\ND\Loader::SECTIONS][$params[0]] = array();
        }
        $this->_dataArray[\WPO\ND\Loader::SECTIONS][$params[0]][$params[1]] = $params[2];
    }
    
    /**
     * 
     * @param array $params
     */
    public function addOption(array $params)
    {
        //make sure all keys are set
        if (!isset($this->_dataArray[\WPO\ND\Loader::OPTIONS][$params[0]])) {
            $this->_dataArray[\WPO\ND\Loader::OPTIONS][$params[0]] = array();
        }
        if (!isset($this->_dataArray[\WPO\ND\Loader::OPTIONS][$params[0]][$params[1]])) {
            $this->_dataArray[\WPO\ND\Loader::OPTIONS][$params[0]][$params[1]] = array();
        }
        $this->_dataArray[\WPO\ND\Loader::OPTIONS][$params[0]][$params[1]][$params[2]] = $params[3];
    }
    
    /**
     * You need to set a plugin before you call writeFile()
     * otherwise it wont be able to create a loader
     * 
     * @param \WPO\Plugin $plugin
     */
    public function setPlugin(\WPO\Plugin $plugin)
    {
        $this->_plugin = $plugin;
    }
    
    /**
     * 
     * @throws \Exception
     * @return \WPO\Plugin
     */
    public function getPlugin()
    {
        if (null === $this->_plugin) {
            throw new \Exception('You must pass a plugin to the writer so he can write files in the right place. Plus the loader needs it.');
        }
        return $this->_plugin;
    }
    
    /**
     * Use the registered normalizer (if any) to perfect the array
     */
    private function _normalize()
    {
        if (null ==! $this->_normalizer) {//allow normalization
            $this->_dataArray = $this->_normalizer->normalize($this->_dataArray);
            $this->_isNormalized = true;
        }
    }
    
    /**
     * Has _normalize() been called for the current dataArray?
     * reset to false when new data is passed to writeFile($data)
     * 
     * @return boolean
     */
    public function isNormalized()
    {
        return $this->_isNormalized;
    }
    
    /**
     * Save the data array to a file this will avoid loading maps and
     * all the scattered option files every time.
     * 
     * @param array $data
     */
    public function writeFile(array $data = array())
    {
        if (!empty($data)) {
            $this->_dataArray = $data;
            $this->_isNormalized = false;
        } else if (empty($this->_dataArray)) {
            throw new \Exception('Some data has to be passed for writing. use set(data, page[, section[, option]]) or pass a not empty data argument.');
        }
        $this->_normalize();
        
        $fileContents = "<?php\nreturn " . var_export($this->_dataArray, true) . ';';
        if (false === file_put_contents($this->getLoader()->getArrayFilePath(), $fileContents)) {
            throw new \Exception('Could not write the normalized data array to file with path: ' . $this->getLoader()->getArrayFilePath());
        }
        $this->_isWritten = true;
    }
}