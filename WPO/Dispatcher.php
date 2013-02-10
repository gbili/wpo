<?php
/**
 * 
 * @author g
 *
 */
namespace WPO;

require_once __DIR__ . '/Plugin.php';

/**
 * 
 * @author g
 *
 */
class Dispatcher
{
    
    const FUNCNAME_PARAM_SEP = '__';
    const ACTION_RENDER      = 'render';
    const ACTION_VALIDATE    = 'validate';
    const ACTION_INTEGRATE   = 'integrate';
    
    /**
     * We should use :
     * __NAMESPACE__ . '\\Validator::' . $themeShortName
     * as reference for the validation callback (3rd param)
     * when register_setting is called.
     * This way we know what theme we are talking about
     * then we handle the call from __callStatic()
     * @param callback $functionName
     * @param  $input
     * @return mixed
     */
    static public function __callStatic($func, $input=null)
    {
        /*
         * here is the function name pattern
         * Dispatcher::<action>__<page-name>__<section-name>__<option-name>
         * action : render, validate
         * page-name ---: either from folder or from options.php array
         * section-name : from options.php array
         * option-name -: from options.php array
         * format     --: page_name, pageName, PageName, pagename
         *                the format is the same as the referenced
         */
        list($pluginIdentifier, $action, $fnameParam) = explode(self::FUNCNAME_PARAM_SEP, $func);
        $plugin = Plugin::getInstance($pluginIdentifier);
        
        switch ($action) {
            case self::ACTION_RENDER;
                require_once __DIR__ . '/View.php';
                new View($plugin, $fnameParam);//fnameParam is page
                break;
                
            case self::ACTION_VALIDATE;
                require_once __DIR__ . '/Validate.php';
                $a = new Validate($plugin, $fnameParam, $input);//fnameParam is page
                $output = $a->getValidated();
                return apply_filters($pluginIdentifier . '_options_validate' , $output, $input, $defaults);
                break;
                
            case self::ACTION_INTEGRATE;
                //the plugin already created an integrate instance
                $plugin->getIntegrate()->$fnameParam();//fnameParam is method that was set in add_action(init_... during integrate instantiation
                break;
                
            default;
                require_once __DIR__ . '/Exception.php';
                throw new Exception('You hooked an action that is not supported');
                break; 
        }
    }
    
    /**
     * Wordpress needs callbacks to call for rendering of pages,
     * this returns the name of the callback for a given page
     * 
     * @param unknown_type $page
     * @return string
     */
    static public function getRenderCallback($pluginIdentifier, $page)
    {
        return self::_getCallbackName($pluginIdentifier, array(self::ACTION_RENDER, $page));
    }
    
    /**
     * 
     * @param unknown_type $page
     */
    static public function getValidateCallback($pluginIdentifier, $page)
    {
        return self::_getCallbackName($pluginIdentifier, array(self::ACTION_VALIDATE, $page));
    }
    
    /**
     * 
     * @param unknown_type $pluginIdentifier
     * @param unknown_type $page
     */
    static public function getIntegrateCallback($pluginIdentifier, $method)
    {
        return self::_getCallbackName($pluginIdentifier, array(self::ACTION_INTEGRATE, $method));
    }
    
    /**
     * 
     * @param array $params
     */
    static private function _getCallbackName($pluginIdentifier, array $params)
    {
        array_unshift($params, $pluginIdentifier);
        //\WPO\Dispatcher::pluginIdentifier__action__page
        return  __CLASS__ . '::' . implode(self::FUNCNAME_PARAM_SEP, $params);
    }
}