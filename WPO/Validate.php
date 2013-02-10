<?php
namespace WPO;

class Validate
{
    /**
     * 
     * @var WPO\Plugin
     */
    private $_plugin;
    
    private $_pageDefaultOptions;
    
    private $_dirtyOptions;
    
    private $_cleanOptions;
    
    /**
     * 
     * @param WPO\Plugin $plugin
     * @param unknown_type $page
     * @param array $input is a two level array[section][option]
     */
    public function __construct(\WPO\Plugin $plugin, $page, array $input)
    {
        $this->_plugin       = $plugin;
        $this->_page         = $page;
        $this->_normalizer   = new \WPO\Option\NormalizedData\Values\DataNormalizer();
        $this->_validate($input);//sets the output in $this->_cleanOptions
    }
    
    /**
     * 
     * @throws Exception
     */
    private function _validate($dirtyOptions)
    {
        foreach ($dirtyOptions as $section => $options) {
            foreach ($options as $option => $value) {
                /*
                 * Switch over each option's validator type
                 */
                if (!isset($this->_pageDefaultOptions[$section][$option])) {
                    throw new Exception('The passed option does not exist');
                }
                //each option's validator is set in the default options array
                switch ($this->_pageDefaultOptions[$section][$option][Option::VALIDATOR]) {
                    case Option::VALIDATOR_DATA;
                        if (isset($this->_pageDefaultOptions[$section][$option][Option::DATA][$value])) {
                            //create a simple Option::VALUE => $value, array that will be normalized, so it can be later saved
                            $this->_cleanOptions[$this->_page][$section][$option] = $this->_normalizer->normalize($this->_page, $section, $option, array(Option::VALUE => $value));
                        }
                    default;
                        throw new Exception('For the moment no other validator have been implemented');
                        //@todo we should create a validator interface, and try to validate according to it
                        break;
                }
            }
        }
    }
    
    /**
     * 
     */
    public function getValidatedOptions()
    {
        return $this->_cleanOptions;
    }
}