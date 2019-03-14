<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//This is sample url you can see***************************************************
//localhost/scrapping/yelp_scrapper/base_yelp_scrapping.php?url=https://www.yelp.com/biz/hudson-grille-atlanta

$res_company['bizurl']=$_GET['url'];
if(!empty($res_company['bizurl'])){
    echo getReviewData1($res_company);
    
}else{
    $data['msg']="please add url";
    $data['data']=array();
    echo json_encode($data);
}
function getReviewData1($res_company){
    
    $proxy_list=array(
        "170.239.85.131:3128",
        "177.126.0.202:8080",
        "103.69.216.154:8080",
        "202.51.122.122:8080",
        "138.204.233.117:57754",
        "103.76.59.43:53281",
        "125.27.10.202:53259",
        "41.39.34.77:80"
        
        
    );
    
//    Please install python in your localhost 
//    paste scrap_yelp.py file in path similar as shown below
//    this python is just use to get dynamic proxy list and static user agent from it.
    $cmdresponse = exec('/usr/bin/python3 /var/www/html/scrapping/yelp_scrapper/scrap_yulp.py');
    $proxy_list_data=json_decode($cmdresponse,true);
  
   if(!empty($proxy_list_data['proxylist'])){
        $proxy_list=$proxy_list_data['proxylist'];
    }
    
    $user_agents_list=$proxy_list_data['user_agents'];
   
    $review_company_post=array();
        $review_url=$res_company['bizurl'];
//      $result = curl_post($review_url, array());
//      $result = file_get_contents($review_url);
//       $result = file_get_contents_proxy($review_url,"tcp://103.95.41.107:30433");
       
 
        
        $proxy_key=array_rand($proxy_list);
        
        $user_agents_key=array_rand($user_agents_list);
    
        $result = file_get_contents_proxy($review_url,"tcp://".$proxy_list[$proxy_key],$user_agents_list[$user_agents_key]);
       if(empty($result)){
       $proxy_key=array_rand($proxy_list);
        $user_agents_key=array_rand($user_agents_list);
        $result = file_get_contents_proxy($review_url,"tcp://".$proxy_list[$proxy_key],$user_agents_list[$user_agents_key]);
           
       }
       

   
     $regex_review_sidebar = '#\<div class=\"review review--with-sidebar\" data-review-id=(.+?)\<div class=\"review-footer clearfix\">#s';
     preg_match_all($regex_review_sidebar, $result, $matches_review_sidebar);
     if(!empty($matches_review_sidebar[0])){
         foreach($matches_review_sidebar[0] as $key=>$review_match_data){
           
             //get company review message form yelp:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
             $regex_review_message= '#\<p lang="en">(.+?)\<\/p\>#s';
             preg_match_all($regex_review_message, $review_match_data, $matches_review_message);
     
     $review_company_post['data'][$key]['message']=$matches_review_message[1][0];
     
       //get company review date form yelp:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
     $regex_review_date= '#\<span class=\"rating-qualifier\">(.+?)\<\/span\>#s';
             preg_match_all($regex_review_date, $review_match_data, $matches_review_date);
             $review_company_post['data'][$key]['review_date']=trim($matches_review_date[1][0]);
             
             
               //get company review user name form yelp:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
          $regex_review_user_name= '#\<li class=\"user-name\">(.+?)\<\/li\>#s';
             preg_match_all($regex_review_user_name, $review_match_data, $matches_review_user_name);
             
          $regex_review_user_name_sub= '#\<a class=\"user-display-name js-analytics-click\" .*?>(.+?)\<\/a\>#s';
             preg_match_all($regex_review_user_name_sub, $matches_review_user_name[1][0], $matches_review_user_name_sub);
                
             $review_company_post['data'][$key]['review_user_name']=$matches_review_user_name_sub[1][0];
             
             
             
                //get company review user name form yelp:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
          $regex_review_rating= '#\<img class=\"offscreen\" height=\"303\" .*? alt=\"(.+?)">#s';
             preg_match_all($regex_review_rating, $review_match_data, $matches_review_rating);
          
             $review_company_post['data'][$key]['rating']=trim(str_replace(" star rating", "",$matches_review_rating[1][0]));
            
//             $review_company_post['data'][$key]['review_date']=$matches_review_user_name[1][0];
          
         }
     }
     
       $regex_total_review_main= '#\<div class="biz-page-header-left claim-status">(.+?)<div class=\"biz-page-header-right u-relative\">#s';
             preg_match_all($regex_total_review_main, $result, $matches_total_review_main);
            
      $regex_total_review= '#\<span class=\"review-count rating-qualifier\">(.+?)\<\/span\>#s';
             preg_match_all($regex_total_review, $matches_total_review_main[1][0], $matches_total_review);
             $review_company_post['total_reviews']=$matches_total_review[1][0];
             
            
     $review_company_post['base_url']=$review_url;
     return json_encode($review_company_post);

            
            
}

function curl_post($addr, $postArray) {
    global $handle;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $addr);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postArray);
    // Download the given URL, and return output
    $output = curl_exec($ch) or die(curl_error($ch));
    // Close the cURL resource, and free system resources
    curl_close($ch);
    if ($output === false) {
        fwrite($handle, 'Error URL:' . $addr . PHP_EOL);
        fwrite($handle, 'Error:' . curl_error($ch) . ' - ' . curl_errno($ch) . PHP_EOL);
    }
    return $output;
}

function file_get_contents_proxy($url,$proxy,$useragent){

    // Create context stream
   
    $context_array = [
    "http" => [
        "proxy"  => $proxy,
        "method" => "GET",
        "header" => "Accept-language: en\r\n" .
        
            "Cookie: foo=bar\r\n".
            "User-Agent: ".$useragent ,
        "request_fulluri"=>true
    ]
];
     
//    $context_array = array('http'=>array('proxy'=>$proxy,'request_fulluri'=>true));
    $context = stream_context_create($context_array);

    // Use context stream with file_get_contents
    $data = file_get_contents($url,false,$context);
   
    // Return data via proxy
    return $data;

}