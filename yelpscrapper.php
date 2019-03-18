<?php

include_once './proxycaller.php';

class YulpScrapper {

    private static $user_agents_list = [];
    private $scrapingUrl;

    // <editor-fold defaultstate="collapsed" desc="Review Grabbing logic which is private">
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

    private function getReviewUserImage($review_match_data) {

        $regex_user_image_base = '#\<div class=\"ypassport media-block\">(.+?)\<\/div\>#s';
        preg_match_all($regex_user_image_base, $review_match_data, $matches_review_user_name_base);

        $regex_user_image = '#\<img .*? class="photo-box-img" height="60" src=\"(.+?)"#s';
        preg_match_all($regex_user_image, $matches_review_user_name_base[1][0], $matches_user_image);

        if (!empty($matches_user_image[1])) {
            return $matches_user_image[1][0];
        }
        return "";
    }

    private function getReviewUserLocation($review_match_data) {
        $regex_review_location = '#\<li class=\"user-location responsive-hidden-small\">(.+?)\<\/li\>#s';
        preg_match_all($regex_review_location, $review_match_data, $matches_review_location);
        $regex_location_name = '#\<b>(.+?)\<\/b\>#s';
        preg_match_all($regex_location_name, $matches_review_location[1][0], $matches_location_name);
        if (!empty($matches_location_name[1])) {
            return $matches_location_name[1][0];
        }
        return "";
    }

    private function getTotalReviewCount($reviewPageHtml) {
        $regex_total_review = '|<span class="review-count rating-qualifier">(.*?)</span>|s';
        preg_match_all($regex_total_review, $reviewPageHtml, $matches_total_review);
        if (!empty($matches_total_review[1])) {
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
                    'reviewerimage' => $this->getReviewUserImage($review_match_data),
                    'reviewerlocation' => $this->getReviewUserLocation($review_match_data),
                    'rating' => $this->getReviewRating($review_match_data),
                );
            }
        }
        $reviewPost['total_reviews'] = $this->getTotalReviewCount($result);
        $reviewPost['base_url'] = $this->scrapingUrl;
        return $reviewPost;
    }

    // </editor-fold>

    
    private function getHtmlFromUrl() {
        $proxy_list = ProxyCaller::getProxyList();
        $result = ProxyCaller::getResponseFromUrl($this->scrapingUrl, $proxy_list[array_rand($proxy_list)], static::$user_agents_list[array_rand(static::$user_agents_list)], FALSE);
        if ($result === FALSE || empty($result)) {
            $result = ProxyCaller::getResponseFromUrl($this->scrapingUrl, $proxy_list[array_rand($proxy_list)], static::$user_agents_list[array_rand(static::$user_agents_list)], FALSE);
        }
        return $result;
    }

    
    public function __construct($bizurl) {
        $this->scrapingUrl = $bizurl;
        if (empty(static::$user_agents_list)) {
            static::$user_agents_list = ProxyCaller::getUserAgentList();
        }
    }

    public function fetchReviews() {
        return $this->getReviews();
    }

}
