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
     * The pack file offsets
     *
     * @var array
     */
    private $_packOffsets = array();

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

                $this->_indexPackFile($file);
            }
        } catch (\UnexpectedValueException $e) {
            throw new Exception\RuntimeException(
                'An error occured while reading pack files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/heads');
            foreach ($iterator as $file) {
                $this->_branches[$file->getFilename()] = trim(file_get_contents($file->getPathname()));
            }
        } catch (\UnexpectedValueException $e) {
            throw new Exception\RuntimeException(
                'An error occured while reading heads files.', 0, $e);
        }

        try {
            $iterator = new \FilesystemIterator($this->_repositoryPath . '/refs/tags');
            foreach ($iterator as $file) {
                $this->_tags[$file->getFilename()] = trim(file_get_contents($file->getPathname()));
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
        // TODO
    }

    /**
     * @param string $branch
     * @return Git\Branch
     */
    public function getBranch($branch)
    {
        $branch = (string) $branch;
        var_dump($this->_branches);
        if (!isset($this->_branches[$branch])) {
            throw new Exception\InvalidArgumentException("No such branch '{$branch}'.");
        }

        try {
            return new Git\Branch($branch, $this->getCommit($this->_branches[$branch]));
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such branch '{$branch}'", null, $e);
        }
    }

    /**
     * @param string $tag
     * @return Git\Tag
     */
    public function getTag($tag)
    {
        $tag = (string) $tag;
        if (!isset($this->_tags[$tag])) {
            throw new Exception\InvalidArgumentException("No such tag '{$tag}'.");
        }

        try {
            return new Git\Tag($tag, $this->getCommit($this->_tags[$branch]));
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such tag '{$tag}'", null, $e);
        }
    }

    /**
     * @param string $commit
     * @return Git\Object\Commit
     */
    public function getCommit($commit)
    {
        $commit = (string) $commit;
        if (isset($this->_commits[$commit])) {
            return $this->_commits[$commit];
        }

        try {
            $object = $this->getObject($commit);
            if (!$object instanceof Git\Object\Commit) {
                throw new Exception\RuntimeException("Hash '{$commit}' is not a commit.");
            }
            $this->_commits[$commit] = $object;

            return $this->_commits[$commit];
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such commit '{$commit}'", null, $e);
        }
    }

    /**
     * @param string $tree
     * @return Git\Object\Tree
     */
    public function getTree($tree)
    {
        $tree = (string) $tree;
        if (isset($this->_trees[$tree])) {
            return $this->_trees[$tree];
        }

        try {
            $object = $this->getObject($tree);
            if (!$object instanceof Git\Object\Tree) {
                throw new Exception\RuntimeException("Hash '{$tree}' is not a tree.");
            }
            $this->_trees[$tree] = $object;

            return $this->_trees[$tree];
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such tree '{$tree}'", null, $e);
        }
    }

    /**
     * @param string $blob
     * @return Git\Object\Blob
     */
    public function getBlob($blob)
    {
        $blob = (string) $blob;
        if (isset($this->_blobs[$blob])) {
            return $this->_blobs[$blob];
        }

        try {
            $object = $this->getObject($blob);
            if (!$object instanceof Git\Object\Blob) {
                throw new Exception\RuntimeException("Hash '{$blob}' is not a blob.");
            }
            $this->_blobs[$blob] = $object;

            return $this->_blobs[$blob];
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException("No such blob '{$blob}'", null, $e);
        }
    }

    public function getObject($hash)
    {
        $hash = (string) $hash;
        $path = $this->_repositoryPath . "/objects/{$hash[0]}{$hash[1]}/" . substr($hash, 2);

        if (file_exists($path)) {
            $raw = gzuncompress(file_get_contents($path));

            $type = substr($raw, 0, strpos($raw, ' '));
            $offset = strpos($raw, "\0") + 1;
            switch ($type) {
            case Git\Object::BLOB:
                return new Git\Object\Blob($hash, substr($raw, $offset));
                break;

            case Git\Object::TREE:
                return new Git\Object\Tree($hash, substr($raw, $offset));
                break;

            case Git\Object::COMMIT:
                return new Git\Object\Commit($hash, substr($raw, $offset));
                break;

            default:
                throw new Exception\RuntimeException("Invalid object type for '{$hash}'");
            }
        }

        if (isset($this->_packIndex[$hash])) {
            $file = $this->_packIndex[$hash];
            $offset = $this->_packOffsets[$file][$hash];

            $hashList = array_keys($this->_packOffsets[$file]);
            $index = array_search($hash, $hashList);
            if ((count($hashList) - 1) == $index) {
                $length = filesize($file) - $offset;
            } else {
                $length = $this->_packOffsets[$file][$hashList[$index + 1]] - $offset;
            }

            if (!$pack = fopen($file, 'rb')) {
                throw new Exception\RuntimeException("Could not open pack file '{$file}'");
            }
            flock($pack, LOCK_SH);

            if (($toc = fread($pack, 4)) === false) {
                throw new Exception\RuntimeException("Could not read pack file '{$file}'");
            }

            if ($toc !== 'PACK') {
                flock($pack, LOCK_UN);
                fclose($pack);

                throw new Exception\RuntimeException("Invalid pack format for file '{$file}'");
            }
            fseek($pack, $offset, SEEK_SET);

            $char = ord(fgetc($pack));
            $type = ($char >> 4) & 0x07;
            $size = $char & 0x0F;
            for ($i = 4; $char & 0x80; $i += 7) {
                $char = ord(fgetc($pack));
                $size |= ($char << $i);
            }

            $rawContent = fread($pack, $compressedLength);

            flock($pack, LOCK_UN);
            fclose($pack);

            switch ($type) {
            case Git\Object::OBJ_BLOB:
                return new Git\Object\Blob($hash, gzuncompress($rawContent));
                break;

            case Git\Object::OBJ_TREE:
                return new Git\Object\Tree($hash, gzuncompress($rawContent));
                break;

            case Git\Object::OBJ_COMMIT:
                return new Git\Object\Commit($hash, gzuncompress($rawContent));
                break;

            case Git\Object::OBJ_TAG:
                return new Git\Object\Tag($hash, gzuncompress($rawContent));
                break;

            case Git\Object::OBJ_OFS_DELTA:
            case Git\Object::OBJ_REF_DELTA:
                throw new Exception\RuntimeException("OBJ_OFS_DELTA and OBJ_REF_DELTA not implemented.");
                break;

            default:
                throw new Exception\RuntimeException("Invalid object type for '{$hash}'");
                break;
            }
        }

        throw new Exception\RuntimeException("Could not find object '{$hash}'");
    }

    /**
     *
     */
    private function _indexPackFile(\SplFileInfo $file)
    {
        if (!$index = fopen($file->getPathname(), 'rb')) {
            throw new Exception\RuntimeException("Could not open pack file '{$file}'");
        }

        $packFile = "{$file->getPath()}/{$file->getBasename('.idx')}.pack";

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

                    $this->_packOffsets[$packFile][$hashes[$i]] = $offset;
                    $this->_packIndex[$hashes[$i]] = "{$file->getPath()}/{$file->getBasename('.idx')}.pack";
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

                    $this->_packOffsets[$packFile][$hash] = $offset;
                    $this->_packIndex[$hash] = "{$file->getPath()}/{$file->getBasename('.idx')}.pack";
                }
            }
        }
        flock($index, LOCK_UN);
        fclose($index);

        asort($this->_packOffsets[$packFile]);

        return array();
    }
}
