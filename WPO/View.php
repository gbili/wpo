<?php
namespace WPO;

require_once WPO_DIR . '/View/ND/Loader/Views.php';

use WPO\View\ND\Normalizer\Views as ViewsNormalizer;

/**
 * 
 * @author g
 *
 */
class View
{
    /**
     * 
     * @var WPO\PLugin
     */
    private $_plugin;
    
    /**
     * The page we are trying to render
     * 
     * @var unknown_type
     */
    private $_page;
    
    /**
     *
     * @param string $pluginIdentifier
     * @param array $info
     */
    public function __construct(\WPO\Plugin $plugin, $page)
    {
        $this->_plugin = $plugin;
        $this->_page   = $page;
        
       /* 
        * Create a page view, and if there are view
        * files for higher taxonomies, the page will
        * try to render them, by creating higher taxonomy
        * view instances
        */
        require_once WPO_DIR . '/View/Taxonomy/Page.php';
        new \WPO\View\Taxonomy\Page($this->_plugin, $this->_page);
    }
}