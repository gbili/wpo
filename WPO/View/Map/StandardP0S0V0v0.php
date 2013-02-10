<?php
namespace WPO\View\Map;

require_once WPO_DIR . '/View/Map/AbstractMap.php';

/**
 * (O|S|P)NamedView(1|0) : Option|Section|Page Named view 1 or 0 (yes or no)
 *    (O|S)SameName(1|0) : Option|Section with Same name across lower taxonomy levels (Pages can never have the same name, because there is no lower taxonomy level)
 * (S|P)MoreThanOne(1|0) : Section|Page more than one allowed (options can always be countless)
 *
 * FullFlat P0S0V0  -   |  -      |  -       | PageLevel
 *           | | ^ AdminPages/option__pageName__sectionName__optionName.phtml
 *           | ^   AdminPages/section__pageName__sectionName.phtml
 *           ^     AdminPages/page__pageName.phtml
 *        
 * Compatibility enforcement is made through 
 * 
 * @author g
 *
 */
class StandardP0S0V0v0
extends AbstractMap
{
    const TAXONOMY_SEP = '__';
    
    const VIEW_FILENAME_TAXONOMY_PAGE = 'page';
    const VIEW_FILENAME_TAXONOMY_SECTION = 'section';
    const VIEW_FILENAME_TAXONOMY_OPTION = 'option';
    
    /**
     * (non-PHPdoc)
     * @see PatternInterface::isMyType()
     */
    protected function _isSupportedPath($pathParts, $count)
    {
        //"\nSupporting x=1 : $count && file contains taxonomy sep , Dump : ";
        if (1 !== $count || false === strpos($pathParts[0], self::VIEW_FILENAME_TAXONOMY_SEP)) {
            return false;
        }
        
        $isSupported = false;
        
        $fileNoSuffix = current(explode(self::dotSuffix(), $pathParts[0]));
        $fileParts = explode(self::VIEW_FILENAME_TAXONOMY_SEP, $fileNoSuffix);
        
        $fPCount = count($fileParts);
        if (2 > $fPCount || $fPCount > 4) {
            return false;
        }
        
        switch ($fPCount) {
            case 2;
                list($taxonomy, $page) = $fileParts;
                $isSupported = ($taxonomy === self::VIEW_FILENAME_TAXONOMY_PAGE);
                $this->setFileTaxonomy($page);
                break;
            case 3;
                list($taxonomy, $page, $section) = $fileParts;
                $isSupported = ($taxonomy === self::VIEW_FILENAME_TAXONOMY_SECTION);
                $this->setFileTaxonomy($page, $section);
                break;
            case 4;
                list($taxonomy, $page, $section, $option) = $fileParts;
                $isSupported = ($taxonomy === self::VIEW_FILENAME_TAXONOMY_OPTION);
                $this->setFileTaxonomy($page, $section, $option);
                break;
            default;
                throw new \Exception('You have edited some code in _isSupportedPath() and you didn\'t do it right');
                break;
        }
        return $isSupported;
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\Map.AbstractMap::_isSupportedIfNoMoreFiles()
     */
    protected function _isSupportedIfNoMoreFiles()
    {
        return !$this->isMissingAnyFileTaxonomy();
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getPagePath()
     */
    public function getPagePath($page)
    {
        return "/" . self::VIEW_FILENAME_TAXONOMY_PAGE .  self::TAXONOMY_SEP . $page . parent::dotSuffix();
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getSectionPath()
     */
    public function getSectionPath($page, $section)
    {
        return "/" . self::VIEW_FILENAME_TAXONOMY_SECTION .  self::TAXONOMY_SEP . $page . self::TAXONOMY_SEP . $section . parent::dotSuffix();
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getOptionPath()
     */
    public function getOptionPath($page, $section, $option)
    {
        return "/" . self::VIEW_FILENAME_TAXONOMY_OPTION .  self::TAXONOMY_SEP . $page . self::TAXONOMY_SEP . $section . self::TAXONOMY_SEP . $option . parent::dotSuffix();
    }
}