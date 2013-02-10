<?php
namespace WPO\Map\Test;

require_once WPO_DIR . '/ND/Loader.php';

/**
 * @todo restructure the whole thing, we should iterate through maps
 * and then try to match all user files with the current map. (Currently
 * we are iterating through all files, and then on each file we try all
 * maps until one matches) 
 * If it matches all files, then it should be kept, and set as alreadyMatched maps. However
 * if one user file does not match, try to match the files with another and repeat
 * the process until one that matches all files is found.
 * 
 * For every file keep the maps that match as keys and the files paths as
 * an array of values. Aside of it, keep a track of all the files paths.
 * At the end of the mapping, loop through all map keys and see if one
 * has matched the same number of files
 * 
 * @author g
 *
 */
class UserFilesIterator 
extends \RegexIterator
{   
    /**
     *
     * @throws Exception
     */
    public function __construct($filesPath, $regex)
    {        
        $directory = new \RecursiveDirectoryIterator( $filesPath );
        $rii = new \RecursiveIteratorIterator($directory);
        parent::__construct( $rii, $regex );
    }

    /**
     *
     * (non-PHPdoc)
     * @see RecursiveRegexIterator::accept()
     */
    public function accept()
    {
        return parent::accept() 
               && $this->isFile()
               && !$this->isDot()
               && false === strpos($this->getBasename(), \WPO\ND\Loader::FILE_PREFIX);
    }
}