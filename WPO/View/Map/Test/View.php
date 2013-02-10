<?php
namespace WPO\View\Map\Test;

require_once WPO_DIR . '/Map/Test/AbstractTest.php';

/**
 * This is a mapper that will try to find the organisation structure
 * for the .phtml files (\WPO\Plugin::VIEW_SUFFIX).
 * 
 * @author g
 *
 */
class View 
extends \WPO\Map\Test\AbstractTest
{
    /**
     * Set the maps path for views test
     */
    public function __construct($testFilesInPath)
    {
        parent::__construct($testFilesInPath, realpath(__DIR__ . '/..'), 'WPO\\View\\Map');
    }
    
    /**
     * 
     */
    public function getRegex()
    {
        //set regex to match anithing ending in .suffix
        return '#^[^.]+\.' . \WPO\Plugin::VIEW_SUFFIX . '$#';
    }
}