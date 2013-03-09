<?php
namespace WPO\View\Map;

/**
 * 
 * @author g
 *
 */
abstract class AbstractMap 
extends \WPO\Map\AbstractMap
{
    /**
     * We dont want to load any specific data.
     * We only want to create a map of keys related to
     * the path.
     * Subclasses still need to implement isSupported($pathParts, $Count);
     * On that call, they should set: 
     * $this->_page [$this->_section [$this->_option]]
     * from the path parts
     * 
     * @param unknown_type $path
     */
    final protected function _loadData($path)
    {   
        return $path;//just save the path
    }
    
    /**
     * Help subclasses
     * 
     * @return string
     */
    public function dotSuffix()
    {
        return '.' . \WPO\Plugin::VIEW_SUFFIX;
    }
    
    /**
     * Too complicated should have stuck without any abstraction
     * 
     * @param array $taxonomies
     * @throws \Exception
     */
    public function getPath(array $taxonomies)
    {
        return self::nestedOptionalParamsDynamicCall($this, parent::getOrderedTaxonomies(), $taxonomies, 'getPagePath', 'getSectionPath', 'getOptionPath');
    }
    
    public function getPagePath($page)
    {
        throw new \Exception('When writing your map you should have either, implemented ' . __METHOD__ . ', or override getPath()');
    }
    
    public function getSectionPath($page, $section)
    {
        throw new \Exception('When writing your map you should have either, implemented ' . __METHOD__ . ', or override getPath()');
    }
    
    public function getOptionPath($page, $section, $option)
    {
        throw new \Exception('When writing your map you should have either, implemented ' . __METHOD__ . ', or override getPath()');
    }
    
    /**
     * Too complicated should have stuck without any abstraction
     * It's not even reused anywhere
     * 
     * nested params, means that there are a number n of params in $params
     * and each of which (lets call it p | p<n) depends on the p-1 param
     * when p=0 it does not depend on any other param.
     * What we do here is to check that every param has the neighbour it
     * depends on.
     * Then we call the method specified in callOpt, callSec
     * and callPag from $instance. If no methods are specified
     * in the last two params, we call the same function for any
     * element in $params that is the one specified in $callPag
     * note: here n = 3
     *
     * could abstract it completely by only using numbers...
     * @param StdObj $instance
     * @param array $paramsOrderedKeys the keys of the second arg array, here as values ordered numerically
     * @param array $paramsKeyVal params as values to keys of first argument
     * @param string $callOpt method to call when there are 3 params
     * @param string $callSec method to call when there are 2 params
     * @param string $callPag method to call when there is one param or: $callOpt and $callSec are null
     * @throws \Exception
     */
    static public function nestedOptionalParamsDynamicCall($instance, array $paramsOrderedKeys, array $paramsKeyVal, $call0, $call1 = null, $call2 = null)
    {
        require_once WPO_DIR . '/Map/AbstractMap.php';
    
        if (null === $call0) {//call the same method?
            $call2 = $call0;
            $call1 = $call0;
        }
    
        if (count($paramsOrderedKeys) < 3) {
            throw new \Exception('The param keys');
        }
    
        if (!isset($paramsKeyVal[$paramsOrderedKeys[0]])) {
            throw new \Exception('The page taxonomy must always be specified to retrieve the path.' ."\n ParamOrderedKeys : " .  print_r($paramsOrderedKeys, true));
        }
    
        if (isset($paramsKeyVal[$paramsOrderedKeys[2]])) {
            if (!isset($paramsKeyVal[$paramsOrderedKeys[1]])) {
                throw new \Exception('The section taxonomy must always be specified to retrieve the path of an option');
            }
            $ret = $instance->$call2($paramsKeyVal[$paramsOrderedKeys[0]],
                    $paramsKeyVal[$paramsOrderedKeys[1]],
                    $paramsKeyVal[$paramsOrderedKeys[2]]);
        } else if (isset($paramsKeyVal[$paramsOrderedKeys[1]])) { //no option neither
            $ret = $instance->$call1($paramsKeyVal[$paramsOrderedKeys[0]],
                    $paramsKeyVal[$paramsOrderedKeys[1]]);
        } else {//TAXONOMY_PAGE
            $ret = $instance->$call0($paramsKeyVal[$paramsOrderedKeys[0]]);
        }
        return$ret;
    }
}