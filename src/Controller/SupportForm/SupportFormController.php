<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\SupportForm;

use MoptWorldline\Service\SupportAccount;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Api\Controller\UserController;
use Shopware\Core\Framework\Api\Response\Type\Api\JsonType;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class SupportFormController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private FileSaver $fileSaver;
    private UserController $userController;
    private JsonType $jsonType;
    private MailService $mailService;

    /**
     * @param SystemConfigService $systemConfigService
     * @param FileSaver $fileSaver
     * @param UserController $userController
     * @param JsonType $jsonType
     * @param MailService $mailService
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        FileSaver           $fileSaver,
        UserController      $userController,
        JsonType            $jsonType,
        MailService         $mailService,
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->fileSaver = $fileSaver;
        $this->userController = $userController;
        $this->jsonType = $jsonType;
        $this->mailService = $mailService;
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
            $credentials = [];
            if ($createAccount) {
                $supportAccount = new SupportAccount($this->jsonType, $this->userController);
                $credentials = $supportAccount->getSupportCredentials();
            }

            $this->sendEmail($description, $attachLog, $credentials);
        } catch (\Exception $e) {
            $message = '<br/>' . $e->getMessage();
        }

        $success = empty($message);

        return $this->response($success, $message);
    }

    /**
     * @param string $description
     * @param bool $attachLog
     * @param array $credentials
     * @return void
     */
    private function sendEmail(string $description, bool $attachLog, array $credentials = []): void
    {
        $data = new ParameterBag();
        $data->set(
            'recipients',
            [
                'support@mediaopt.de' => 'Support'
            ]
        );

        $data->set('senderName', 'Plugin User'); //todo get server, get admin email (field?)
        if (!empty($credentials)) {
            $description .= json_encode($credentials);
        }
        $data->set('contentHtml', $description);
        $data->set('contentPlain', $description);
        $data->set('subject', 'Support request');
        $data->set('salesChannelId', '0190ba6d01fe737f97404eff7c72bd7b'); //todo get salesChannelId

        if ($attachLog) {
            //todo attach file
        }


        $this->mailService->send(
            $data->all(),
            Context::createDefaultContext(),
        );
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
