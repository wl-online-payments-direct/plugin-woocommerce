<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\SupportForm;

use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\Helper;
use MoptWorldline\Service\SupportAccount;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\ContextSource;
use Shopware\Core\Framework\Api\Controller\UserController;
use Shopware\Core\Framework\Api\Response\Type\Api\JsonType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Kernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Controller\PaymentMethod\PaymentMethodController;
use function Symfony\Component\Translation\t;

#[Route(defaults: ['_routeScope' => ['api']])]
class SupportFormController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $paymentMethodRepository;
    private EntityRepository $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private UserController $userController;
    private JsonType $jsonType;

    private array $messages;


    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $salesChannelRepository
     * @param EntityRepository $paymentMethodRepository
     * @param EntityRepository $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     * @param EntityRepository $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     * @param UserController $userController
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository    $salesChannelRepository,
        EntityRepository    $paymentMethodRepository,
        EntityRepository    $salesChannelPaymentRepository,
        PluginIdProvider    $pluginIdProvider,
        EntityRepository    $mediaRepository,
        MediaService        $mediaService,
        FileSaver           $fileSaver,
        UserController      $userController,
        JsonType            $jsonType
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelPaymentRepository = $salesChannelPaymentRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->userController = $userController;
        $this->jsonType = $jsonType;
    }

    #[Route(
        path: '/api/_action/support-form/send',
        name: 'api.action.support-form.send',
        methods: ['POST']
    )]
    public function send(Request $request, Context $context): JsonResponse
    {
        //todo snippets acc create

        $createAccount = $request->request->get('createAccount');
        $attachLog = $request->request->get('attachLog');
        $description = $request->request->get('description');

        $message = '321';
        try {
            $supportAccount = new SupportAccount($this->jsonType, $this->userController);
            $message .= json_encode($supportAccount);
        } catch (\Exception $e) {
            $message = '<br/>' . $e->getMessage();
        }

        $success = empty($message);

        return $this->response($success, $message);
    }


    /**
     * @param bool $success
     * @param string $message
     * @return JsonResponse
     */
    private function response(bool $success, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => $success,
            'message' => $message,
        ]);
    }
}
