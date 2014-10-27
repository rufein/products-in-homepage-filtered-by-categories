<?php
/*
* 2007-2014 PrestaShop
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
*
*  @author Koldo Gonzalez
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class HomeCategoriesFeatured extends Module
{
	protected static $cache_products;
	protected static $cache_categories;

	public function __construct()
	{
		$this->name = 'homecategoriesfeatured';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Koldo Gonzalez (Rufein)';
		$this->need_instance = 0;

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Categorized products on the homepage');
		$this->description = $this->l('Displays categorized products in the central column of your homepage.');
	}

	public function install()
	{
		$this->_clearCache('*');
		Configuration::updateValue('CAT_FEATURED_NBR', 8);
		Configuration::updateValue('CAT_FEATURED_ID', array(1)); // Empty array that contains ID categories

		if (!parent::install()
			|| !$this->registerHook('header')
			|| !$this->registerHook('addproduct')
			|| !$this->registerHook('updateproduct')
			|| !$this->registerHook('deleteproduct')
			|| !$this->registerHook('categoryUpdate')
			|| !$this->registerHook('displayHomeTab')
			|| !$this->registerHook('displayHomeTabContent')
		)
			return false;

		return true;
	}

	public function uninstall()
	{
		$this->_clearCache('*');

		return parent::uninstall();
	}

	public function getContent()
	{
		$output = '';
		$errors = array();
		if (Tools::isSubmit('submitHomeFeatured'))
		{
			$nbr = (int)Tools::getValue('CAT_FEATURED_NBR');
			$ids = Tools::getValue('CAT_FEATURED_ID');
			if(empty($ids) || !is_array($ids)){ $ids = array(1); }
			
			if (!$nbr || $nbr <= 0 || !Validate::isInt($nbr)) {
				$errors[] = $this->l('An invalid number of products has been specified.');
			}elseif(!is_array($ids)) {
			  $errors[] = $this->l('Not an array');
			}
			else
			{
				Tools::clearCache(Context::getContext()->smarty, $this->getTemplatePath('homecategoriesfeatured.tpl'));
				Configuration::updateValue('CAT_FEATURED_NBR', (int)$nbr);
				Configuration::updateValue('CAT_FEATURED_ID', serialize($ids));
			}
			if (isset($errors) && count($errors))
				$output .= $this->displayError(implode('<br />', $errors));
			else
				$output .= $this->displayConfirmation($this->l('Your settings have been updated.'));
		}

		return $output.$this->renderForm();
	}

	public function hookDisplayHeader($params)
	{
		$this->hookHeader($params);
	}

	public function hookHeader($params)
	{
		if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index')
			$this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
		$this->context->controller->addCSS(($this->_path).'homefeatured.css', 'all');
	}

	public function _cacheProducts()
	{
		if (!isset(HomeCategoriesFeatured::$cache_products)) {
		  
		  HomeCategoriesFeatured::$cache_products = array();
		  HomeCategoriesFeatured::$cache_categories = array();
		  
		  $config_values = $this::getConfigFieldsValues();
		  $nb = $config_values['CAT_FEATURED_NBR'];
		  
		  foreach ($config_values['CAT_FEATURED_ID'] as $id){
		     if($id == 1) { continue; } // Skip the default id
			   $category = new Category((int)$id, (int)Context::getContext()->language->id);
			   HomeCategoriesFeatured::$cache_categories[$id] = $category;
			   HomeCategoriesFeatured::$cache_products[$id] = $category->getProducts((int)Context::getContext()->language->id, 1, ($nb ? $nb : 8), 'position');
		  }
		  
		}

		if (HomeCategoriesFeatured::$cache_products === false || empty(HomeCategoriesFeatured::$cache_products))
			return false;
	}

	public function hookDisplayHomeTab($params)
	{

		if (!$this->isCached('tab.tpl', $this->getCacheId('homecategoriesfeatured-tab'))) {
			$this->_cacheProducts();
			$this->smarty->assign(
			    array(
			        'homefeaturedcategories' => HomeCategoriesFeatured::$cache_categories,  
			    )
			);
		}
		
		
		return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('homecategoriesfeatured-tab'));
	}

	public function hookDisplayHome($params)
	{
		if (!$this->isCached('homecategoriesfeatured.tpl', $this->getCacheId())) {
			$this->_cacheProducts();
			$this->smarty->assign(
				array(
				  'homefeaturedcategories' => HomeCategoriesFeatured::$cache_categories,
					'homefeaturedproducts' => HomeCategoriesFeatured::$cache_products,
					'add_prod_display' => Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
					'homeSize' => Image::getSize(ImageType::getFormatedName('home')),
				)
			);
		}
		
		
		return $this->display(__FILE__, 'homecategoriesfeatured.tpl', $this->getCacheId());
	}

	public function hookDisplayHomeTabContent($params)
	{
		return $this->hookDisplayHome($params);
	}

	public function hookAddProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookUpdateProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookDeleteProduct($params)
	{
		$this->_clearCache('*');
	}

	public function hookCategoryUpdate($params)
	{
		$this->_clearCache('*');
	}

	public function _clearCache($template, $cache_id = NULL, $compile_id = NULL)
	{
		parent::_clearCache('homecategoriesfeatured.tpl');
		parent::_clearCache('tab.tpl', 'homecategoriesfeatured-tab');
	}

	public function renderForm()
	{
		$values = $this->getConfigFieldsValues(); // Get values form database
		$var = $values['CAT_FEATURED_ID'];
		if (!is_array($var)) { $var = array(1);}
		
		// $categories = Category::getCategories();
	  $fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs'
				),
				'description' => $this->l('To add products to your homepage, simply add them to the root product category (default: "Home").'),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Number of products to be displayed'),
						'name' => 'CAT_FEATURED_NBR',
						'class' => 'fixed-width-xs',
						'desc' => $this->l('Set the number of products that you would like to display on homepage (default: 8).'),
					),
				  array(
				    'type' => 'categories',
				    'label' => $this->l('Selected categories'),
				    'name' => 'CAT_FEATURED_ID',
				    'class' => 'fixed-width-xs',
				    'desc' => $this->l('Set the categories to be showed.'),
				    'tree'  => array(
						  'id'                  => 'categories-tree',
						  'selected_categories' => $var,
					 	  'disabled_categories' => null,
				      'use_search'          => true,
				      'use_checkbox'        => true,
					    ),
				  ),
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitHomeFeatured';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}

	public function getConfigFieldsValues()
	{
	  $r = array(
			'CAT_FEATURED_NBR' => Tools::getValue('CAT_FEATURED_NBR', Configuration::get('CAT_FEATURED_NBR')),
		  'CAT_FEATURED_ID' =>  Tools::getValue('CAT_FEATURED_ID', Configuration::get('CAT_FEATURED_ID')),
		);
	  
	  if(!is_array($r['CAT_FEATURED_ID']) && !empty($r['CAT_FEATURED_ID'])){
	    $r['CAT_FEATURED_ID'] = unserialize($r['CAT_FEATURED_ID']);
	  }
	  
		return $r;
	}
}
