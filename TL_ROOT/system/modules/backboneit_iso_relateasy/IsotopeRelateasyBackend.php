<?php

class IsotopeRelateasyBackend extends Backend {
	
	public function hookLoadDataContainer($strTable) {
		if(TL_MODE == 'FE' || $strTable != 'tl_iso_products') {
			return;
		}
		
		$objCategory = $this->Database->prepare(
			'SELECT	id, name
			FROM	tl_iso_related_categories'
		)->execute();
		
		$arrFieldsDCA = &$GLOBALS['TL_DCA']['tl_iso_products']['fields'];
		
		while($objCategory->next()) {
			$arrFieldsDCA['bbit_iso_relateasy_' . $objCategory->id]
				= $this->generateFieldDCA($objCategory->id, $objCategory->name);
		}
	}
	
	public function generateFieldDCA($intID, $strName) {
		$arrFieldDCA['label']					= array_map(
			'sprintf',
			$GLOBALS['TL_LANG']['tl_iso_products']['bbit_iso_relateasy'],
			array_fill(0, 2, $strName)
		);
		$arrFieldDCA['inputType']				= 'tableLookup';
		$arrFieldDCA['bbit_iso_relateasy']		= $intID;
		
		$arrFieldDCA['eval']['doNotSaveEmpty']	= true;
		$arrFieldDCA['eval']['foreignTable']	= 'tl_iso_products';
		$arrFieldDCA['eval']['fieldType']		= 'checkbox';
		$arrFieldDCA['eval']['listFields']		= array('name', 'sku');
		$arrFieldDCA['eval']['searchFields']	= array('name', 'sku');
		$arrFieldDCA['eval']['sqlWhere']		= 'pid=0';
		$arrFieldDCA['eval']['searchLabel']		= &$GLOBALS['TL_LANG']['MSC']['searchLabel'];
		$arrFieldDCA['eval']['tl_class']		= 'clr';
		
		$arrFieldDCA['load_callback']['bbit_iso_relatt']= array('IsotopeRelateasyBackend', 'loadRelatedProducts');
		$arrFieldDCA['save_callback']['bbit_iso_relatt']= array('IsotopeRelateasyBackend', 'saveRelatedProducts');

		$arrFieldDCA['attributes']['legend']			= 'media_legend';
		$arrFieldDCA['attributes']['customer_defined']	= false;
		$arrFieldDCA['attributes']['variant_option']	= false;
		$arrFieldDCA['attributes']['ajax_option']		= false;
		$arrFieldDCA['attributes']['multilingual']		= false;
		$arrFieldDCA['attributes']['dynamic']			= true;
		
		return $arrFieldDCA;
	}
	
	public function loadRelatedProducts($varValue, $objDC) {
		$intCategory = $GLOBALS['TL_DCA']['tl_iso_products']['fields'][$objDC->field]['bbit_iso_relateasy'];
		
		$objResult = $this->Database->prepare(
			'SELECT *
			FROM	tl_iso_related_products
			WHERE	category = ?
			AND		pid = ?'
		)->execute($intCategory, $objDC->id);
		
		return deserialize($objResult->products, true);
	}
	
	public function saveRelatedProducts($varValue, $objDC) {
		$intCategory = $GLOBALS['TL_DCA']['tl_iso_products']['fields'][$objDC->field]['bbit_iso_relateasy'];
		
		$objResult = $this->Database->prepare(
			'DELETE
			FROM	tl_iso_related_products
			WHERE	category = ?
			AND		pid = ?'
		)->execute($intCategory, $objDC->id);
		
		$varValue = deserialize($varValue, true);
		
		if(false !== $i = array_search($objDC->id, $varValue)) {
			$varValue = array_splice($varValue, $i, 1);
		}
		
		if($varValue) {
			$objResult = $this->Database->prepare(
				'INSERT INTO tl_iso_related_products %s'
			)->set(array(
				'pid'		=> $objDC->id,
				'tstamp'	=> time(),
				'category'	=> $intCategory,
				'products'	=> $varValue
			))->execute();
		}
		
		return null;
	}
	
	protected function __construct() {
		parent::__construct();
	}
	
	private static $objInstance;
	
	public static function getInstance() {
		if(!isset(self::$objInstance)) {
			self::$objInstance = new self();
		}
		return self::$objInstance;
	}
	
}
