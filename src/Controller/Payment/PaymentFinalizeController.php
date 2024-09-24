<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Payment;

use Monolog\Level;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Service\AdminTranslate;
use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\OrderHelper;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PaymentFinalizeController extends AbstractController
{
    private RouterInterface $router;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private AsynchronousPaymentHandlerInterface $paymentHandler;
    private OrderTransactionStateHandler $transactionStateHandler;
    private SystemConfigService $systemConfigService;
    private TranslatorInterface $translator;

    public function __construct(
        SystemConfigService                 $systemConfigService,
        EntityRepository                    $orderRepository,
        EntityRepository                    $customerRepository,
        AsynchronousPaymentHandlerInterface $paymentHandler,
        OrderTransactionStateHandler        $transactionStateHandler,
        RouterInterface                     $router,
        TranslatorInterface                 $translator
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->paymentHandler = $paymentHandler;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return RedirectResponse
     */
    #[Route(
        path: '/worldline/payment/finalize-transaction',
        name: 'worldline.payment.finalize.transaction',
        methods: ['GET']
    )]
    public function finalizeTransaction(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $hostedCheckoutId = $this->getHostedCheckoutId($request->query);
        if (is_null($hostedCheckoutId)) {
            return new RedirectResponse('/');
        }
        $context = $salesChannelContext->getContext();

        $order = OrderHelper::getOrder($context, $this->orderRepository, $hostedCheckoutId);

        $finishUrl = $this->buildFinishUrl($request, $order, $salesChannelContext);

        return new RedirectResponse($finishUrl);
    }

    /**
     * @param InputBag $query
     * @return string|null
     */
    private function getHostedCheckoutId(InputBag $query): ?string
    {
        if ($hostedCheckoutId = $query->get('hostedCheckoutId')) {
            return $hostedCheckoutId;
        } elseif ($hostedCheckoutId = $query->get('paymentId')) {
            $id = explode('_', $hostedCheckoutId);
            return $id[0] ?: null;
        }

        return null;
    }

    /**
     * @param Request $request
     * @param OrderEntity|null $order
     * @param SalesChannelContext $salesChannelContext
     * @return string
     */
    private function buildFinishUrl(
        Request                $request,
        ?OrderEntity           $order,
        SalesChannelContext    $salesChannelContext
    ): string
    {
        $orderTransaction = $order->getTransactions()->last();
        $paymentTransactionStruct = new AsyncPaymentTransactionStruct($orderTransaction, $order, '');

        $orderId = $order->getId();
        $changedPayment = $request->query->getBoolean('changedPayment');
        $finishUrl = $this->router->generate('frontend.checkout.finish.page', [
            'orderId' => $orderId,
            'changedPayment' => $changedPayment,
        ]);

        $salesChannelId = $salesChannelContext->getSalesChannelId();
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
        try {
            $logger = new LogHelper($adapter);
            $logger->log(AdminTranslate::trans($this->translator->getLocale(), 'forwardToPaymentHandler'));
            $this->paymentHandler->finalize($paymentTransactionStruct, $request, $salesChannelContext);
        } catch (PaymentException $paymentException) {
            LogHelper::addLog(
                Level::Error,
                AdminTranslate::trans($this->translator->getLocale(), 'errorWithConfirmRedirect'),
                ['message' => $paymentException->getMessage(), 'error' => $paymentException]
            );
            $finishUrl = $this->redirectToConfirmPageWorkflow(
                $paymentException,
                $orderId
            );
        }

        return $finishUrl;
    }

    /**
     * @param PaymentException $paymentException
     * @param string $orderId
     * @return string
     */
    private function redirectToConfirmPageWorkflow(
        PaymentException $paymentException,
        string           $orderId
    ): string
    {
        // Shopware cancel order by itself, no need to cancel it here
        if ($paymentException->getErrorCode() != PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL) {
            $transactionId = $paymentException->getOrderTransactionId();

            LogHelper::addLog(
                Level::Error,
                $paymentException->getMessage(),
                ['orderTransactionId' => $transactionId, 'error' => $paymentException]
            );
        }

        $errorUrl = $this->router->generate('frontend.account.edit-order.page', ['orderId' => $orderId]);
        $urlQuery = \parse_url($errorUrl, \PHP_URL_QUERY) ? '&' : '?';
        return \sprintf('%s%serror-code=%s', $errorUrl, $urlQuery, $paymentException->getErrorCode());
    }
}
