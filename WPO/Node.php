<?php
namespace WPO;

class Node 
implements \Iterator
{
    private $_identifier = null;
    
    /**
     * A pointer to the root node
     * @var self
     */
    private $_root = null;
    
    /**
     * A pointer to the immediate parent
     * (it may be the root too)
     * 
     * @var unknown_type
     */
    private $_parent = null;
    private $_children = array();
    
    private $_childrenArrayKeys = array();
    private $_childrenArrayKeysPointer = 0;
    
    private $_data = null;
    
    public function __construct($identifier, $data)
    {
        $this->_id = $identifier;
        $this->_data = $data;
        $this->_root = $this;//node is not linked so initialize it as root
    }
    
    public function current()
    {
        return $this->_children[$this->_childrenArrayKeysPointer];
    }
    
    public function key()
    {
        return $this->_childrenArrayKeys[$this->_childrenArrayKeysPointer];
    }
    
    public function next()
    {
        return $this->_children[++$this->_childrenArrayKeysPointer];
    }
    
    public function rewind()
    {
        $this->_childrenArrayKeysPointer = 0;
    }
    
    public function valid()
    {
        return isset($this->_childrenArrayKeys[$this->_childrenArrayKeysPointer]);
    }
    
    public function getIdentifier()
    {
        return $this->_identifier;
    }
    
    public function isRoot()
    {
        return null !== $this->_root;
    }
    
    public function hasRoot()
    {
        return null !== $this->_root;
    }
    
    public function getRoot()
    {
        return $this->_root;
    }
    
    public function setRoot(self $r)
    {
        $this->_root = $r;
    }
    
    
    public function hasParent()
    {
        return null !== $this->_parent;
    }
    
    public function getParent()
    {
        if (null === $this->_parent) {
            throw new \Exception('no parent is set');
        }
        return $this->_parent;
    }
    
    public function setParent(self $p)
    {
        $this->_root = $p->getRoot();
        $this->_parent = $p;//important before addChild() to avoid infinite setting
        if (!$p->hasChild($this->_identifier)) {
            $p->addChild($this);
        }
    }
    
    public function hasChild($identifier)
    {
        return isset($this->_children[$identifier]);
    }
    
    public function hasChildren()
    {
        return !empty($this->_child);
    }
    
    public function getChildren()
    {
        return $this->_children;
    }
    
    public function getChild($identifier)
    {
        if (!isset($this->_children[$identifier])) {
            throw new \Exception('No child with this identifier');
        }
        return $this->_children[$identifier];
    }
    
    public function addChild(self $c)
    {
        if ($c->hasParent()) {
            if ($this !== $c->getParent()) {
                $c->getParent()->unsetChild($c->getIdentifier());
                $c->setParent($this);
            }
        } else {
            $c->setParent($this);
        }
        $id = $c->getIdentifier();
        $this->_childrenArrayKeys[] = $id;
        $this->_children[$id] = $c;
    }
    
    public function unsetChild($id)
    {
        unset($this->_children[$id]);
    }
    
    public function hasData()
    {
        return null !== $this->_data;
    }
    
    public function getData()
    {
        return $this->_data;
    }
    
    public function setData($d)
    {
        $this->_data = $d;
    }
}