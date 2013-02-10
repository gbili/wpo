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
class OptionTaxonomy
extends AbstractMap
{
    /**
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_isSupportedPath()
     */
    protected function _isSupportedPath($pathParts, $count)
    {
        //the file contains the option's data
        if (!(3 === $count && $pathParts[2] !== $this->getOptionsFileName())) {
            return false;
        }
        $this->setFileTaxonomy($pathParts[0], $pathParts[1], $pathParts[2]);
        return true;
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
        return "/$page/$section/" . $this->getOptionsFileName();
    }
}