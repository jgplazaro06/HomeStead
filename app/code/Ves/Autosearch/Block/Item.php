<?php
/**
 * Venusthem
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the venustheme.com license that is
 * available through the world-wide-web at this URL:
 * http://venustheme.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Venusthem
 * @package    Ves_Autosearch
 * @copyright  Copyright (c) 2016 Venusthem (http://www.venustheme.com/)
 * @license    http://www.venustheme.com/LICENSE-1.0.html
 */
namespace Ves\Autosearch\Block;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class Item extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
{	
	static $_config = array();
	/**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;
	/**
	 * Contructor
	 */
	public function __construct(
		\Magento\Catalog\Block\Product\Context $context,
		PriceCurrencyInterface $priceCurrency,
		array $data = []
	){	
		if(isset($data['template']) && $data['template']) {
			$this->setData("template", $data['template']);
		}
		$this->priceCurrency = $priceCurrency;
		parent::__construct($context, $data);		
	}


	/**
     * Rendering block content
     *
     * @return string
     */
	function _toHtml() 
	{
		$this->assign("show_image", $this->getConfig("show_image"));
		$this->assign("thumbHeight", $this->getConfig("thumbHeight"));
		$this->assign("thumbWidth", $this->getConfig("thumbWidth"));
		$this->assign("show_price", $this->getConfig("show_price"));

		if($template = $this->getConfig("template")) {
			$this->setTemplate( $template );
		} else {
			$this->setTemplate( "result_item.phtml" );
		}
		
		
        return parent::_toHtml();
	}

	/**
	 * get value of the extension's configuration
	 *
	 * @return string
	 */
	public function getConfig($key, $default = '')
	{
		if($this->hasData($key))
		{
			return $this->getData($key);
		}
		return $default;
	}
	/**
     * Format price value
     *
     * @param float $amount
     * @param bool $includeContainer
     * @param int $precision
     * @return float
     */
    public function formatCurrency(
        $amount,
        $includeContainer = true,
        $precision = PriceCurrencyInterface::DEFAULT_PRECISION
    ) {
        return $this->priceCurrency->format($amount, $includeContainer, $precision);
    }

}