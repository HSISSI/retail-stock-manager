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

    public function checkStock($stores,$grantToken, $context, $api_key, $products){
        $result = [];
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
        #$grantToken = 'eyJhbGciOiJSUzI1NiIsImtpZCI6Ik1BSU4iLCJwaS5hdG0iOiI1In0.eyJzY29wZSI6WyJvcGVuaWQiLCJwcm9maWxlIl0sImNsaWVudF9pZCI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwiaXNzIjoiaWRwZGVjYXRobG9uIiwianRpIjoiSmY1ZFl3RHlkSyIsInN1YiI6IkMxMWRmZWU2OTMxMDE5NzNkNDg4NDc5YTkyYWU5MjQ2NDI0OGIxMjkwIiwib3JpZ2luIjoiY29ycG9yYXRlIiwiZXhwIjoxNjcxMTE4MTI2fQ.KDh_jCULFR77Ldd-FY2YBzahtY0P4BgaCcb00Rsc-eT_JYCXFY5ygc5BlqE4srHI-RWVHNdoIg-R1N0q0fLejP0sJrWiUXb9aiieDNiHlHv1IlOxzj1eqlTBcOswTQBqThVAPW9wYIBp5DwXvSJpVqe8ignKQCMH38x--xqNFnVfr1kIhp8cFhJg35VKeNUwl4-GkCw4SPGiNAtUkkGvTyeXPHwN6jdqKI5qazA8ptcJZD0NAG0bpCd1BvWKGhE2W69qJ3HIdYL9kutjS7XYRpGiLZC55L0VkGww2Hr-Rt51riZ3IBKfd2-eNKgHZLDs9aFY6QKUL1cINKRZj4sa_g';
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
        return $result;
    }
    public function createOrders(){
        $currentDay = date('N', strtotime(date('l')));
        if($storesCronDays = DBInteractionsAmi::getCronDays($currentDay)){
            echo '<pre>';print_r($storesCronDays);echo '<pre>';
            foreach($storesCronDays as $store){
                if($amiOrders = DBInteractionsAmi::getAmiOrders($store['store_number'],$store['store_client_account'])){
                    echo '<pre>';print_r($amiOrders);echo '<pre>';
                    $grantToken = Module::getInstanceByName('deca_fedid')->getToken();    
                    echo '<pre>';print_r(array_column($amiOrders,'article'));echo '<pre>';
                    $basic_configs = DBInteractionsAmi::getBasicConfigs();
                    echo "check stock : ";
                    $responses = $this->checkStock($store['store_number'],$grantToken, $basic_configs[0]['baseUrl'], $basic_configs[0]['api_key'], array_column($amiOrders,'article'));
                    echo '<pre>';print_r($responses);echo '<pre>';
                    foreach($amiOrders as $order){
                        foreach($responses as $response){
                            if(($response->item == $order['article']) && ((int)$response->stock <= (int)$order['mini'])){
                                if(((int)$order['maxi'])>0){
                                    echo 'order cretion starts **<br/>';
                                    $product_id = DBInteractionsAmi::get_order_id_product($order['article']);
                                    echo $product_id[0]['id_product'];
                                    if($this->ValidateOrder($store['store_number'],$order['store_client_id'], $order['id_address'], $basic_configs[0]['carrier'], $product_id[0]['id_product'], $order['maxi'],$basic_configs[0]['status_cmd'])){
                                    #if($this->ValidateOrder($store['store_number'],$order['store_client_id'], $order['id_address'], ''.$basic_configs[0]['carrier'], 164192, $order['maxi'], $basic_configs[0]['status_cmd'])){
                                        echo 'order creation ends';
                                    }
                                    die;
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
        echo 'am here';
        $new_cart->updateQty($product_quantity, $id_product); // Added product_quantity to product with the id number id_product
        echo '<br/> product added';
        // Creating order from cart
        $r=$payment_module->validateOrder(
            (int) $new_cart->id,
            (int) $id_order_state,
            $new_cart->getOrderTotal(true, Cart::BOTH),
            $payment_module->displayName,
            'Test auto ami order'
        );
        echo '<br/> validation result:<br/>';print_r($r);

        // Get the order id after creating it from the cart.
        $id_order = Order::getOrderByCartId($new_cart->id);
        echo "<br/>payment_module:<br/> ";
        print_r($payment_module);
        #$new_order = new Order($id_order);
        echo '<br/>current order:<br/>';
        print_r($payment_module->currentOrder);
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
                    #$email_val = (($store_config[0]['store_client_account'] !='') && ($store['store_number'] == $store_config[0]['store_number']))?$store_config[0]['store_client_account']:'';
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