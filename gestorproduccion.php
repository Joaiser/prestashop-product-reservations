<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class GestorProduccion extends Module
{
    public function __construct()
    {
        $this->name = 'gestorproduccion';
        $this->tab = 'AdminCatalog';
        $this->version = '1.0.0';
        $this->author = 'Aitor';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Gestor de Producción y Reservas de Productos');
        $this->description = $this->l('Este módulo gestiona los estados de producción y las reservas de productos.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar este módulo?');
    }

    public function install()
{
    require_once dirname(__FILE__).'/classes/InstallHelper.php';
    
    if (!parent::install() 
        || !InstallHelper::installDB() 
        || !InstallHelper::installReservationEnabledDB() 
        || !$this->installTab() 
        || !$this->registerHook('displayBackOfficeHeader') 
        || !$this->registerHook('displayCustomerAccount')
        || !InstallHelper::installDBForNotesReservation()) {
        return false;
    }
    return true;
}

public function uninstall()
{
    require_once dirname(__FILE__).'/classes/InstallHelper.php';
    
    if (!parent::uninstall() 
        || !InstallHelper::uninstallDB() 
        || !InstallHelper::uninstallReservationEnabledDB() 
        || !$this->uninstallTab()
        || !InstallHelper::uninstallDBForNotesReservation()) {
        return false;
    }
    return true;
}


    private function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminGestorProduccion';
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->module = $this->name;
        $tab->name = [];

        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Gestor Reservas';
        }

        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminGestorProduccion');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }


    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path.'views/css/gestorproduccionadmin.css');
        $this->context->controller->addJS($this->_path.'views/js/adminGestorProduccion.js');     
    }

    /*A partir de aquí, vamos a poner los hooks para la UI del usuario*/

     public function hookDisplayCustomerAccount($params)
{
    // Obtener el usuario actual
    $customer = $this->context->customer;

    // Verificar si el usuario está logueado y es un comercial (id_default_group = 4)
    if ($customer->isLogged() && $customer->id_default_group == 4) {

        // Enlaces
        $product_reservation_link = $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation');
        $my_reservations_link = $this->context->link->getModuleLink('gestorproduccion', 'ProductReservation', ['view' => 'reservations']);

        // Asignar variables para el primer TPL
        $this->context->smarty->assign([
            'product_reservation_link' => $product_reservation_link
        ]);
        $customerAccountTpl = $this->fetch('module:gestorproduccion/views/templates/front/customer_account.tpl');

        // Asignar variables para el segundo TPL
        $this->context->smarty->assign([
            'my_reservations_link' => $my_reservations_link
        ]);
        $customerAccountMyReservationsTpl = $this->fetch('module:gestorproduccion/views/templates/front/customer_account_my_reservations.tpl');

        // Devolver ambos TPL concatenados
        return $customerAccountTpl . $customerAccountMyReservationsTpl;
    }

    return ''; // Si el cliente no está logueado o no es un comercial, no muestra nada
}

    public function hookModuleRoutes()
    {
        return [
            'module-gestorproduccion-productreservation' => [
                'controller' => 'ProductReservation',
                'rule' => 'gestorproduccion/product-reservation',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'gestorproduccion',
                ],
            ],
        ];
    }
    

    public function getCustomersByComercial($id_comercial)
{
    require_once dirname(__FILE__).'/classes/ReservationModel.php';
    return ReservationModel::getCustomersByComercial($id_comercial);
}



}
