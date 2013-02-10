<?php
namespace WPO\View\Map;

require_once WPO_DIR . '/View/Map/AbstractMap.php';

/**
 * (O|S|P)NamedView(1|0) : Option|Section|Page Named view 1 or 0 (yes or no)
 *    (O|S)SameName(1|0) : Option|Section with Same name across lower taxonomy levels (Pages can never have the same name, because there is no lower taxonomy level)
 * (S|P)MoreThanOne(1|0) : Section|Page more than one allowed (options can always be countless)
 * 
 *  ONv0Std P1S1V0 ONv0 |  -      |  -       | -
 *	         | | ^ AdminPages/PageFolder/SectionFolder/optionName.phtml
 * 	         | ^   AdminPages/PageFolder/SectionFolder/view.phtml
 * 	         ^     AdminPages/PageFolder/view.phtml
 *
 * @author g
 *
 */
class StandardP1S1V0v1
extends AbstractMap
{   
    const VIEW_FILENAME = 'view.phtml';
    const VIEW_FOLDERNAME = 'view';
    
    /**
     * (non-PHPdoc)
     * @see PatternInterface::isMyType()
     */
    protected function _isSupportedPath($pathParts, $count)
    {
        //"\nSupporting x=2, x=3 : $count, Dump : ";
        if (2 !== $count && $count !== 3) {
            return false;
        }
        $isSupported = false;
        switch ($count) {
            case 2;
                list($page, $viewFile) = $pathParts;
                $isSupported = (self::VIEW_FILENAME === $viewFile);
                $this->setFileTaxonomy($page);
                break;
            case 3;
                list($page, $section, $viewFile) = $pathParts;
                $isSupported = true;
                if (self::VIEW_FILENAME === $viewFile) {
                    $this->setFileTaxonomy($page, $section);
                } else {
                    $option = current(explode(parent::dotSuffix(), $viewFile));
                    $this->setFileTaxonomy($page, $section, $option);
                }
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
        return "/$page/" . self::VIEW_FILENAME;
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getSectionPath()
     */
    public function getSectionPath($page, $section)
    {
        return $path = "/$page/$section/" . self::VIEW_FILENAME;
    }
    
    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getOptionPath()
     */
    public function getOptionPath($page, $section, $option)
    {
        if ($option === 'view') {
            throw new \Exception('Options cannot be named view in with file structure.');
        }
        return "/$page/$section/$option" . parent::dotSuffix();
    }
}