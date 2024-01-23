<?php
require_once _PS_MODULE_DIR_.'ami/models/DBInteractionsAmi.php';
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ami extends Module{
    public function __construct(){
        $this->name= 'ami';
        $this->author ='Decathlon Morocco by HSISSI Youssef';
        $this->version = '1.0.0';
        $this->tab = 'others';
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName =$this->l('Auto AMI Module');
        $this->description = $this->l('This module helps to control and manage AMI products stock for each store.');
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => '1.7.99',
        ];
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    public function install(){
        include_once($this->local_path.'sql/install.php');

        // Install Tabs
		$parent_tab = new Tab();
		// Need a foreach for the language
		$parent_tab->name[$this->context->language->id] = $this->l('Produits AMI');
		$parent_tab->class_name = 'AdminMainAmi';
		$parent_tab->id_parent = 0; // Home tab
		$parent_tab->module = $this->name;
		$parent_tab->add();

		$tab = new Tab();
		// Need a foreach for the language
		$tab->name[$this->context->language->id] = $this->l('Auto AMI');
		$tab->class_name = 'AdminAmi';
		$tab->id_parent = $parent_tab->id;
		$tab->module = $this->name;
		$tab->add();

        return parent::install() && $this->init_amiStores();
    }

    public function uninstall(){
        include_once($this->local_path.'sql/uninstall.php');

        // Uninstall Tabs
		$moduleTabs = Tab::getCollectionFromModule($this->name);
		if (!empty($moduleTabs)) {
			foreach ($moduleTabs as $moduleTab) {
				$moduleTab->delete();
			}
		}
        return parent::uninstall();
    }

    public function getContent(){
        if(isset($_GET['action']) && $_GET['action']!=null && $_GET['action']!=""){
            
            $action=$_GET['action'];
            if($action =='getModelCodes'){
                $this->getModelCodes();
            }else if($action =='getArticlesByMC'){
                $this->getArticlesByMC(Tools::getValue('id_mc'));
            }else if($action =='getStores'){
                $this->getStores();
            }else if($action =='setNewConfig'){
                $this->setNewConfig(json_decode(stripslashes(Tools::getValue('articles_config')), true) );
            }else if($action == 'getConfigHistory'){
                $this->getConfigHistory();
            }else if($action=='editConfig'){
                $this->editConfig(Tools::getValue('model_code'));
            }else if($action=='deleteConfig'){
                $this->deleteConfig(Tools::getValue('model_code'));
            }else if($action=='updateConfig'){
                $this->updateConfig(json_decode(stripslashes(Tools::getValue('articles_config')), true));
            }else if($action=='setBasicConfigs'){
                $this->setBasicConfigs(Tools::getValue('api_key'),Tools::getValue('baseUrl'),Tools::getValue('status_cmd'),Tools::getValue('carrier'));
            }else if($action=='getCarrierLst'){
                $this->getCarrierLst();
            }else if($action=='getOrderStates'){
                $this->getOrderStates();
            }else if($action=='getBasicConfigs'){
                $this->getBasicConfigs();
            }
            exit;
        }

        $this->context->smarty->assign("conf_url" ,  Context::getContext()->link->getAdminLink('AdminModules').'&configure='.$this->name);
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/ami_config.js');
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/notify.js');
        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }
    
    public function getCarrierLst(){
        $current_carrier = '';
        if($current_configs = DBInteractionsAmi::getBasicConfigs()){
            $current_carrier = current_configs[0]['id_carrier'];
        }
        $carrier_lst_html = '<option value=""></option>';
        if($res = DBInteractionsAmi::getCarrierLst()){
            foreach($res as $carrier){
                $selected = ($carrier['id_carrier'] == $current_carrier)?'selected':'';
                $carrier_lst_html .= '<option value="'.$carrier['id_carrier'].'" '.$selected.' >'.$carrier['name'].'</option>';
            }
        }
        echo $carrier_lst_html;
    }
    public function getOrderStates(){
        $current_state = '';
        if($current_configs = DBInteractionsAmi::getBasicConfigs()){
            $current_state = current_configs[0]['status_cmd'];
        }
        $order_states_lst_html = '<option value=""></option>';
        if($res = DBInteractionsAmi::getOrderStates()){
            foreach($res as $order_state){
                $selected = ($order_state['id_order_state'] == $current_state)?'selected':'';
                $order_states_lst_html .= '<option value="'.$order_state['id_order_state'].'" '.$selected.' >'.$order_state['name'].'</option>';  
            }
        }
        echo $order_states_lst_html;
    }
    public function init_amiStores(){
        if($stores = DBInteractionsAmi::getStores()){
            foreach($stores as $store){
                if(!DBInteractionsAmi::init_amiStores($store['store_number'])){
                    return false;
                }
            }
        return true;
        }
    }
    public function getBasicConfigs(){
        $config_details = array();
        if($res = DBInteractionsAmi::getBasicConfigs()){
            $config_details['api_key'] = $res[0]['api_key'];
            $config_details['baseUrl'] = $res[0]['baseUrl'];
            $config_details['status_cmd'] = $res[0]['status_cmd'];
            $config_details['carrier'] = $res[0]['id_carrier'];
        }
        echo json_encode($config_details);
    }
    public function setBasicConfigs($api_key, $baseUrl,$status_cmd, $carrier){
        if(!DBInteractionsAmi::setBasicConfigs($api_key, $baseUrl,$status_cmd, $carrier)){
            return false;
        }
        return true;
    }
    
    public function editConfig($model_code){
        $config_details = array();
        $lst_articles_html='';
        $lst_stores ='<div class="checkbox"><label><input type="radio" value="1" name="checkStores">Tous les magasins</label><label><input type="radio" value="0" name="checkStores">reset all</label></div>';
        if($currentConfig = DBInteractionsAmi::getCurrentConfig($model_code)){
            $articles_details = DBInteractionsAmi::getArticlesByMC($model_code);
            $stores = DBInteractionsAmi::getStores();
            $targeted_stores = explode('|', $currentConfig[0]['targeted_stores']);
            foreach($currentConfig as $row){
                foreach($articles_details as $article){
                    if(in_array($row['articles'],$article)){
                        $web_label = $article['web_label'];
                        $life_cycle = $article['life_cycle'];
                        $lst_articles_html .= '<tr><td>'.$row['articles'].'</td><td>'.$web_label.'</td><td>'.$life_cycle.'</td><td><input type="text" value="'.$row['mini'].'" name="mini"></td><td><input type="text" value="'.$row['maxi'].'" name="maxi"></td><td><input name="id" type="hidden" value="'.$row['id'].'"></td></tr>';
                    }
                }
            }
            
            foreach($stores as $store){
                $is_checked= '';
                if(in_array($store['store_number'],$targeted_stores)){
                    $is_checked= ' checked';
                }
                $lst_stores .='<div class="checkbox">
                    <label><input type="checkbox" name="store" '.$is_checked.' value="'.$store['store_number'].'">'.$store['name'].'</label>
                  </div>';
            }

            $config_details['model_code'] = $model_code;
            $config_details['lst_articles'] = $lst_articles_html;
            $config_details['listStores'] = $lst_stores;
        }
        echo json_encode($config_details);
    }

    public function deleteConfig($model_code){
        if(!DBInteractionsAmi::deleteConfig($model_code)){
            return false;
        }
        return;
    }

    public function getConfigHistory(){
        if($configs = DBInteractionsAmi::getConfigHistory()){
            $conf_history_html ='';
            foreach($configs as $conf){
                $conf_history_html.='<tr>
                                        <td><input type="text" value="'.$conf['model_code'].'" readonly="true" name="model_code"/></td>
                                        <td>'.$conf['web_label'].'</td>
                                        <td>
                                            <a class="editConfig" href=""><svg class="btn btn-default" style="width: 35px;padding:5px !important;" enable-background="new 0 0 512 512" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path d="M485.469,26.562c23.656,23.656,32.344,58.484,22.625,90.516l-33.938,33.938L361,37.875l33.938-33.938  C426.969-5.813,461.781,2.89,485.469,26.562z M451.5,173.64L202.594,422.531L0,512l89.453-202.625L338.375,60.515L451.5,173.64z   M146.938,412.125L99.86,365.031l-37.219,84.313L146.938,412.125z M512,480H223v32h289V480z"/></svg></a>
                                        </td>
                                        <td>
                                            <a class="deleteConfig" href=""><svg class="btn btn-default" style="width: 35px;padding:0 !important;"color="red" version="1.1" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="m256.01 204.64 100.12-100.15 51.344 51.33-100.12 100.15-51.344-51.329z" fill="red"/><path d="m155.83 407.48-51.344-51.358 100.16-100.13 51.344 51.358-100.16 100.13z" fill="red"/><path d="m407.5 356.11-51.373 51.358-100.12-100.15 51.373-51.358 100.12 100.15z"/><path d="m104.5 155.86 51.337-51.351 100.15 100.12-51.337 51.351-100.15-100.12z"/><path d="m255.98 307.36-51.351-51.365 51.365-51.351 51.351 51.365-51.365 51.351z" fill="red"/></svg></a>
                                        </td>
                                    </tr>';
            }
            echo $conf_history_html;
        }
    }

    public function setNewConfig($articles_config){
        foreach($articles_config as $conf){
            if(!DBInteractionsAmi::setNewConfig($conf['mc'], $conf['article'], $conf['mini'], $conf['maxi'], $conf['stores'])){
                return false;
            }
        }
        return true;
    }
    public function updateConfig($articles_config){
        foreach($articles_config as $conf){
            if(!DBInteractionsAmi::updateConfig($conf['id'],$conf['mc'], $conf['article'], $conf['mini'], $conf['maxi'], $conf['stores'])){
                return false;
            }
        }
        return true;
    }

    public function getModelCodes(){
        if($mcs = DBInteractionsAmi::getModelCodes()){
            $mc_html = '';
            foreach($mcs as $mc){
                $mc_html .= '<option value="'.$mc['id_code_model'].'">';
            }
            echo $mc_html;
        }
    }
    public function getArticlesByMC($id_mc){
        $lst_articles_html = '';
        if($articles = DBInteractionsAmi::getArticlesByMC($id_mc)){
            foreach($articles as $article){
                $lst_articles_html .= '<tr><td>'.$article['id_code_article'].'</td><td>'.$article['web_label'].'</td><td>'.$article['life_cycle'].'</td><td><input type="text" value="" placeholder="ex:5" name="mini"></td><td><input type="text" value="" placeholder="ex:10" name="maxi"></td><td><input name="id" type="hidden" value=""></td></tr>';
            }
        }
        echo $lst_articles_html;
    }

    public function getStores(){
        if($stores = DBInteractionsAmi::getStores()){
            $lst_stores ='<div class="checkbox">
            <label><input type="radio" value="1" name="checkStores" >Tous les magasins</label>
            <label><input type="radio" value="0" name="checkStores" >reset all</label>
          </div>';
            foreach($stores as $store){
                $lst_stores .='<div class="checkbox">
                <label><input type="checkbox" name="store" value="'.$store['store_number'].'">'.$store['name'].'</label>
              </div>';
            }
            echo $lst_stores;
        }
    }

    //--------------------------- create order --------------------------
    public function checkStock($stores,$grantToken, $context, $api_key, $products){
        $result = [];
        $baseUrl = Configuration::get('DECA_STOCK_URL');

        $productQuery = '';
        if (is_array($products)) {
            foreach ($products as $product => $value) {
                if (empty($productQuery)) {
                    $productQuery .= "item=${value}";
                } else {
                    $productQuery .= "&item=${value}";
                }
            }
        } else {
            $productQuery .= "item=${products}";
        }
        $url = "${baseUrl}${stores}/items/stocks/sale/pictures?${productQuery}";
        $aHttpHeaders = [
            'x-api-key: ' . Configuration::get('DECA_STOCK_KEY'),
            'Authorization: Bearer ' . $grantToken,
        ];

        
        if (false !== ($ch = curl_init())) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHttpHeaders);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, self::CURL_CONNECT_TIMEOUT); // maximum amount of time that is allowed to make the connection to the server.
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, self::CURL_EXEC_TIMEOUT); // is a maximum amount of time in seconds to which the execution of individual cURL extension function calls will be limited. Note that the value for this setting should include the value for CURLOPT_CONNECTTIMEOUT

            $response = curl_exec($ch);
            
            if (false !== $response) {
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if (404 == $httpcode) {
                    $response = "{'stock':0}";
                }

                if (200 != $httpcode && 404 != $httpcode) {
                    throw new Exception('Error getting Bearer - HTTP ' . $httpcode);
                }

                $result = json_decode($response);

                if (empty($result)) {
                    $response = "{'stock':0}";
                }

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new Exception('Unable to parse response body into JSON: ' . json_last_error());
                }
            } else {
                throw new Exception('Error reaching stock api');
            }
        } else {
            throw new Exception('Curl init failed.');
        }

        return $result;
        /*$result = [];
        $basic_configs = DBInteractionsAmi::getBasicConfigs();
        $productQuery = "";
        if (is_array($products)) {
            foreach ($products as $product => $value){
                if(empty($productQuery)){
                    $productQuery .= "item=${value}";
                }else {
                    $productQuery .= "&item=${value}";
                }
            }
        } else {
            $productQuery .= "item=${products}";
        }
        $url = "${context}retail-stock-pictures/api/v1/stores/${stores}/items/stocks/sale/pictures?${productQuery}";
        $aHttpHeaders = [
            'x-api-key: '. $api_key,
            'Authorization: Bearer '.$grantToken,
        ];
        if(false !== ($ch = curl_init())) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHttpHeaders);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1000); // maximum amount of time that is allowed to make the connection to the server.
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5000); // is a maximum amount of time in seconds to which the execution of individual cURL extension function calls will be limited. Note that the value for this setting should include the value for CURLOPT_CONNECTTIMEOUT
            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if($httpcode == 200){
                $result = json_decode($response);
            }else {
                throw new Exception('Error reaching stock api');
            }
        }else {
            throw new Exception('Curl init failed.');
        }
        return $result;*/
    }
    public function createOrders(){
        $currentDay = date('N', strtotime(date('l')));
        if($storesCronDays = DBInteractionsAmi::getCronDays($currentDay)){
            echo 'storesCronDays:<br/><pre>';print_r($storesCronDays);echo '<pre>';echo '<br/> ---------------<br/>';
            foreach($storesCronDays as $store){
                if($amiOrders = DBInteractionsAmi::getAmiOrders($store['store_number'],$store['store_client_account'])){
                    $customer_id_address = DBInteractionsAmi::getCustomerIdAddress($store['store_client_account']);
                    echo 'amiOrders:<br/><pre>';print_r($amiOrders);echo '<pre>';echo '<br/> ---------------<br/>';
                    $grantToken = Module::getInstanceByName('deca_fedid')->getToken();
                    echo 'amiOrders - article: <br/><pre>';print_r(array_column($amiOrders,'article'));echo '<pre>';echo '<br/> ---------------<br/>';
                    $basic_configs = DBInteractionsAmi::getBasicConfigs();
                    echo "check stock : ";
                    
                    //check stock platforme
                    #$platforme_id = "1949";
                    #$stock_platforme = $this->checkStock($platforme_id,$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], array_column($amiOrders,'article'));
                    
                    //check stock retail
                    #$responses = $this->checkStock($store['store_number'],$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], array_column($amiOrders,'article'));
                    #$responses = $this->checkStock('1949',$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], '4480791' );
                    #echo '<pre>';print_r($responses);echo '<pre>';die;
                    foreach($amiOrders as $order){
                        echo "order:"; print_r($order);echo "<br/>";
                        #foreach($responses as $response){
                            #if(($response->item == $order['article']) && ((int)$response->stock <= (int)$order['mini'])){
                               
                                if(((int)$order['maxi'])>0){
                                     //TODO : check stock platforme before creating an order
                                    #$grantToken= "eyJhbGciOiJSUzI1NiIsImtpZCI6Ik1BSU4iLCJwaS5hdG0iOiI1In0.eyJzY29wZSI6WyJvcGVuaWQiLCJwcm9maWxlIl0sImF1dGhvcml6YXRpb25fZGV0YWlscyI6W10sImNsaWVudF9pZCI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwiaXNzIjoiaWRwZGVjYXRobG9uIiwianRpIjoiaHlzRXZVNXNrMk1HbFpUOWdid3MwbCIsInN1YiI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwib3JpZ2luIjoiY29ycG9yYXRlIiwiaWF0IjoxNzAwNTcxODM2LCJleHAiOjE3MDA1NzkwMzZ9.dpXnCJRPKKJ6kavPX6cRxc-KZaLFD4CfvbJmeecI84T8MolJMI7I0GPVyL8KVZddfNgiLtOBhthJD59tmC_Je4FiYuoXzSkfhi5Zfe7bGQJ7N9FVwuwQE5R1Heyx9GXtxyUEonXVkdCtvh2_95WIa7LtY1qkidgFbnbFZ0IqbH43INN7F2c0rehN-k2wXMvzKup-NE9IXuFCOmRwkpNsu02CCZ4MpJ_8uC-rRvwgF88Jn9dE7SuFTQWdWQy-TbiSWIEpLvM5wL0kubymQkpRo6O5I60zXIwznAsJs_xd1yqmZ5S_0VqgfFP5Uo8Yrzc85rJP82HYLADljnfvf_bXtw";
                                    #$stock_platforme = $this->checkStock($platforme_id,$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], $order['article']);

                                    echo 'order cretion starts **<br/>';    
                                    $product_id = DBInteractionsAmi::get_order_id_product($order['article']);
                                    if($this->ValidateOrder($store['store_number'],$order['store_client_id'], $customer_id_address[0]['id_address'], $basic_configs[0]['id_carrier'], $product_id[0]['id_product'], $order['maxi'], $basic_configs[0]['status_cmd'])){
                                        echo 'order creation ends';
                                    }
                                }
                            #}
                        #}
                    }
                }
            }
        }
        return;
    }
    public function ValidateOrder($store_number,$id_store_customer, $id_address, $id_carrier, $id_product, $product_quantity, $id_order_state){
        echo '<br/>id_product: ' . $id_product;
        echo '<br/>product_quantity: ' . $product_quantity;
        echo '<br/>id_order_state: ' . $id_order_state;
        echo '<br/>id_carrier: ' . $id_carrier;
        $module_name = 'cmi';
        $payment_module = Module::getInstanceByName($module_name);
        Context::getContext()->customer = new Customer((int) $id_store_customer);
        // Cart informations
        $new_cart = new Cart();
        $new_cart->id_customer = (int) $id_store_customer;
        $new_cart->id_address_delivery = (int) $id_address;
        $new_cart->id_address_invoice  = (int) $id_address;
        $new_cart->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        
        $new_cart->id_currency = 1;
        $new_cart->id_carrier = $id_carrier;
        $new_cart->add();
        $productAttributeID = Product::getDefaultAttribute($id_product);
        $new_cart->updateQty((int) $product_quantity, (int) $id_product, (int) $productAttributeID); // Added product_quantity to product with the id number id_product
        echo '<br/> product added';
        // Creating order from cart
        echo '<br/> cart id:'; echo $new_cart->id;
        #echo "<br/> Cart: <br/>";print_r($new_cart);
        $payment_module->validateOrder(
            (int) $new_cart->id,
            (int) $id_order_state,
            $new_cart->getOrderTotal(true, Cart::BOTH),
            $payment_module->displayName,
            'Test auto ami order'
        );
        #echo '<br/> validation result: '.$r.'<br/>';

        // Get the order id after creating it from the cart.
        $id_order = Order::getOrderByCartId($new_cart->id);
        echo '<br/>id order: '. $id_order;
        #echo "<br/>payment_module:<br/> ";
        #print_r($payment_module);
        #$new_order = new Order($id_order);
        #echo '<br/>current order:<br/>';
        #print_r($payment_module->currentOrder);
        if ($payment_module->currentOrder) {
            $provider_StoreId = '1949';
            $reference_StoreId = $store_number;
            $cost = '0';
            $scenario = $this->createScenario($provider_StoreId, $reference_StoreId, $id_carrier);
            $orderData = [
                'provider' => $provider_StoreId,
                'reference' => $reference_StoreId,
                'id_carrier'=> $id_carrier,
                'id_order' => $payment_module->currentOrder,
                'id_customer' => $new_cart->id_customer,
                'id_cart' => $new_cart->id,
                'price' => $new_cart->getOrderTotal(true, Cart::BOTH),
                'service' => "-",
                'scenario' => $scenario,
            ];
            $res=$this->insertFFMOrder($orderData);
            echo "<br/>finish creation:<br/>";
            print_r($res);
            return $res;            
        }
    }
    public function insertFFMOrder($orderData){
        $provider = $orderData['provider'];
        $reference = $orderData['reference'];
        $id_carrier = $orderData['id_carrier'];
        $id_order = $orderData['id_order'];
        $price = $orderData['price'];
        $scenario = $orderData['scenario'];
        $service = $orderData['service'];
        $id_customer = $orderData['id_customer'];
        $id_cart = $orderData['id_cart'];
        $allscenarios = '';
        $code = '2';
        $FFM_Selected_Num = '1';        
        if(!DBInteractionsAmi::insertFFMOrder($provider,$reference,$id_carrier,$id_order,$price,$scenario,$allscenarios,$code, $service,$FFM_Selected_Num,$id_customer,$id_cart)){
            return false;
        }
        return true;
    }

    public function createScenario($provider_StoreId, $reference_StoreId, $id_carrier){
        $id_lang = Context::getContext()->language->id;
       # $providerData = new Store((int)$provider_StoreId, $id_lang);
    
        $scenario = [];
        $carrier = new Carrier((int)$id_carrier,$id_lang);
        $carrierName = $carrier->name;
        #$service = $this->getService($id_delivery);

        $referenceData = new Store((int)$reference_StoreId, $id_lang);
        #$Methode_shipper = "Livraison standard - ARX";

        $scenario = [
        'PROVIDER' => $provider_StoreId, 
        'NAME' => $referenceData->name,
        'DESCRIPTION' => 'AMI auto generated order',
        'METHOD_SHIPPER' => $carrier->name,
        'METHOD_DESCR' => '',
        'STORE_ID' => $reference_StoreId,
        'STORE_NAME' => $referenceData->name,
        'STORE_ADDRESS1' => $referenceData->address1,
        'STORE_ADDRESS2' => $referenceData->address2,
        'STORE_CITY' => $referenceData->city,
        'STORE_STATE' => '',
        'STORE_ZIP' => $referenceData->postcode,
        'STORE_COUNTRY' => 'MOROCCO',
        'STORE_COUNTRYCODE' => 'ma',
        'STORE_REFERENCE' => $referenceData->store_number,
        'STORE_PHONE' => $referenceData->phone,
        'STORE_HOURS' => '',
        'TYPE' => '1',
        'COST' => '',
        'DELAY' => '',
        'id_carrier' => $id_carrier,
        ];
        return base64_encode(serialize($scenario));
    }
}