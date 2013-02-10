<?php
namespace WPO\View;

use WPO\Option as Option;//option constants may be used in views;

/**
 * 
 * @author g
 *
 */
class TaxonomyOption
extends \WPO\View\AbstractTaxonomy
{
    protected $optionInUse;
    protected $page;
    protected $section;
    protected $option;
    protected $id;
    
    /**
     * (non-PHPdoc)
     * @see WPO\View.AbstractView::_init()
     */
    protected function _init()
    {
        $this->page = $this->info[Option::PAGE];
        $this->section = $this->info[Option::SECTION];
        $this->option = $this->id = $this->info[Option::OPTION];
        //we already have all options in use in optionsInUse, the goal here is to make the current default option's option in use available
        //in optionInUse  
        $this->optionInUse = $this->optionsInUse[$this->page][$this->section][$this->option];
    }
}