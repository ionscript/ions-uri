<?php

namespace Ions\Uri;

/**
 * Class Uri
 * @package Ions\Uri
 *
 *    foo://example.com:8080/over/there?name=values#noise
 *    \_/   \______________/\_________/ \_________/ \__/
 *     |           |            |            |        |
 *  scheme     authority       path        query   fragment
 *     |   _____________________|__
 *    / \ /                        \
 *    urn:example:animal:ferret:nose
 *
 *
 *  URI  = scheme ":" part [ "?" query ] [ "#" fragment ]
 *  part = "//" authority path-empty
 *  / path-absolute
 *  / path-rootless
 *  / path-empty
 *
 */
class Uri implements UriInterface
{
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    const CHAR_GEN_DELIMS = ':\/\?#\[\]@';
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';
    const CHAR_RESERVED = ':\/\?#\[\]@!\$&\'\(\)\*\+,;=';

    const CHAR_QUERY_DELIMS = '!\$\'\(\)\*\,';

    const HOST_IPV4 = 0x01; //00001
    const HOST_IPV6 = 0x02; //00010
    const HOST_IPVANY = 0x07; //00111
    const HOST_DNS = 0x08; //01000
    const HOST_DNS_OR_IPV4 = 0x09; //01001
    const HOST_DNS_OR_IPV6 = 0x0A; //01010
    const HOST_DNS_OR_IPV4_OR_IPV6 = 0x0B; //01011
    const HOST_DNS_OR_IPVANY = 0x0F; //01111
    const HOST_REGNAME = 0x10; //10000
    const HOST_DNS_OR_IPV4_OR_IPV6_OR_REGNAME = 0x1B; //11011
    const HOST_ALL = 0x1F; //11111

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $userInfo;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $port;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $query;

    /**
     * @var string
     */
    protected $fragment;

    /**
     * @var string
     */
    protected $validHostTypes = self::HOST_ALL;

    /**
     * @var array
     */
    protected static $validSchemes = [];

    /**
     * @var array
     */
    protected static $defaultPorts = [];

