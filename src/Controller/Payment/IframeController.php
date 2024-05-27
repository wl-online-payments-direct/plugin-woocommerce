<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Payment;

use Exception;
use Monolog\Level;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\LogHelper;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\Session;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class IframeController extends AbstractController
{
    public SystemConfigService $systemConfigService;
    private Session $session;
    private EntityRepository $customerRepository;

    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepository          $customerRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->session = new Session();
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(
        path: '/worldline_iframe',
        name: 'worldline.iframe',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET'],
    )]
    public function showIframe(Request $request): JsonResponse
    {
        $salesChannelId = $request->get('salesChannelId');
        $token = $request->get('token');
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
        $tokenizationUrl = $adapter->createHostedTokenizationUrl($token);

        return new JsonResponse([
            'url' => $tokenizationUrl
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(
        path: '/worldline_cardToken',
        name: 'worldline.cardToken',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET'],
    )]
    public function saveCardToken(Request $request): JsonResponse
    {
        $token = $request->get('worldline_cardToken') ?: null;
        $this->session->set(Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN, $token);
        return new JsonResponse([]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    #[Route(
        path: '/worldline_accountCardToken',
        name: 'worldline.accountCardToken',
        defaults: ['XmlHttpRequest' => true],
        methods: ['GET'],
    )]
    public function saveAccountCardToken(Request $request): JsonResponse
    {
        $token = $request->get('worldline_accountCardToken') ?: null;
        $this->session->set(Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_ACCOUNT_PAYMENT_CARD_TOKEN, $token);
        return new JsonResponse(['success'=>true]);
    }

    /**
     * @param string $tokenId
     * @param SalesChannelContext $context
     * @param CustomerEntity $customer
     * @return RedirectResponse
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    #[Route(
        path: '/worldline/card/delete/{tokenId}',
        name: 'worldline.card.delete',
        options: ['seo' => false],
        methods: ['POST'],
        defaults: ['_loginRequired' => true]
    )]
    public function deleteCard(string $tokenId, SalesChannelContext $context, CustomerEntity $customer): RedirectResponse
    {
        $success = true;
        if (!$tokenId) {
            throw new MissingRequestParameterException('tokenId');
        }

        try {
            $fields = $this->prepareCustomField($tokenId, $customer);
            $this->customerRepository->update([
                [
                    'id' => $customer->getId(),
                    'customFields' => $fields
                ]
            ], $context->getContext());
            $adapter = new WorldlineSDKAdapter($this->systemConfigService, $context->getSalesChannelId());
            $adapter->deleteToken($tokenId);
        } catch (Exception $exception) {
            $success = false;
            LogHelper::addLog(Level::Error, $exception->getMessage());
        }

        return new RedirectResponse(
            $this->container->get('router')
                ->generate('frontend.account.payment.page', ['cardDeleted' => $success])
        );
    }

    /**
     * @param string $tokenId
     * @param CustomerEntity $customer
     * @return mixed
     * @throws Exception
     */
    private function prepareCustomField(string $tokenId, CustomerEntity $customer)
    {
        $key = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;

        if (!$customerCustomFields = $customer->getCustomFields()) {
            throw new Exception('No custom fields');
        }

        if (!array_key_exists($key, $customerCustomFields)) {
            throw new Exception('No saved cards');
        }

        $savedCards = $customerCustomFields[$key];
        if (!array_key_exists($tokenId, $savedCards)) {
            throw new Exception("Can not find saved card with token $tokenId");
        }

        //If customer remove default card - set random card as default
        $wasDefault = $savedCards[$tokenId]['default'];
        unset($savedCards[$tokenId]);
        if ($wasDefault && !empty($savedCards)) {
            $savedCards[key($savedCards)]['default'] = true;
        }
        $customerCustomFields[$key] = $savedCards;
        return $customerCustomFields;
    }
}
