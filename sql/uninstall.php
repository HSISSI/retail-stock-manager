<?php

$sqls = array();
$sqls[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ami_stores`';
$sqls[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ami_config`';
$sqls[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ami_basic_configs`';

foreach($sqls as $sql){
    if(!Db::getInstance()->execute($sql))
        return false;
}