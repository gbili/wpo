<?php
namespace WPO;

/**
 * Another apprach would consist in having an option object
 * that would hold what we set here as constants, as members.
 * then one could call 
 * Option::useOption('myPage', 'mySection', 'myOpt');
 * Option::get()->title;
 * Option::get()->value;
 * etc.
 * @todo Static crap must be deleted, useless
 * @author g
 *
 */
class Option
{
    /**
     * When validator is set to this value
     * the validation is made against the $_data
     * array. If the value is present, then it
     * is considered valid.
     * @var unknown_type
     */
    const VALIDATOR_DATA       = 'data';//self::DATA
    const VALIDATOR            = 'validator';
    
    const PAGE                 = 'page';
    const PAGE_TITLE           = 'pageTitle';
    
    const SECTION              = 'section';
    const SECTION_TITLE        = 'sectionTitle';
    
    const OPTION               = 'id';
    const TITLE                = 'title';
    
    const DATA                 = 'data';
    const DATA_VALUE           = 'value';
    const VALUE                = 'value';
    const DEFAULT_VALUE        = 'defaultValue';
    const RENDERER             = 'renderer';//use function for rendering rather than view
    
    const DATA_FOREIGN_REF     = 'dataForeignRef';
    const DFREF_SEPARATOR      = ':';
    
    /**
     * 
     * @var WPO\Option\NormalizedData\Values
     */
    static private $_array;
    
    /**
     * This may be helpful to avoid specifying
     * the page on every get() call
     * 
     * @var unknown_type
     */
    static private $_pageInUse  = null;
    static private $_sectionInUse = null;
    
    /**
     * 
     * @param WPO\NormalizedData $na
     */
    static public function setArray(array $a)
    {
        self::$_array = $a;
    }
    
    /**
     * 
     * @param string $pageOrSecOrOpt
     * @param string $sectionOrOptOrNull
     * @param string $optionOrNull
     */
    static public function get($pageOrSecOrOpt, $sectionOrOptOrNull = null, $optionOrNull = null)
    {
        if (null === $sectionOrOptOrNull) {
            $option  = $pageOrSecOrOpt;
            $section = (null !== self::$_sectionInUse)? self::$_sectionInUse : Plugin::getDefaultSectionName();
            $page    = (null !== self::$_pageInUse)? self::$_pageInUse : Plugin::getDefaultPageName();
        } else if (null === $optionOrNull) {
            $option  = $sectionOrOptOrNull;
            $section = $pageOrSecOrOpt;
            $page    = (null !== self::$_pageInUse)? self::$_pageInUse : Plugin::getDefaultPageName();
        } else {
            $option  = $optionOrNull;
            $section = $sectionOrOptOrNull;
            $page    = $pageOrSecOrOpt;
        }
        
        if (!isset(self::$_array[$page][$section][$option])) {
            throw new Exception(
                "One of the following keys is not set p: $page, s:$section, o:$option in options array : " 
                . print_r(self::$_array, true)
            );
        }
        return self::$_array[$page][$section][$option];
    }
    
    /**
     * Get an option's data
     * @param string $pageOrSecOrOpt
     * @param string $sectionOrOptOrNull
     * @param string $optionOrNull
     * @return unknown
     */
    static public function getData($pageOrSecOrOpt, $sectionOrOptOrNull = null, $optionOrNull = null)
    {
        $optionArray = self::get($pageOrSecOrOpt, $sectionOrOptOrNull, $optionOrNull);
        return $optionArray[self::DATA];
    }
    
    /**
     * Get the options current title
     * @param unknown_type $pageOrSecOrOpt
     * @param unknown_type $sectionOrOptOrNull
     * @param unknown_type $optionOrNull
     * @return unknown
     */
    static public function getTitle($pageOrSecOrOpt, $sectionOrOptOrNull = null, $optionOrNull = null)
    {
        $optionArray = self::get($pageOrSecOrOpt, $sectionOrOptOrNull, $optionOrNull);
        return $optionArray[self::VALUE];
    }
    
    /**
     * Get the options current value
     * @param unknown_type $pageOrSecOrOpt
     * @param unknown_type $sectionOrOptOrNull
     * @param unknown_type $optionOrNull
     * @return unknown
     */
    static public function getValue($pageOrSecOrOpt, $sectionOrOptOrNull = null, $optionOrNull = null)
    {
        $optionArray = self::get($pageOrSecOrOpt, $sectionOrOptOrNull, $optionOrNull);
        return $optionArray[self::VALUE];
    }
    
    /**
     * Allow to skip specifying section on each get(p,s,o) call
     * Options will be gotten from this section if not otherwise
     * specified in self::get(...)
     * 
     * @param string $sectionOrPage
     * @param string $sectionOrNull
     */
    static public function useSection($sectionOrPage, $sectionOrNull = null)
    {
        if (null === $sectionOrNull) {
            self::$_sectionInUse = $sectionOrPage;
        } else {
            self::$_pageInUse = $sectionOrPage;
            self::$_sectionInUse = $sectionOrPage;
        }
    }
    
    /**
     * 
     * @param unknown_type $page
     */
    static public function usePage($page)
    {
        self::$_pageInUse = $page;
    }
}