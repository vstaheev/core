<?php
/**
 * RequestInfo.
 *
 * @author lunatic <lunatic@jetstyle.ru>
 */
class RequestInfo
{
    public static $host = '';
    public static $hostProt = '';
    
    protected static $ip;
    
    const STATE_USE = 0;
    const STATE_IGNORE = 1;
    const METHOD_GET = "get";
    const METHOD_POST = "post";

    public static $pageUrl = '';
    public static $baseUrl = '';
    public static $baseFull = '';

    public static $baseDomain = '';
    public static $cookieDomain = '';

    private static $data = array();
    private static $params = array();
    private static $denyForeignPosts = false;
    private static  $_compiled_ready = false;
    private static  $_compiled = array();
    private static  $values = array();

    private function __construct(){}

    /**
     * �������������
     *
     */
    public static function init()
    {
        if (self::$denyForeignPosts)
        {
            if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST')
            {
                if ($_SERVER['HTTP_HOST'] OR $_ENV['HTTP_HOST'])
                {
                    $http_host = ($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']);
                }
                else if ($_SERVER['SERVER_NAME'] OR $_ENV['SERVER_NAME'])
                {
                    $http_host = ($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_ENV['SERVER_NAME']);
                }
                if ($http_host AND $_SERVER['HTTP_REFERER'])
                {
                    $referrer_parts = parse_url($_SERVER['HTTP_REFERER']);
                    $http_host = preg_replace('#^www\.#i', '', $http_host);
                    $thishost = preg_quote($http_host . !empty($referrer_parts['port']) ? ":$referrer_parts[port]" : '', '#');
                    $refhost = $referrer_parts['host'] . !empty($referrer_parts['port']) ? ":$referrer_parts[port]" : '';

                    if (!preg_match('#' . $thishost . '$#siU', $refhost))
                    {
                        throw new JSException("POST requests from foreign hosts are not allowed.");
                    }
                }
            }
        }

        if (get_magic_quotes_gpc())
        {
            self::fuckQuotes($_POST);
            self::fuckQuotes($_GET);
            self::fuckQuotes($_COOKIE);
            self::fuckQuotes($_REQUEST);
        }

        self::$host = preg_replace('/:.*/','',$_SERVER["HTTP_HOST"]);
        self::$hostProt = "http://".$_SERVER["HTTP_HOST"];
        self::$baseFull = "http://".self::$host.Config::get('base_url');
        self::$baseUrl  = Config::get('base_url');

        self::$pageUrl = $_REQUEST['page'];

        self::load($_GET);
        self::free('page');

        self::$baseDomain = preg_replace("/^www\./i", "", $_SERVER["SERVER_NAME"]);
        self::$cookieDomain = strpos(self::$baseDomain, '.') === false ? false : "." . self::$baseDomain;
    }

    /**
     * �������� ����������
     *
     * @param array $values
     */
    public static function load($values)
    {
        if(!is_array($values) || empty($values))    return;
        foreach($values AS $key => $value)
        {
            self::$data[$key] = $value;
        }
    }

    public static function free($key)
    {
        unset(self::$data[$key]);
    }


    public static function get($key)
    {
        return self::$data[$key];
    }

    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }

    public static function href()
    {
        return self::hrefChange('', array());
    }

    /**
     * ���������� / ��������� / �������� ���������� �� ����
     *
     * @param string $url 
     * @param array $key
     * @return string
     *
     * OR
     *
     * @param array $keys - to add to current url
     */
    public static function hrefChange($url, $key)
    {
        if (is_array($url))
        {
            $key = $url;
            unset($url);
        }
        
        if(!$url)    $url = self::$baseUrl.self::$pageUrl;
        if (!is_array($key)) $key = array();

        $d = self::$data;

        foreach($key AS $k => $v)
        {
            if ($d[$k])
            {
                $d[$k] = $v;
            }
            else if ($v)
            {
                $d[$k] = $v;
            }
        }

        foreach($d AS $k => $v)
        {
            if ($v !='' )   //skip vars like rubric_id=[nothing] and pass rubric_id=0
            {
                if (is_array($v))
                {
                    $dd = array();
                    foreach($v AS $kv => $vv)
                    {
                        if ($vv)
                        {
                            $dd[] = $k.'['.urlencode($kv).']='.urlencode($vv);
                        }
                    }

                    if (!empty($dd))
                    {
                        $d[$k] = implode('&', $dd);
                    }
                    else
                    {
                        unset($d[$k]);
                    }
                }
                else
                {
                    $d[$k] = $k.'='.urlencode($v);
                }
            }
            else
            {
                unset($d[$k]);
            }
        }

        if (is_array($d))
        {
            $line = @implode('&', $d);
            if ($line)
            {
                $url = $url.'?'.$line;
            }
        }

        return $url;
    }

    public static function pack( $method=self::METHOD_GET, $filter = array() ) // -- ��������� � ������ ��� GET/POST �������
    {
        if (!self::$_compiled_ready)
        {
            self::$_compiled[self::METHOD_GET ] = "";
            self::$_compiled[self::METHOD_POST] = "";
            $f=0;
            foreach(self::$data AS $k=>$v)
                if( $v != '' )
                    if (!count($filter) || in_array($k, $filter))
                    {
                        if (is_array($v))
                        {
                            $v0 = array_map(htmlspecialchars, $v);
                            $v1 = array_map(urlencode, $v);
                        }
                        else
                        {
                            $v0 = htmlspecialchars($v);
                            $v1 = urlencode($v);
                        }
                        if ($f) self::$_compiled[self::METHOD_GET ] .= '&'; else $f=1;
                        self::$_compiled[self::METHOD_GET ] .= $k."=".$v1;
                        self::$_compiled[self::METHOD_POST] .= "<input type='hidden' name='".$k."' value='".$v0."' />\n";
                    }
            self::$_compiled_ready = 1;
        }
        return self::$_compiled[$method];
    }

    private static function fuckQuotes(&$a)
    {
        if (is_array($a))
            foreach ($a AS $k => &$v)
                if (is_array($v))
                    self::fuckQuotes($v);
                else
                    $v = stripslashes($v);
    }
    
        
    public static function getIp() {
        if (!self::$ip) {
            if ($_SERVER['HTTP_CLIENT_IP']) {
                self::$ip = $_SERVER['HTTP_CLIENT_IP'];
            }
            else if ($_SERVER['HTTP_X_FORWARDED_FOR'] && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
            {
                // make sure we dont pick up an internal IP defined by RFC1918
                foreach ($matches[0] AS $ip)
                {
                    if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip))
                    {
                        self::$ip = $ip;
                        break;
                    }
                }
            }
            else if ($_SERVER['HTTP_FROM'])
            {
                self::$ip = $_SERVER['HTTP_FROM'];
            }
            else
            {
                self::$ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        return self::$ip;
    }
    
}
?>
