<?php
namespace WPO\View\Map;

require_once WPO_DIR . '/View/Map/AbstractMap.php';

/**
 * (O|S|P)NamedView(1|0) : Option|Section|Page Named view 1 or 0 (yes or no)
 *    (O|S)SameName(1|0) : Option|Section with Same name across lower taxonomy levels (Pages can never have the same name, because there is no lower taxonomy level)
 * (S|P)MoreThanOne(1|0) : Section|Page more than one allowed (options can always be countless)
 * 
 *  FullStd P1S1V1  -   |  -      |  -       | -
 *           | | ^ /PageFolder/SectionFolder/view/optionName.phtml
 *           | ^   /PageFolder/SectionFolder/view.phtml
 *           ^     /PageFolder/view.phtml
 *
 * @author g
 *
 */
class StandardP1S1V1v1 //page folder, section folder, view folder, view.phtml file
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
        //Supporting 2 <= count <= 4
        if (2 > $count || $count > 4) {
            return false;
        }
        $isSupported = false;
        switch ($count) {
            case 2;
                list($page, $viewFile) = $pathParts;
                $isSupported = (self::VIEW_FILENAME === $viewFile);
                $this->setFileTaxonomy($page);
                $this->setDataTaxonomyLevel(\WPO\Map\AbstractMap::TAXONOMY_PAGE);
                break;
            case 3;
                list($page, $section, $viewFile) = $pathParts;
                $isSupported = (self::VIEW_FILENAME === $viewFile);
                $this->setFileTaxonomy($page, $section);
                $this->setDataTaxonomyLevel(\WPO\Map\AbstractMap::TAXONOMY_SECTION);
                break;
            case 4;
                list($page, $section, $viewFolder, $optionFileName) = $pathParts;
                $isSupported = (self::VIEW_FOLDERNAME === $viewFolder);
                $option = current(explode(parent::dotSuffix(), $optionFileName));
                $this->setFileTaxonomy($page, $section, $option);
                $this->setDataTaxonomyLevel(\WPO\Map\AbstractMap::TAXONOMY_OPTION);
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
        return "/$page/$section/" . self::VIEW_FILENAME;
    }

    /**
     * (non-PHPdoc)
     * @see WPO\View\Map.AbstractMap::getOptionPath()
     */
    public function getOptionPath($page, $section, $option)
    {
        return "/$page/$section/view/$option" . parent::dotSuffix();
    }
}