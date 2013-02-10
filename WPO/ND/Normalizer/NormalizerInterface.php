<?php
namespace WPO\ND\Normalizer;

require_once WPO_DIR . '/Plugin.php';

interface NormalizerInterface
{
    public function __construct($params=null);
    public function normalize($data);
}