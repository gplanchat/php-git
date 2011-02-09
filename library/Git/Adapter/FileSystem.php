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
     * The HEAD commit
     *
     * @var string
     */
    private $_head = null;

    /**
     * The pack index
     *
     * @var array
     */
    private $_packIndex = array();

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
     * The Git\Object\Commit instance heap
     *
     * @var array
     */
    protected $_commits = array();

    /**
     * The remotes list
     *
     * @var array
     */
    protected $_remotes = array();

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
            throw new Exception\InvalidArgumentException(
                'Repository path should be defined by instanciation options.');
        }

        $this->_repositoryPath = realpath($this->getOption('path'));
        if (!is_dir($this->_repositoryPath)) {
            throw new Exception\RuntimeException(
                'Repository path should be a directory.');
        }
        if (!$this->getOption('bare', false)) {
            $this->_repositoryPath .= DIRECTORY_SEPARATOR . '.git';
        }
        if (!is_readable($this->_repositoryPath)) {
            throw new Exception\RuntimeException(
                'Repository path should be at least accessible in read mode.');
        }
        if (!is_writable($this->_repositoryPath) && $this->getOption('readonly', false)) {
            throw new Exception\RuntimeException(
                'Repository path should be accessible in write mode or the "readonly" option should be set to true.');
        }

// TODO: parse HEAD file
//        $this->_head = file($this->_repositoryPath . '/HEAD', FILE_IGNORE_NEW_LINES);

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/objects/pack');
            foreach (new \RegexIterator($iterator, '#\.idx$#i') as $file) {
                if (($hash = substr($file->getFilename(), 5, 40)) === false || !ctype_xdigit($hash)) {
                    continue;
                }

                $this->_indexPackFile($file->getPathname());
            }
        } catch (\UnexpectedValueException $e) {
            throw new Exception\RuntimeException(
                'An error occured while reading pack files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/heads');
            foreach ($iterator as $file) {
                $this->_branches[$file->getFilename()] = file($file->getPathname(), FILE_IGNORE_NEW_LINES);;
            }
        } catch (\UnexpectedValueException $e) {
            throw new Exception\RuntimeException(
                'An error occured while reading heads files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/tags');
            foreach ($iterator as $file) {
                $this->_branches[$file->getFilename()] = file($file->getPathname(), FILE_IGNORE_NEW_LINES);;
            }
        } catch (\UnexpectedValueException $e) {
            throw new Exception\RuntimeException(
                'An error occured while reading tag files.', 0, $e);
        }
    }

    /**
     * @return Git\Object\Commit
     */
    public function getHead()
    {
    }

    /**
     * @param string $branch
     * @return Git\Object\Commit
     */
    public function getBranch($branch)
    {
        if (!isset($this->_branches[(string) $branch])) {
            throw new Exception\InvalidArgumentException("No such branch '{$branch}'.");
        }

        try {
            return $this->getCommit($this->_branches[(string) $branch]);
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such branch '{$branch}'");
        }
    }

    /**
     * @param string $branch
     * @return Git\Object\Commit
     */
    public function getTag($tag)
    {
        if (!isset($this->_tags[(string) $branch])) {
            throw new Exception\InvalidArgumentException("No such tag '{$tag}'.");
        }

        try {
            return $this->getCommit($this->_tags[(string) $branch]);
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such tag '{$tag}'");
        }
    }

    /**
     * @param string $commit
     * @return Git\Object\Commit
     */
    public function getCommit($commit)
    {
        if (isset($this->_commits[(string) $commit])) {
            return $this->_commits[(string) $commit];
        }

        try {
            $this->_commits[(string) $commit] = new Git\Object\Commit($this->_readObject($commit));
            return $this->_commits[(string) $commit];
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such commit '{$commit}'");
        }
    }

    private function _readObject($hash)
    {
        $path = $this->_repositoryPath . "/objects/{$hash[0]}{$hash[1]}/" . substr($hash, 2);

        if (file_exists($path)) {
            return gzuncompress(file_get_contents($path));
        }

        throw new Exception\RuntimeException("Could not find object '{$hash}'");
    }

    private function _parsePackFile($file)
    {
        if (!$index = @fopen($file, 'rb')) {
            throw new Exception\RuntimeException("Could not open pack file '{$file}'");
        }
        flock($index, LOCK_SH);

        if (($toc = fread($index, 4)) === false) {
            throw new Exception\RuntimeException("Could not read pack file '{$file}'");
        }

        if ($toc === 'PACK') {
            $version = current(unpack('N', fread($index, 4)));
            $objectsCount = current(unpack('N', fread($index, 4)));

            // TODO: parse .pack file
        }

        flock($index, LOCK_UN);
        fclose($index);

        return null;
    }

    private function _indexPackFile($file)
    {
        if (!$index = @fopen($file, 'rb')) {
            throw new Exception\RuntimeException("Could not open pack file '{$file}'");
        }

        flock($index, LOCK_SH);
        if (($toc = fread($index, 4)) !== false) {
            if ($toc == "\xFFtOc") {
                // TODO: parse .idx file version 2
                // skip the fanout
                fseek($index, 1024, SEEK_CUR);

                $tmp = unpack('N', fread($index, 4));
                $objectCount = current($tmp);

                $hashes = array();
                for ($i = 0; $i < $objectCount; $i++) {
                    $tmp = unpack('H40', fread($index, 20));
                    $hashes[] = current($tmp);
                }

                fseek($index, 4 * $objectCount, SEEK_CUR);

                for ($i = 0; $i < $objectCount; $i++) {
                    $tmp = unpack('N', fread($index, 4));
                    $offset = current($tmp);

                    $this->_packIndex[$hashes[$i]] = array(
                        'file'   => $file,
                        'offset' => $offset
                        );
                }
            } else {
                // skip the fanout
                fseek($index, 1016, SEEK_CUR);

                $tmp = unpack('N', fread($index, 4));
                $objectCount = current($tmp);

                for ($i = 0; $i < $objectCount; $i++) {
                    $tmp = unpack('N', fread($index, 4));
                    $offset = current($tmp);

                    $tmp = unpack('H40', fread($index, 20));
                    $hash = current($tmp);

                    $this->_packIndex[$hash] = array(
                        'file'   => $file,
                        'offset' => $offset
                        );
                }
            }
        }
        flock($index, LOCK_UN);
        fclose($index);

        return array();
    }
}
