<?php
/**
 * Venustheme
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the venusthem.com license that is
 * available through the world-wide-web at this URL:
 * http://venusthem.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Venustheme
 * @package    Ves_Autosearch
 * @copyright  Copyright (c) 2016 Venustheme (http://www.venusthem.com/)
 * @license    http://www.venusthem.com/LICENSE-1.0.html
 */
namespace Ves\Autosearch\Block\Widget;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;

class CustomWidget extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
	/**
    * @var \Magento\Customer\Model\Session
    */
    protected $customerSession;
    
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
     * @param \Magento\Catalog\Block\Product\Context $context     
     * @param \Magento\Framework\Url\Helper\Data     $urlHelper   
     * @param array                                  $data        
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        CustomerSession $customerSession,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        CollectionFactory $queryCollectionFactory,
        array $data = []
        ) {
    	$this->customerSession = $customerSession;
        $this->_categoryFactory = $categoryFactory;
        $this->_queryCollectionFactory = $queryCollectionFactory;
        if(isset($data['template']) && $data['template']) {
            $this->setData("template", $data['template']);
        }
        parent::__construct($context, $data);
    }

    public function _toHtml()
    {
        $categories = "";
        $searchCollection = "";

        if($this->getConfig('show_filter_category')) {

            $rootCatId = $this->_storeManager->getStore()->getRootCategoryId();
            $categories = $this->getTreeCategories($rootCatId, 0);
        }

        if($this->getConfig('enable_search_term', 0)) {
            $limit = $this->hasData('limit_term')?$this->getData('limit_term'):$this->getConfig('limit', 5);
            $searchCollection = $this->getListSearchTerms((int)$limit);
        }

        $this->assign( "categories_links", self::$_categories_links);
        $this->assign( "categories", $categories );
        $this->assign( "limit", $this->getConfig("limit"));
        $this->assign( "thumb_width", $this->getConfig("thumb_width"));
        $this->assign( "thumb_height", $this->getConfig("thumb_height"));
        $this->assign( "searchCollection", $searchCollection );
        $this->assign( "listProductLink", $this->listProductLink() );
        $this->assign( "prefix", $this->getConfig('prefix') );
        $this->assign( "show_filter_category", $this->getConfig('show_filter_category') );
        $this->assign( "show_image", $this->getConfig('show_image') );
        $this->assign( "show_price", $this->getConfig('show_price') );
        $this->assign( "show_short_description", $this->getConfig('show_short_description') );
        $this->assign( "short_max_char", $this->getConfig('short_max_char') );

        $template = 'Ves_Autosearch::widget/custom_widget.phtml';
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
    
    public function getConfig($key, $default = '')
    {
        if($this->hasData($key) && $this->getData($key))
        {
            return $this->getData($key);
        }
        return $default;
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
        
        public function getTerms(){
            if($this->getConfig('enable_search_term', 0)) {
                $limit = $this->getConfig('limit', 5);
                $this->getListSearchTerms((int)$limit);
                return $this->_terms;
            }

            return array();
            
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
}