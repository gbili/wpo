<?php
namespace WPO\Map\Test;

require_once WPO_DIR . '/Map/AbstractMap.php';

/**
 * Use a files iterator to loop through all the files that
 * match the regex (specified in AbstractTest subclass).
 * For each of the files that match, the iterator will try to
 * make some sense out of the path structure. It does this by 
 * iterating through all maps until one recognizes the structure.
 * Note that once a map matches, all resources are tested
 * against the same map. If one resource does not match,
 * an exception is thrown: all resources should comply to the
 * same organisation structure.
 * 
 * @todo rollback when subsequent files path structure dont match
 * the map and there are more maps that have not been tested. 
 * Maybe two or more maps match some path, but the discrepancy
 * is made later, with another file's path. That's why we should
 * try the other maps that have not been tested yet.
 * 
 * On the other hand, if all files match the same map, the map
 * will be stored in AbstractMap::$_alreadyMatchedMaps.
 * This will allow the next resouce type to have further
 * organisation refinement. In the sense that maps of the
 * next resource type (of which we will try to find out the 
 * organisation) can set map compatibility blaclists;
 * The abstract map will use the contents to enforce maps compliancy 
 * over different resource types. The order or matching is not
 * important. You can blacklist from anywhere and even if the
 * maps you blacklisted from the other resource type have not
 * already been tested, AbstractMap keeps those lists in static
 * memory such that it is available for the next resource mapping.
 * 
 * @author g
 *
 */
abstract class AbstractTest
{   
    /**
     * Only make test for a type of resource once
     * Every test for which we find a map, is stored
     * here
     * 
     * @var unknown_type
     */
    static private $_testToMap = array();
    /**
     *
     * @var array
     */
    private $_matchingMapClassNamesToMapInstances = array();
    
    /**
     *
     * @var unknown_type
     */
    private $_currPartsCount = null;
    
    /**
     * Iterator's path
     * 
     * @var unknown_type
     */
    private $_testFilesInPath;
    
    /**
     * Set this from subclass to tell where your maps directory is
     * 
     * @var string
     */
    protected $_mapsPath = null;
    
    /**
     * Maps are checked by iterating through
     * map files, which are named after the map
     * base class name. We need to create the fully
     * qualified className with this maps namespace
     * 
     * @var string
     */
    protected $_mapsNs   = null;
    
    /**
     * 
     * @var WPO\Data\Path\Map\AbstractMap
     */
    private $_mapInstance = null;
    
    private $_testClassNameEndPart = null;
    
    /**
     * 
     * @param string $testFilesInPath path to files to be tested
     * @param string $mapsPath        path to maps that will be used during test
     * @param string $mapsNs          namespace of the maps
     */
    public function __construct($testFilesInPath, $mapsPath, $mapsNs)
    {
        $this->_testFilesInPath = $testFilesInPath;
        //init testToMap array for this path
        if (!isset(self::$_testToMap[$this->_testFilesInPath])) {
            self::$_testToMap[$this->_testFilesInPath] = array();
        }
        $this->_testClassNameEndPart = end(explode('\\', get_class($this)));
        $this->_mapsPath = $mapsPath;
        $this->_mapsNs   = $mapsNs;
    }
    
