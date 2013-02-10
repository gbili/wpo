<?php
namespace WPO\Option\ND\Loader;

require_once WPO_DIR . '/ND/Loader.php';

/**
 * This class is used to write the normalized array to a file,
 * and get the options normalized data. It will avoid using
 * maps on every script load. It simply includes the normalized
 * data array, and makes it statically available for anyone to use.
 * 
 * @author g
 *
 */
class Values
extends \WPO\ND\Loader
{
}