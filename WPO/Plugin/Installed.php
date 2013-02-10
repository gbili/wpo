<?php
namespace WPO\Plugin;

/**
 * @todo make the plugin load lightfast by allowing to instatiate an installed plugin, which makes much less
 * checks. WPO classes should use this to get data (it doesnt work yet, to implement...)
 * 
 * @author g
 *
 */
class Installed
{
    static private $_singletons;
    
    public function __construct($identifier)
    {
        $this->_integrate = new WPO\Integrate($identifier);
    }
    
    /**
     *
     * @param string $pluginIdentifierOrName name if first time, identifier otherwise
     * @throws Exception
     * @return \WPO\Plugin
     */
    static public function getInstance($pluginIdentifierOrName)
    {
        if (!is_string($pluginIdentifierOrName)) {
            throw new Exception('Argument not supported');
        }
    
        //first call create the instance and consider parameter as name
        if (!isset(self::$_singletons[$pluginIdentifierOrName])) {
            $name = $pluginIdentifierOrName;
            $self = new self($name);
            $identifier = $self->getIdentifier();
            self::$_singletons[$identifier] = $self;
        } else {
            $identifier = $pluginIdentifierOrName;
        }
    
        //there should be an instance by now
        if (!isset(self::$_singletons[$identifier])) {
            throw new Exception('The argument, does not seem to be a valid plugin identifier');
        }
    
        return self::$_singletons[$identifier];
    }
}