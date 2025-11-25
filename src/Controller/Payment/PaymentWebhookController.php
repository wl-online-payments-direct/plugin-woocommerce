<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Payment;

use Monolog\Level;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\OrderHelper;
use OnlinePayments\Sdk\Webhooks\InMemorySecretKeyStore;
use OnlinePayments\Sdk\Webhooks\WebhooksHelper;
use MoptWorldline\Service\PaymentHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PaymentWebhookController extends AbstractController
{
    private RouterInterface $router;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private AsynchronousPaymentHandlerInterface $paymentHandler;
    private OrderTransactionStateHandler $transactionStateHandler;
    private SystemConfigService $systemConfigService;
    private TranslatorInterface $translator;
    private StateMachineRegistry $stateMachineRegistry;

    public function __construct(
        SystemConfigService                 $systemConfigService,
        EntityRepository                    $orderRepository,
        EntityRepository                    $customerRepository,
        AsynchronousPaymentHandlerInterface $paymentHandler,
        OrderTransactionStateHandler        $transactionStateHandler,
        RouterInterface                     $router,
        TranslatorInterface                 $translator,
        StateMachineRegistry                $stateMachineRegistry
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->paymentHandler = $paymentHandler;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->router = $router;
        $this->translator = $translator;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return Response
     * @throws \Exception
     */
    #[Route(
        path: '/worldline/payment/webhook',
        name: 'worldline.payment.webhook',
        methods: ['POST']
    )]
    public function webhook(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $data = $this->parseRequest($request, $salesChannelContext->getSalesChannelId());
        if ($data === false) {
            return new Response();
        }

        try {
            $order = OrderHelper::getOrder(
                $salesChannelContext->getContext(),
                $this->orderRepository,
                $data['hostedCheckoutId']
            );
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage(), $request->request->all());
            return new Response();
        }

        $paymentHandler = new PaymentHandler(
            $this->systemConfigService,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            $salesChannelContext->getContext(),
            $this->transactionStateHandler,
            $this->stateMachineRegistry
        );
        $logger = new LogHelper(
            new WorldlineSDKAdapter($this->systemConfigService, $salesChannelContext->getSalesChannelId())
        );
        $logger->setTranslator($this->translator);
        $logger->paymentLog($order->getOrderNumber(), 'webhook', 0, $request->request->all());

        $paymentHandler->updatePaymentStatus($data['hostedCheckoutId']);

        return new Response();
    }

    /**
     * @param Request $request
     * @param string $salesChannelId
     * @return array|false
     */
    private function parseRequest(Request $request, string $salesChannelId)
    {
        // Get rid of additional array level
        $headers = $request->headers->all();
        foreach ($headers as $key => $header) {
            $headers[$key] = $header[0];
        }

        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
        $keys = new InMemorySecretKeyStore($adapter->getWebhookCredentials());
        $helper = new WebhooksHelper($keys);

        $additionalData = [$request->getContent(), $request->headers->all()];
        try {
            //Request validation
            $event = $helper->unmarshal($request->getContent(), $headers);

            $payment = $event->getPayment();
            if (is_null($payment)) {
                LogHelper::addLog(Level::Error, 'errorWithWebhookRequest', $additionalData);
                return false;
            }

            $paymentId = $payment->getId();
            $paymentId = explode('_', $paymentId);
            $hostedCheckoutId = $paymentId[0];
            $statusCode = $event->getPayment()->getStatusOutput()->getStatusCode();
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error,$e->getMessage(), $additionalData);
            return false;
        }

        return [
            'hostedCheckoutId' => $hostedCheckoutId,
            'statusCode' => $statusCode
        ];
    }
}
