<?php
/**
 * 
 * @author g
 *
 */
namespace WPO\Map;

/**
 * Maps are meant to deal with paths, and file contents. What is expected
 * is that they guess the taxonomy of every piece of information that is
 * inside the files.
 * Taxonomies are defined in two ways that can be mixed.
 *  1. As nested dirnames and filenames
 *  2. inside the array that the file is sopposed to return.
 *  
 * For example taxonomies in views are defined strictly from the directories
 * and files names. This is because view files do not return any array of data.
 * On the other hand, options may have their taxonomies define in both ways. Some user may
 * want to create a unique opions.php file, in the root of its AdminPages. That
 * means that all the taxonomies are set as keys inisde the returned array. Well
 * there is in fact a hook that allows default taxonomies to be used when none is
 * specified, but more on that later. Or he could create an options file per page
 * or per section, or even per option. In that case the taxonomies should be popped 
 * out the array and be set as nested directories and filenames containing tha array.
 * 
 * @author g
 *
 */
abstract class AbstractMap
{
    /**
     * 
     * @var string
     */
    const TAXONOMY_PAGE = 'Page';
    const TAXONOMY_SECTION = 'Section';
    const TAXONOMY_OPTION = 'Option';
    
    /**
     * The page section and option that the path maps to
     * This should be set indirectly (with setPage($page) etc.) 
     * by sublasses, to tell the abstract class how to store 
     * the loaded data into the $_dataArray.
     * @see _askMapsToLoadUserDataFromPath()
     * They should set these in the _isSupportedPath() method, from
     * the pathParts
     * The abstract class also, guesses the taxonomy from
     * these members
     *
     * @var string
     */
    private $_page;
    private $_section;
    private $_option;
    
    /**
     * Used for taxonomy coherence check
     * 
     * @var array
     */
    private $_setPages = array();
    private $_setSections = array();
    private $_setOptions = array();
    
    private $_missingPages = array();
    private $_missingSections = array();

    /**
     * The highest taxonomy is an additional piece
     * of information that  can be used to take some
     * conditional actions
     * For example it can be used by views to determine
     * at which level the view files are coded:
     * do we have views for options? or the option's
     * views are grouped in a section view? or are 
     * section views in turn grouped in a page view?
     * Taxonomy is like Russian dolls
     * 
     * @var string
     */
    private $_highestTaxonomy = false;
    private $_fileTaxonomy = null;
    
    /**
     * @see self::isMissingHigherFileTaxonomy();
     * @var boolean
     */
    private $_isMissingHigherFileTaxonomy = null;
    
    /**
     * An array with double relations of maps that are not compatible
     * array(0=>
     * array(mapNotCompatibleWith => otherMap,
     *       otherMap             => mapNotCompatibleWith,
     *       etc.), 
     *       1=> array(map=>map2, map2=>map));
     * This is to counter the execution order. When a map blacklists
     * another map that has not been executed, the compatibility check
     * fails to highlight the incompatibility in the future.
     * Therefor we have this, that will allow the compatibility check
     * to highlight incompatibility, once the referenced map is executed.
     * 
     * BLACKLIST
     * 
     * @var array
     */
    static private $_incompatibilityArray = array();
    
    
    /**
     * WHITELIST
     * 
     * @var array
     */
    static private $_limitedCompatibilityArray = array();
    
    /**
     * All AbstractMap subclasses that matched a path, are saved
     * here, for compatibility tests
     *  
     * @var unknown_type
     */
    static private $_alreadyMatchedMapsClassNames = array();
    
    /**
     * Set a path part that you want to be stripped
     * out from the paths that are passed to the maps.
     * Ex : "/path/to/some/project"
     *  Given : "/path/to/some/project/Here/is/what/i/keep.php"
     *  We keep : "/Here/is/what/i/keep.php"
     *
     * This helps in making path agnostic maps
     * 
     * @var unknown_type
     */
    static private $_removePathFromPath = null;
    
    /**
     * Allow the abstract class, to directly load the
     * file contents wile testing the map.
     * 
     * @var unknown_type
     */
    private $_loadOnTest = true;
    
    /**
     * Dont load data twice
     * keep a copy of what is loaded
     *
     * @var unknown_type
     */
    static private $_pathToDataArray    = array();

