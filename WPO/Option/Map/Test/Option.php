<?php
namespace WPO\Option\Map\Test;

require_once WPO_DIR . '/Map/Test/AbstractTest.php';

/**
 * This will enforce the abstract mapper to only test files
 * with name = \WPO\Plugin::OPTIONS_FILE_NAME against patterns.
 * Files are provided by FilesIterator the AbstractMapper
 * 
 * @author g
 *
 */
class Option
extends \WPO\Map\Test\AbstractTest
{
    
    /**
     * Set the maps path for options test
     */
    public function __construct($testFilesInPath)
    {
        parent::__construct($testFilesInPath, realpath(__DIR__ . '/..'), 'WPO\\Option\\Map');
    }
    
    /**
     * 
     */
    public function getRegex()
    {
        //set regex to match options.php files
        return '#' . preg_replace('#\.#', '\\.',\WPO\Plugin::OPTIONS_FILE_NAME) . '$#';
    }
}