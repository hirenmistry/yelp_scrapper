<?php

require_once './yelpscrapper.php';
header('Content-Type: application/json');
if (key_exists('url', $_GET)) {
    $bizurl = $_GET['url'];
    $ysObj = new YulpScrapper($bizurl);
    echo $ysObj->fetchReviews();
} else {
    $data['msg'] = "please add url";
    $data['data'] = array();
    echo json_encode($data);
}