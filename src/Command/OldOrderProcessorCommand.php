<?php declare(strict_types=1);

namespace MoptWorldline\Command;

use MoptWorldline\Service\OldOrderProcessor;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'mopt-worldline:old_order_processor')]
class OldOrderProcessorCommand extends Command
{
    private EntityRepository $salesChannelRepository;
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private OrderTransactionStateHandler $transactionStateHandler;
    private TranslatorInterface $translator;
    private StateMachineRegistry $stateMachineRegistry;

    /**
     * @param EntityRepository $salesChannelRepository
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param TranslatorInterface $translator
     * @param StateMachineRegistry $stateMachineRegistry
     */
    public function __construct(
        EntityRepository             $salesChannelRepository,
        SystemConfigService          $systemConfigService,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        TranslatorInterface          $translator,
        StateMachineRegistry         $stateMachineRegistry
    )
    {
        parent::__construct();
        $this->salesChannelRepository = $salesChannelRepository;
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->translator = $translator;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $oldOrderProcessor = new OldOrderProcessor(
                $this->salesChannelRepository,
                $this->systemConfigService,
                $this->orderRepository,
                $this->customerRepository,
                $this->transactionStateHandler,
                $this->translator,
                $this->stateMachineRegistry,
            );

            $oldOrderProcessor->process();

            $output->writeln('All old orders successfully processed.');
            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln('There was an error:');
            $output->writeln($exception->getMessage());
            $output->writeln($exception->getTraceAsString());
            return Command::FAILURE;
        }
    }
}