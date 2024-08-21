<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\SupportForm;

use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\SupportAccount;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaException;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Api\Controller\UserController;
use Shopware\Core\Framework\Api\Response\Type\Api\JsonType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
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
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;

    /**
     * @param UserController $userController
     * @param JsonType $jsonType
     * @param MailService $mailService
     * @param EntityRepository $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     */
    public function __construct(
        UserController   $userController,
        JsonType         $jsonType,
        MailService      $mailService,
        EntityRepository $mediaRepository,
        MediaService     $mediaService,
        FileSaver        $fileSaver,
    )
    {
        $this->userController = $userController;
        $this->jsonType = $jsonType;
        $this->mailService = $mailService;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
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
        if (empty($contact) || empty($description)) {
            $errorMessage = 'Please provide an email and description.';
        }
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
        return new JsonResponse([
            'mediaUrl' => $this->getUrl($archivePath),
            'mediaName' => Form::LOG_FILENAME . '.' . Form::LOG_FILE_EXT,
        ]);
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
                    'fileName' => Form::LOG_FILENAME . '.' . Form::LOG_FILE_EXT,
                    'mimeType' => Form::LOG_FILE_MIME_TYPE,
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

    /**
     * @param string $archivePath
     * @return string|null
     * @throws \Doctrine\DBAL\Exception
     */
    private function getUrl(string $archivePath): mixed
    {
        $mediaId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $url = '';
        try {
            $mediaId = $this->mediaService->createMediaInFolder('', $context, false);
            $mediaFile = new MediaFile(
                $archivePath,
                Form::LOG_FILE_MIME_TYPE,
                Form::LOG_FILE_EXT,
                filesize($archivePath)
            );
            $this->fileSaver->persistFileToMedia($mediaFile, Form::LOG_FILENAME, $mediaId, $context);
            $url = $this->getPath($mediaId);
            debug($url);
        } catch (MediaException $e) {
            if ($e->getErrorCode() == MediaException::MEDIA_DUPLICATED_FILE_NAME) {
                $this->clearMedia($mediaId, $context);
                $this->clearMedia($this->getDuplicateId(), $context);
                $url = $this->getUrl($archivePath);
            }
        }  catch (\Exception $e) {
            $this->clearMedia($mediaId, $context);
        }
        return $url;
    }

    /**
     * @param $mediaId
     * @param $context
     * @return void
     */
    private function clearMedia($mediaId, $context): void
    {
        $this->mediaRepository->delete([['id' => $mediaId]], $context);
    }

    /**
     * @param string $mediaId
     * @return string|null
     * @throws \Doctrine\DBAL\Exception
     */
    private function getPath(string $mediaId): mixed
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('m.path')
            ->from('media', 'm')
            ->where('m.id = UNHEX(:id)')
            ->setParameter('id', $mediaId);
        debug($qb->getSQL());
        return $qb->fetchOne();
    }

    /**
     * @return string|null
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDuplicateId(): mixed
    {
        $filename = Form::LOG_FILENAME;
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('LOWER(HEX(m.id)) as id')
            ->from('media', 'm')
            ->where("m.file_name = '$filename'");
        return $qb->fetchOne();
    }
}
