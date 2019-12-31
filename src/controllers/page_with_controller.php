<?php
global $store,$reactor;
$result = 1+1;
//this script is running on sandbox mode you have 2 variables:
//$reactor: is a instance of OnePagePHP\Reactor class in this case it was defined in loader.php
//$store: is a instance of OnePagePHP\Store class in this case it was defined in loader.php
//$params: it content the parameters defined in the url
//use "global $my_var" to access to global variables
$store->addServerVariable("result",$result);//use {{result}} to render this value
$reactor->addScript("console.log('page loaded')");//run this script on page load
