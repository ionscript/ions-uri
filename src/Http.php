<?php

namespace Ions\Uri;

/**
 * Class Http
 * @package Ions\Uri
 */
class Http extends Uri
{
    /**
     * @var array
     */
    protected static $validSchemes = [
        'http',
        'https'
    ];

    /**
     * @var array
     */
    protected static $defaultPorts = [
        'http'  => 80,
        'https' => 443,
    ];

    /**
     * @var string
     */
    protected $user;

    /**
     * @var string
     */
    protected $password;

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = null === $user ? null : (string) $user;

        $this->buildUserInfo();

        return $this;
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = null === $password ? null : (string) $password;

        $this->buildUserInfo();

        return $this;
    }

    /**
     * @param $userInfo
     * @return $this
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = null === $userInfo ? null : (string) $userInfo;

        $this->parseUserInfo();

        return $this;
    }

    /**
     * @param $host
     * @param int $allowed
     * @return bool
     */
    public static function validateHost($host, $allowed = self::HOST_DNS_OR_IPV4_OR_IPV6)
    {
        return parent::validateHost($host, $allowed);
    }

    /**
     * @return null|void
     */
    protected function parseUserInfo()
    {
        // No user information? we're done
        if (null === $this->userInfo) {
            $this->setUser(null);
            $this->setPassword(null);
            return null;
        }

        // If no ':' separator, we only have a username
        if (false === strpos($this->userInfo, ':')) {
            $this->setUser($this->userInfo);
            $this->setPassword(null);
            return null;
        }

        // Split on the ':', and set both user and password
        list($this->user, $this->password) = explode(':', $this->userInfo, 2);
    }

    /**
     * @return void
     */
    protected function buildUserInfo()
    {
        if (null !== $this->password) {
            $this->userInfo = $this->user . ':' . $this->password;
        } else {
            $this->userInfo = $this->user;
        }
    }

    /**
     * @return string
     */
    public function getPort()
    {
        if (empty($this->port)) {
            if (array_key_exists($this->scheme, static::$defaultPorts)) {
                return static::$defaultPorts[$this->scheme];
            }
        }
        return $this->port;
    }

    /**
     * @param $uri
     * @return $this
     */
    public function parse($uri)
    {
        parent::parse($uri);

        if (empty($this->path)) {
            $this->path = '/';
        }

        return $this;
    }
}
