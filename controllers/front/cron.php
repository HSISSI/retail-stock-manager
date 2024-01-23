<?php
class AmiCronModuleFrontController extends ModuleFrontController
{
    public $auth = false;

    /** @var bool */
    public $ajax;

    public function display()
    {
        $this->ajax = 1;

        AdminAmiController::getInstance()->createOrders();
        #$this->ajaxRender("hello\n");
    }
}