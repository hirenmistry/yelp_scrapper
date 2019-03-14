<?php

class YulpScrapper {

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
    private $scrapingUrl;

    public static function getProxyList() {
        $cmdresponse = exec('/usr/bin/python3 ' . realpath('scrap_yulp.py'));
        return json_decode($cmdresponse, true);
    }

    private function getHtmlFromUrl() {
        $proxy_list = static::getProxyList();

        $result = $this->fileGetContentsWithProxy($this->scrapingUrl, $proxy_list[array_rand($proxy_list)], static::$user_agents_list[array_rand(static::$user_agents_list)]);

        if (empty($result)) {

            $result = $this->fileGetContentsWithProxy($this->scrapingUrl, $proxy_list[array_rand($proxy_list)], static::$user_agents_list[array_rand(static::$user_agents_list)]);
        }
        return $result;
    }

    private function getReviewMessage($review_match_data) {
        $regex_review_message = '#\<p lang="en">(.+?)\<\/p\>#s';
        preg_match_all($regex_review_message, $review_match_data, $matches_review_message);

        if (!empty($matches_review_message[1])) {
            return trim($matches_review_message[1][0]);
        }
        return '';
    }

    private function getReviewDate($review_match_data) {
        $regex_review_date = '#\<span class=\"rating-qualifier\">(.+?)\<\/span\>#s';
        preg_match_all($regex_review_date, $review_match_data, $matches_review_date);

        if (!empty($matches_review_date[1])) {
            return trim($matches_review_date[1][0]);
        }
        return date('Y-m-d');
    }

    private function getReviewUserName($review_match_data) {
        $regex_review_user_name = '#\<li class=\"user-name\">(.+?)\<\/li\>#s';
        preg_match_all($regex_review_user_name, $review_match_data, $matches_review_user_name);

        $regex_review_user_name_sub = '#\<a class=\"user-display-name js-analytics-click\" .*?>(.+?)\<\/a\>#s';
        preg_match_all($regex_review_user_name_sub, $matches_review_user_name[1][0], $matches_review_user_name_sub);

        if (!empty($matches_review_user_name_sub[1])) {
            return trim($matches_review_user_name_sub[1][0]);
        }
        return '';
    }

    private function getReviewRating($review_match_data) {
        $regex_review_rating = '#\<img class=\"offscreen\" height=\"303\" .*? alt=\"(.+?)">#s';
        preg_match_all($regex_review_rating, $review_match_data, $matches_review_rating);

        if (!empty($matches_review_rating[1])) {
            return trim(str_replace(" star rating", "", $matches_review_rating[1][0]));
        }
        return 0;
    }

    private function getTotalReviewCount($reviewPageHtml) {
        $regex_total_review_main = '#\<div class="biz-page-header-left claim-status">(.+?)<div class=\"biz-page-header-right u-relative\">#s';
        preg_match_all($regex_total_review_main, $reviewPageHtml, $matches_total_review_main);

        $regex_total_review = '#\<span class=\"review-count rating-qualifier\">(.+?)\<\/span\>#s';
        preg_match_all($regex_total_review, $matches_total_review_main[1][0], $matches_total_review);
        if (!empty($matches_total_review_main[1])) {
            return $matches_total_review[1][0];
        }
        return 0;
    }

    private function getReviews() {
        $result = $this->getHtmlFromUrl();
        $reviewPost = array();
        $regex_review_sidebar = '#\<div class=\"review review--with-sidebar\" data-review-id=(.+?)\<div class=\"review-footer clearfix\">#s';
        preg_match_all($regex_review_sidebar, $result, $matches_review_sidebar);

        if (!empty($matches_review_sidebar[0])) {
            foreach ($matches_review_sidebar[0] as $key => $review_match_data) {
                $reviewPost['data'][$key] = array(
                    'message' => $this->getReviewMessage($review_match_data),
                    'review_date' => $this->getReviewDate($review_match_data),
                    'review_user_name' => $this->getReviewUserName($review_match_data),
                    'rating' => $this->getReviewRating($review_match_data)
                );
            }
        }
        $reviewPost['total_reviews'] = $this->getTotalReviewCount($result);
        $reviewPost['base_url'] = $this->scrapingUrl;
        return json_encode($reviewPost);
    }

    private function fileGetContentsWithProxy($url, $proxyIpAndPort, $useragent) {
        $context_array = [
            "http" => [
                "proxy" => "tcp://" . $proxyIpAndPort,
                "User-Agent: " . $useragent,
                "request_fulluri" => true
            ]
        ];

//    $context_array = array('http'=>array('proxy'=>$proxy,'request_fulluri'=>true));
        $context = stream_context_create($context_array);

        // Use context stream with file_get_contents
        $data = file_get_contents($url, false, $context);

        // Return data via proxy
        return $data;
    }

    public function __construct($bizurl) {
        $this->scrapingUrl = $bizurl;
    }

    function fetchReviews() {
        return $this->getReviews();
    }

}
