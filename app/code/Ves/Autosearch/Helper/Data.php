<?php
/**
 * Venustheme
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
 * @category   Venustheme
 * @package    Ves_Autosearch
 * @copyright  Copyright (c) 2016 Venustheme (http://www.venustheme.com/)
 * @license    http://www.venustheme.com/LICENSE-1.0.html
 */
namespace Ves\Autosearch\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterfac
     */
    protected $_scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;



    CONST ENABLE_MODULE      	= 'vesautosearch/general/show';
    CONST PREFIX 				= 'vesautosearch/general/prefix';
    CONST SHOW_FILTER_CATEGORY  = 'vesautosearch/general/show_filter_category';
    CONST SHOW_IMAGE 			= 'vesautosearch/general/show_image';
    CONST THUMB_WIDTH 			= 'vesautosearch/general/thumb_width';
    CONST THUMB_HEIGHT	 		= 'vesautosearch/general/thumb_height';
    CONST SHOW_PRICE            = 'vesautosearch/general/show_price';
    CONST SHOW_SHORT_DES        = 'vesautosearch/general/show_short_description';
    CONST SHOW_MAX              = 'vesautosearch/general/short_max_char';
    CONST LIMIT 	 		 	= 'vesautosearch/general/limit';
    CONST ENABLE_SEARCH_TERM  	= 'vesautosearch/search_terms/enable_search_term';
    CONST LIMIT_TERM 		  	= 'vesautosearch/search_terms/limit_term';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager

    ) {
        parent::__construct($context);
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_storeManager = $storeManager;

        
    }

    public function getEnableModule(){
        return $this->_scopeConfig->getValue(
        	self::ENABLE_MODULE, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getPrefix(){
        return $this->_scopeConfig->getValue(
        	self::PREFIX, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getShowFilterCategory(){
        return $this->_scopeConfig->getValue(
        	self::SHOW_FILTER_CATEGORY, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getShowImage(){
        return $this->_scopeConfig->getValue(
        	self::SHOW_IMAGE, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getThumbWidth(){
        return $this->_scopeConfig->getValue(
        	self::THUMB_WIDTH, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getThumbHeight(){
        return $this->_scopeConfig->getValue(
        	self::THUMB_HEIGHT, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getShowPrice(){
        return $this->_scopeConfig->getValue(
            self::SHOW_PRICE, 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    public function getShowShortDes(){
        return $this->_scopeConfig->getValue(
            self::SHOW_SHORT_DES, 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    public function getShowMax(){
        return $this->_scopeConfig->getValue(
            self::SHOW_MAX, 
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
    }

    public function getLimit(){
        return $this->_scopeConfig->getValue(
        	self::LIMIT, 
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }

    public function getLimitTerm(){
        return $this->_scopeConfig->getValue(
        	self::LIMIT_TERM, 
        	self::ENABLE_SEARCH_TERM,
        	\Magento\Store\Model\ScopeInterface::SCOPE_STORE
        	);
    }
    public function subString($text, $length = 100, $replacer = '...', $is_striped = true) {
        $text = ($is_striped == true) ? strip_tags($text) : $text;
        if (strlen($text) <= $length) {
            return $text;
        }
        $text = substr($text, 0, $length);
        $pos_space = strrpos($text, ' ');
        return substr($text, 0, $pos_space) . $replacer;
    }
    public function getConfig($key, $store = null)
    {
        $store = $this->_storeManager->getStore($store);
        $websiteId = $store->getWebsiteId();

        $result = $this->scopeConfig->getValue(
            'vesautosearch/'.$key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store);
        return $result;
    }

}

 