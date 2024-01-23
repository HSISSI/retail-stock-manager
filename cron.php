<?php
include dirname(__FILE__) . '/../../config/config.inc.php';
#include dirname(__FILE__) . '/../../config/pp_defines_custom.inc.php';
#include(dirname(__FILE__) . '/../../init.php');
include dirname(__FILE__) . '/ami.php';


set_time_limit(0);
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

if (!Module::isInstalled('ami')) {
    exit('Module ami is not install');
} else {
    $CreateOrders = new Ami();
    echo '******  Creating orders ********' . PHP_EOL;
    $CreateOrders->createOrders();
    echo 'Done creating' . PHP_EOL;
}