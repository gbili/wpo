<?php
namespace WPO\View\ND\Normalizer;

require_once WPO_DIR . '/ND/Normalizer/NormalizerInterface.php';
require_once WPO_DIR . '/Map/AbstractMap.php';

/**
 * This is used to further normalize the array and save it
 * to a file. It gets the foreign references data and sets it
 * as local option data.
 * 
 * @uses WPO\View\NormalizedData\Views
 * @author g
 *
 */
class Views
implements \WPO\ND\Normalizer\NormalizerInterface
{

    /**
     * Views may be defined at an option level,
     * meaning that there is a view file per option
     */
    
    /**
     * 
     * @var string
     */
    private $_taxonomyLevel = null;
    
    /**
     * 
     * @param array $data
     */
    public function __construct($taxonomyLevel=null)
    {
        if (null === $taxonomyLevel) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception(
                'You must pass the taxonomy level as a constructor (probably not passed when registered normalizer with writer)'
            );
        }
        /*
         * At which level are views defined? This is guessed from Suclasses class name
         */
        $t = array( \WPO\Map\AbstractMap::TAXONOMY_OPTION,
                    \WPO\Map\AbstractMap::TAXONOMY_SECTION,
                    \WPO\Map\AbstractMap::TAXONOMY_PAGE);
        
        if (!in_array($taxonomyLevel, $t)) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception(
                'You must set a valid taxonomy level in your map constructor' . print_r($taxonomyLevel, true)
            );
        }
        $this->_taxonomyLevel = $taxonomyLevel;
    }
    
    /**
     * Prepend the taxonomy level as key to data
     * @param unknown_type $data
     */
    public function normalize($data)
    {
        $na = array();
        $na[$this->_taxonomyLevel] = $data;
        return $na;
    }  
}