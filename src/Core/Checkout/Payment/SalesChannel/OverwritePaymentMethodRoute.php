<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Core
 */

namespace MoptWorldline\Core\Checkout\Payment\SalesChannel;

use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\Payment;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRoute;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class OverwritePaymentMethodRoute extends PaymentMethodRoute
{
    private SalesChannelRepository $paymentMethodsRepository;
    private Session $session;
    private EntityRepository $customerRepository;

    /**
     * @param SalesChannelRepository $paymentMethodsRepository
     * @param EntityRepository $customerRepository
     */
    public function __construct(SalesChannelRepository $paymentMethodsRepository, EntityRepository $customerRepository)
    {
        parent::__construct($paymentMethodsRepository);
        $this->paymentMethodsRepository = $paymentMethodsRepository;
        $this->session = new Session();
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return PaymentMethodRoute
     */
    public function getDecorated(): PaymentMethodRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @param Request $request
     * @param SalesChannelContext $context
     * @param Criteria $criteria
     * @return PaymentMethodRouteResponse
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $defaultPaymentMethodId = null;
        $customer = $context->getCustomer();
        if (!is_null($customer)) {
            $defaultPaymentMethodId = $context->getCustomer()->getDefaultPaymentMethodId();
        }
        $criteria
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'))
            ->addAssociation('media');

        $result = $this->paymentMethodsRepository->search($criteria, $context);

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $result->getEntities();

        if ($request->query->getBoolean('onlyAvailable')) {
            $paymentMethods = $paymentMethods->filterByActiveRules($context);
        }

        $isDefaultSet = false;
        /** @var PaymentMethodEntity $method */
        foreach ($paymentMethods as $key => $method) {
            $customFields = $method->getCustomFields();
            if (!empty($customFields)
                && array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)
                && $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID] == Payment::SAVED_CARD_PAYMENT_METHOD_ID
            ) {
                $savedCardMethod = clone $method;
                $paymentMethods->remove($key);
                continue;
            }

            if ($method->getId() == $defaultPaymentMethodId) {
                $isDefaultSet = true;
                $method->setCustomFields(['default' => true]);
            }
        }

        if (isset($savedCardMethod)) {
            if ($savedCardsMethods = $this->getSavedPaymentMethods($context, $savedCardMethod, $isDefaultSet)) {
                $savedCardsMethods->merge($paymentMethods);
                $paymentMethods = $savedCardsMethods;
            }
        }

        return $this->buildResponse($result, $paymentMethods);
    }

    /**
     * @param EntitySearchResult $result
     * @param PaymentMethodCollection $methods
     * @return PaymentMethodRouteResponse
     */
    private function buildResponse(EntitySearchResult &$result, PaymentMethodCollection $methods): PaymentMethodRouteResponse
    {
        $result->assign(['entities' => $methods, 'elements' => $methods, 'total' => $methods->count()]);
        return new PaymentMethodRouteResponse($result);
    }

    /**
     * @param SalesChannelContext $context
     * @param PaymentMethodEntity $savedCardMethod
     * @param bool $isDefaultSet
     * @return PaymentMethodCollection|null
     */
    private function getSavedPaymentMethods(SalesChannelContext $context, PaymentMethodEntity $savedCardMethod, bool $isDefaultSet): ?PaymentMethodCollection
    {
        $customer = $context->getCustomer();
        if (is_null($customer) || !$customerCustomFields = $customer->getCustomFields()) {
            return null;
        }
        $tokenKey = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;
        if (!array_key_exists($tokenKey, $customerCustomFields)) {
            return null;
        }
        $defaultAccountToken = $this->session->get(Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_ACCOUNT_PAYMENT_CARD_TOKEN);
        if (!empty($defaultAccountToken)) {
            $savedCards = $this->processDefaultSavedCard($defaultAccountToken, $context);
        } else {
            $savedCards = $customerCustomFields[$tokenKey];
        }

        $sessionToken = $this->session->get($tokenKey);
        $savedCardsMethods = new PaymentMethodCollection();
        $uniqueId = false;
        foreach ($savedCards as $savedCard) {
            $newMethod = clone $savedCardMethod;
            $newMethod->setTranslated([
                'name' => "Pay with my previously saved card {$savedCard['paymentCard']} {$savedCard['title']}"
            ]);

            // Old saved cards compatibility
            if (!array_key_exists('redirectToken', $savedCard)) {
                $savedCard['redirectToken'] = false;
                // Redirect saved cards have '*' as mask, non-redirect have 'X'
                if (strpos($savedCard['paymentCard'], '******')) {
                    $savedCard['redirectToken'] = true;
                }
            }

            $customFields = [
                'token' => $savedCard['token'],
                'redirectToken' => $savedCard['redirectToken'],
            ];
            if ($sessionToken === $savedCard['token']) {
                $customFields['selected'] = true;
            }
            if (($savedCard['default'] && !$isDefaultSet) || $savedCard['token'] == $defaultAccountToken) {
                $customFields['default'] = 1;
            }
            $newMethod->setCustomFields($customFields);

            // For validation reasons we need to have at least one saved card method with uniqueId from saved payment methods
            if ($uniqueId) {
                $newMethod->setUniqueIdentifier(md5($savedCard['token']));
            } else {
                $uniqueId = true;
            }
            $savedCardsMethods->add($newMethod);
        }

        return $savedCardsMethods;
    }

    /**
     * @param string $token
     * @param SalesChannelContext $context
     * @return array
     */
    private function processDefaultSavedCard(string $token, SalesChannelContext $context): array
    {
        $customer = $context->getCustomer();
        $customFields = $customer->getCustomFields();
        $tokenKey = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;
        foreach ($customFields[$tokenKey] as $cardKey => $savedCard) {
            $customFields[$tokenKey][$cardKey]['default'] = $savedCard['token'] == $token ? 1 : 0;
        }

        $this->customerRepository->update([
            [
                'id' => $customer->getId(),
                'customFields' => $customFields
            ]
        ], $context->getContext());

        return $customFields[$tokenKey];
    }
}
