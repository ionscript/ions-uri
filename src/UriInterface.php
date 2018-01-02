<?php

namespace Ions\Uri;

/**
 * Interface UriInterface
 * @package Ions\Uri
 */
interface UriInterface
{
    /**
     * UriInterface constructor.
     * @param null $uri
     */
    public function __construct($uri = null);

    /**
     * @return mixed
     */
    public function isValid();

    /**
     * @return mixed
     */
    public function isValidRelative();

    /**
     * @return mixed
     */
    public function isAbsolute();

    /**
     * @param $uri
     * @return mixed
     */
    public function parse($uri);

    /**
     * @return mixed
     */
    public function toString();

    /**
     * @return mixed
     */
    public function normalize();

    /**
     * @param $baseUri
     * @return mixed
     */
    public function makeRelative($baseUri);

    /**
     * @return mixed
     */
    public function getScheme();

    /**
     * @return mixed
     */
    public function getUserInfo();

    /**
     * @return mixed
     */
    public function getHost();

    /**
     * @return mixed
     */
    public function getPort();

    /**
     * @return mixed
     */
    public function getPath();

    /**
     * @return mixed
     */
    public function getQuery();

    /**
     * @return mixed
     */
    public function getQueryAsArray();

    /**
     * @return mixed
     */
    public function getFragment();

    /**
     * @param $scheme
     * @return mixed
     */
    public function setScheme($scheme);

    /**
     * @param $userInfo
     * @return mixed
     */
    public function setUserInfo($userInfo);

    /**
     * @param $host
     * @return mixed
     */
    public function setHost($host);

    /**
     * @param $port
     * @return mixed
     */
    public function setPort($port);

    /**
     * @param $path
     * @return mixed
     */
    public function setPath($path);

    /**
     * @param $query
     * @return mixed
     */
    public function setQuery($query);

    /**
     * @param $fragment
     * @return mixed
     */
    public function setFragment($fragment);

    /**
     * @return mixed
     */
    public function __toString();
}