    /**
     * Creates an iterator to loop through files in path
     * given a regex spcified by subclass.
     * We test each file against available Maps wich will
     * be rejected if they do not support the file.
     * 
     * @throws Exception
     * @return boolean found or not
     */
    public function findMap()
    {
        /*
         * only try to find a map in some directory tree for a resource type once per script exec
         */
        $alreadyTested = isset(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart]);
        if ($alreadyTested) {
            $oldMapClassNameEndPart = end(explode('\\', get_class(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart])));
            trigger_error("This $this->_testClassNameEndPart's test has already been done in {$this->_testFilesInPath}. It has been found out that the supported map is : $oldMapClassNameEndPart. Testing again.. but why?", E_USER_NOTICE);
        }

        /*
         * Iterate through all files and try to map the structure to a map 
         */
        require_once WPO_DIR . '/Map/Test/UserFilesIterator.php';
        $iterator = new \WPO\Map\Test\UserFilesIterator($this->_testFilesInPath,
                                                        $this->getRegex());
        
        //iterate through all files, and find a map that matches all files
        foreach ($iterator as $file) {
            if (!$this->fileMatchesSomeMap($file)) {
                require_once WPO_DIR . '/Map/Test/Exception.php';
                throw new Exception(
                    "Your file structure does not fit any map. The file not matching the rest is: " . $file->getPathname()
                );
            }
            //let subclasses do something with the files
            $this->_interceptFile($iterator, $file);
        }
        
        /*
         * Only continue if there is some map that has matched.
         * This test is needed cause dont want _announce... to
         * throw exception, we want to return bool on not found
         */
        if (!$this->hasMatchingMap()) {
            return false;
        }
        
        /*
         * Now the map doesn't know that there are no more files,
         * announce that, and see if it still wants to support the file
         * structure given that fact. (Some maps need a certain
         * amount of files, so they could match all the files in
         * a structure, but not support the structure in the end, 
         * because theyv were written for a structure with more files...)
         * 
         * Think of it as sets.. :/ we have checked if the structure
         * is a subset of the map's structure, and now we are checking
         * for equality
         */
        $this->_announceNoMoreFilesAndDropNotSupported();
        
        /*
         * The final check was made, did some map survive?
         */
        if (!$this->hasMatchingMap()) {
            return false;
        }
        
        /*
         * If some map survived, store it statically
         */
        self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart] = $this->getMatchingMap();
        
        //in case it was already tested, did this test differ from the last?
        if ($alreadyTested) {
            $newMapClassNameEndPart = end(explode('\\', get_class(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart])));
            $difference = ($oldMapClassNameEndPart !== $newMapClassNameEndPart)? "different : $newMapClassNameEndPart"  : 'the same';
            trigger_error("The map is $difference!", E_USER_NOTICE);
        }
        return true;
    }
    
    /**
     * Have we already done a test of this type to find a map
     * inside the current directory 
     */
    public function isAlreadyTested()
    {
        $this->_testClassNameEndPart = end(explode('\\', get_class($this)));
        return isset(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart]);
    }
    
    /**
     * 
     * @throws Exception
     */
    public function throwIfNoMaps()
    {
        if (!$this->hasMatchingMaps()) {
            require_once WPO_DIR . '/Map/Test/Exception.php';
            throw new Exception(
                    'Your resources are either not supported by'
                    . ' any Test (the file names are not compliant),'
                    . ' or no Map recognizes your files organisation.'
                    . ' In the latter case you may as well be using diffrent'
                    . " organisation types, and it's not permitted either."
            );
        }
    }
    
    /**
     * let maps attempt to not support the file structure
     * once all files have been checked and there will be no more
     * 
     * there may be many maps matching each file individually, but
     * some maps may need to check if they got all the meta data
     * they need to successfully match the user directory structure.
     * This lets them know that if they do not have everything they
     * need by now, they should tell us they are not supporting the
     * structure, so we can drop them off.
     * 
     * @throws Exception
     */
    private function _announceNoMoreFilesAndDropNotSupported()
    {
        $this->throwIfNoMaps();
        
        foreach ($this->_matchingMapClassNamesToMapInstances as $mapClassName => $mapInstance) {
            //trim down the number of matching maps
            if (!($mapInstance->isSupportedIfNoMoreFiles())) {
                unset($this->_matchingMapClassNamesToMapInstances[$mapClassName]);
            }
        }
    }
    
    /**
     * Unset those maps that dont match the current file
     * Remember that maps are all loaded into _matchingMapClassNamesToMapInstances
     * and then we drop those that dont match.
     * 
     * @return boolean does the current file match at least one map?
     */
    public function fileMatchesSomeMap(\SplFileInfo $file)
    {
        if (empty($this->_matchingMapClassNamesToMapInstances)) {
            $this->_loadMaps();
        }
        $path = $file->getPathname();
        
        foreach ($this->_matchingMapClassNamesToMapInstances as $mapClassName => $mapInstance) {
            //trim down the number of matching maps
            if (!($mapInstance->isSupported($path))) {
                unset($this->_matchingMapClassNamesToMapInstances[$mapClassName]);
            }
        }
        return $this->hasMatchingMaps();//if no map supported the file, the maps array would be empty, returning false
    }
    
    /**
     * First time the iterator loads maps
     * stores the instances inside $this->_matchingMapClassNamesToMapInstances
     * @throws Exception
     */
    private function _loadMaps()
    {
        require_once WPO_DIR . '/Map/Test/MapFilesIterator.php';
        $dir = new \WPO\Map\Test\MapFilesIterator($this->_mapsPath);
        foreach ($dir as $mapClassFile) {
            //Create an instance
            $mapClassName  = $this->_mapsNs . '\\' . $mapClassFile->getBasename('.php');
            require_once $mapClassFile->getPathname();
            $map = new $mapClassName();
            //make sure its a map
            if (!($map instanceof \WPO\Map\AbstractMap)){ //already required by AbstractTest which instantiates iterator
                require_once WPO_DIR . '/Exception.php';
                throw new Exception(
                    'The map with name ' . get_class($map)
                    . ' is actually not a map at all, it must implement'
                    . ' Map\\MapInterface'
                );
            }
            $this->_matchingMapClassNamesToMapInstances[$mapClassName] = $map;
        }
        if (empty($this->_matchingMapClassNamesToMapInstances)) {
            require_once WPO_DIR . '/Exception.php';
            throw new Exception(
                    'There are no maps available for testing'
            );
        }
    }
    
    /**
     * Is there any map supporting all the paths that
     * have currently been tested?
     * 
     * added static check
     * 
     * @return boolean
     */
    public function hasMatchingMaps()
    {
        return !empty($this->_matchingMapClassNamesToMapInstances) || isset(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart]);
    }
    
    /**
     *
     * @return boolean
     */
    public function hasManyMatchingMaps()
    {
        return 1 < count($this->_matchingMapClassNamesToMapInstances);
    }
    
    /**
     * Have we matched a SINGLE map
     *
     * @return boolean
     */
    public function hasMatchingMap()
    {
        return 1 === count($this->_matchingMapClassNamesToMapInstances) || isset(self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart]);
    }
    
    /**
     * Once a map that matches the resources organisation
     * has been found, this will return its name.
     *
     * @return string
     */
    public function getMatchingMapName()
    {
        return get_class($this->getMatchingMap());
    }
    
    /**
     *
     * @return string
     */
    public function getMatchingMapNameEndPart()
    {
        return end(explode('\\', $this->getMatchingMapName()));
    }
    
    /**
     * (non-PHPdoc)
     * @see Iterator::getMatchingMap()
     */
    public function getMatchingMap()
    {
        //make sure we have a map to return
        $this->throwIfNoMaps();
        reset($this->_matchingMapClassNamesToMapInstances);
        return current($this->_matchingMapClassNamesToMapInstances);
    }
    
    /**
     * 
     * @throws Exception
     * @return multitype:
     */
    public function getMatchingMaps()
    {
        //make sure we have a map to return
        $this->throwIfNoMaps();
        if ($this->hasManyMatchingMaps()) {
            trigger_error("More than one map has matched your structure, using one randomly...", E_USER_NOTICE);
        }
        return $this->_matchingMapClassNamesToMapInstances;
    }
    
    /**
     * If no test was made already, do it and return the map
     * If it was tested, just make sure some map was found and return it
     * 
     * @todo :/ before edition retur $this->getMatchingMap() but could not understand the workings of it so
     * now it returns from static
     * 
     */
    public function getMap()
    {
        if (!$this->isAlreadyTested()) {
            $this->findMap();
        }
        $this->throwIfNoMaps();
        return self::$_testToMap[$this->_testFilesInPath][$this->_testClassNameEndPart];//return $this->getMatchingMap();
    }
    
    /**
     * Implement this as you please every file
     * that matches the subclass regex is passed
     * to this function
     *
     * @param unknown_type $file
     */
    protected function _interceptFile($iterator, $file){}
    
    /**
     * You should return the regex for the RecursiveRegexIterator
     * ex: return '#^[^.]+\.phtml$#'; //only get *.phtml files
     */
    abstract public function getRegex();
}