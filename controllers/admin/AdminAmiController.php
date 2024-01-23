<?php
require_once _PS_MODULE_DIR_.'ami/models/DBInteractionsAmi.php';

class AdminAmiController extends ModuleAdminController{
    public function __construct(){
        parent::__construct();
    }

    public function init(){
        parent::init();
        $this->bootstrap = true;
    }

    public function initContent(){
        $this->context->smarty->assign('contr_link',$this->context->link->getAdminLink('AdminAmi'));
        $this->setTemplate('ami.tpl');

        if(isset($_GET['action']) && $_GET['action']!=null && $_GET['action']!=""){
            $action=$_GET['action'];
            if($action=="getStores"){
                $this->getStores();
            }else if($action=="setcronDays"){
                $this->setcronDays(json_decode(stripslashes(Tools::getValue('cronDays')), true));
            }else if($action=="createOrders"){
                $this->createOrders();
            }
            exit;
        }
        parent::initContent();
    }

    public function setMedia(){
        parent::setMedia();
        $this->addJquery();
        $this->addJS(_MODULE_DIR_.'ami/views/js/ami.js');
        $this->addJS(_MODULE_DIR_.'ami/views/js/notify.js');
        $this->addCss(_MODULE_DIR_.'ami/views/css/ami.css');  
    }

    public function setcronDays($cronDays){
        foreach($cronDays as $store_cronDays){
            if(!DBInteractionsAmi::setcronDays($store_cronDays['store'], $store_cronDays['cronDays'], $store_cronDays['email'])){
                return false;
            }
        }
        return true;
    }
    public function Log2Console($tag, $msg){
        echo "<script>console.log('" .$tag. " : " . $msg . "' );</script>";
    }

