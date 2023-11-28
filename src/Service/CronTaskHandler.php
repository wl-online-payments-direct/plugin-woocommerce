<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Kernel;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Contracts\Translation\TranslatorInterface;

class CronTaskHandler extends ScheduledTaskHandler
{
    private EntityRepository $salesChannelRepository;
    private SystemConfigService $systemConfigService;
    private Logger $logger;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private OrderTransactionStateHandler $transactionStateHandler;
    private TranslatorInterface $translator;
    private StateMachineRegistry $stateMachineRegistry;

    const CANCELLATION_MODE = 'cancellation';
    const CAPTURE_MODE = 'capture';

    public function __construct(
        EntityRepository             $scheduledTaskRepository,
        EntityRepository             $salesChannelRepository,
        SystemConfigService          $systemConfigService,
        Logger                       $logger,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        TranslatorInterface          $translator,
        StateMachineRegistry         $stateMachineRegistry
    )
    {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->translator = $translator;
        $this->stateMachineRegistry = $stateMachineRegistry;
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

            $captureOrdersList = $this->getOrderList($salesChannel->getId(), self::CAPTURE_MODE);
            foreach ($captureOrdersList as $order) {
                $this->processOrder($order);
            }
            $cancellationOrdersList = $this->getOrderList($salesChannel->getId(), self::CANCELLATION_MODE);
            foreach ($cancellationOrdersList as $order) {
                $transactionId = strtolower($order['trans_id']);
                $this->transactionStateHandler->cancel($transactionId, Context::createDefaultContext());
            }
        }
    }

    /**
     * @param string $salesChannelId
     * @param string $mode
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getOrderList(string $salesChannelId, string $mode): array
    {
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $connection = Kernel::getConnection();

        $qb = $connection->createQueryBuilder();

        $qb->select('HEX(o.id) as id, o.custom_fields, o.updated_at')
            ->from('`order`', 'o')
            ->where("o.sales_channel_id = UNHEX(:salesChannelId)")
            ->orderBy('o.updated_at', 'DESC')
            ->setParameter('salesChannelId', $salesChannelId);

        switch ($mode) {
            case "capture":
            {
                $captureConfig = $adapter->getPluginConfig(Form::AUTO_CAPTURE);
                if ($captureConfig === Form::AUTO_PROCESSING_DISABLED) {
                    return [];
                }

                $qb->andWhere(
                    $qb->expr()->or(
                        $qb->expr()->like('o.custom_fields', "'%payment_transaction_status\": \"5%'"),
                        $qb->expr()->like('o.custom_fields', "'%payment_transaction_status\": \"56%'")
                    )
                );

                $timeInterval = $this->getTimeInterval($captureConfig) * 60 * 60 * 24;
                break;
            }
            case "cancellation":
            {
                $cancellationConfig = $adapter->getPluginConfig(Form::AUTO_CANCEL);
                if ($cancellationConfig === Form::AUTO_PROCESSING_DISABLED) {
                    return [];
                }

                $qb->select('o.custom_fields, hex(ot.id) as trans_id, sms.technical_name')
                    ->leftJoin('o', 'order_transaction', 'ot', "ot.order_id = o.id")
                    ->leftJoin('ot', 'state_machine_state', 'sms', "sms.id = ot.state_id")
                    ->andWhere(
                        $qb->expr()->or(
                            $qb->expr()->like('o.custom_fields', "'%payment_transaction_status\": \"0%'"),
                            $qb->expr()->like('o.custom_fields', "'%payment_transaction_status\": \"46%'")
                        )
                    )
                    ->andWhere("sms.technical_name != :technicalName")
                    ->setParameter('technicalName', OrderStates::STATE_CANCELLED);

                $timeInterval = $this->getTimeInterval($cancellationConfig) * 60 * 60;
                break;
            }
            default:
                return [];
        }

        if ($timeInterval > 0) {
            $qb->andWhere("UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(o.updated_at) > :timeInterval")
                ->setParameter('timeInterval', $timeInterval);
        }

        return $qb->execute()->fetchAllAssociative() ?: [];
    }

    /**
     * @return int
     */
    private function getTimeInterval($config)
    {
        return (int)filter_var($config, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * @param array $order
     * @return void
     * @throws \Exception
     */
    private function processOrder(array $order)
    {
        if (!array_key_exists('custom_fields', $order)) {
            return;
        }
        $customFields = json_decode($order['custom_fields'], true);
        if (!array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID, $customFields)) {
            return;
        }
        $hostedCheckoutId = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID];

        $order = OrderHelper::getOrder(
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
            $this->transactionStateHandler,
            $this->stateMachineRegistry
        );

        $amount = (int)round($order->getAmountTotal() * 100);
        $paymentHandler->capturePayment($hostedCheckoutId, $amount, []);
    }
}
