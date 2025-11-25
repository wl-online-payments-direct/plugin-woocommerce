<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Core
 */

namespace MoptWorldline\Core\Checkout\Payment\SalesChannel;

use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\CachedPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Adapter\Cache\AbstractCacheTracer;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\Script\Execution\ScriptExecutor;

class OverwriteCachedPaymentMethodRoute extends CachedPaymentMethodRoute
{
    private SalesChannelRepository $paymentMethodsRepository;
    private EntityRepository $customerRepository;
    private ScriptExecutor $scriptExecutor;
    private EventDispatcherInterface $dispatcher;

    /**
     * @param AbstractPaymentMethodRoute $decorated
     * @param TagAwareCacheInterface $cache
     * @param EntityCacheKeyGenerator $generator
     * @param AbstractCacheTracer $tracer
     * @param EventDispatcherInterface $dispatcher
     * @param array $states
     * @param SalesChannelRepository $paymentMethodsRepository
     * @param EntityRepository $customerRepository
     * @param ScriptExecutor $scriptExecutor
     */
    public function __construct(
        AbstractPaymentMethodRoute      $decorated,
        TagAwareCacheInterface          $cache,
        EntityCacheKeyGenerator         $generator,
        AbstractCacheTracer             $tracer,
        EventDispatcherInterface        $dispatcher,
        array                           $states,
        SalesChannelRepository          $paymentMethodsRepository,
        EntityRepository                $customerRepository,
        ScriptExecutor                  $scriptExecutor
    )
    {
        parent::__construct($decorated, $cache, $generator, $tracer, $dispatcher, $states);
        $this->paymentMethodsRepository = $paymentMethodsRepository;
        $this->customerRepository = $customerRepository;
        $this->scriptExecutor = $scriptExecutor;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $context
     * @param Criteria $criteria
     * @return PaymentMethodRouteResponse
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $key = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;
        $customer = $context->getCustomer();
        if (!is_null($customer)) {
            $fields = $customer->getCustomFields();
            if (!is_null($fields) && array_key_exists($key, $fields) && !empty($fields[$key])) {
                $paymentMethodRoute = new OverwritePaymentMethodRoute(
                    $this->paymentMethodsRepository,
                    $this->dispatcher,
                    $this->scriptExecutor,
                    $this->customerRepository
                );
                return $paymentMethodRoute->load($request, $context, $criteria);
            }
        }
        return parent::load($request, $context, $criteria);
    }
}
