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
namespace Ves\Autosearch\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;

class Autosearch extends \Magento\Framework\View\Element\Template
{	
	/**
	* @var \Magento\Customer\Model\Session
	*/
	protected $customerSession;
    protected $scopeConfig;

    static $arr               = array();
    static $tmp               = array();
    static $_categories_links = array();

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * Query collection factory
     *
     * @var CollectionFactory
     */
    protected $_queryCollectionFactory;
    /**
     * @var array
     */
    protected $_terms;

    /**
     * @var int
     */
    protected $_minPopularity;

    /**
     * @var int
     */
    protected $_maxPopularity;
	/**
	 * Contructor
	 */
    /**
    * @var \Ves\Autosearch\Helper\Data
    */
    protected $_autosearchHelper;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		CustomerSession $customerSession,
		\Magento\Catalog\Model\CategoryFactory $categoryFactory,
		CollectionFactory $queryCollectionFactory,
        \Ves\Autosearch\Helper\Data $autosearchHelper,
        array $data=[]
	){
		$this->customerSession = $customerSession;
		$this->_categoryFactory = $categoryFactory;
		$this->_queryCollectionFactory = $queryCollectionFactory;
        $this->_autosearchHelper =$autosearchHelper;
        $this->scopeConfig = $context->getScopeConfig();
        if(isset($data['template']) && $data['template']) {
            $this->setData("template", $data['template']);
        }
		parent::__construct($context, $data);

	}

    /**
     * Get key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {       
        // var_dump($this->getData());
        return [
        'VES_AUTOSEARCH_BLOCK_AUTOSEARCH',
        $context->getNameInLayout(),
        $this->_storeManager->getStore()->getId(),
        $this->_design->getDesignTheme()->getId(),
        $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP),
        $this->customerSession->getCustomerGroupId,
        ];
    }
      
	/**
     * Rendering block content
     *
     * @return string
     */
	public function _toHtml()
    {
        $categories = "";
        $searchCollection = "";
        if($this->_autosearchHelper->getConfig('general/show_filter_category')) {
            $rootCatId = $this->_storeManager->getStore()->getRootCategoryId();
            // die($rootCatId);
            $categories = $this->getTreeCategories($rootCatId, 0);
        }

        if($this->_autosearchHelper->getConfig('search_terms/enable_search_term')) {
            $limit = $this->_autosearchHelper->getConfig('search_terms/limit_term');
            $searchCollection = $this->getListSearchTerms((int)$limit);
        }

        $this->assign( "categories_links", self::$_categories_links);
        $this->assign( "categories", $categories );
        $this->assign( "limit", $this->_autosearchHelper->getConfig('general/limit'));
        $this->assign( "thumb_width", $this->_autosearchHelper->getConfig('general/thumb_width'));
        $this->assign( "thumb_height", $this->_autosearchHelper->getConfig('general/thumb_height'));
        $this->assign( "searchCollection", $searchCollection );
        $this->assign( "listProductLink", $this->listProductLink() );
        $this->assign( "prefix", $this->_autosearchHelper->getConfig('general/prefix') );
        $this->assign( "show_filter_category", $this->_autosearchHelper->getConfig('general/show_filter_category') );
        $this->assign( "show_image", $this->_autosearchHelper->getConfig('general/show_image') );
        $this->assign( "show_price", $this->_autosearchHelper->getConfig('general/show_price') );
        $this->assign( "show_short_description", $this->_autosearchHelper->getConfig('general/show_short_description') );
        $this->assign( "short_max_char", $this->_autosearchHelper->getConfig('general/short_max_char') );

        $template = 'Ves_Autosearch::default.phtml';
        if($tmp_template = $this->getConfig("template")) {
            $template = $tmp_template;
        } 
        $this->setTemplate($template);
        return parent::_toHtml();
    }

    public function listProductLink()
    {
        $isSecure = $this->_storeManager->getStore()->isCurrentlySecure();
        if($isSecure) {
            return $this->getUrl('autosearch/index/ajaxgetproduct', array('_secure'=>true));
        } else {
            return $this->getUrl('autosearch/index/ajaxgetproduct');
        }
    }

	public function getCatalogSearchLink() 
    {
        $isSecure = $this->_storeManager->getStore()->isCurrentlySecure();
        if($isSecure) {
            return $this->getUrl('catalogsearch/result/', array('_secure'=>true));
        } else {
            return $this->getUrl('catalogsearch/result/');
        }
    }


	public function getListSearchTerms($limit = 0) 
    {
        if (empty($this->_terms)) {
            $this->_terms = [];
            $terms = $this->_queryCollectionFactory->create()->setPopularQueryFilter(
                $this->_storeManager->getStore()->getId()
            )->setPageSize(
                $limit
            )->load()->getItems();

            if (count($terms) == 0) {
                return $this;
            }

            $this->_maxPopularity = reset($terms)->getPopularity();
            $this->_minPopularity = end($terms)->getPopularity();
            $range = $this->_maxPopularity - $this->_minPopularity;
            $range = $range == 0 ? 1 : $range;
            foreach ($terms as $term) {
                if (!$term->getPopularity()) {
                    continue;
                }
                $term->setRatio(($term->getPopularity() - $this->_minPopularity) / $range);
                $temp[$term->getName()] = $term;
                $termKeys[] = $term->getName();
            }
            natcasesort($termKeys);

            foreach ($termKeys as $termKey) {
                $this->_terms[$termKey] = $temp[$termKey];
            }
        }
        return $this;
    }

	public function getTreeCategories($parentId,$level = 0, $caret = '  '){
        $category_id = $this->getRequest()->getParam("cat");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $current_category = $objectManager->get('Magento\Framework\Registry')->registry('current_category');
        if(!$category_id && $current_category){
            $category_id = $current_category->getId();
        }
        $allCats = $this->_categoryFactory->create()->getCollection()
        ->addAttributeToSelect('*')
        ->addAttributeToFilter('is_active','1')
        ->addAttributeToSort('position', 'asc'); 
        if ($parentId) {
            $allCats->addAttributeToFilter('parent_id',array('eq' => $parentId));
        }
        $html= '';
        $prefix = "";
        if($level) {
            // $prefix = "|_";
            for($i=0;$i < $level; $i++) {
                $prefix .= $caret;
            }
        }
        // echo $allCats->getSellect();die('minh');
        foreach($allCats as $category)
        {
            if(!isset(self::$_categories_links[$category->getId()])) {
                self::$_categories_links[$category->getId()] = $category->getId();
                $subcats = $category->getChildren();
                $html .= '<option value="'.$category->getId().'" '.($category_id == $category->getId() ? 'selected="selected"':'') .'>'.$prefix.$category->getName().'</option>';
                $subcats = $category->getChildren();
                if($subcats != ''){ 
                    $html .= $this->getTreeCategories($category->getId(), (int)$level + 1, $caret.'&nbsp;');
                }

            }
            
        }
        return $html;
    }

	public function getTerms(){
        if($this->_autosearchHelper->getConfig('general/enable_search_term')){
            $limit = $this->_autosearchHelper->getConfig('general/limit');
            $this->getListSearchTerms((int)$limit);
            return $this->_terms;
        } 

        return array();
    }
	/**
     * @return int
     */
    public function getMaxPopularity()
    {
        return $this->_maxPopularity;
    }

    /**
     * @return int
     */
    public function getMinPopularity()
    {
        return $this->_minPopularity;
    }
    public function getRootCategory(){
        return $this->_storeManager->getStore()->getRootCategoryId();
    }
    public function getConfig($key, $default = '')
    {
        if($this->hasData($key) && $this->getData($key))
        {
            return $this->getData($key);
        }
        return $default;
    }
}