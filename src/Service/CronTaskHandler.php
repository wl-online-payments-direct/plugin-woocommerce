<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;

class CronTaskHandler extends ScheduledTaskHandler
{
    private EntityRepositoryInterface $salesChannelRepository;
    private SystemConfigService $systemConfigService;
    private Logger $logger;
    private EntityRepositoryInterface $orderRepository;
    private EntityRepositoryInterface $customerRepository;
    private OrderTransactionStateHandler $transactionStateHandler;
    private TranslatorInterface $translator;

    public function __construct(
        EntityRepositoryInterface    $scheduledTaskRepository,
        EntityRepositoryInterface    $salesChannelRepository,
        SystemConfigService          $systemConfigService,
        Logger                       $logger,
        EntityRepositoryInterface    $orderRepository,
        EntityRepositoryInterface    $customerRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        TranslatorInterface          $translator
    )
    {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->translator = $translator;
        parent::__construct($scheduledTaskRepository);
    }

    public static function getHandledMessages(): iterable
    {
        return [CronTask::class];
    }

    public function run(): void
    {
        $salesChannels = $this->salesChannelRepository->search(new Criteria(), Context::createDefaultContext());
        foreach ($salesChannels as $salesChannel) {

            $ordersList = $this->getOrderList($salesChannel->getId());

            if (empty($ordersList)) {
                continue;
            }

            foreach ($ordersList as $order) {
                $this->processOrder($order);
            }
        }
    }

    /**
     * @param $salesChannelId
     * @return array|\mixed[][]|void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getOrderList($salesChannelId)
    {
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $captureConfig = $adapter->getPluginConfig(Form::AUTO_CAPTURE);
        if ($captureConfig === Form::AUTO_CAPTURE_DISABLED) {
            return;
        }

        $daysInterval = $this->getDaysInterval($captureConfig);
        $connection = Kernel::getConnection();

        $qb = $connection->createQueryBuilder();
        $qb->select('ot.custom_fields, ot.updated_at')
            ->from('`order`', 'o')
            ->leftJoin('o', 'order_transaction', 'ot', "ot.order_id = o.id")
            ->where("o.sales_channel_id = UNHEX(:salesChannelId)")
            ->andWhere(
                $qb->expr()->or(
                    $qb->expr()->like('ot.custom_fields', "'%payment_transaction_status\": \"5%'"),
                    $qb->expr()->like('ot.custom_fields', "'%payment_transaction_status\": \"56%'")
                )
            )
            ->orderBy('ot.updated_at', 'DESC')
            ->setParameter('salesChannelId', $salesChannelId);

        if ($daysInterval > 0) {
            $qb->andWhere("DATEDIFF(NOW(), ot.updated_at) > :daysInterval")
                ->setParameter('daysInterval', $daysInterval);
        }

        return $qb->execute()->fetchAllAssociative();
    }

    /**
     * @return int
     */
    private function getDaysInterval($captureConfig)
    {
        return (int)filter_var($captureConfig, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * @param $order
     * @return void
     * @throws \Exception
     */
    private function processOrder($order)
    {
        if (!array_key_exists('custom_fields', $order)) {
            return;
        }
        $customFields = json_decode($order['custom_fields'], true);
        if (!array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID, $customFields)) {
            return;
        }
        $hostedCheckoutId = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID];

        $order = PaymentHandler::getOrder(
            Context::createDefaultContext(),
            $this->orderRepository,
            $hostedCheckoutId
        );

        $paymentHandler = new PaymentHandler(
            $this->systemConfigService,
            $this->logger,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            Context::createDefaultContext(),
            $this->transactionStateHandler
        );

        $amount = (int)round($order->getAmountTotal() * 100);
        $paymentHandler->capturePayment($hostedCheckoutId, $amount, []);
    }
}
