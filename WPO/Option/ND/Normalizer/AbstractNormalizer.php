<?php
namespace WPO\Option\ND\Normalizer;

require_once WPO_DIR . '/ND/Normalizer/NormalizerInterface.php';

abstract class AbstractNormalizer
implements \WPO\ND\Normalizer\NormalizerInterface
{
    /**
     * @var array
     */
    protected $_dataArrayToNormalize = array();
    protected $_param = null;


    /**
     *
     * @param Integrate $integrate
     * @param unknown_type $adminPagesPath
     * @throws Exception
     */
    public function __construct($param=null)
    {
        if (null !== $param) {
            $this->_param = $param;
        }
    }
    
    /**
     * data is an array(0_>arraofoptions, 1->arrayofsectionsdata 2->arrayofpagesdata)
     * here we only treat taxonomy options. because in options array (whole $data) for the moment,
     * we dont allow the specification of separate page info.
     * @todo how would we treat page and section specific info? how would user need to represent that data into an array? Note that this kind of page section data, would be wpo metadata, not theme or plugin usefull data...
     * (non-PHPdoc)
     * @see WPO\ND\Normalizer.NormalizerInterface::normalize()
     */
    public function normalize($data)
    {
        require_once WPO_DIR . '/ND/Loader.php';
        $this->_dataArrayToNormalize = $data[\WPO\ND\Loader::OPTIONS];//used for later reference
        //go through the almost completely user defined array and refine it for faster retrieval (later when loading once installed)
        $normOptsArray = array();
        foreach ($this->_dataArrayToNormalize as $page => $parray) {
            $normOptsArray[$page] = array();
            foreach ($parray as $section => $sArray) {
                $normOptsArray[$page][$section] = array();
                foreach ($sArray as $option => $info) {
                    $normOptsArray[$page][$section][$option] = $this->_normalize($page, $section, $option, $info);
                }
            }
        }
        return array(\WPO\ND\Loader::OPTIONS => $normOptsArray);
    }
}