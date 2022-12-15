<?php
class DBInteractionsAmi{
    public static function insertFFMOrder($provider,$reference,$id_carrier,$id_order,$price,$scenario,$allscenarios,$code, $service,$FFM_Selected_Num,$id_customer,$id_cart){
        $req = 'insert into ' . _DB_PREFIX_ . 'ffm_selected (`provider`,`reference`, `date_add`, `date_upd`,`id_carrier`, `id_order`, `price`, `scenario`, `allscenarios`, `code`, `service`, `FFM_Selected_Num`,`id_customer`,`id_cart`) VALUES(' . $provider . ', "' . $reference . '", NOW(), NOW(), "' . $id_carrier . '", "' . $id_order . '", "' . $price . '", "' . $scenario . '", "' . $allscenarios . '", "' . $code . '", "' . $service . '", "' . $FFM_Selected_Num . '", "' . $id_customer . '", "' . $id_cart . '")';
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }
    public static function getCarrierLst(){
        $sql= 'select ca.id_carrier, ca.name FROM '._DB_PREFIX_.'carrier ca where not ca.deleted and not is_module';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getOrderStates(){
        $sql='select id_order_state, name from '._DB_PREFIX_.'order_state_lang where id_lang=1';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getBasicConfigs(){
        $sql='select * from ' . _DB_PREFIX_ . 'ami_basic_configs';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function setBasicConfigs($api_key, $baseUrl,$status_cmd, $carrier){
        $req='update ' . _DB_PREFIX_ . 'ami_basic_configs set api_key="'.$api_key.'" , baseUrl="'.$baseUrl.'", status_cmd="'.$status_cmd.'", carrier="'.$carrier.'"';
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }

    public static function getCronDays($currentDay){
        $sql='select store_number, store_client_account, cronDays from '._DB_PREFIX_.'ami_stores WHERE cronDays LIKE "%'.$currentDay.'%"';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getAmiOrders($store_number, $store_client_account){
        $sql='select ami.articles as article, ami.mini, ami.maxi, addr.address1, addr.address2, c.id_customer as store_client_id, addr.id_address FROM '._DB_PREFIX_.'ami_config ami, '._DB_PREFIX_.'customer c, '._DB_PREFIX_.'address addr WHERE c.email="'.$store_client_account.'" and ami.targeted_stores LIKE "%'.$store_number.'%" and addr.id_customer=c.id_customer';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function setcronDays($store_number, $cronDays, $email){
        $req = 'update '._DB_PREFIX_.'ami_stores set cronDays="' . $cronDays .'", store_client_account="'.$email.'" where store_number='.$store_number;
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }
    public static function getStoreConfig($store_number){
        $sql = 'select cronDays, store_client_account from '._DB_PREFIX_.'ami_stores where store_number=' . $store_number;
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function init_amiStores($store_number){
        $req = 'insert into '._DB_PREFIX_.'ami_stores (store_number) value ('.$store_number.')';
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }
    public static function setNewConfig($id_mc, $articles, $mini, $maxi, $stores){
        $req='insert into '._DB_PREFIX_.'ami_config (model_code, articles,mini, maxi, targeted_stores) VALUES('.$id_mc.', "'.$articles.'", '.$mini.', '.$maxi.', "'.substr($stores,0,-1).'")';
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }
    public static function updateConfig($id,$id_mc, $articles, $mini, $maxi, $stores){
        $req='update '._DB_PREFIX_.'ami_config set model_code='.$id_mc.', articles='.$articles.',mini='.$mini.', maxi='.$maxi.', targeted_stores="'.$stores.'" where id='.$id;
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }
    public static function getCurrentConfig($model_code){
        $sql= 'select id, articles, mini, maxi, targeted_stores from '._DB_PREFIX_.'ami_config where model_code='.$model_code;
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getStores(){
        $sql='select s.store_number, l.name
        from '._DB_PREFIX_.'store s
        left join '._DB_PREFIX_.'store_lang l on (s.id_store = l.id_store)
        where l.id_lang=1 and store_number!=1949';

        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getModelCodes(){
        $sql= 'select id_code_model from etl_article_lang';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getArticlesByMC($id_mc){
        $sql='select art.id_code_article, art.life_cycle, l.web_label 
        from etl_article art 
        left join etl_model_lang l on (art.id_code_model = l.id_code_model)
        where art.id_code_model = ' . $id_mc;
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function getConfigHistory(){
        $sql='select distinct conf.model_code, etl.web_label
        from '._DB_PREFIX_.'ami_config conf
        left join etl_model_lang etl on (conf.model_code = etl.id_code_model)';
        if ($results = Db::getInstance()->executeS($sql)){
            return $results;
        }
    }
    public static function deleteConfig($codeModel){
        $req='delete from '._DB_PREFIX_.'ami_config where model_code=' . $codeModel;
        if (!Db::getInstance()->execute($req)){
            return false;
        }else{
            return true;
        }
    }   
}