    /**
     * Uri constructor.
     * @param null|string|UriInterface $uri
     * @throws \InvalidArgumentException
     */
    public function __construct($uri = null)
    {
        if (is_string($uri)) {
            $this->parse($uri);
        } elseif ($uri instanceof UriInterface) {
            // Copy constructor
            $this->setScheme($uri->getScheme());
            $this->setUserInfo($uri->getUserInfo());
            $this->setHost($uri->getHost());
            $this->setPort($uri->getPort());
            $this->setPath($uri->getPath());
            $this->setQuery($uri->getQuery());
            $this->setFragment($uri->getFragment());
        } elseif ($uri !== null) {
            throw new \InvalidArgumentException(sprintf(
                'Expecting a string or a URI object, received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            ));
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if ($this->host) {
            return !(strlen($this->path) > 0 && $this->path[0] !== '/');
        }

        if ($this->userInfo || $this->port) {
            return false;
        }

        if ($this->path) {
            return !(0 === strpos($this->path, '//'));
        }

        if (!($this->query || $this->fragment)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isValidRelative()
    {
        if ($this->scheme || $this->host || $this->userInfo || $this->port) {
            return false;
        }

        if ($this->path) {
            return !(0 === strpos($this->path, '//'));
        }

        if (!($this->query || $this->fragment)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isAbsolute()
    {
        return ($this->scheme !== null);
    }

    /**
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function reset()
    {
        $this->setScheme(null);
        $this->setPort(null);
        $this->setUserInfo(null);
        $this->setHost(null);
        $this->setPath(null);
        $this->setFragment(null);
        $this->setQuery(null);
    }

    /**
     * @param $uri
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function parse($uri)
    {
        $this->reset();

        // scheme
        if (($scheme = self::parseScheme($uri)) !== null) {
            $this->setScheme($scheme);
            $uri = substr($uri, strlen($scheme) + 1) ?: '';
        }

        // authority
        if (preg_match('|^//([^/\?#]*)|', $uri, $match)) {
            $authority = $match[1];
            $uri = substr($uri, strlen($match[0]));

            // userInfo and host
            if (strpos($authority, '@') !== false) {
                $segments = explode('@', $authority);
                $authority = array_pop($segments);
                $userInfo = implode('@', $segments);
                unset($segments);
                $this->setUserInfo($userInfo);
            }

            $nMatches = preg_match('/:[\d]{1,5}$/', $authority, $matches);
            if ($nMatches === 1) {
                $portLength = strlen($matches[0]);
                $port = substr($matches[0], 1);

                $this->setPort((int)$port);
                $authority = substr($authority, 0, -$portLength);
            }

            $this->setHost($authority);
        }

        if (!$uri) {
            return $this;
        }

        // path
        if (preg_match('|^[^\?#]*|', $uri, $match)) {
            $this->setPath($match[0]);
            $uri = substr($uri, strlen($match[0]));
        }

        if (!$uri) {
            return $this;
        }

        // query
        if (preg_match('|^\?([^#]*)|', $uri, $match)) {
            $this->setQuery($match[1]);
            $uri = substr($uri, strlen($match[0]));
        }
        if (!$uri) {
            return $this;
        }

        // fragment
        if ($uri && $uri[0] === '#') {
            $this->setFragment(substr($uri, 1));
        }

        return $this;
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function toString()
    {
        if (!$this->isValid()) {
            if ($this->isAbsolute() || !$this->isValidRelative()) {
                throw new \InvalidArgumentException(
                    'URI is not valid and cannot be converted into a string'
                );
            }
        }

        $uri = '';

        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }

        if ($this->host !== null) {
            $uri .= '//';
            if ($this->userInfo) {
                $uri .= $this->userInfo . '@';
            }
            $uri .= $this->host;
            if ($this->port) {
                $uri .= ':' . $this->port;
            }
        }

        if ($this->path) {
            $uri .= static::encodePath($this->path);
        } elseif ($this->host && ($this->query || $this->fragment)) {
            $uri .= '/';
        }

        if ($this->query) {
            $uri .= '?' . static::encodeQueryFragment($this->query);
        }

        if ($this->fragment) {
            $uri .= '#' . static::encodeQueryFragment($this->fragment);
        }

        return $uri;
    }

    /**
     * @return $this
     */
    public function normalize()
    {
        if ($this->scheme) {
            $this->scheme = static::normalizeScheme($this->scheme);
        }

        if ($this->host) {
            $this->host = static::normalizeHost($this->host);
        }

        if ($this->port) {
            $this->port = static::normalizePort($this->port, $this->scheme);
        }

        if ($this->path) {
            $this->path = static::normalizePath($this->path);
        }

        if ($this->query) {
            $this->query = static::normalizeQuery($this->query);
        }

        if ($this->fragment) {
            $this->fragment = static::normalizeFragment($this->fragment);
        }

        if ($this->host && empty($this->path)) {
            $this->path = '/';
        }

        return $this;
    }

    /**
     * @param $baseUri
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function resolve($baseUri)
    {
        // Ignore if URI is absolute
        if ($this->isAbsolute()) {
            return $this;
        }

        if (is_string($baseUri)) {
            $baseUri = new static($baseUri);
        } elseif (!$baseUri instanceof Uri) {
            throw new \InvalidArgumentException(
                'Provided base URI must be a string or a Uri object'
            );
        }

        if ($this->getHost()) {
            $this->setPath(static::removePathDotSegments($this->getPath()));
        } else {
            $basePath = $baseUri->getPath();
            $relPath = $this->getPath();
            if (!$relPath) {
                $this->setPath($basePath);
                if (!$this->getQuery()) {
                    $this->setQuery($baseUri->getQuery());
                }
            } else {
                if ($relPath[0] === '/') {
                    $this->setPath(static::removePathDotSegments($relPath));
                } else {
                    if ($baseUri->getHost() && !$basePath) {
                        $mergedPath = '/';
                    } else {
                        $mergedPath = substr($basePath, 0, strrpos($basePath, '/') + 1);
                    }
                    $this->setPath(static::removePathDotSegments($mergedPath . $relPath));
                }
            }

            // Set the authority part
            $this->setUserInfo($baseUri->getUserInfo());
            $this->setHost($baseUri->getHost());
            $this->setPort($baseUri->getPort());
        }

        $this->setScheme($baseUri->getScheme());
        return $this;
    }

    /**
     * @param $baseUri
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function makeRelative($baseUri)
    {
        $baseUri = new static($baseUri);

        $this->normalize();
        $baseUri->normalize();

        $host = $this->getHost();
        $baseHost = $baseUri->getHost();
        if ($host && $baseHost && ($host !== $baseHost)) {
            return $this;
        }

        $port = $this->getPort();
        $basePort = $baseUri->getPort();
        if ($port && $basePort && ($port !== $basePort)) {
            return $this;
        }

        $scheme = $this->getScheme();
        $baseScheme = $baseUri->getScheme();
        if ($scheme && $baseScheme && ($scheme !== $baseScheme)) {
            return $this;
        }

        $this
            ->setHost(null)
            ->setPort(null)
            ->setScheme(null);

        if ($this->getPath() === $baseUri->getPath()) {
            $this->setPath('');
            return $this;
        }

        $pathParts = preg_split('|(/)|', $this->getPath(), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $baseParts = preg_split('|(/)|', $baseUri->getPath(), null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $matchingParts = array_intersect_assoc($pathParts, $baseParts);

        foreach ($matchingParts as $index => $segment) {

            if ($index && !isset($matchingParts[$index - 1])) {
                array_unshift($pathParts, '../');
                continue;
            }

            unset($pathParts[$index]);
        }

        $this->setPath(implode($pathParts));

        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getQueryAsArray()
    {
        $query = [];

        if ($this->query) {
            parse_str($this->query, $query);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function setScheme($scheme)
    {
        if (($scheme !== null) && (!self::validateScheme($scheme))) {
            throw new \InvalidArgumentException(sprintf(
                'Scheme "%s" is not valid or is not accepted by %s',
                $scheme,
                get_class($this)
            ));
        }

        $this->scheme = $scheme;

        return $this;
    }

    /**
     * @param $userInfo
     * @return $this
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
        return $this;
    }

    /**
     * @param $host
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setHost($host)
    {
        if (($host !== '')
            && ($host !== null)
            && !self::validateHost($host, $this->validHostTypes)
        ) {
            throw new \InvalidArgumentException(sprintf(
                'Host "%s" is not valid or is not accepted by %s',
                $host,
                get_class($this)
            ));
        }

        $this->host = $host;
        return $this;
    }

    /**
     * @param $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query)
    {
        if (is_array($query)) {
            $query = str_replace('+', '%20', http_build_query($query));
        }

        $this->query = $query;
        return $this;
    }

    /**
     * @param $fragment
     * @return $this
     */
    public function setFragment($fragment)
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param $scheme
     * @return bool
     */
    public static function validateScheme($scheme)
    {
        if (!empty(static::$validSchemes)
            && !in_array(strtolower($scheme), static::$validSchemes, true)
        ) {
            return false;
        }

        return (bool)preg_match('/^[A-Za-z][A-Za-z0-9\-\.+]*$/', $scheme);
    }

    /**
     * @param $userInfo
     * @return bool
     */
    public static function validateUserInfo($userInfo)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':]+|%[A-Fa-f0-9]{2})*$/';
        return (bool)preg_match($regex, $userInfo);
    }

    /**
     * @param $host
     * @param int $allowed
     * @return bool
     */
    public static function validateHost($host, $allowed = self::HOST_ALL)
    {
        if ($allowed & self::HOST_IPVANY) {
            if (static::isValidIpAddress($host, $allowed)) {
                return true;
            }
        }

        if ($allowed & self::HOST_REGNAME) {
            if (static::isValidRegName($host)) {
                return true;
            }
        }

        if ($allowed & self::HOST_DNS) {
            if (static::isValidDnsHostname($host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $port
     * @return bool
     */
    public static function validatePort($port)
    {
        if ($port === 0) {
            return false;
        }

        if ($port) {
            $port = (int)$port;
            if ($port < 1 || $port > 0xffff) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $path
     * @return bool
     */
    public static function validatePath($path)
    {
        $pchar = '(?:[' . self::CHAR_UNRESERVED . ':@&=\+\$,]+|%[A-Fa-f0-9]{2})*';
        $segment = $pchar . "(?:;{$pchar})*";
        $regex = "/^{$segment}(?:\/{$segment})*$/";
        return (bool)preg_match($regex, $path);
    }

    /**
     * @param $input
     * @return bool
     */
    public static function validateQueryFragment($input)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})*$/';
        return (bool)preg_match($regex, $input);
    }

    /**
     * @param $userInfo
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function encodeUserInfo($userInfo)
    {
        if (!is_string($userInfo)) {
            throw new \InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($userInfo) ? get_class($userInfo) : gettype($userInfo))
            ));
        }

        $regex = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:]|%(?![A-Fa-f0-9]{2}))/';

        $replace = function ($match) {
            return static::escape($match[0]);
        };

        return preg_replace_callback($regex, $replace, $userInfo);
    }

    /**
     * @param $path
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function encodePath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($path) ? get_class($path) : gettype($path))
            ));
        }

        $regex = '/(?:[^' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/';

        $replace = function ($match) {
            return static::escape($match[0]);
        };

        return preg_replace_callback($regex, $replace, $path);
    }

    /**
     * @param $input
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function encodeQueryFragment($input)
    {
        if (!is_string($input)) {
            throw new \InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($input) ? get_class($input) : gettype($input))
            ));
        }

        $regex = '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/';

        $replace = function ($match) {
            return static::escape($match[0]);
        };

        return preg_replace_callback($regex, $replace, $input);
    }

    /**
     * @param $uriString
     * @return null
     * @throws \InvalidArgumentException
     */
    public static function parseScheme($uriString)
    {
        if (!is_string($uriString)) {
            throw new \InvalidArgumentException(sprintf(
                'Expecting a string, got %s',
                (is_object($uriString) ? get_class($uriString) : gettype($uriString))
            ));
        }

        if (preg_match('/^([A-Za-z][A-Za-z0-9\.\+\-]*):/', $uriString, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * @param $path
     * @return bool|string
     */
    public static function removePathDotSegments($path)
    {
        $output = '';

        while ($path) {
            if ($path === '..' || $path === '.') {
                break;
            }

            switch (true) {
                case ($path === '/.'):
                    $path = '/';
                    break;
                case ($path === '/..'):
                    $path = '/';
                    $lastSlashPos = strrpos($output, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $output = substr($output, 0, $lastSlashPos);
                    break;
                case (0 === strpos($path, '/../')):
                    $path = '/' . substr($path, 4);
                    $lastSlashPos = strrpos($output, '/', -1);
                    if (false === $lastSlashPos) {
                        break;
                    }
                    $output = substr($output, 0, $lastSlashPos);
                    break;
                case (0 === strpos($path,'/./')):
                    $path = substr($path, 2);
                    break;
                case (0 === strpos($path, './')):
                    $path = substr($path, 2);
                    break;
                case (0 === strpos($path, '../')):
                    $path = substr($path, 3);
                    break;
                default:
                    $slash = strpos($path, '/', 1);
                    if ($slash === false) {
                        $seg = $path;
                    } else {
                        $seg = substr($path, 0, $slash);
                    }

                    $output .= $seg;
                    $path = substr($path, strlen($seg));
                    break;
            }
        }

        return $output;
    }

    /**
     * @param $baseUri
     * @param $relativeUri
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function merge($baseUri, $relativeUri)
    {
        $uri = new static($relativeUri);
        return $uri->resolve($baseUri);
    }

    /**
     * @param $host
     * @param $allowed
     * @return bool
     */
    protected static function isValidIpAddress($host, $allowed)
    {
        if($allowed === static::HOST_ALL || $allowed === static::HOST_IPV4) {
            // only IPv4
            $return = static::validateIPv4($host);
            if ($return) {
                return true;
            }
        }

        if($allowed === static::HOST_ALL || $allowed === static::HOST_IPV6) {
            // IPv6
            static $regex = '/^\[.*\]$/';
            return (preg_match($regex, $host) && static::validateIPv6($host));
        }

        return false;
    }

    /**
     * @param $value
     * @return bool
     */
    protected static function validateIPv4($value)
    {
        if (preg_match('/^([01]{8}\.){3}[01]{8}\z/i', $value)) {
            // binary  00000000.00000000.00000000.00000000
            $value = bindec(substr($value, 0, 8)) . '.' . bindec(substr($value, 9, 8)) . '.'
                . bindec(substr($value, 18, 8)) . '.' . bindec(substr($value, 27, 8));
        } elseif (preg_match('/^([0-9]{3}\.){3}[0-9]{3}\z/i', $value)) {
            // octet 777.777.777.777
            $value = (int)substr($value, 0, 3) . '.' . (int)substr($value, 4, 3) . '.'
                . (int)substr($value, 8, 3) . '.' . (int)substr($value, 12, 3);
        } elseif (preg_match('/^([0-9a-f]{2}\.){3}[0-9a-f]{2}\z/i', $value)) {
            // hex ff.ff.ff.ff
            $value = hexdec(substr($value, 0, 2)) . '.' . hexdec(substr($value, 3, 2)) . '.'
                . hexdec(substr($value, 6, 2)) . '.' . hexdec(substr($value, 9, 2));
        }

        $ip2long = ip2long($value);
        if ($ip2long === false) {
            return false;
        }

        return ($value === long2ip($ip2long));
    }

    /**
     * @param $value
     * @return bool|int
     */
    protected static function validateIPv6($value)
    {
        if (strlen($value) < 3) {
            return $value === '::';
        }

        if (strpos($value, '.')) {
            $lastcolon = strrpos($value, ':');
            if (!($lastcolon && static::validateIPv4(substr($value, $lastcolon + 1)))) {
                return false;
            }

            $value = substr($value, 0, $lastcolon) . ':0:0';
        }

        if (strpos($value, '::') === false) {
            return preg_match('/\A(?:[a-f0-9]{1,4}:){7}[a-f0-9]{1,4}\z/i', $value);
        }

        $colonCount = substr_count($value, ':');
        if ($colonCount < 8) {
            return preg_match('/\A(?::|(?:[a-f0-9]{1,4}:)+):(?:(?:[a-f0-9]{1,4}:)*[a-f0-9]{1,4})?\z/i', $value);
        }

        if ($colonCount === 8) {
            return preg_match('/\A(?:::)?(?:[a-f0-9]{1,4}:){6}[a-f0-9]{1,4}(?:::)?\z/i', $value);
        }

        return false;
    }

    /**
     * @param $host
     * @return bool
     */
    protected static function isValidDnsHostname($host)
    {
        if (!is_string($host)) {
            return false;
        }

        if (((preg_match('/^[0-9.]*$/', $host) && strpos($host, '.') !== false) || (preg_match('/^[0-9a-f:.]*$/i', $host) && strpos($host, ':') !== false)) && static::isValidIpAddress($host)) {
            return true;
        }

        return false;
    }

    /**
     * @param $host
     * @return bool
     */
    protected static function isValidRegName($host)
    {
        $regex = '/^(?:[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ':@\/\?]+|%[A-Fa-f0-9]{2})+$/';
        return (bool)preg_match($regex, $host);
    }

    /**
     * @param $scheme
     * @return string
     */
    protected static function normalizeScheme($scheme)
    {
        return strtolower($scheme);
    }

    /**
     * @param $host
     * @return string
     */
    protected static function normalizeHost($host)
    {
        return strtolower($host);
    }

    /**
     * @param $port
     * @param null $scheme
     * @return null
     */
    protected static function normalizePort($port, $scheme = null)
    {
        if ($scheme
            && isset(static::$defaultPorts[$scheme])
            && ($port === static::$defaultPorts[$scheme])
        ) {
            return null;
        }

        return $port;
    }

    /**
     * @param $path
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected static function normalizePath($path)
    {
        $path = self::encodePath(
            self::decodeUrlEncodedChars(
                self::removePathDotSegments($path),
                '/[' . self::CHAR_UNRESERVED . ':@&=\+\$,\/;%]/'
            )
        );

        return $path;
    }

    /**
     * @param $query
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected static function normalizeQuery($query)
    {
        $query = self::encodeQueryFragment(
            self::decodeUrlEncodedChars(
                $query,
                '/[' . self::CHAR_UNRESERVED . self::CHAR_QUERY_DELIMS . ':@\/\?]/'
            )
        );

        return $query;
    }

    /**
     * @param $fragment
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected static function normalizeFragment($fragment)
    {
        $fragment = self::encodeQueryFragment(
            self::decodeUrlEncodedChars(
                $fragment,
                '/[' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]/'
            )
        );

        return $fragment;
    }

    /**
     * @param $input
     * @param string $allowed
     * @return mixed
     */
    protected static function decodeUrlEncodedChars($input, $allowed = '')
    {
        $decodeCb = function ($match) use ($allowed) {
            $char = rawurldecode($match[0]);
            if (preg_match($allowed, $char)) {
                return $char;
            }
            return strtoupper($match[0]);
        };

        return preg_replace_callback('/%[A-Fa-f0-9]{2}/', $decodeCb, $input);
    }

    /**
     * @param $string
     * @return string
     */
    public static function escape($string)
    {
        return rawurlencode($string);
    }
}
