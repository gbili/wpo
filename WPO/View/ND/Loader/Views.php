<?php
namespace WPO\View\ND\Loader;

require_once WPO_DIR . '/ND/Loader.php';

class Views
extends \WPO\ND\Loader
{
    /**
     * Option, section or page (there is one view file per...)
     * @see WPO\View\Map\AbstractMap
     * @var string
     */
    private $_taxonomyLevel = null;
    
    /**
     * Use the array key as taxonomy level, and Keep a one level array
     * (non-PHPdoc)
     * @see WPO.NormalizedData::_afterLoad()
     */
    protected function _afterLoad()
    {
        //data array is loaded now
        //set the taxonomy level
        $this->_taxonomyLevel = key($this->_dataArray);
        //and trim the data array one level down (remove taxonomy level key)
        $this->_dataArray = current($this->_dataArray);
        //this wont afect the array file, because it won't save if
        //parent::wirteFile() is not called
    }
    
    /**
     * Data array must have been loaded for this to have something
     * @return string
     */
    public function getHighestTaxonomyLevel()
    {
        return $this->_taxonomyLevel;
    }
}