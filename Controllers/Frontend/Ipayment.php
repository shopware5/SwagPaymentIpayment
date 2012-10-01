<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Plugin
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Heiner Lohaus
 * @author     $Author$
 */

/**
 * Paypal payment controller
 */
class Shopware_Controllers_Frontend_Ipayment extends Shopware_Controllers_Frontend_Payment
{
	/**
	 * Index action method.
	 * 
	 * Forwards to correct the action.
	 */
	public function indexAction()
	{
        if($this->getAmount() > 0 && $this->getPaymentShortName() == 'ipayment') {
			$this->forward('gateway');
		} else {
			$this->redirect(array('controller' => 'checkout'));
		}
	}

	/**
	 * Gateway action method.
	 * 
	 * Collects the payment information and transmit it to the payment provider.
	 */
	public function gatewayAction()
	{
		$router = $this->Front()->Router();
		$config = $this->Plugin()->Config();
        $test = $config->get('ipaymentSandbox');

        $url = 'https://ipayment.de/merchant/';
        $url .= $test ? '99999' : $config->get('ipaymentAccountId');
        $url .= 0 && $test ? '/example' : '/processor';
        $url .= '/2.0/';

        $uniqueId = $this->createPaymentUniqueId();

        $params = array(
            'trxuser_id' => !$test ? $config->get('ipaymentAppId') : '99998',
            'trxpassword' => !$test ? $config->get('ipaymentAppPassword') : '0',
            'adminactionpassword' => !$test ? $config->get('ipaymentAdminPassword') : '5cfgRT34xsdedtFLdfHxj7tfwx24fe',
            'silent' => 1,
            'trx_paymenttyp' => 'cc',
            'trx_typ' => $config->get('ipaymentPaymentPending') ? 'preauth' : 'auth',
            'trx_amount' => number_format($this->getAmount(), 2, '', ''),
            'trx_currency' => $this->getCurrencyShortName(),
            'silent_error_url' => $router->assemble(array('action' => 'return', 'forceSecure' => true)),
            'hidden_trigger_url' => $router->assemble(array('action' => 'notify', 'forceSecure' => true)),
            'redirect_url' => $router->assemble(array('action' => 'return', 'forceSecure' => true)),
            'client_name' => 'Shopware ' . Shopware::VERSION,
            'client_version' => $this->Plugin()->getVersion(),
            'from_ip' => $this->Request()->getClientIp(),
            'error_lang' => Shopware()->Shop()->getLocale()->getLanguage(),
            'browser_user_agent' => $this->Request()->getHeader('user_agent'),
            'browser_accept_headers' => $this->Request()->getHeader('accept'),
            'sw_unique_id' => $uniqueId
        );

        $securityHash = array(
            $params['trxuser_id'], $params['trx_amount'], $params['trx_currency'],
            $params['trxpassword'], !$test ? $config->get('ipaymentSecurityKey') : 'testtest',
        );
        $params['trx_securityhash'] = md5(implode('', $securityHash));

        $params = array_merge($params, $this->getCustomerParameter());
        $this->View()->assign(array(
            'gatewayUrl' => $url,
            'gatewayParams' => $params,
            'gatewayAmount' => $this->getAmount(),
            'gatewaySecureImage' => $config->get('ipaymentSecureImage')
        ));
        if(!empty(Shopware()->Session()->IpaymentError)) {
            $this->View()->assign('gatewayError', Shopware()->Session()->IpaymentError);
            unset(Shopware()->Session()->IpaymentError);
        }
	}

	/**
	 * Return action method
	 * 
	 * Reads the transactionResult and represents it for the customer.
	 */
	public function returnAction()
	{
        $request = $this->Request();
		$config = $this->Plugin()->Config();
        $test = $config->get('ipaymentSandbox');

        $status = $this->Request()->getParam('ret_status');
        if ($status == 'ERROR'){
            Shopware()->Session()->IpaymentError = array(
                'errorCode' => $request->getParam('ret_errorcode'),
                'errorMessage' => $request->getParam('ret_errormsg')
            );
            $this->redirect(array('action' => 'index', 'forceSecure' => true));
            return;
        }

        $secret = $test ? 'testtest' : $config->get('ipaymentSecurityKey');
        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getRequestUri();
        $url = substr($url, 0, strpos($url, '&ret_url_checksum') + 1);
        $result = $request->getQuery();

        if ($request->get('ret_url_checksum') != md5($url . $secret)) {
            $this->redirect(array('action' => 'index', 'forceSecure' => true));
            return;
        }

        $transactionId = $result['ret_trx_number'];
        $paymentUniqueId = $result['sw_unique_id'];
        $paymentStatus = $result['trx_typ'];
        if($this->getAmount() > ($result['trx_amount'] / 100)) {
            $paymentStatus = 'miss'; //Überprüfung notwendig
        }
        $this->saveOrder($transactionId, $paymentUniqueId);
        $this->Plugin()->setPaymentStatus($transactionId, $paymentStatus);

        $this->redirect(array(
            'controller' => 'checkout',
            'action' => 'finish',
            'sUniqueID' => $paymentUniqueId
        ));
	}

    /**
     * Notify action method
     */
    public function notifyAction()
    {
        $request = $this->Request();

        if (!preg_match('/\.ipayment\.de$/', gethostbyaddr($request->getClientIp(false)))) {
            return;
        }
        if ($request->getParam('ret_status') != 'SUCCESS') {
            return;
        }

        $transactionId = $request->getParam('ret_trx_number');
        $paymentStatus = $request->getParam('trx_typ');
        $paymentUniqueId = $request->getParam('sw_unique_id');
        $paymentStatusId = $this->Plugin()->getPaymentStatusId($paymentStatus);
        if ($paymentStatusId == 12 || $paymentStatusId == 18) {
            $this->saveOrder($transactionId, $paymentUniqueId);
        }
        $this->Plugin()->setPaymentStatus($transactionId, $paymentStatus);
    }

    /**
     * Returns the prepared customer parameter data.
     *
     * @return array
     */
    protected function getCustomerParameter()
    {
        $user = $this->getUser();
        if(empty($user)) {
            return array();
        }
        $billing = $user['billingaddress'];
        $customer = array(
            'shopper_id' => $billing['customernumber'],
            'addr_name' => $billing['firstname'] . ' ' . $billing['lastname'],
            'addr_street' => $billing['street'] . ' ' .$billing['streetnumber'],
            'addr_zip' => $billing['zipcode'],
            'addr_city' => $billing['city'],
            'addr_country' => $user['additional']['country']['countryiso'],
            'addr_email' => $user['additional']['user']['email'],
            'addr_telefon' => $billing['phone'],
        );
        if(!empty($user['additional']['stateBilling']['shortcode'])) {
            $customer['addr_state'] = $user['additional']['stateBilling']['shortcode'];
        }
        return $customer;
    }

    /**
     * Returns the payment plugin config data.
     *
     * @return Shopware_Plugins_Frontend_SwagPaymentIpayment_Bootstrap
     */
    public function Plugin()
    {
        return Shopware()->Plugins()->Frontend()->SwagPaymentIpayment();
    }
}
