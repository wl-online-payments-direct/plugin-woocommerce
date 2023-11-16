<?php
declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline
 */

namespace MoptWorldline;

use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentMethodHelper;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use MoptWorldline\Service\CustomField;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class MoptWorldline extends Plugin
{
    const PLUGIN_NAME = 'MoptWorldline';
    const PLUGIN_VERSION = '2.0.5';

    /**
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $customField = new CustomField($this->container);
        $customField->addCustomFields($installContext);

        /** @var EntityRepository $paymentMethodRep */
        $paymentMethodRep = $this->container->get('payment_method.repository');
        /** @var EntityRepository $salesChannelPaymentMethodRep */
        $salesChannelPaymentMethodRep = $this->container->get('sales_channel_payment_method.repository');
        /** @var PluginIdProvider $pluginIdProvider */
        $pluginIdProvider = $this->container->get(PluginIdProvider::class);
        /** @var EntityRepository $salesChannelRep */
        $salesChannelRep = $this->container->get('sales_channel.repository');

        foreach (Payment::METHODS_LIST as $method) {
            $methodId = PaymentMethodHelper::addPaymentMethod(
                $paymentMethodRep,
                $pluginIdProvider,
                $installContext->getContext(),
                $method
            );

            PaymentMethodHelper::linkPaymentMethod(
                $methodId,
                null,
                true,
                $salesChannelRep,
                $salesChannelPaymentMethodRep,
                $installContext->getContext()
            );
        }
    }

    /**
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        $this->setPaymentMethodsStatus(false, $uninstallContext->getContext());
    }

    /**
     * @param ActivateContext $activateContext
     */
    public function activate(ActivateContext $activateContext): void
    {
        parent::activate($activateContext);
        $this->setPaymentMethodsStatus(true, $activateContext->getContext());
    }

    /**
     * @param DeactivateContext $deactivateContext
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        parent::deactivate($deactivateContext);
        $this->setPaymentMethodsStatus(false, $deactivateContext->getContext());
    }

    /**
     * @param bool $status
     * @param Context $context
     * @return void
     */
    private function setPaymentMethodsStatus(bool $status, Context $context)
    {
        /** @var EntityRepository $paymentMethodRepository */
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        foreach (Payment::METHODS_LIST as $method) {
            PaymentMethodHelper::setPaymentMethodStatus($paymentMethodRepository, $status, $context, $method['id']);
        }
    }
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
