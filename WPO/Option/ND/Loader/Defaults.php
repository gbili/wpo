<?php
namespace WPO\Option\ND\Loader;

require_once WPO_DIR . '/ND/Loader.php';

/**
 * @todo? Options only use the \WPO\ND\Loader::OPTIONS part of the loaded array.
 * It might be an idea to directly remove that wrapping key at load time
 * by overriding some parent methods
 * @author g
 *
 */
class Defaults
extends \WPO\ND\Loader
{

}