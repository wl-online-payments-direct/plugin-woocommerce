<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Level;
use Monolog\Logger;
use OstSixColorEmotion\Services\PluginConfig;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Kernel;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\Rule\CurrencyRule;

class RuleHelper
{
    private EntityRepository $ruleRepository;
    private EntityRepository $paymentRepository;
    private EntityRepository $ruleConditionRepository;

    /**
     * @param EntityRepository $ruleRepository
     * @param EntityRepository $paymentRepository
     * @param EntityRepository $ruleConditionRepository
     */
    public function __construct(
        EntityRepository $ruleRepository,
        EntityRepository $paymentRepository,
        EntityRepository $ruleConditionRepository
    )
    {
        $this->ruleRepository = $ruleRepository;
        $this->paymentRepository = $paymentRepository;
        $this->ruleConditionRepository = $ruleConditionRepository;
    }

    /**
     * @param string $methodId
     * @param string $internalMethodId
     * @return void
     */
    public function applyRule(string $methodId, string $internalMethodId): void
    {
        $ruleId = $this->getRuleId($methodId);
        $this->paymentRepository->update(
            [['id' => $internalMethodId, 'availabilityRuleId' => $ruleId]],
            Context::createDefaultContext()
        );
    }

    /**
     * @param string $methodId
     * @return string
     */
    private function getRuleId(string $methodId): string
    {
        $dbRuleId = $this->isDbRuleExist($methodId);
        if ($dbRuleId) {
            return $dbRuleId;
        }

        [$ruleId, $rule, $condition] = $this->getRuleNode($methodId);

        $context = Context::createDefaultContext();
        $this->ruleRepository->create($rule, $context);
        $this->ruleConditionRepository->create($condition, $context);

        return $ruleId;
    }

    /**
     * @param $methodId
     * @return false|mixed
     */
    private function isDbRuleExist($methodId)
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $name = $this->getRuleName($methodId);
        try {
            $qb->select('LOWER(HEX(r.id)) as id')
                ->from('rule', 'r')
                ->where("r.name = '$name'");
            return $qb->fetchOne();
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage());
        }
        return false;
    }

    /**
     * @param string $methodId
     * @return string
     */
    private function getRuleName(string $methodId): string
    {
        if (array_key_exists($methodId, PaymentProducts::PAYMENT_PRODUCT_NAMES)) {
            return PaymentProducts::PAYMENT_PRODUCT_NAMES[$methodId] . " payment method rule";
        }
        return '';
    }

    /**
     * @param string $methodId
     * @return array
     */
    private function getRuleNode(string $methodId): array
    {
        $rules = PaymentProducts::PAYMENT_PRODUCT_RULES[$methodId];
        $currencyIds = $this->getCurrencyIDs($rules);
        $name = $this->getRuleName($methodId);

        $ruleId = Uuid::randomHex();
        $rule = [['id' => $ruleId, 'name' => $name, 'priority' => 1]];
        $condition = [
            [
                'type' => 'currency',
                'ruleId' => $ruleId,
                'value' => [
                    'currencyIds' => $currencyIds,
                    'operator' => '=',
                ],
            ],
        ];

        return [$ruleId, $rule, $condition];
    }

    /**
     * @param array $currencyISO
     * @return array
     */
    private function getCurrencyIDs(array $currencyISO): array
    {
        $codes = "'" . implode("','", $currencyISO) . "'";
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        try {

            $qb->select('lower(hex(id)) as id')
                ->from('currency')
                ->where("iso_code IN ($codes)");

            return $qb->fetchFirstColumn();
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage());
        }
        return [];
    }
}