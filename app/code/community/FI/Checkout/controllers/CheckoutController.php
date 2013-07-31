<?php
/**
 * Zero Step Checkout controller
 *
 * @category    FI
 * @package     FI_Checkout
 * @author      Sergiy Stotskiy <sergiy.stotskiy@freaksidea.com>
 */
class FI_Checkout_CheckoutController extends Mage_Checkout_Controller_Action
{
    /**
     * Pre dispatch hook. Remove addresses created by multishipping checkout
     *
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $checkoutSessionQuote = Mage::getSingleton('checkout/session')->getQuote();
        if ($checkoutSessionQuote->getIsMultiShipping()) {
            $checkoutSessionQuote->setIsMultiShipping(false);
            $checkoutSessionQuote->removeAllAddresses();
        }

        return $this;
    }

    /**
     * Set specific headers if session expired and send response
     *
     * @return FI_Checkout_CheckoutController
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Check if session expired. If session expired call self::_ajaxRedirectResponse method
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->getOnepage()->getQuote()->hasItems()
            || $this->getOnepage()->getQuote()->getHasError()
            || $this->getOnepage()->getQuote()->getIsMultiShipping()
        ) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    /**
     * Get customer session
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Process error. If error exists throw exception
     *
     * @param mixed $result
     * @return FI_Checkout_CheckoutController
     */
    protected function _validate($result)
    {
        if (isset($result['error'])) {
            $message = $result['message'];
            if (is_array($message)) {
                $message = join('<br>', $message);
            }
            Mage::throwException($message);
        }
        return $this;
    }

    /**
     * Subscribe customer to newsletter
     *
     * @param Varien_Object $customer
     * @param bool  $isWantSubscribe
     * @return bool
     */
    protected function _subscribeCustomer($customer, $isWantSubscribe)
    {
        $helper = Mage::helper('fi_checkout');
        if (!$isWantSubscribe
            || !$helper->isVisibleNewsletter()
            || $isWantSubscribe && $helper->isCustomerSubscribed()
        ) {
            return false;
        }

        $ownerId = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($customer->getEmail())
            ->getId();

        $session = $this->_getSession();
        if ($ownerId !== null && $ownerId != $session->getCustomer()->getId()) {
            Mage::throwException(Mage::helper('newsletter')->__('Sorry, you are trying to subscribe email assigned to another user'));
        }

        $status = Mage::getModel('fi_checkout/subscriber')
            ->setIsSendSuccessEmail($helper->isNeedSendNewsletterEmail('success'))
            ->setIsSendRequestEmail($helper->isNeedSendNewsletterEmail('request'))
            ->subscribe($customer->getEmail());
        return Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED == $status;
    }

    /**
     * Place order. Check for billing agreements and zero subtotal
     */
    protected function _placeOrder()
    {
        if ($requiredAgreements = Mage::helper('checkout')->getRequiredAgreementIds()) {
            $postedAgreements = array_keys($this->getRequest()->getPost('agreement', array()));
            if ($diff = array_diff($requiredAgreements, $postedAgreements)) {
                $result['success'] = false;
                $result['error'] = true;
                $result['message'] = Mage::helper('checkout')->__('Please agree to all the terms and conditions before placing the order.');
                $this->_validate($result);
                return;
            }
        }

        // update payment information from request for order payment
        if ($payment = $this->getRequest()->getPost('payment')) {
            $this->getOnepage()
                ->getQuote()
                ->getPayment()
                ->importData($payment);
        }

        $this->getOnepage()->saveOrder();
    }

    /**
     * Set response in special format
     *
     * @param Varien_Object $response
     * @return FI_Checkout_CheckoutController
     */
    protected function _respondWith(Varien_Object $response)
    {
        if ($response->hasErrorMessage()) {
            $errorHtml = $this->getLayout()
                ->createBlock('core/messages')
                ->addError($response->getErrorMessage())
                ->getGroupedHtml();

            $response->setErrorMessage($errorHtml);
        }

        $this->getResponse()->setBody($response->toJson());

        return $this;
    }

    protected function _saveAddressesFor($sessionOrder)
    {
        $quote   = $this->getOnepage()->getQuote();
        $address = Mage::helper('fi_checkout')->extractAddressFrom($sessionOrder);
        $result  = $this->getOnepage()->saveBilling($address, $address['id']);
        $this->_validate($result);

        $session = $this->_getSession();
        /**
         * Addresses are validated in saveBilling method,
         * so we are disabled validation
         */
        $quote->getShippingAddress()
            ->setSaveInAddressBook(!$session->getCustomer() || !$session->getCustomer()->getDefaultShipping())
            ->setShouldIgnoreValidation(true);

        $quote->getBillingAddress()
            ->setSaveInAddressBook(!$session->getCustomer() || !$session->getCustomer()->getDefaultBilling())
            ->setShouldIgnoreValidation(true);
    }

