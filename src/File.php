<?php

namespace Ions\Uri;

/**
 * Class File
 * @package Ions\Uri
 */
class File extends Uri
{
    /**
     * @var array
     */
    protected static $validSchemes = ['file'];

    /**
     * @return bool
     */
    public function isValid()
    {
        if ($this->query) {
            return false;
        }

        return parent::isValid();
    }

    /**
     * @param $userInfo
     * @return $this
     */
    public function setUserInfo($userInfo)
    {
        return $this;
    }

    /**
     * @param $fragment
     * @return $this
     */
    public function setFragment($fragment)
    {
        return $this;
    }

    /**
     * @param $path
     * @return static
     */
    public static function fromUnixPath($path)
    {
        $url = new static('file:');
        if ($path[0] === '/') {
            $url->setHost('');
        }

        $url->setPath($path);
        return $url;
    }

    /**
     * @param $path
     * @return static
     */
    public static function fromWindowsPath($path)
    {
        $url = new static('file:');

        $path = str_replace(['/', '\\'], ['%2F', '/'], $path);

        if (preg_match('|^([a-zA-Z]:)?/|', $path)) {
            $url->setHost('');
        }

        $url->setPath($path);
        return $url;
    }
}
