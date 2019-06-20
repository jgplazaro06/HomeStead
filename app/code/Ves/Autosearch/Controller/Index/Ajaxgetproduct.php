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
namespace Ves\Autosearch\Controller\Index;

use Magento\CatalogSearch\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;

class Ajaxgetproduct extends \Magento\Framework\App\Action\Action
{
    /**
     * Catalog search data
     *
     * @var Data
     */
    protected $catalogSearchData;
    
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var QueryFactory
     */
    private $_queryFactory;

     /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;

    protected $autosearchModel;


    /**
    * @var \Ves\Autosearch\Helper\Data
    */
    protected $_helper;
    protected $_categoryModel;

    /**      
    * @param \Magento\Framework\App\Action\Context $context      
    */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Data $catalogSearchData,        
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Ves\Autosearch\Model\Search $autosearchModel,
        \Ves\Autosearch\Helper\Data $helper,
        \Magento\Catalog\Model\Category $categoryModel 
        ){
        $this->resultPageFactory = $resultPageFactory;
        $this->catalogSearchData = $catalogSearchData;
        $this->_storeManager = $storeManager;
        $this->_queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
        $this->autosearchModel = $autosearchModel;
        $this->_helper = $helper;
        $this->_categoryModel = $categoryModel;
        parent::__construct($context);
    } 

    /**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) 
     */
    public function execute()
    {
      // die($_POST['filter_category_id']);
        $post_data = $this->getRequest()->getPostValue();
        $json = array();
        $config = array();
        $limit = isset($post_data['limit'])?$post_data['limit']:$this->_helper->getLimit();
        $limit = (int)$limit;

        $thumbW = isset($post_data['thumb_width'])?$post_data['thumb_width']:$this->_helper->getThumbWidth();
        $thumbH = isset($post_data['thumb_height'])?$post_data['thumb_height']:$this->_helper->getThumbHeight();
        $showImage = isset($post_data['show_image'])?$post_data['show_image']:$this->_helper->getShowImage();
        $showPrice = isset($post_data['show_price'])?$post_data['show_price']:$this->_helper->getShowPrice();
        $showDes = isset($post_data['show_short_description'])?$post_data['show_short_description']:$this->_helper->getShowShortDes();
        $shortMax = isset($post_data['short_max_char'])?$post_data['short_max_char']:$this->_helper->getShowMax();
        $this->getRequest()->setParam('q', $post_data['filter_name']);
        $category_id = $post_data['filter_category_id'];
        $category_id = $category_id?$category_id:$this->getRootCategory();
        $searchstring = $post_data['filter_name'];
        $storeid = $this->_storeManager->getStore()->getId();
        $category = $this->_categoryModel->load((int)$category_id);  
        //   $category = $this->_categoryModel->load(2);  
        // }
        
        // $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        /* @var $query \Magento\Search\Model\Query */
        $query = $this->_queryFactory->get();
        $query->setStoreId($storeid);
        if ($query->getQueryText() != '') {
            if ($this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->isMinQueryLength()) {
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                $query->saveIncrementalPopularity();

                if ($query->getRedirect()) {
                    $this->getResponse()->setRedirect($query->getRedirect());
                    return;
                }
            }

            if(!$this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->isMinQueryLength()){
                $collection = $this->autosearchModel->getResultSearchCollection($searchstring , $category, $storeid);
                // echo $collection;die('minh');
                $total = $collection->getSize(); // get total result

                $collection->setPage(1, $limit);
                if(!empty($collection))
                { 
                  $query->saveNumResults($total);
                  foreach ($collection as $_product){

                    $item_html = $this->_view->getLayout()->createBlock('Ves\Autosearch\Block\Item')
                                    ->assign('product', $_product)
                                    ->assign('thumbW', $thumbW)
                                    ->assign('thumbH', $thumbH)
                                    ->assign('showImage', $showImage)
                                    ->assign('showPrice', $showPrice)
                                    ->assign('showDes', $showDes)
                                    ->assign('shortMax', $shortMax)
                                    ->toHtml();


                    $json[] = array(
                     'total'     => $total,
                     'product_id' => $_product->getId(),
                     'name'       => strip_tags(html_entity_decode($_product->getName(), ENT_QUOTES, 'UTF-8')), 
                     // 'image'       => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product/'.$_product->getData('small_image'),
                     'image' => '1',
                     'link'      => $_product->getProductUrl(),
                     'price'      => $_product->getPrice(),
                     'html'       => $item_html
                     );        
                  }
                  if (!empty($json)) {
                    $this->getResponse()->representJson(
                        $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($json)
                    );

                  }else{
                   $json[] = array(
                    'total'      => 0,
                    'product_id' => 0,
                    'name'       => '',  
                    'image'      => '2',
                    'link'       => '',
                    'price'      => 0,
                    'html'       => __('No products exists'),
                    );       
                   $this->getResponse()->representJson(
                        $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($json)
                    );
                 }
                 
               } else {
                $json[] = array(
                  'total'      => 0,
                  'product_id' => 0,
                  'name'       => '',  
                  'image'      => '3',
                  'link'       => '',
                  'price'      => 0,
                  'html'       => __('No products exists'),
                  );       
               $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($json)
                );
              }
            }

        }
        if(!$json) {
        $json[] = array(
              'total'      => 0,
              'product_id' => 0,
              'name'       => '',  
              'image'      => '4',
              'link'       => '',
              'price'      => 0,
              'html'       => __('No products exists'),
              'query'    =>  $query->getQueryText(),
              );       
            $this->getResponse()->representJson(
                    $this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($json)
                );
        }

    }
    public function getRootCategory(){
        return $this->_storeManager->getStore()->getRootCategoryId();
    } 
}