    public function checkStock($store,$grantToken, $context, $api_key, $products){
        $result = [];
        $baseUrl = Configuration::get('DECA_STOCK_URL');
        $productQuery = '';
        if (is_array($products)) {
            foreach ($products as $product => $value) {
                $productQuery .= empty($productQuery)?"item=${value}":"&item=${value}";
            }
        } else {
            $productQuery .= "item=${products}";
        }
        $url = "${baseUrl}${store}/items/stocks/sale/pictures?${productQuery}";
        $aHttpHeaders = [
            'x-api-key: ' . Configuration::get('DECA_STOCK_KEY'),
            'Authorization: Bearer ' . $grantToken,
        ];

        if (false !== ($ch = curl_init())) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            // mandatory in local for api to work, test to comment the following two lines in production should it will work without.
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $headers = array();
            $headers[] = 'Accept: application/json;charset=UTF-8';
            $headers[] = 'Authorization: Bearer '. $grantToken;
            $headers[] = 'X-Api-Key: '. Configuration::get('DECA_STOCK_KEY');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
                }
            $result = json_decode(curl_exec($ch));
        } else {
            throw new Exception('Curl init failed.');
        }
        return $result;
    }
    public function createOrders(){
        $currentDay = date('N', strtotime(date('l')));
        if($storesCronDays = DBInteractionsAmi::getCronDays($currentDay)){
            foreach($storesCronDays as $store){
                if($amiOrders = DBInteractionsAmi::getAmiOrders($store['store_number'],$store['store_client_account'])){
                    $customer_id_address = DBInteractionsAmi::getCustomerIdAddress($store['store_client_account']);
                    $grantToken = Module::getInstanceByName('deca_fedid')->getToken();
                    $basic_configs = DBInteractionsAmi::getBasicConfigs();
                    
                    //to remove after tests
                    #$grantToken = "eyJhbGciOiJSUzI1NiIsImtpZCI6Ik1BSU4iLCJwaS5hdG0iOiI1In0.eyJzY29wZSI6WyJvcGVuaWQiLCJwcm9maWxlIl0sImF1dGhvcml6YXRpb25fZGV0YWlscyI6W10sImNsaWVudF9pZCI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwiaXNzIjoiaWRwZGVjYXRobG9uIiwianRpIjoiZGZkdFNCUVpyZHhiNnY1cXo1aFdUeSIsInN1YiI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwib3JpZ2luIjoiY29ycG9yYXRlIiwiaWF0IjoxNzA2MDI3ODQyLCJleHAiOjE3MDYwMzUwNDJ9.oW3cwym8cGWxr51NAiRbgVmfR9wJwIuvGHqi6HmYl7NRsjQ3V9PgT8F-5KdThVCbPViqG-Y4zuOcNxOaCS7EYoZOUUXw3CFArWbO29mE1jyuZFhNi423akUEtwFYHL153rthjJH4uEpbRM3XgrHQk75iFpj1tUskU5KbbdbDN2OTgt9ohlvoW-beFRzYmGqhFff-mmUPDtjty9Nr2STkg3255cTTEhEEf_gV4bvDsslBLu4wGpH1LhlW0xfkpFm-zX_WZbN-pC6ONzWM14Il_-eIoYMwDqupHBQcW7NqKh5Qr-9krbsWys5peFtKiTMYZXuxOxqESh2KAlT7UTZ-Xg";

                    //check stock retail
                    $responses = $this->checkStock($store['store_number'],$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], array_column($amiOrders,'article'));
                    foreach($amiOrders as $order){
                        foreach($responses as $response){
                            if(($response->item == $order['article']) && ((int)$response->stock <= (int)$order['mini'])){
                               
                                if(((int)$order['maxi'])>0){
                                     //check stock platforme before creating an order
                                    $platforme_id = "1949";
                                    $stock_platforme = $this->checkStock($platforme_id,$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], $order['article']);
                                    $stock_platforme = json_decode(json_encode($stock_platforme), true);
                                    if(((int)$stock_platforme[0]['stock'] > 0) && ((int)$stock_platforme[0]['stock'] > (int)$order['maxi'])){
                                        $product_id = DBInteractionsAmi::get_order_id_product($order['article']);
                                        if($this->ValidateOrder($store['store_number'],$order['store_client_id'], $customer_id_address[0]['id_address'], $basic_configs[0]['id_carrier'], $product_id[0]['id_product'], $order['maxi'], $basic_configs[0]['status_cmd'])){
                                            $this->Log2Console('success', 'order creation for article: ' . $product_id[0]['id_product'] . ' - store: '. $store['store_number']);
                                        }else{
                                            $this->Log2Console('error', 'order creation for article: ' . $product_id[0]['id_product'] . ' - store: '. $store['store_number']);
                                        }
                                    }else{
                                        $this->Log2Console('error', 'unsuffisant stock platforme for article: ' . $product_id[0]['id_product'] . ' - store: '. $store['store_number'] . 'availible: '. $stock_platforme[0]['stock']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return;
    }
    public function ValidateOrder($store_number,$id_store_customer, $id_address, $id_carrier, $id_product, $product_quantity, $id_order_state){
        $module_name = 'cmi';
        $payment_module = Module::getInstanceByName($module_name);
        Context::getContext()->customer = new Customer((int) $id_store_customer);
        // Cart informations
        $new_cart = new Cart();
        $new_cart->id_customer = (int) $id_store_customer;
        $new_cart->id_address_delivery = (int) $id_address;
        $new_cart->id_address_invoice  = (int) $id_address;
        $new_cart->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $new_cart->id_currency = $this->context->currency->id;
        $new_cart->id_carrier = $id_carrier;
        $new_cart->add();
        $productAttributeID = Product::getDefaultAttribute($id_product);
        $new_cart->updateQty((int) $product_quantity, (int) $id_product, (int) $productAttributeID); // Added product_quantity to product with the id number id_product
        // Creating order from cart
        $payment_module->validateOrder(
            (int) $new_cart->id,
            (int) $id_order_state,
            $new_cart->getOrderTotal(true, Cart::BOTH),
            $payment_module->displayName,
            'auto ami order'
        );
        
        // Get the order id after creating it from the cart.
        $id_order = Order::getOrderByCartId($new_cart->id);
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
        $scenario = [];
        $carrier = new Carrier((int)$id_carrier,$id_lang);
        $carrierName = $carrier->name;
        $referenceData = DBInteractionsAmi::getStoreData($reference_StoreId, $id_lang);
        $scenario = [
        'PROVIDER' => $provider_StoreId, 
        'NAME' => $referenceData[0]["name"],
        'DESCRIPTION' => 'AMI auto generated order',
        'METHOD_SHIPPER' => $carrier->name,
        'METHOD_DESCR' => '',
        'STORE_ID' => $reference_StoreId,
        'STORE_NAME' => $referenceData[0]["name"],
        'STORE_ADDRESS1' => $referenceData[0]["address1"],
        'STORE_ADDRESS2' => $referenceData[0]["address2"],
        'STORE_CITY' => $referenceData[0]["city"],
        'STORE_STATE' => '',
        'STORE_ZIP' => $referenceData[0]["postcode"],
        'STORE_COUNTRY' => 'MOROCCO',
        'STORE_COUNTRYCODE' => 'ma',
        'STORE_REFERENCE' => $reference_StoreId,
        'STORE_PHONE' => $referenceData[0]["phone"],
        'STORE_HOURS' => '',
        'TYPE' => '1',
        'COST' => '',
        'DELAY' => '',
        'id_carrier' => $id_carrier,
        ];
        return base64_encode(serialize($scenario));
    }
    public function getStores(){
        if($stores = DBInteractionsAmi::getStores()){
            $weekDays = array('Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche');
            $storesWeeks_html = '<tr><td class="store_def">TOUS LES MAGASINS<input type="text" style="display: none;" name="email"></td>';
            $i=1;
            foreach($weekDays as $day){
                $storesWeeks_html.='<td><label class="checkbox-inline"><input type="checkbox" value="'.strval($i).'" name="'.$day.'">'.$day.'</label></td>';
                $i++;
            }
            $storesWeeks_html.='<td><input type="hidden" value="1949" name="idx"/></td></tr>';
            //fetching stores
            foreach($stores as $store){
                if($store_config = DBInteractionsAmi::getStoreConfig($store['store_number'])){
                    $j=1;
                    $placeholder =($store_config[0]['store_client_account']!='')?'':' placeholder="ex: decathlon@decathlon.com"';
                    $storesWeeks_html .= '<tr><td class="store_def">'.$store['name'].'<input type="text" value="'.$store_config[0]['store_client_account'].'" name="email"'.$placeholder.'></td>';
                    foreach($weekDays as $dayIdx => $day){
                        $is_checked = '';
                        if(in_array(($dayIdx+1), explode("|",$store_config[0]['cronDays']))){
                            $is_checked= ' checked';
                        }
                        $storesWeeks_html .= '<td><label class="checkbox-inline"><input type="checkbox" value="'.strval($j).'" name="'.$day.'" '.$is_checked.'>'.$day.'</label></td>';
                        $j++;    
                    }
                    $storesWeeks_html.='<td><input type="hidden" value="'.$store['store_number'].'" name="idx"/></td></tr>';
                }
            }
            echo $storesWeeks_html;
        }
    }
}