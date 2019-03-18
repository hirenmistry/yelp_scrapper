<?php

/**
 * Description of Proxer
 * This class is use to manage different proxy based calling
 * It has some of third party calls to grab the list of proxies
 * it also have some base calling functionalities to manage calls with proxy
 * @author hiren
 */
class ProxyCaller {

    private static $user_agents_list = [
        #Chrome
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        'Mozilla/5.0 (Windows NT 5.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36',
        #Firefox
        'Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1)',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
        'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 6.2; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
        'Mozilla/5.0 (Windows NT 6.1; Win64; x64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
        'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
        'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)'
    ];

    /**
     * Get list of some user agents
     * @return array of user agents
     */
    public static function getUserAgentList() {
        return static::$user_agents_list;
    }

    //By Python
    /**
     * @return array of proxy list
     */
    public static function getProxyList() {
        $cmdresponse = exec('/usr/bin/python3 ' . realpath('scrap_proxylist.py'));
        return json_decode($cmdresponse, true);
    }

    //By Api
    /**
     * @return array of proxy list
     */
    public static function getProxyByApi() {
        $proxy_api_url = "https://www.proxy-list.download/api/v1/get?type=https&anon=elite";
        $response = file_get_contents($proxy_api_url);
        return array_filter(explode("\r\n", $response));
    }

    //By PHP scrapper
    /**
     * @return array of proxy list
     */
    public static function getProxyArray() {
        $url = 'https://www.sslproxies.org/';
        $response = file_get_contents($url);
        preg_match_all("|<tbody>(.*?)</tbody>|s", $response, $alltrs);
        preg_match_all("|<tr>(.*?)</tr>|s", $alltrs[1][0], $alltds);
        $proxylist = array();
        foreach ($alltds[1] as $key => $value) {
            preg_match_all("|<td>(.*?)</td>|s", $value, $tds);
            $proxylist[] = $tds[1][0] . ':' . $tds[1][1];
        }
        return $proxylist;
    }

    /**
     * Get header response only from the specific URL
     * @param string $url
     * @param string $proxyIpPort
     * @param string $userAgent
     * @return string
     */
    public static function checkUrlWorkingWithProxy($url, $proxyIpPort, $userAgent) {
        $ch = curl_init($url);
        $cproxy = explode(":", $proxyIpPort);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, $cproxy[1]);
        curl_setopt($ch, CURLOPT_PROXYTYPE, 'HTTP');
        curl_setopt($ch, CURLOPT_PROXY, $cproxy[0]);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $c = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return $response_code;
    }

    /**
     * Get details from the url
     * @param string $url
     * @param string $proxyIpPort i.e. xxx.xxx.xxx.xxx:xx format
     * @param string $userAgent
     * @return string response of the provide URL
     * @throws LogicException if not able to grab data from URL
     */
    public static function getResponseFromUrl($url, $proxyIpPort, $userAgent, $allowThrowException = true) {
        $context_array = [
            "http" => [
                "proxy" => "tcp://" . $proxyIpPort,
                "User-Agent: " . $userAgent,
                "request_fulluri" => true,
                "timeout" => 10,
            ]
        ];
        
        //    $context_array = array('http'=>array('proxy'=>$proxy,'request_fulluri'=>true));
        $context = stream_context_create($context_array);

        // Use context stream with file_get_contents
        $data = file_get_contents($url, false, $context);

        //Check if any issue
        if ($allowThrowException && $data === FALSE) {
            throw new LogicException('Not able to grab data from ' . $url);
        }

        // Return data via proxy
        return $data;
    }

    private function __construct() {
        
    }

}
