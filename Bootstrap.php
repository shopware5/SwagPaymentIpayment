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
use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;

/**
 * Shopware Ipayment Plugin
 */
class Shopware_Plugins_Frontend_SwagPaymentIpayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * Installs the plugin
     *
     * Creates and subscribe the events and hooks
     * Creates and save the payment row
     * Creates the payment table
     * Creates payment menu item
     *
     * @return bool
     */
    public function install()
    {
        $this->createMyEvents();
        $this->createMyPayment();
        $this->createMyForm();
        $this->createMyAttributes();

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        try {
            $this->Application()->Models()->removeAttribute(
                's_order_attributes',
                'swag_ipayment',
                'description'
            );
        } catch (Exception $e) {
        }
        $this->Application()->Models()->generateAttributeModels(array('s_order_attributes'));

        return true;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function update($version)
    {
        $this->createMyForm();
        $this->createMyAttributes();
        $this->createMyEvents();

        return true;
    }

    /**
     * Fetches and returns Ipayment payment row instance.
     *
     * @return \Shopware\Models\Payment\Payment
     */
    public function Payment()
    {
        return $this->Payments()->findOneBy(
            array('name' => 'ipayment')
        );
    }

    /**
     * Activate the plugin Ipayment plugin.
     * Sets the active flag in the payment row.
     *
     * @return bool
     */
    public function enable()
    {
        $payment = $this->Payment();
        $payment->setActive(true);

        return true;
    }

    /**
     * Disable plugin method and sets the active flag in the payment row
     *
     * @return bool
     */
    public function disable()
    {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->setActive(false);
        }

        return true;
    }

    /**
     * Creates and subscribe the events and hooks.
     */
    protected function createMyEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_Ipayment',
            'onGetControllerPathFrontend'
        );

        $this->subscribeEvent('Theme_Compiler_Collect_Plugin_Less', 'addLessFiles');
    }

    /**
     * Creates and save the payment row.
     */
    protected function createMyPayment()
    {
        $this->createPayment(
            array(
                'name' => 'ipayment',
                'description' => 'Kreditkarte (iPayment)',
                'action' => 'ipayment',
                'active' => 0,
                'additionalDescription' => 'Zahlen Sie sicher, schnell und bequem per Kreditkarte. Wir akzeptieren die folgenden Kreditkarten: VISA / Master Card / American Express',
                'embedIFrame' => '',
                'class' => '',
                'template' => '',
            )
        );
    }

    /**
     * Creates and stores the payment config form.
     */
    protected function createMyForm()
    {
        $form = $this->Form();

        // API settings
        $form->setElement(
            'text',
            'ipaymentAccountId',
            array(
                'label' => 'Account-ID',
                'required' => true
            )
        );
        $form->setElement(
            'text',
            'ipaymentAppId',
            array(
                'label' => 'Anwendungs-ID',
                'required' => true
            )
        );
        $form->setElement(
            'text',
            'ipaymentAppPassword',
            array(
                'label' => 'Anwendungspasswort',
                'required' => true
            )
        );
        $form->setElement(
            'text',
            'ipaymentAdminPassword',
            array(
                'label' => 'Adminaktionspasswort',
                'description' => 'Tragen Sie hier Ihr Adminaktionspasswort für sicherere und / oder wiederkehrende Zahlungen ein.',
                'required' => false
            )
        );
        $form->setElement(
            'text',
            'ipaymentSecurityKey',
            array(
                'label' => 'Security-Key',
                'required' => false
            )
        );
        $form->setElement(
            'boolean',
            'ipaymentSandbox',
            array(
                'label' => 'Testmodus aktivieren'
            )
        );
        $form->setElement(
            'boolean',
            'ipaymentRecurring',
            array(
                'label' => 'Wiederkehrende Zahlungen aktivieren',
                'description' => 'Achtung: Für diese Funktion müssen Sie den Security-Key deaktivieren und das Feld dafür leer lassen.',
            )
        );

        $form->setElement(
            'boolean',
            'ipaymentSecureImage',
            array(
                'label' => '3-D Secure Bild anzeigen',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );

        // Payment settings
        $form->setElement(
            'boolean',
            'ipaymentPaymentPending',
            array(
                'label' => 'Zahlung reservieren?',
                'value' => false,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'select',
            'ipaymentStatusId',
            array(
                'label' => 'Zahlstatus nach der kompletter Zahlung',
                'value' => 12,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $form->setElement(
            'select',
            'ipaymentPendingStatusId',
            array(
                'label' => 'Zahlstatus nach der Reservierung',
                'value' => 18,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
    }

    /**
     * Creates and stores the payment config form.
     */
    protected function createMyAttributes()
    {
        try {
            $this->Application()->Models()->addAttribute(
                's_order_attributes',
                'swag_ipayment',
                'description',
                'VARCHAR(255)'
            );
        } catch (Exception $e) {
        }

        $this->Application()->Models()->generateAttributeModels(array('s_order_attributes'));
    }

    /**
     *
     */
    protected function registerMyTemplateDir()
    {
        if (Shopware()->Shop()->getTemplate()->getVersion() >= 3) {
            $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/responsive');
        } else {
            $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/emotion');
        }

    }

    /**
     * Returns the path to a frontend controller for an event.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function onGetControllerPathFrontend(Enlight_Event_EventArgs $args)
    {
        $this->registerMyTemplateDir();
        $this->Application()->Loader()->registerNamespace(
            'Shopware_Components_Ipayment',
            $this->Path() . 'Components/Ipayment/'
        );

        return $this->Path() . 'Controllers/Frontend/Ipayment.php';
    }

    /**
     * Provide the file collection for less
     *
     * @return ArrayCollection
     */
    public function addLessFiles()
    {
        $less = new LessDefinition(
        //configuration
            array(),

            //less files to compile
            array(__DIR__ . '/Views/responsive/frontend/_public/src/less/all.less'),

            //import directory
            __DIR__
        );

        return new ArrayCollection(array($less));
    }

    /**
     * @param   string $paymentStatus
     * @return  int
     */
    public function getPaymentStatusId($paymentStatus)
    {
        switch ($paymentStatus) {
            case 'auth':
                $paymentStatusId = $this->Config()->get('ipaymentStatusId', 12);
                break;
            case 'preauth':
                $paymentStatusId = $this->Config()->get('ipaymentPendingStatusId', 18);
                break; //Reserviert
            default:
                $paymentStatusId = 21;
                break;
        }

        return $paymentStatusId;
    }

    /**
     * @param string $transactionId
     * @param string $paymentStatus
     * @param string|null $note
     * @return void
     */
    public function setPaymentStatus($transactionId, $paymentStatus, $note = null)
    {
        $paymentStatusId = $this->getPaymentStatusId($paymentStatus);
        $sql = '
            SELECT id
            FROM s_order
            WHERE transactionID=?
              AND status!=-1
        ';
        $orderId = Shopware()->Db()->fetchOne($sql, array($transactionId));
        $order = Shopware()->Modules()->Order();
        $order->setPaymentStatus($orderId, $paymentStatusId, false, $note);
        if ($paymentStatusId == 12) {
            $sql = '
                UPDATE s_order
                SET cleareddate=NOW()
                WHERE transactionID=?
                  AND cleareddate IS NULL LIMIT 1
            ';
            Shopware()->Db()->query($sql, array($transactionId));
        }
    }

    public function getAccountData()
    {
        $config = $this->Config();
        if ($config->get('ipaymentSandbox')) {
            return array(
                'accountId' => '99999',
                'trxuserId' => '99999',
                'trxpassword' => '0',
                'adminactionpassword' => '5cfgRT34xsdedtFLdfHxj7tfwx24fe',
            );
        } else {
            return array(
                'accountId' => $config->get('ipaymentAccountId'),
                'trxuserId' => $config->get('ipaymentAppId'),
                'trxpassword' => $config->get('ipaymentAppPassword'),
                'adminactionpassword' => $config->get('ipaymentAdminPassword'),
            );
        }
    }

    /**
     *
     * @return array
     */
    public function getLabel()
    {
        return 'iPayment';
    }

    /**
     * Returns the version of plugin as string.
     *
     * @return string
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'label' => $this->getLabel()
        );
    }
}
