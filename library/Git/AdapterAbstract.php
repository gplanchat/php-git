<?php

namespace Git;

class AdapterAbstract
    extends \FilesystemIterator
{
    protected $_options = array();

    abstract public function __construct($options);

    public function setConfig(Zend_Config $options)
    {
        return $this->setOptions($options->toArray());
    }

    public function mergeConfig(Zend_Config $options)
    {
        return $this->mergeOptions($options->toArray());
    }

    public function setOptions(Array $options)
    {
        $this->_options = $options;
    }

    public function mergeOptions(Array $options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    public function setOption($optionKey, $value)
    {
        $this->_options[(string) $optionKey] = $value;
    }

    public function hasOption($optionKey)
    {
        if (isset($this->_options[(string) $optionKey])) {
            return true;
        }
        return false;
    }

    public function getOption($optionKey, $default = null)
    {
        if ($this->hasOption($optionKey)) {
            return $this->_options[(string) $optionKey];
        }
        return $default;
    }

    public function unsetOption($optionKey)
    {
        if ($this->hasOption($optionKey)) {
            unset($this->_options[(string) $optionKey]);
        }
    }
}