    protected function _saveShippingMethod()
    {
        $quote  = $this->getOnepage()->getQuote();
        $method = $this->getRequest()->getPost('shipping_method');
        if (!$quote->getIsVirtual() && empty($method)) {
            $this->_validate(array(
                'error'   => true,
                'message' => Mage::helper('checkout')->__('Invalid shipping method.')
            ));
        }

        $quote->getShippingAddress()->setShippingMethod($method);
        Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array(
            'request' => $this->getRequest(),
            'quote'   => $quote
        ));
    }

    /**
     * Return easy checkout page model
     *
     * @return FI_Checkout_Model_Page
     */
    public function getOnepage()
    {
        return Mage::getSingleton('fi_checkout/page');
    }

    protected function _buildSessionOrder()
    {
        $request = $this->getRequest();
        $helper  = Mage::helper('fi_checkout');
        $order   = $helper->buildSessionOrder()->setData(array_filter(array(
            'payment' => $request->getPost('payment'),
            'user'    => $request->getPost('user'),
            'shipping_method'    => $request->getPost('shipping_method'),
        )));

        $address = $helper->parseLocationOf($order->getAddress());
        $order->updateAddressWith($address);
        return $order;
    }

    /**
     * Customer login action.
     * Set before_auth_url to session and forwards to Mage_Customer_AccountController::loginPost
     */
    public function loginAction()
    {
        $this->_buildSessionOrder()->drop();
        $this->_getSession()->setBeforeAuthUrl(Mage::getUrl('checkout/cart'));
        $this->_forward('loginPost', 'account', 'customer');
    }

    /**
     * Country/region/city autocompleter.
     * Depends on request param "location". Set html to response.
     *
     * @return Mage_Core_Controller_Response_Http
     */
    public function locationAutocompleteAction()
    {
        $location = $this->getRequest()->getParam('location');
        if (!$location) {
            return;
        }

        $location = Mage::helper('fi_checkout')->explodeLocation($location);
        $items = Mage::getResourceModel('fi_checkout/countries')->search($location);

        return $this->getResponse()->setBody($this->getLayout()
            ->createBlock('core/template')
            ->setTemplate('freaks/autocomplete.phtml')
            ->setItems($items)
            ->toHtml()
        );
    }

    /**
     * Checkout UI updater.
     * Update shipping/payment methods and totals blocks.
     * Depends on POST request with fields "type" and "value". "type" specify what parts of UI should be updated.
     *
     * @return Mage_Core_Controller_Response_Http
     */
    public function updateAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost() || !$request->getParam('type') || $this->_expireAjax()) {
            return $this;
        }

        $this->_buildSessionOrder();
        $this->getOnepage()->getQuote()->collectTotals();

        $blocks = array();
        $type = array_flip(explode(',', $request->getParam('type')));

        $this->getLayout()->getUpdate()->load('fi_checkout_update');
        $this->getLayout()->generateXml()->generateBlocks();

        if (isset($type['shipping'])) {
            $blocks['shipping'] = $this->getLayout()
                ->getBlock('fi_checkout.shipping_method.available')
                ->toHtml();
        }

        if (isset($type['payment'])) {
            $blocks['payment'] = $this->getLayout()
                ->getBlock('fi_checkout.payment_methods')
                ->toHtml();
        }

        if (isset($type['totals'])) {
            $blocks['totals'] = $this->getLayout()
                ->getBlock('checkout.cart.totals')
                ->toHtml();
        }

        if ($blocks) {
            $response = new Varien_Object($blocks);
            return $this->getResponse()->setBody($response->toJson());
        }
    }

    /**
     * Set html to response. Return regions html select box for specific country.
     * Depends on "country_id" parameter
     */
    public function regionsAction()
    {
        $countryId = $this->getRequest()->getParam('country_id');
        if (!$countryId) {
            return $this;
        }

        $regions = Mage::getModel('directory/region')->getResourceCollection()
            ->addCountryFilter($countryId)
            ->load()
            ->toOptionArray();

        $html = $this->getLayout()->createBlock('core/html_select')
            ->setTitle(Mage::helper('checkout')->__('State/Province'))
            ->setClass('required-entry validate-state')
            ->setName('user[address][region_id]')
            ->setId('address-region')
            ->setOptions($regions)
            ->getHtml();

        $this->getResponse()->setBody($html);
    }

    /**
     * Place order action. Listen for POST requests only
     *
     * @return FI_Checkout_CheckoutController
     */
    public function placeAction()
    {
        $response = new Varien_Object();
        $data = $this->getRequest()->getPost('user');
        if (!$this->getRequest()->isPost() || !$data || $this->_expireAjax()) {
            $response->setRedirect(Mage::getUrl('checkout/cart'));
            return $this->_respondWith($response);
        }

        $quote = $this->getOnepage()->getQuote();
         if (!$quote->validateMinimumAmount()) {
            $response->setErrorMessage(Mage::getStoreConfig('sales/minimum_order/error_message'));
            return $this->_respondWith($response);
        }

        $sessionOrder = $this->_buildSessionOrder();
        $session  = $this->_getSession();
        $hasError = false;

        try {
            if ($session->isLoggedIn()) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);
            } elseif (!$sessionOrder->getAddress()->getId()) {
                $quote->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);
            }

            $this->getOnepage()->initCheckout();
            $this->_saveAddressesFor($sessionOrder);
            $this->_subscribeCustomer($sessionOrder->getCustomer(), $this->getRequest()->getPost('subscribe', false));
            $this->_saveShippingMethod();

            $quote->setTotalsCollectedFlag(false)
                ->collectTotals();

            // save payment information
            $payment = $this->getRequest()->getPost('payment');
            $this->getOnepage()->savePayment($payment);

            $redirectUrl = $quote->getPayment()->getCheckoutRedirectUrl();
            if ($redirectUrl) {
                $response->setRedirect($redirectUrl);
                return $this->_respondWith($response);
            }

            // save order
            $this->_placeOrder();
            $redirectUrl = $this->getOnepage()
                ->getCheckout()
                ->getRedirectUrl();

            $quote->setIsActive(!empty($redirectUrl))->save();
            $sessionOrder->drop();
        } catch (Mage_Core_Exception $e) {
            $hasError = true;
            $response->setErrorMessage($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $hasError = true;
            $response->setErrorMessage(Mage::helper('checkout')->__('Unable to process your order. Please try again later'));
        }

        if (empty($redirectUrl)) {
            $redirectUrl = $hasError ? '' : Mage::getUrl('*/*/success');
        }

        $response->setError($hasError)
            ->setSuccess(!$hasError)
            ->setRedirect($redirectUrl);
        $this->_respondWith($response);

        Mage::dispatchEvent('controller_action_postdispatch_checkout_onepage_saveOrder', array(
            'controller_action' => $this
        ));

        return $this;
    }

    /**
     * Order success action
     */
    public function successAction()
    {
        $session = $this->getOnepage()->getCheckout();
        if (!$session->getLastSuccessQuoteId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $lastQuoteId = $session->getLastQuoteId();
        $lastOrderId = $session->getLastOrderId();
        $lastRecurringProfiles = $session->getLastRecurringProfileIds();
        if (!$lastQuoteId || (!$lastOrderId && empty($lastRecurringProfiles))) {
            $this->_redirect('checkout/cart');
            return;
        }

        $session->clear();
        $this->loadLayout();
        $this->_initLayoutMessages('checkout/session');
        Mage::dispatchEvent('checkout_onepage_controller_success_action', array('order_ids' => array($lastOrderId)));
        $this->renderLayout();
    }

    /**
     * Verify card using 3D secure
     *
     * @return FI_Checkout_CheckoutController
     */
    public function verifyAction()
    {
        $payment = $this->getRequest()->getPost('payment');
        if (!$payment) {
            return $this;
        }

        $verifyUrl = '';
        $quote = $this->getOnepage()->getQuote();
        $this->getOnepage()->savePayment($payment);

        $paymentMethod = $quote->getPayment()->getMethodInstance();
        if ($paymentMethod && $paymentMethod->getIsCentinelValidationEnabled()) {
            $centinel = $paymentMethod->getCentinelValidator();
            if ($centinel && $centinel->shouldAuthenticate()) {
                $verifyUrl = $centinel->getAuthenticationStartUrl();
            }
        }

        if ($verifyUrl) {
            $html = $this->getLayout()->createBlock('core/template')
                ->setTemplate('freaks/checkout/centinel/authentication.phtml')
                ->setFrameUrl($verifyUrl)
                ->toHtml();
        } else {
            $html = $this->getLayout()->createBlock('core/template')
                ->setTemplate('freaks/checkout/centinel/complete.phtml')
                ->setIsProcessed(true)
                ->setIsSuccess(true)
                ->toHtml();
        }

        $response  = new Varien_Object(array(
            'url'  => $verifyUrl,
            'html' => $html
        ));

        $this->getResponse()->setBody($response->toJson());
        return $this;
    }
}
