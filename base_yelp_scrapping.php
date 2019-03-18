<?php

require_once './yelpscrapper.php';
//header('Content-Type: application/json');
$data = array();
if (key_exists('url', $_GET)) {
    $bizurl = $_GET['url'];
    $ysObj = new YulpScrapper($bizurl);
    $data['data'] = $ysObj->fetchReviews();
    $data['msg'] = '';
    $data['success'] = true;
} else {
    $data['data'] = array();
    $data['msg'] = "please add url";
    $data['success'] = false;
}
echo '<pre>';
print_r($data);
echo '</pre>';