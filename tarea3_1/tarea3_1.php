<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tarea3_1 extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tarea3_1';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Ruben';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('tarea3_1');
        $this->description = $this->l('modulo para la tarea 3 ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('TAREA3_1_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TAREA3_1_LIVE_MODE');

        return parent::uninstall();
    }

    
    public function getContent()
    {
        return $this->postProcess() . $this->getForm();
    
    }

    public function getForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->title = $this->displayName;

       
        $helper->submit_action = 'importar';
        $helper->fields_value['texto_header'] = Configuration::get('HOLI_MODULO_TEXTO_HOME');
        
        $helper->fields_value['texto_footer'] = Configuration::get('HOLI_MODULO_TEXTO_FOOTER');
        


    $this->form[0] = array(
            'form' => array(
                'legend' => array(
                  
                    'title' => $this->l('Suba su fichero CSV')
                 ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l('Fichero csv'),
                        'desc' => $this->l('Importar CSV'),
                        'hint' => $this->l('Fichero csv '),
                        'name' => 'archivo',
                        'accept'=>'.csv',
                        'lang' => false,
                     ),
                 ),
                'submit' => array(
                    'title' => $this->l('Save')
                 )
             )
         );
       
        return $helper->generateForm( $this->form );
    }

   

    public function postProcess()
    {
      
        if (Tools::isSubmit('importar')) {
                if($this->comprobarextension($_FILES['archivo']['name'])){
                     
                      $this->parsearCsv($_FILES['archivo']);
                      return $this->displayConfirmation($this->l('Updated Successfully'));
                   
                }else{
                   
                    return $this->displayError($this->l('Wrong format'));
                  
                    
                }

          
        }
    }
    public function comprobarextension($file)
{

   $allowed='csv,xls';  

   $extension_allowed=  explode(',', $allowed);
    $file_extension=  pathinfo($file, PATHINFO_EXTENSION);
    if(in_array($file_extension, $extension_allowed))
    {
       return true;
    }
    else
    {
        
       return false;
    }
}

public function leerCSV($csvFile){
    $file_handle = fopen($csvFile, 'r');
    $line_of_text = array();
    while (!feof($file_handle) ) {
        $line_of_text[] = fgetcsv($file_handle, 0, ',');
    }
    fclose($file_handle);
    return $line_of_text;
}

public function parsearCsv($adjunto)
{
    $csv = $this->leerCSV($adjunto['tmp_name']);
    $totRows = count($csv);

    if ($totRows<2) {
        $this->controller->errors[] = $this->l('Formato de excel erroneo, comprueba las filas.');
        return false;
    }
    
    $csvContent = array();
    $obligatorias = array('nombre', 'referencia', 'ean13', 'precio de coste', 'precio de venta', 'iva', 'cantidad', 'categorias' , 'marca');

    $separado_por_comas = strtolower(implode(",", $csv[0]));
    
    $csvTitles=explode(",", $separado_por_comas);
 


    $intersect = count(array_intersect($obligatorias, $csvTitles));

    if ($intersect != count($obligatorias)) {
        $this->controller->errors[] = $this->l('Faltan columnas obligatorias');
        return false;
    }
  
    array_shift($csv);
    $idxRow = 0;
    foreach ($csv as $row) {
        $idxCol = 0;
        if (!empty($row)) {
            foreach ($row as $col) {
                $this->l('Faltan columnas obligatorias');
                $csvContent[$idxRow][$csvTitles[$idxCol]] = $col;
                $idxCol++;
            }
        }
        $idxRow++;
    }

    $this->formatoCSV($csvContent);
    

}   

public function formatoCSV($csv){
    $limite=count($csv);
    for ($i=0; $i <$limite ; $i++) {  
       
           $id_categoria=[];
       
        $id_categoria[]=$this->crearCategoria($csv[$i]['categorias']);
        $id_fabricante=$this->crearFabricante($csv[$i]['marca']);
        $nombre=$csv[$i]['nombre'];
        $referencia=$csv[$i]['referencia'];
        $ean13=$csv[$i]['ean13'];
        $preciocoste=$csv[$i]['precio de coste'];
        $precioventa=$csv[$i]['precio de venta'];
        $iva=$csv[$i]['iva'];
        $cantidad=$csv[$i]['cantidad'];

        $this->crearproducto($id_categoria,$id_fabricante,$nombre,$referencia,$ean13,$preciocoste,$precioventa,$iva,$cantidad);

     
       }
       return $this->displayConfirmation($this->l('Updated Successfully'));
}

public function crearproducto($id_categoria,$id_fabricante,$nombre,$referencia,$ean13,$preciocoste,$precioventa,$iva,$cantidad){
    
    $product = new Product();
    $product->reference = $referencia;
    $product->name =  array((int)(Configuration::get('PS_LANG_DEFAULT')) => $nombre);  
    $product->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  Tools::str2url($nombre));
    $product->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => " ");  
    $product->description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => " "); 
 
    $product->id_category_default = $id_categoria[0][0];  
 
  
    $product->redirect_type = '404';
    $product->minimal_quantity = 1;
   
  
 
    $product->show_price = 1;
    $product->on_sale = 0;
    $product->online_only = 1;                        
   
   $product->ecotax = '0.000000';
    $product->price =  Validate::isPrice($precioventa);
    $product->wholesale_price = $preciocoste;
    
    $product->ean13 = $ean13;
    
    $product->id_manufacturer=$id_fabricante;
  
   $product->Add();
  
   $product->updateCategories($id_categoria[0]);
   StockAvailable::setQuantity ($product->id ,null, $cantidad );  
  

  
  
}

public function crearCategoria($datoscat){
    $home = (int)Configuration::get('PS_HOME_CATEGORY');
  
   $nombres=str_replace(";",",",$datoscat);
  
   $nombrearray=explode(",",$nombres);


	$arraydecategorias=array();
    $limite=count( $nombrearray);

    for ($i=0; $i <$limite ; $i++) { 
        $category = new Category();
        $category->name= array((int)(Configuration::get('PS_LANG_DEFAULT')) =>  $nombrearray[$i]); 
              
        $category->link_rewrite = array((int)Configuration::get('PS_LANG_DEFAULT') =>  Tools::str2url($nombrearray[$i]));
        $category->description_short = array((int)(Configuration::get('PS_LANG_DEFAULT')) => "");  
        $category->description = array((int)(Configuration::get('PS_LANG_DEFAULT')) => ""); 
        $category->id_parent = $home; 
        $category->active = 1;
        
               if(!$id_category=Db::getInstance()->getValue('SELECT id_category FROM '._DB_PREFIX_.'category_lang WHERE name in("'.pSQL($nombrearray[$i]).'")')){
               
            $category->add();
         
         array_push($arraydecategorias, $category->id);
                }
        else{
           
            array_push($arraydecategorias, $id_category);
           
        }  
    
        
    }


return $arraydecategorias;
}

public function crearFabricante($nombre){
    $manucfacturer = new Manufacturer();


    $manucfacturer->name= Validate::isCatalogName($nombre); 
        $manucfacturer->name = $nombre;
        $manucfacturer->active = 1;
    
        $manucfacturer->description = '';
        $manucfacturer->short_description = '';
        if(!$id_manufacturer=Db::getInstance()->getValue('SELECT id_manufacturer FROM '._DB_PREFIX_.'manufacturer WHERE name="'.pSQL($nombre).'"')){
            $manucfacturer->add();
        
          return $manucfacturer->id; 
                }
        else{
           
            return $id_manufacturer;
            
        }   
        
}




    // hasta aqui 

    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
}
