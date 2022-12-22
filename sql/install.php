<?php
$sqls=  array();
$sqls[]= 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ami_config`(
    `id` INT(10) AUTO_INCREMENT,
    `model_code` INT(10),
    `articles` INT(10) DEFAULT 0,
    `mini` INT(10) ,
    `maxi` INT(10) ,
    `targeted_stores` VARCHAR(255),
    PRIMARY KEY(`id`)
) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8';

$sqls[]= 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ami_stores`(
    `store_number` INT(10),
    `cronDays` VARCHAR(255) DEFAULT NULL,
    `store_client_account` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY(`store_number`)
) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8';

$sqls[]= 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ami_basic_configs`(
    `api_key` VARCHAR(255),
    `baseUrl` VARCHAR(255),
    `status_cmd` VARCHAR(255),
    `id_carrier` VARCHAR(255)
) ENGINE = '._MYSQL_ENGINE_.' DEFAULT CHARSET=UTF8';

$sql[]= 'insert into '._DB_PREFIX_.'ami_basic_configs values ("","","","")';

foreach($sqls as $sql){
    if(!Db::getInstance()->execute($sql))
        return false;
}