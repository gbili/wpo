<?php
namespace WPO\Map\Test;

/**
 * This is a directory iterator that will skip
 * all files that do not look like a valid map file.
 * It avoids having conditions inside foreach loops
 * (Cleaner code)
 * 
 * @author g
 *
 */
class MapFilesIterator
extends \RegexIterator
{   
    /**
     *
     * @throws Exception
     */
    public function __construct($mapFilesPath)
    {        
        $directory = new \DirectoryIterator( $mapFilesPath );
        parent::__construct( $directory, '#\.php#');
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
               && false === strpos($this->getBasename(), 'Abstract');
    }
}