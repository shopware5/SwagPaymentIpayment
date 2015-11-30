<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware Ipayment Client
 */
class Shopware_Components_Ipayment_Client extends Zend_Soap_Client
{
    protected $accountData = array();

    /**
     * Constructor method
     *
     * Expects a configuration parameter.
     *
     * @param Enlight_Config $config
     */
    public function __construct($config)
    {
        $wsdl = 'https://ipayment.de/service/3.0/?wsdl';
        if ($config->get('ipaymentSandbox')) {
            $this->accountData = array(
                'accountId' => '99999',
                'trxuserId' => '99998',
                'trxpassword' => '0',
                'adminactionpassword' => '5cfgRT34xsdedtFLdfHxj7tfwx24fe',
            );
        } else {
            $this->accountData = array(
                'accountId' => $config->get('ipaymentAccountId'),
                'trxuserId' => $config->get('ipaymentAppId'),
                'trxpassword' => $config->get('ipaymentAppPassword'),
                'adminactionpassword' => $config->get('ipaymentAdminPassword'),
            );
        }
        parent::__construct(
            $wsdl,
            array(
                'useragent' => 'Shopware ' . Shopware::VERSION,
            )
        );
    }

    /**
     * Performs pre processing of all arguments.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function _preProcessArguments($arguments)
    {
        $arguments = array_merge(
            array($this->accountData),
            $arguments
        );

        return $arguments;
    }
}