    /**
     * Allow cross map (un)compatibility
     * @var array
     */
    protected $_compatibilityWhitelist = array();//@todo implement this
    
    protected $_compatibilityBlacklist = array();
    
    /**
     * Make this data available to avoid recalculating it
     * from subclasses, or accross function calls.
     * @var array
     */
    protected $_pathParts = array();
    protected $_pathPartsCount = null;
    
    private $_isSupported = null;
    
    /**
     * Only true when setFileTaxonomy() was successfully called by subclass
     * @var unknown_type
     */
    private $_isCalledSetTaxonomy = false;
    
    /**
     * 
     * @var WPO\ND\Writer;
     */
    private $_normalizedDataWriter = null;
    
    /**
     * 
     * @var const
     */
    private $_dataTaxonomyLevel = null;
    
    /**
     * @see loadOntTest
     */
    public function __construct($loadOnTest = true)
    {
        $this->_loadOnTest = (bool) $loadOnTest;
    }
    
    /**
     * Use this to remove the former path part of a path
     * 
     * @param unknown_type $path
     */
    static public function setSubPathToRemoveFromPath($formerPartOfPath)
    {
        self::$_removePathFromPath = $formerPartOfPath;
    }
    
    /**
     * Returns an array of the taxonomies with their order
     * the lowest index is the parent of the higher indexes
     * @return array
     */
    static public function getOrderedTaxonomies()
    {
        return array(0=>self::TAXONOMY_PAGE, 1=>self::TAXONOMY_SECTION, 2=>self::TAXONOMY_OPTION);
    }
    
    /**
     * Allow not to load the user filecontents on test
     * (dont confound with normalized data ND filecontents loading)
     * @param boolean $bool
     */
    public function setLoadOnTest($bool = true)
    {
        $this->_loadOnTest = (bool) $bool;
    }
    
    /**
     * This is made for map consistency checks, and
     * if specified, for data loading.
     * 
     * Will set the page section and or option
     * that will be used for storing the loaded data
     * in case it is loaded.
     * 
     * It will keep track of options missing sections
     * and sections missing pages
     * 
     * Moreover, it will keep track of the pages
     * missing sections, and the sections missing options
     * 
     * If you couple all of this with the highestTaxonomy,
     * you can create endless map structures, and ensure they
     * are consistent
     * 
     * @param unknown_type $page
     * @param unknown_type $section
     * @param unknown_type $option
     * @throws \WPO\Exception
     */
    public function setFileTaxonomy($page, $section=null, $option=null)
    {
        if (null === $page) {
            require_once WPO_DIR . '/Map/Test/Exception.php';
            throw new \WPO\Map\Test\Exception('You cannot pass null value as page for setFileTaxonomy($p,$s,$o)');
        }
        if (null === $section && null !== $option) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception('If the option is set, the section must be too');
        }
        //OPTION
        
