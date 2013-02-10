<?php
namespace WPO\Option\ND\Normalizer;

require_once WPO_DIR . '/Option.php';
require_once WPO_DIR . '/Option/ND/Normalizer/AbstractNormalizer.php';

use WPO\Option                         as Option;

/**
 * This is used to further normalize the array and save it
 * to a file. It gets the foreign references data and sets it
 * as local option data.
 * 
 * @uses WPO\Option\NormalizedData\Defaults
 * @author g
 *
 */
class Values
extends \WPO\Option\ND\Normalizer\AbstractNormalizer
{

    /**
     * This will make sure there is a value, or get it from the 
     * defaults. It will also match the data array key with the
     * value.
     * 
     * @param string $page
     * @param string $section
     * @param string $option
     * @param array $info
     * @throws Exception
     * @return multitype:
     */
    protected function _normalize($page, $section, $option, array $info)
    {
        /*
         * Get the defaults only for the current option
         */
        $defaults = $this->_dataArrayToNormalize;
        $defaults = $defaults[$page][$section][$option];
        
        //if no value is passed use defaults
        if (!isset($info[Option::VALUE])) {
            $info[Option::VALUE] = $defaults[Option::DEFAULT_VALUE];
        }
        
        //add to info the k=>v (from defaults) that are missing 
        $info = array_merge($defaults, $info);
        //unset som default keys we dont need
        unset($info[Option::DEFAULT_VALUE]);
        //only keep data (if there is) that is related to the current value
        if (isset($info[Option::DATA])) {
            if (!isset($info[Option::DATA][$info[Option::VALUE]])) {
                throw new Exception('The value does not exist in in options data array');
            }
            //get only the key related to info value, and replace data with that array
            $info[Option::DATA] = $info[Option::DATA][$info[Option::VALUE]];
        }
        return $info;//sanitized
    }
}