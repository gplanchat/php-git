<?php

namespace Git\Adapter;

use Git;

class FileSystem
    extends Git\AdapterAbstract
{
    /**
     * The current repository path.
     * @var string
     */
    private $_repositoryPath = null;

    /**
     * The pack list
     *
     * @var array
     */
    private $_packs = array();

    /**
     * The branch list
     *
     * @var array
     */
    private $_branches = array();

    /**
     * The tags list
     *
     * @var array
     */
    protected $_tags = array();

    /**
     * The tags list
     *
     * @var array
     */
    protected $_trees = array();

    /**
     * The tags list
     *
     * @var array
     */
    protected $_blobs = array();

    /**
     * The tags list
     *
     * @var array
     */
    protected $_commits = array();

    /**
     *
     */
    public function __construct($options)
    {
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } else if (is_array($options)) {
            $this->setOptions($options);
        } else {
            throw new Git\Adapter\InvalidArgumentException(__METHOD__
                . " method 1st parameter should be either a Zend_Config or an array.");
        }

        if ($this->getOption('path') === null) {
            throw new Git\Adapter\InvalidArgumentException(
                'Repository path should be defined by instanciation options.');
        }

        $this->_repositoryPath = realpath($this->getOption('path'));
        if (!is_dir($this->_repositoryPath)) {
            throw new Git\Adapter\RuntimeException(
                'Repository path should be a directory.');
        }
        if (!$this->getOption('bare', false)) {
            $this->_repositoryPath .= DIRECTORY_SEPARATOR . '.git';
        }
        if (!is_readable($this->_repositoryPath)) {
            throw new Git\Adapter\RuntimeException(
                'Repository path should be at least accessible in read mode.');
        }
        if (!is_writable($this->_repositoryPath) && $this->getOption('readonly', false)) {
            throw new Git\Adapter\RuntimeException(
                'Repository path should be accessible in write mode or the "readonly" option should be set to true.');
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/objects/pack');
            foreach ($iterator as $file) {
                if (($hash = substr($file->getFilename(), 5, 40)) === false || !ctype_xdigit($hash)) {
                    continue;
                }
                $this->_packs[$hash] = $file;
            }
        } catch (\UnexpectedValueException $e) {
            throw new Git\Adapter\RuntimeException(
                'An error occured while reading pack files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/heads');
            foreach ($iterator as $file) {
                $this->_branches[$file->getFilename()] = file($file->getPathname(), FILE_IGNORE_NEW_LINES);;
            }
        } catch (\UnexpectedValueException $e) {
            throw new Git\Adapter\RuntimeException(
                'An error occured while reading pack files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/tags');
            foreach ($iterator as $file) {
                $this->_branches[$file->getFilename()] = file($file->getPathname(), FILE_IGNORE_NEW_LINES);;
            }
        } catch (\UnexpectedValueException $e) {
            throw new Git\Adapter\RuntimeException(
                'An error occured while reading pack files.', 0, $e);
        }
    }
}