        if (null !== $option) {
            //1. Keep trace of the highest taxonomy 
            if (self::TAXONOMY_OPTION !== $this->_highestTaxonomy) {
                $this->_highestTaxonomy = self::TAXONOMY_OPTION;
            }
            $this->_fileTaxonomy = self::TAXONOMY_OPTION;
            //2. set key for storing data on load
            $this->_option = $option;
            $this->_section = $section;
            $this->_page = $page;
            
            //3. make a lower taxonomy check (no missing sections, pages)
            if (!isset($this->_setSections[$page][$section])) {
                $this->_missingSections[$page][$section] = true;//true is just to put something
            }
            if (!isset($this->_setPages[$page])) {
                $this->_missingPages[$page] = true;
            }
            
            //4. help lower taxonomies to make a higher taxonomy check
            $this->_setOptions[$page][$section][$option] = true;
        //SECTION    
        } else if (null !== $section) {
                        //1. null, Page (or Section)
            if (self::TAXONOMY_OPTION !== $this->_highestTaxonomy) {
                $this->_highestTaxonomy = self::TAXONOMY_SECTION;
            }
            $this->_fileTaxonomy = self::TAXONOMY_SECTION;
            //2. set key for storing data on load
            $this->_section = $section;
            $this->_page = $page;
            
            //3. make a lower taxonomy check (no missing pages)
            if (!isset($this->_setPages[$page])) {
                $this->_missingPages[$page] = true;
            }
            
            //4. help higher|lower taxonomies make a lower|higher taxonomy check
            if (!isset($this->_setSections[$page][$section])) {
                if (isset($this->_missingSections[$page][$section])) {
                    unset($this->_missingSections[$page][$section]);
                }
                if (empty($this->_missingSections[$page])) {
                    unset($this->_missingSections[$page]);
                }
                $this->_setSections[$page][$section] = true;
            }
        //PAGE
        } else {
            //1. 
            if (null === $this->_highestTaxonomy) {
                $this->_highestTaxonomy = self::TAXONOMY_PAGE;
            }
            $this->_fileTaxonomy = self::TAXONOMY_PAGE;
            //2. set key for storing data on load
            $this->_page = $page;
            
            //3. no backware taxonomies...
            
            //4. help higher taxonomies make a lower taxonomy check
            if (!isset($this->_setPages[$page])) {
                if (isset($this->_missingPages[$page])) {
                    unset($this->_missingPages[$page]);
                }
                $this->_setPages[$page] = true;
            }
        }
        $this->_isCalledSetTaxonomy = true;
    }
    
    /**
     * At which taxonomy level is the data set
     * i.e. : does the data concern the page, the section
     * or the option?
     * @param unknown_type $taxonomy
     */
    public function setDataTaxonomyLevel($taxonomy)
    {
        if (! in_array($taxonomy, array(self::TAXONOMY_OPTION, self::TAXONOMY_SECTION, self::TAXONOMY_PAGE))) {
            throw new \Exception('The taxonomy is not valid');
        }
        $this->_dataTaxonomyLevel = $taxonomy;
    }
    
    /**
     * 
     * @return \WPO\Map\const
     */
    public function getDataTaxonomyLevel()
    {
        if (null === $this->_dataTaxonomyLevel) {
            if (!$this->hasHighestFileTaxonomy()) {
                throw new \Exception('Cannot guess data taxonomy level, because no taxonomies were set. You can either set a taxonomy, or set the taxonomy level directly');
            }
            //copied from highest taxonomy, but you can override this by setting it yourself
            $this->_dataTaxonomyLevel = $this->getFileTaxonomy();
        }
        return $this->_dataTaxonomyLevel;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function isMissingAnyFileTaxonomy()
    {
        return $this->isMissingHigherFileTaxonomy() || $this->isMissingLowerFileTaxonomy();
    }
    
    /**
     * Use this if you want to check whether each option
     * has a parent section, and each section has a parent
     * page
     * 
     * @return boolean
     */
    public function isMissingLowerFileTaxonomy()
    {
        return !empty($this->_missingSections) || $this->isMissingPages(); 
    }
    
    /**
     * Use this if you want to check whether each page has sections, && each
     * section has options
     * 
     * If you force views to be set on a per section basis or per view basis,
     * you can call this to make sure it was done the way you wanted
     * 
     * @throws \WPO\Exception
     */
    public function isMissingHigherFileTaxonomy()
    {
        if (null !== $this->_isMissingHigherFileTaxonomy) {
            return $this->_isMissingHigherFileTaxonomy;
        }
        
        $missing = false;
        foreach ($this->_setPages as $page => $useless) {
            if (!isset($this->_setSections[$page])) {
                $missing = true;
                break;
            }
            foreach ($this->_setSections[$page] as $section => $alsoUseless) {
                if (!isset($this->_setOptions[$page][$section])) {
                    $missing = true;
                    break 2;
                }
            }
        }
        return $this->_isMissingHigherFileTaxonomy = $missing;
    }
    
    
    /**
     * Options are always higher taxonomy
     * @return boolean
     */
    public function isMissingOptions()
    {
        /*
         * options are the highest taxonomy, 
         * so if a higher taxonomy is missing, 
         * options are missing
         */
        return $this->isMissingHigherFileTaxonomy();
    }
    
    /**
     * Section can be a lower taxonomy for options
     * and a higher taxonomy for pages
     * 
     * @return boolean
     */
    public function isMissingSections()
    {
        //make sure the missing higher taxonomy was computed
        $this->isMissingHigherFileTaxonomy();
        return !empty($this->_missingSections) || ($this->_highestTaxonomy === self::TAXONOMY_PAGE);
    }
    
    /**
     * Pages are allway lower taxonomies
     * no need to call $this->isMissingHigherFileTaxonomy()
     * to compute
     * 
     * @return boolean
     */
    public function isMissingPages()
    {
        return !empty($this->_missingPages);
    }
    
    /**
     * It answers the question: what is the highest
     * taxonomy that was set through setFileTaxonomy($page, $section, $option)?
     * Option is considered the highest and page the lowest.
     * Maps set the taxonomy from the path parts. Thus
     * this taxonomy is relative to the file. But not necessarily
     * to the data contained in the file. It is possible
     * that the taxonomy of the file is page, and the file
     * contents taxonomy is option.
     * 
     * 
     * @return string
     */
    public function getHighestFileTaxonomy()
    {
        if (!$this->hasHighestFileTaxonomy()) {
           throw new \Exception('Missing highest file taxonomy call hasHighestFileTaxonomy()');
        }
        return $this->_highestTaxonomy;
    }
    
    /**
     * @return boolean
     */
    public function hasHighestFileTaxonomy()
    {
        return false !== $this->_highestTaxonomy;
    }
    
    /**
     * The taxono
     */
    public function getFileTaxonomy()
    {
        return $this->_fileTaxonomy;
    }
    
    /**
     * Are there any map class names actually in use that
     * do not support (or are not supported by) this map type.
     * 

     * 
     * @return boolean
     */
    public function isCompatibleWithAlreadyMatchedMaps()
    {
        return $this->_isBlacklistCompatible() && $this->_isWhitelistCompatible();
    }
    
    /**
     * We keep an incompatibility array statically: it stores any
     * map's incompatibilities, even for those maps that don't match.
     * Every time a new map is checked, it may add more incompatibility
     * records. After it adds incompatibilities, we check the
     * incompatibilityArray, for any mentions of its class. If there
     * is a mention, we get the name of the class it is incompatible
     * with. Then, if the latter has matched (it has been added to
     * alreadyMatchedMaps) we throw away this map, because it is not
     * compatible with already matched maps.
     * This means, that it's important in which order maps are matched.
     * 
     * @return boolean
     */
    private function _isBlacklistCompatible()
    {
        $thisClass = get_class($this);
        //Add icompatibility records to static array
        if (!empty($this->_compatibilityBlacklist)) {
            foreach ($this->_compatibilityBlacklist as $incompatibleMap) {
                self::$_incompatibilityArray[] = array($thisClass       => $incompatibleMap,
                                                       $incompatibleMap => $thisClass);
            }
        }
        
        //@todo|suggestion:Could use the method is compatibleWithMap() foreach alreadyMatchedMaps? Is it better?
        $cnt = count(self::$_incompatibilityArray);
        $incompatibilityFound = false;
        for ($i=0; $i < $cnt; $i++) {
            if (isset(self::$_incompatibilityArray[$i][$thisClass])) {
                $mapClassIncompatibleWithMe = self::$_incompatibilityArray[$i][$thisClass];
                if (in_array($mapClassIncompatibleWithMe, self::$_alreadyMatchedMapsClassNames)) {
                    $incompatibilityFound = true;
                    break;
                }
            }
        }
        return !$incompatibilityFound;
    }
    
    /**
     * 
     * @return boolean
     */
    private function _isWhitelistCompatible()
    {
        $thisClass = get_class($this);
        
        //foreach already matched maps that uses whitelisting, check if thisClass is not listed as compatible
        if (!empty(self::$_limitedCompatibilityArray)) {
            $alreadyAsKeys = array_flip(self::$_alreadyMatchedMapsClassNames);//alreadyMatchedMaps contains something if !empty ^
            $alreadyLimitedCompatibility = array_intersect_key(self::$_limitedCompatibilityArray, $alreadyAsKeys);
            foreach ($alreadyAsKeys as $alreadyMapClassName => $arrayIndex) {
                if (!in_array($thisClass, $alreadyLimitedCompatibility[$alreadyMapClassName])) {
                    return false;
                }
            }         
        }
        
        //does this map use whitelisting?
        if (!empty($this->_compatibilityWhitelist)) {
            //add this whitelist for later maps that have not been tested yet, and that might not be whitelisted
            self::$_limitedCompatibilityArray[$thisClass] = $this->_compatibilityWhitelist;
            //1. check if this class has not whitelisted some already matched maps
            $notWhiteListedMaps = array_diff(self::$_alreadyMatchedMapsClassNames, $this->_compatibilityWhitelist);
            if(!empty($notWhiteListedMaps)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 
     * @param unknown_type $map
     * @throws \WPO\Exception
     * @return boolean
     */
    public function isCompatibleWithMap($map)
    {
        //if the passed map is a string get an instance
        $map = self::mockFactory($map);
        $thisClass = get_class($this);
        $mapClass = get_class($map);
        
        //prepare whitelist
        $thisWL = $this->getWhitelistedMaps();
        $mapWL = $map->getWhitelistedMaps();
        $isWhitelistCompatible = true;
        if (!empty($thisWL)) {
            $isWhitelistCompatible = in_array($mapClass, $thisWL);
        }
        if ($isWhitelistCompatible && !empty($mapWL)) {
            $isWhitelistCompatible = in_array($thisClass, $mapWL);
        }
        //test blacklist and whitelist
        return ( $isWhitelistCompatible 
                 && !( in_array($mapClass, $this->getBlacklistedMaps()) 
                      || in_array($thisClass, $map->getBlacklistedMaps()) ) 
               );
    }
    
    /**
     * Get a map with class name
     * @param multytype:string|object $map if object does a simple instanceof check
     * @throws \WPO\Exception
     */
    static public function mockFactory($map)
    {
        if (is_string($map)) {
            try {
                require_once WPO_PARENT_DIR . preg_replace('#\\\#', '/', $map) . '.php';
                $map = new $map();
            } catch (\Exception $e) {
                require_once WPO_DIR . '/Exception.php';
                throw new \WPO\Exception($e->getMessage());
            }
        }
        if (!is_object($map) || !($map instanceof self)) {
            require_once WPO_DIR . '/Exception.php';
            throw new \WPO\Exception('You must pass a classname or an object of type :' . __CLASS__);
        }
        return $map;
    }
    
    /**
     * Returns the maps that are not compatible with this one
     * 
     * @return multitype:
     */
    public function getBlacklistedMaps()
    {
        return $this->_compatibilityBlacklist;
    }
    
    public function getWhitelistedMaps()
    {
        return $this->_compatibilityWhitelist;
    }
    
    /**
     * 
     * @param string $path
     * @throws \Exception
     */
    public function isSupported($path)
    {
        if (!$comp = $this->isCompatibleWithAlreadyMatchedMaps()) {
            return false;//maps matching till here are not supported by this one
        }
        //allow path trimming
        if (null !== self::$_removePathFromPath) {
            $matches = array();
            $reg = '#' . self::$_removePathFromPath . '/(.+)$#';
            if (0 === preg_match($reg, $path, $matches)) {
                require_once WPO_DIR . '/Map/Test/Exception.php';
                throw new \WPO\Map\Test\Exception(
                    'The path part in AbstractMap::$_removePathFromPath you'
                    . ' want to remove from current path does not match the'
                    . ' path'
                );
            }
            $path = $matches[1];
        }
        //explode path into parts, map subclasses dont have to do it again
        $parts = explode('/', $path);
                
        $isSupported = $this->_isSupportedPath($parts, count($parts));
        //does the user want to load everything on test?
        if ($isSupported) {
            if($this->_loadOnTest) {
                //pass the full path
                $this->_askMapsToLoadUserDataFromPath(self::$_removePathFromPath . '/' . $path);
            }
            //add to static supported maps array
            //this can be used by sublclasses to limit compatibility
            //with other types of maps
            self::$_alreadyMatchedMapsClassNames[] = get_class($this);
        }
        return (bool) $isSupported;
    }
    
    /**
     * The writer is needed to create a normalized
     * data array (ND) that will be written to a file
     * from the loaded user data (that map subclasses load).
     * 
     * @return \WPO\ND\Writer;
     */
    public function getNDWriter()
    {
        if (null === $this->_normalizedDataWriter) {
            require_once WPO_DIR . '/ND/Writer.php';
            $this->_normalizedDataWriter = new \WPO\ND\Writer();
        }
        return $this->_normalizedDataWriter;
    }
    
    /**
     * 
     * @param unknown_type $path
     * @throws Exception
     */
    private function _askMapsToLoadUserDataFromPath($path)
    {
        $data = array();
        
        if (isset(self::$_pathToDataArray[$path])) {
            return;
        }
        
        //allow sublass to save whatever it wants as data from a path
        $data = $this->_loadData($path);
        
        if (null === $data) {
            throw new \Exception('You asked to load data on test, but you did\'t return anything to put in data');
        }
        
        
        
        //Use taxonomies set by sublasses to wrap the data, but allow refactoring
        //of the data if needed
        switch ($this->getFileTaxonomy()) {//returns the current file highest taxonomy not the overall
            case self::TAXONOMY_OPTION;
                $tmpData = $this->_interceptDataOnOptionFileTaxonomy($data);
                $data = array($this->_page => array($this->_section => array($this->_option => $tmpData)));
                break;
            case self::TAXONOMY_SECTION;
                $tmpData = $this->_interceptDataOnSectionFileTaxonomy($data);
                $data = array($this->_page => array($this->_section => $tmpData));
                break;
            case self::TAXONOMY_PAGE;
                $tmpData = $this->_interceptDataOnPageFileTaxonomy($data);
                $data = array($this->_page => $tmpData);
                break;
            default;//no taxonomy was set
                $data = $this->_interceptDataOnNoFileTaxonomy($data);
                break;
        }
        
        /*
         * getDataTaxonomyLevel() defaults to the same taxonomy as the file taxonomy.
         * To change that, call setDataTaxonomyLevel(const).
         * 
         * Subclasses may define a taxonomy level for the data. This is the taxonomy
         * of the data, and not the file containing the data. The difference may be
         * difficult to grasp: File taxonomies are the taxonomies that can be guessed
         * from the file path. Whereas data taxonomy, is the taxonomy the data inside
         * that file is defined at. For example: view maps have the same file taxonomy
         * and data taxonomy. Options on the other hand, may be stored in a page level,
         * (a file inside a page folder), but inside that options file, the data is defined
         * for each option, thus the data taxonomy is at an option level and the file
         * taxonomy is at a page level. Thus data taxonomy is different from the file
         * taxonomy.
         * @todo rename stuff and make the concepts clearer
         */
        switch ($this->getDataTaxonomyLevel()) {
            case self::TAXONOMY_OPTION;
                $this->getNDWriter()->setTo(\WPO\ND\Loader::OPTIONS, $data);
                break;
            case self::TAXONOMY_SECTION;
                $this->getNDWriter()->setTo(\WPO\ND\Loader::SECTIONS, $data);
                break;
            case self::TAXONOMY_PAGE;
                $this->getNDWriter()->setTo(\WPO\ND\Loader::PAGES, $data);
                break;
            default;
                throw new \Exception('You should have set some taxonomy by calling setFileTaxonomy($p[, $s[, $o]]) or called setDataTaxonomyLevel($taxonomy)');
                break;
        }
        
        //map every path to $data, to avoid loading twice
        self::$_pathToDataArray[$path] = $data;
        //unset for next call
        $this->_page = null;
        $this->_section = null;
        $this->_option = null;
        //it will be reset on next call to getDataTaxonomyLevel
        $this->_dataTaxonomyLevel = null;
    }
    
    /**
     * Maps set the taxonomy foreach file. Thus they can use the path
     * or the contents of the file to set some taxonomy. But they will 
     * be able to do that (by calling $this->setFileTaxonomy()) ONCE foreach
     * file (in fact: $path) they are asked about: "_isSupported($pathParts, $partsCount)".
     * $this->setFileTaxonomy(...) sets 1 to 3 members $this->_page, $this->_section
     * or $this->_option, and when abstract class is told to load data, it will 
     * ask the map subclass, to give what the subclass wants to return as data from
     * a given path. That data is then used to populate the ND\Writer depending
     * on what taxonomies where set @see _askMapsToLoadUserDataFromPath().
     * 
     * In some situations no taxonomy can be set by the map subclass from
     * the given path. This arises probably when all taxonomies are defined
     * not in the path, but rather inside the file itself (the file pointed
     * by the path), AND there is more than one element per taxonomy. Thus
     * a call to setTaxonomy would not be sufficient, and many calls would
     * erase any previous call. In this type of configuration, there is a way
     * to circumvent the problem, by implementing _populateNDWriter().
     * 
     * Instead of using setTaxonomy and let the abstract class, populate the
     * ND\Writer for each file isSupported() is called, subclasses can implement
     * this method. Inside it, they must call $this->getNDWriter()->addPage(), 
     * addSection() or addOpiton() for the taxonomies they collect from the $data
     * passed as argument. $data will be beforehand intercepted by 
     * _interceptDataAndSetTaxonomyOnNoTaxonomyExplicitPath($data), a method that subclasses 
     * can also implement to do whatever is needed with the data.
     * 
     * A situation where you don't need this, is when taxonomies are guessed
     * from within the file contents, and there is only one element per lower
     * taxonomy levels (per page, and section). Another situation is when there
     * are no lower taxonomies explicitly defined anywhere, and the subclass 
     * is supposed (if supported) the default page taxonomy, and the default 
     * section taxonomy. Note that these latter cases should be handled from 
     * _interceptDataAndSetTaxonomyOnNoTaxonomyExplicitPath($data), and you 
     * should not use the ND\Writer, just use the abstract class's 
     * setFileTaxonomy($p, $s, $o).
     * 
     * @throws \Exception
     */
    protected function _populateNDWriter($data)
    {
        throw new \Exception('Your map did not call setFileTaxonomy(page[, section[, opiton]]), so you should implement _populateNDWriter($data) and from there use $this->getNDWriter()->addPage(), addSection(), addOption() from what is inside the paramater');
    }
    
    /**
     * No taxonomy could be guessed from the path, this means taxonomies are
     * iniside the file (in the array) or not specified at all... You can
     * try to guess the taxonomy from the $data passed as argument, and call
     * setFileTaxonomy()
     * @param mixed $data
     * @return mixed
     */
    protected function _interceptDataOnNoFileTaxonomy($data)
    {
        return $data;
    }
    
    /**
     * Add hooks that subclasses can use to attempt to refactor
     * the data in case there is a missing taxonomy
     * 
     * @param multitype $data
     */
    protected function _interceptDataOnPageFileTaxonomy($data) 
    { 
        return $data; 
    }
    
    /**
     * Add hooks that subclasses can use to attempt to refactor
     * the data
     *
     * @param multitype $data
     */
    protected function _interceptDataOnSectionFileTaxonomy($data) 
    { 
        return $data; 
    }
    
    /**
     * Add hooks that subclasses can use to attempt to refactor
     * the data
     *
     * @param multitype $data
     */
    protected function _interceptDataOnOptionFileTaxonomy($data) 
    { 
        return $data; 
    }
    
    /**
     * The test can tell the maps that there
     * are no more files to check. This way maps
     * can guess if there are enough files
     * for his requirements, by calling the methods:
     * isMissingOptions(), isMissing(Higher|Lower)Taxonomy() etc.
     * 
     */
    public function isSupportedIfNoMoreFiles()
    {
        $isSupported = $this->_isSupportedIfNoMoreFiles();
        if (!is_bool($isSupported)) {
            require_once WPO_DIR . '/Map/Test/Exception.php';
            throw new \WPO\Map\Test\Exception('You have to return a boolean value from _noMoreFiles()');
        }
        return $isSupported;
    }
    
    /**
     * Puts the data into $this->_normalizedDataWriter
     * @param unknown_type $path
     */
    abstract protected function _loadData($path);
    
    /**
     * If the map knows what each taxonomy is
     *
     * @param array $pathParts exploded path parts
     * @param integer $count path parts count
     */
    abstract protected function _isSupportedPath($pathParts, $count);
    
    /**
     * This can be called by a test when there are not more
     * files. So if any finaly check has to be done, to test
     * whether all the needed files were passed, you should
     * test it inside here
     */
    abstract protected function _isSupportedIfNoMoreFiles();
}