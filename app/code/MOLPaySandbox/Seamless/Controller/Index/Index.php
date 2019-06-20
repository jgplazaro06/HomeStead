<?php

namespace MOLPaySandbox\Seamless\Controller\Index;

use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

     /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        if( isset($_POST['payment_options']) && $_POST['payment_options'] != "" ) {

            // Attempt to store the cart into magento system
            // This function should be execute during MOLPay selection page AFTER the address selection
            // Begin calling Magento API

            $om =   \Magento\Framework\App\ObjectManager::getInstance();

            ### At first time, create quote and order
            $cartData = $om->create('\Magento\Checkout\Model\Cart')->getQuote();
            $quote = $om->create('\Magento\Quote\Model\Quote');
            $quote->load($cartData->getId());
            $quote->getPayment()->setMethod('molpaysandbox_seamless'); // Todo: Will Appear MOLPay Seamless

            $customerSess = $om->create('\Magento\Customer\Model\Session');
            $checkoutHelperData = $om->create('\Magento\Checkout\Helper\Data');

            $customerType = '';
            if ($customerSess->isLoggedIn()) {
                $customerType = \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
            }
            if (!$quote->getCheckoutMethod()) {
                if ($checkoutHelperData->isAllowedGuestCheckout($quote)) {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
                } else {
                    $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
                }

                $customerType = $quote->getCheckoutMethod();
            }

            if ( $customerType == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {

                $quote->setCustomerId(null)
                    ->setCustomerEmail($_POST['current_email'])
                    ->setCustomerIsGuest(true)
                    ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
            }

            if( $quote ){
                $cartManagement = $om->create('\Magento\Quote\Model\QuoteManagement');
                $order = $cartManagement->submit($quote);

                if( $order ){
                    $orderArr = [];
                    $orderArr = [
                        'oid' => $order->getId(),
                        "flname" => $order->getCustomerFirstName()." ".$order->getCustomerLastName(),
                        'lastorderid' => $order->getIncrementId() ];

                    $order_step2 = $om->create('\Magento\Sales\Model\Order')
                                     ->load($order->getId());

                        $order_step2->setState("pending_payment")->setStatus("pending_payment");

                                $order_step2->save();

                }

            }

            ### Begin to save quote and order in session
            $checkoutSession = $om->create('\Magento\Checkout\Model\Session');

            ### initial order created, save their data in session
            if( $order ){
                    $checkoutSession->setLastQuoteId($cartData->getId())->setLastSuccessQuoteId($cartData->getId());
                    $checkoutSession->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId())
                        ->setLastOrderStatus('pending_payment');
            }

            ### When 2nd attempt to make payment but above order create is error then use the session
            if( !$order ){
                $sess_quotedata = $checkoutSession->getData();

                if( isset($sess_quotedata['last_real_order_id']) && $sess_quotedata['last_real_order_id'] != null){

                    $lastOId = $sess_quotedata['last_real_order_id'];

                    $order = $om->create('\Magento\Sales\Api\Data\OrderInterface');
                    $order->loadByIncrementId($lastOId);
                    $orderArr = [];
                    $orderArr = [
                        'orderid'       => $lastOId,
                        'customer_name' => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                        'customer_email'=> $order->getCustomerEmail(),
                        'customer_tel'  => $order->getBillingAddress()->getTelephone(),
                        'amount'        => $order->getGrandTotal(),
                        'currency'      => $order->getOrderCurrencyCode()

                    ];
                }

            }
            $merchantid = $this->_objectManager->create('MOLPaySandbox\Seamless\Helper\Data')->getMerchantID();
            $vkey = $this->_objectManager->create('MOLPaySandbox\Seamless\Helper\Data')->getVerifyKey();

            $base_url = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl();

            ### Make sure amount is always same format
            $order_amount = number_format(floatval($order->getGrandTotal()),2,'.','');

            ### End calling Magento API and set parameter for seamless button
            $params = array(
                'status'          => true,  // Set True to proceed with MOLPay
                'mpsmerchantid'   => $merchantid,
                'mpschannel'      => $_POST['payment_options'],
                'mpsamount'       => $order_amount,
                'mpsorderid'      => $order->getIncrementId(),
                'mpsbill_name'    => $order->getBillingAddress()->getFirstname()." ".$order->getBillingAddress()->getLastname(),
                'mpsbill_email'   => $order->getCustomerEmail(),
                'mpsbill_mobile'  => $order->getBillingAddress()->getTelephone(),    // To Do - Change to customer mobile number
                'mpsbill_desc'    => "Payment for Order #".$order->getIncrementId(),
                'mpscountry'      => "MY",
                'mpsvcode'        => md5($order_amount.$merchantid.$order->getIncrementId().$vkey),
                'mpscurrency'     => $order->getOrderCurrencyCode(),
                'mpslangcode'     => "en",
                'mpsreturnurl'    => $base_url.'sandboxseamless'
            );

            $this->getResponse()->setBody(json_encode($params));

        }
        else if( isset($_REQUEST['status']) && $_REQUEST != "" ) {   // Get the return from MOLPay
            //$this->logger->debug(json_encode($_REQUEST));
            //file_put_contents("/var/www/html/mag221/sandbox1/var/log/lina_test.log",json_encode($_REQUEST)."\n",FILE_APPEND);

            $this->_ack($_REQUEST);
            $status = $_REQUEST['status'];
            $order_id = $_REQUEST['orderid'];
            $skey = $_REQUEST['skey'];

            if(isset($_REQUEST['nbcb']))
            {
                    $nbcb = $_REQUEST['nbcb'];
            }
            else
            {
                $nbcb = 0;
            }

            $gate_response = $_REQUEST;

            $om =   \Magento\Framework\App\ObjectManager::getInstance();

            $order = $om->create('Magento\Sales\Api\Data\OrderInterface');
            $order->loadByIncrementId($order_id);


            $vkey = $this->_objectManager->create('MOLPaySandbox\Seamless\Helper\Data')->getSecretKey();


            $key0 = md5($_REQUEST['tranID'].$order_id.$status.$_REQUEST['domain'].$_REQUEST['amount'].$_REQUEST['currency']);
            $key1 = md5($_REQUEST['paydate'].$_REQUEST['domain'].$key0.$_REQUEST['appcode'].$vkey);

            if($skey == $key1) {

                if($status == '00') {   // Success Payment

                    $this->messageManager->addSuccess('Order has been successfully placed!');
                    $order->setState('processing',true);
                    $order->setStatus('processing',true);

                    //Create New Invoice and Transaction functions
                    $quoteId = $order->getQuoteId();
                    $payment = $order->getPayment();
                    $mp_amount = $_REQUEST['amount'];
                        $mp_txnid = $_REQUEST['tranID'];
                    $this->update_invoice_transaction( $order, $payment, $mp_txnid );

                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                    $this->checkoutSession->setLastOrderId($order->getId()); //add this to get order id (magento)


                    //page redirect
                    $url_checkoutredirection = 'checkout/onepage/success';


                } else if($status == '22') {    // Pending Payment
                    $this->messageManager->addSuccess('Order has been successfully placed!');
                    $order->setState('pending',true);
                    $order->setStatus('pending',true);

                    $quoteId = $order->getQuoteId();
                    $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);
                    $this->checkoutSession->setLastOrderId($order->getId()); //add this to get order id (magento)

                    $url_checkoutredirection = 'checkout/onepage/success';

                } else { // Fail Payment
                    $commentMsg = "Fail to complete payment.";
                    if ($order->getId() && $order->getState() != 'canceled') {
                        $order->registerCancellation($commentMsg)->save();
                    }

                    $this->checkoutSession->restoreQuote(); //get the cart back
                    $this->messageManager->addError($commentMsg);
                    if( $nbcb == "1" ) //Callback : possible differ update when return URL (e.g pending payment to fail)
                    {
                        $order->setState('canceled',true);
                        $order->setStatus('canceled',true);
                    }
                    $url_checkoutredirection = 'checkout/cart';
                }
            } else {

                $this->messageManager->addError('Key is not valid.');
                $order->setState('fraud',true);
                $order->setStatus('fraud',true);

                $url_checkoutredirection = 'checkout/cart';
            }
            $order->save();

            if(isset($_REQUEST['nbcb']) && $_REQUEST['nbcb'] == 1)
            {
                echo 'CBTOKEN:MPSTATOK';
            }elseif($nbcb == 0) {
                $this->_redirect($url_checkoutredirection);
            }

        }

        else if( empty($_REQUEST) ){
            $this->_redirect('/');
        }

    }

    public function _ack($P) {

        $P['treq'] = 1;
        while ( list($k,$v) = each($P) ) {
          $postData[]= $k."=".$v;
        }
        $postdata   = implode("&",$postData);
        $url        = "https://www.onlinepayment.com.my/MOLPay/API/chkstat/returnipn.php";
        $ch         = curl_init();
        curl_setopt($ch, CURLOPT_POST           , 1     );
        curl_setopt($ch, CURLOPT_POSTFIELDS     , $postdata );
        curl_setopt($ch, CURLOPT_URL            , $url );
        curl_setopt($ch, CURLOPT_HEADER         , 1  );
        curl_setopt($ch, CURLINFO_HEADER_OUT    , TRUE   );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1  );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , FALSE);
        $result = curl_exec( $ch );
        curl_close( $ch );
        return;
    }

    public function update_invoice_transaction($order, $payment, $e){ //$a:$order_id, $b:$order, $c:$payment, $d:$mp_amount, $e:$mp_txnid
        if($order->canInvoice()) {
                $payment
                        ->setTransactionId($e)
                        ->setShouldCloseParentTransaction(1)
                        ->setIsTransactionClosed(0);
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transaction = $this->transactionFactory->create();

                $transaction->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

                $this->invoiceSender->send($invoice);
                $order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                ->setIsCustomerNotified(true);
        }
    }

}