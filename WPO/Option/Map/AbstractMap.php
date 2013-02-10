<?php
namespace WPO\Option\Map;

abstract class AbstractMap 
extends \WPO\Map\AbstractMap //already required
{
    /**
     * 
     */
    public function __construct()
    {
        //we want all data to be loaded
        parent::setLoadOnTest(true);
        parent::__construct();
        //all data in options is defined at an options taxonomy level
        $this->setDataTaxonomyLevel(parent::TAXONOMY_OPTION);
    }
    
    /**
     * Include the array
     * 
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_loadData()
     */
    final protected function _loadData($path)
    {
        return include $path;
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_interceptDataOnNoTaxonomyExplicitPath()
     */
    protected function _interceptDataOnNoFileTaxonomy($data)
    {
        require_once WPO_DIR . '/Option.php';
        require_once WPO_DIR . '/Plugin.php';
        //@todo make sure that the data array is fully wrapped with all the taxonomies
        //if no file taxonomy, check if there are no page or|and sections specified in data array
        //and use defaults and wrapt the data, because now it is too late, calls to setTaxonomy
        //wont affect the data wrapping.
        $dataTaxonomy = $this->_recursiveTaxonomyGuess($data, array(parent::TAXONOMY_OPTION, parent::TAXONOMY_SECTION, parent::TAXONOMY_PAGE));
        switch ($dataTaxonomy) {
            //missing page and section, use default
            case parent::TAXONOMY_OPTION;
                $wrappedData = array(\WPO\Plugin::DEFAULT_PAGE_NAME=>array(\WPO\Plugin::DEFAULT_SECTION_NAME=>$data));//only contains options
                break;
            case parent::TAXONOMY_SECTION;//already contains sections
                $wrappedData = array(\WPO\Plugin::DEFAULT_PAGE_NAME=>$data);
                break;
            default; //parent::TAXONOMY_PAGE;
                $wrappedData = $data;//already contains pages and sections
                break;
        }
        return $wrappedData;
    }
    
    /**
     * Recursively search for the options info data array, and conclude the taxonomy
     * 
     * @param array $array
     * @param array $taxonomiesOrderedArray
     * @param unknown_type $taxonomyOAKey
     * @throws \Exception
     */
    private function _recursiveTaxonomyGuess(array $array, array $taxonomiesOrderedArray, $taxonomyOAKey = 0)
    {
        if ($taxonomyOAKey >= count($taxonomiesOrderedArray)) {
            throw new \Exception('Stack overflow, or whatever... the taxonomy key is out of taxonomiesOrderdArray range, the array is too deep');
        }
        foreach ($array as $k => $v) {
            if (!is_array($v)) {
                throw new \Exception('The data must be an array, or the array is not well formed');
            }
            //is there a key named data, is that data not pointing to an array? That would confirm that its not a section named data
            if ($this->_isOptionInfoArray($v)) {
                return $taxonomiesOrderedArray[$taxonomyOAKey];
            }
            return $this->_recursiveTaxonomyGuess($v, $taxonomiesOrderedArray, ++$taxonomyOAKey);
        }
    }
    
    /**
     * is there a key named data, is that data not pointing to an array? That would confirm that its not a section named data
     * @param array $array
     */
    private function _isOptionInfoArray(array $array)
    {
        if (isset($array[\WPO\Option::DATA]) && is_array($array[\WPO\Option::DATA])) {
            foreach ($array[\WPO\Option::DATA] as $optionDataValues) {
                //every option's data key, points to an array with a key named value must be named value
                if (is_array($optionDataValues) && isset($optionDataValues[\WPO\Option::DATA_VALUE]) && is_string($optionDataValues[\WPO\Option::DATA_VALUE])) {//confirmation that data value is an options data value and not a section or option
                   return true;
                } else {//the key with name data is considered a section's identifier or options identifier
                    break;//return false
                }
            }
        }
        return false;
    }
    
    /**
     * Allow the user not to specify the section name when in
     * page file taxonomy.
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_interceptDataOnPageFileTaxonomy()
     */
    protected function _interceptDataOnPageFileTaxonomy($data)
    {
        //lets guess how the data array is structured
        // if the iterated stuff were: section -> option -> info
        //then info would be an array
        //else if it's not an array then it is probably that we missinterpreted
        //and it is in fact : option -> info -> string
        //that is why we prepend the default section name
        foreach ($data as $section => $options) {
            foreach ($options as $option => $info) {
                //all option keys point to an array of info, so if its not an array, it is not an option
                if (!is_array($info)) {
                    //no section was specified
                    $data = array(\WPO\Plugin::DEFAULT_SECTION_NAME => $data);
                    break 2;
                }
            }
        }
        return $data;
    }
    
    //@todo allow the user to do many things when in many taxonomy situations... intercept for each taxonomy
    
    /**
     * Help pattern subclasses build the file path
     */
    public function getOptionsFileName()
    {
        return \WPO\Plugin::OPTIONS_FILE_NAME;
    }
}