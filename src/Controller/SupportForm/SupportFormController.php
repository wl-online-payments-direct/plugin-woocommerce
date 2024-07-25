<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\SupportForm;

use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\SupportAccount;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Framework\Api\Controller\UserController;
use Shopware\Core\Framework\Api\Response\Type\Api\JsonType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\Kernel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[Route(defaults: ['_routeScope' => ['api']])]
class SupportFormController extends AbstractController
{
    private UserController $userController;
    private JsonType $jsonType;
    private MailService $mailService;

    /**
     * @param UserController $userController
     * @param JsonType $jsonType
     * @param MailService $mailService
     */
    public function __construct(
        UserController $userController,
        JsonType       $jsonType,
        MailService    $mailService,
    )
    {
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
        $createAccount = $request->request->get('createAccount');
        $attachLog = $request->request->get('attachLog');
        $contact = $request->request->get('contact');
        $description = $request->request->get('description');

        $errorMessage = '';
        try {
            $message = "$description<br/>contact email: $contact";
            if ($createAccount) {
                $supportAccount = new SupportAccount($this->jsonType, $this->userController);
                $credentials = $supportAccount->getSupportCredentials();
                $message .= '<br/> support account: ' . json_encode($credentials);
            }
            $this->sendEmail($message, $attachLog);
        } catch (ConstraintViolationException $e) {
            foreach ($e->getErrors() as $error) {
                $errorMessage .= '<br/>' . json_encode($error);
            }
        } catch (\Exception $e) {
            $errorMessage = '<br/>' . $e->getMessage();
        }

        return $this->response(empty($errorMessage), $errorMessage);
    }

    #[Route(
        path: '/api/_action/support-form/download_log',
        name: 'api.action.support-form.download_log',
        methods: ['POST']
    )]
    public function downloadLog(): JsonResponse
    {
        $archivePath = LogHelper::getArchive();
        $content = file_get_contents($archivePath);
        $response = new JsonResponse();
        $response->setContent($content);

        return $response;
    }


    /**
     * @param string $message
     * @param bool $attachLog
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    private function sendEmail(string $message, bool $attachLog): void
    {
        $data = new ParameterBag();
        $data->set('recipients', ['support@mediaopt.de' => 'Support']);

        $data->set('senderName', 'Plugin User');
        $data->set('contentHtml', $message);
        $data->set('contentPlain', $message);
        $data->set('subject', 'Support request');
        $data->set('salesChannelId', $this->getSalesChannelId());

        if ($attachLog) {
            $archivePath = LogHelper::getArchive();
            $attachment = [
                [
                    'content' => file_get_contents($archivePath),
                    'fileName' => 'log.zip',
                    'mimeType' => 'application/zip',
                ],
            ];
            $data->set('binAttachments', $attachment);
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

    /**
     * @return string
     * @throws \Doctrine\DBAL\Exception
     */
    private function getSalesChannelId(): string
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('lower(hex(id)) as id')
            ->from('sales_channel');

        return $qb->fetchOne();
    }
}
