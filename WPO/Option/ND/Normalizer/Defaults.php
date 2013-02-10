<?php
namespace WPO\Option\ND\Normalizer;

require_once WPO_DIR . '/Option.php';
require_once WPO_DIR . '/Option/ND/Normalizer/AbstractNormalizer.php';

use WPO\Option                          as Option;

/**
 * This is used to further normalize the array and save it
 * to a file. It gets the foreign references data and sets it
 * as local option data.
 * 
 * @uses WPO\Option\NormalizedData\Defaults
 * @author g
 *
 */
class Defaults
extends \WPO\Option\ND\Normalizer\AbstractNormalizer
{

    /**
     * Pages and sections are specified either as keys or
     * as directory names. Therefor, their names are set
     * as slugs. This even more important for the page, name
     * that is used as a GET requrest parameter.
     *
     * However, there is a need to have the pages and sections
     * names represented in normal space separateed phrases.
     * Thats why we provide some regex to try to split the
     * slug into words. Then we will glue it back with spaces
     *
     * @var string
     */
    const SLUG_PATTERN_CAMELCASED          = '/(?<=[a-z])(?=[A-Z])/x';
    const SLUG_PATTERN_DELIMITERSEPARATED = '/[_-]/';
    
    /**
     * 
     * @var WPO\Option\NormalizedData\Defaults
     */
    private $_defaultsData = null;
    
    /**
     * 
     * @param unknown_type $slug
     */
    public function slugToTitle($slug)
    {
        //could test, to avoid double replacement, but dont know if its faster
        $str = preg_replace(self::SLUG_PATTERN_DELIMITERSEPARATED, ' ', $slug);
        $str = preg_replace(self::SLUG_PATTERN_CAMELCASED, ' ', $str);
        return ucfirst($str);
    }

    /**
     * This will make sure all keys are present in the options
     * info array. If there is any foreign reference, it will
     * get that reference data.
     *
     * @param string $array
     */
    protected function _normalize($page, $section, $identifier, array $info)
    {
       /*
        * Set params into the info array
        */
        $info[Option::PAGE]          = $page;
        $info[Option::SECTION]       = $section;
        $info[Option::OPTION]        = $identifier;
        
        /*
         * Convert slugs to human friendly titles if not allready done
         */
        if (!isset($info[Option::PAGE_TITLE])) {
            $info[Option::PAGE_TITLE]    = $this->slugToTitle($page);
        }
        
        if (!isset($info[Option::SECTION_TITLE])) {
            $info[Option::SECTION_TITLE] = $this->slugToTitle($section);
        }

        if (!isset($info[Option::TITLE])) {
            $info[Option::TITLE]         = $this->slugToTitle($identifier);
        }
        
        //if no validator is set, validator is considered to be data
        if (!isset($info[Option::VALIDATOR])) {
            $info[Option::VALIDATOR] = Option::VALIDATOR_DATA;
        }
        
        /*
         * does the validator need data to validate? then, is Option::DATA set?
         */
        if (Option::VALIDATOR_DATA === $info[Option::VALIDATOR]) {
            
            /*
             * Do we have, or are we referencing any data for validation?
             */
            if (!isset($info[Option::DATA]) && !isset($info[Option::DATA_FOREIGN_REF])) {
                throw new \Exception(
                    'When the validator is "' . Option::VALIDATOR_DATA . '"'
                    . ' your option info array must have a key named: "'
                    . Option::DATA .'"'
                    . ' or one named: "' . Option::DATA_FOREIGN_REF . '"'
                );
            }
            /*
             * When foreign ref, get the data from the referenced option
             */
            if (isset($info[Option::DATA_FOREIGN_REF])) {
                list($otherOptId, $keyInRefOptData) = explode(Option::DFREF_SEPARATOR, $info[Option::DATA_FOREIGN_REF]);
                //@todo create a better foreign ref decoder for the moment it only supports same 
                //@todo page section option data. Simply use count: page:section:option:datakey = 4
                //@todo section:option:datakey = 3, option:datakey = 2...
                $refOptInfo = $this->_dataArrayToNormalize[$info[Option::PAGE]][$info[Option::SECTION]][$otherOptId];
                //quickly test the other option internal data
                //(not as exhaustive as _populate)
                if (!isset($refOptInfo[Option::DATA])
                 || !isset($refOptInfo[Option::DEFAULT_VALUE])
                 || !($dv = $refOptInfo[Option::DEFAULT_VALUE])//simple assignement
                 || !isset($refOptInfo[Option::DATA][$dv][$keyInRefOptData])) {
                    throw new \Exception(
                        'Some data is missing in the referenced option with: "'
                        . $info[Option::DATA_FOREIGN_REF] . '"'
                    );
                }
                //set the default value
                $info[Option::DEFAULT_VALUE] = $refOptInfo[Option::DATA][$dv][$keyInRefOptData];
                //create the data array
                $info[Option::DATA] = array();
                foreach ($refOptInfo[Option::DATA] as $k => $array) {
                    if (!isset($array[$keyInRefOptData])) {
                        throw new \Exception(
                            'The referenced option does not support the referenced key'
                            . ', it\'s missing. The key: "' . $keyInRefOptData . '"'
                            . ' is not in: ' . print_r($array, true)
                        );
                    }
                    $info[Option::DATA][$array[$keyInRefOptData]] = array('value' => $array[$keyInRefOptData]);
                }
                //remove the foreign ref
                unset($info[Option::DATA_FOREIGN_REF]);
                
            }
            
            /*
             * Now $info[Option::DATA] has something and will be used for validation
             */
            if (!isset($info[Option::DEFAULT_VALUE])) {
                throw new \Exception('No default value has been set, and not using foreign reference.');
            }
        } else {
            /*
             * Validation is made with some other validator, 
             * @todo we should test the validator, have we access to it? do we understand what to do with it?
             */
            throw new \Exception('For the moment validators other than Option::VALIDATOR_DATA are not supported');
        }
        return $info;//sanitized
    }
    
    /**
     * 
     * @return \WPO\Option\NormalizedData\Defaults
     */
    public function getNormalizedData()
    {
        // Once normalized write
        return $this->_defaultsData;
    }
        
}