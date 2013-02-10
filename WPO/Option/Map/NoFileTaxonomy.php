<?php
namespace WPO\Option\Map;

require_once WPO_DIR . '/Option/Map/AbstractMap.php';

/**
 * Options files:
 *    OptionTaxonomy  AdminPages/PageFolder/SectionFolder/optionName.php array(info...)
 *    SectionTaxonomy AdminPages/PageFolder/SectionFolder/options.php    array(options...)
 *    PageTaxonomy    AdminPages/PageFolder/options.php                  array(section=>array(options...), section=>array(options....))
 *    NoFileTaxonomy  AdminPages/options.php                             array(page=>array(section=>array(options...), section=>array(options....)), page=>array(section=>array(options...), section=>array(options....)))
 * 
 * @author g
 *
 */
class NoFileTaxonomy
extends AbstractMap
{
    /**
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_isSupportedPath()
     */
    protected function _isSupportedPath($pathParts, $count)
    {
        //all taxonomies are set inside the file
        return (1 === $count && $pathParts[0] === $this->getOptionsFileName());
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_isSupportedIfNoMoreFiles()
     */
    protected function _isSupportedIfNoMoreFiles()
    {
        return true;
    }
    
    /**
     * 
     * @param unknown_type $page
     * @param unknown_type $section
     * @param unknown_type $option
     */
    public function getPath($page, $section, $option)
    {
        return "/" . $this->getOptionsFileName();
    }
}