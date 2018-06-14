<?php

/**
 * Request 常用工具
 * 
 */
class E_Request {

    /**
     *  根据二级域名获取当前应用的前缀
     */
    public static function getAppPrefix() {

        preg_match_all('/^https?:\/\/(.*)\..*\..*$/i', E_Request::currentUrl(), $ret);
        return isset($ret[1][0]) ? $ret[1][0] : '';
    }

    /**
     * 是否是程序员在公司局域网内操作
     * 
     * @return boolean   true是；false不是
     */
    public static function isProgrammer() {
        $cip = trim(self::clientIp());

        if (isset($_SERVER['HTTP_HOST']) && ("10.5.3.62:82" == trim($_SERVER['HTTP_HOST']))) {
            return true;
        }

        return E_Ip::netMatch($cip, Extconfig::$intelnalIp);
    }

    /**
     * 是否是生产环境
     * 
     * @return boolean   true生产环境；false不是
     */
    public static function isOnline() {
        $serverName = self::server('SERVER_NAME');
        return '10.5.3' !== substr($serverName, 0, strrpos($serverName, '.'));
    }

    /**
     * Get Client Ip
     *
     * @param string $default
     * @return string
     */
    public static function clientIp($default = '0.0.0.0') {
        $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $ips = explode(',', $_SERVER[$key], 1);
            $ip = $ips[0];
            if (false != ip2long($ip) && long2ip(ip2long($ip) === $ip)) {
                return $ips[0];
            }
        }

        return $default;
    }

    /**
     * Return current url
     *
     * @return string
     */
    public static function currentUrl() {
        $url = 'http';

        if ('on' == self::server('HTTPS')) {
            $url .= 's';
        }

        $url .= "://" . self::server('SERVER_NAME');

        $port = self::server('SERVER_PORT');
        if (80 != $port) {
            $url .= ":{$port}";
        }

        return $url . self::server('REQUEST_URI');
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key get value by key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns NULL if key does not exist
     */
    public static function server($key = NULL, $default = NULL) {
        if (NULL === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

}
