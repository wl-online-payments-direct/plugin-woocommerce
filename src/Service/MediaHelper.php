<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Logger;
use OnlinePayments\Sdk\Domain\PaymentProduct;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class MediaHelper
{
    const TEMP_NAME = 'image-import-from-url';
    const MEDIA_FOLDER = 'payment_method';
    const FILE_PREFIX = 'Worldline_logo ';

    private Logger $logger;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private EntityRepository $paymentRepository;

    /**
     * @param EntityRepository $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     * @param Logger $logger
     * @param EntityRepository $paymentRepository
     */
    public function __construct(
        EntityRepository $mediaRepository,
        MediaService     $mediaService,
        FileSaver        $fileSaver,
        Logger           $logger,
        EntityRepository $paymentRepository
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->logger = $logger;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @param PaymentProduct $product
     * @param Context $context
     * @return string|null
     */
    public function createProductLogo(PaymentProduct $product, Context $context): ?string
    {
        $productData = PaymentProducts::getPaymentProductDetails($product->getId());
        if ($productData['fileName'] === PaymentProducts::PAYMENT_PRODUCT_MEDIA_DEFAULT) {
            return $this->addImageToMediaFromURL($product->getDisplayHints()->getLogo(), $context);
        }
        return $this->createMediaFromFile($productData['logo'], $productData['fileName'], 'svg', $context);
    }

    /**
     * @param string $imageUrl
     * @param Context $context
     * @return string|null
     */
    public function addImageToMediaFromURL(string $imageUrl, Context $context): ?string
    {
        $mediaId = null;

        $filePathParts = pathinfo($imageUrl);
        $fileName = self::FILE_PREFIX . $filePathParts['filename'];
        $fileExtension = $filePathParts['extension'];

        if ($fileName && $fileExtension) {
            $filePath = tempnam(sys_get_temp_dir(), self::TEMP_NAME);
            file_put_contents($filePath, file_get_contents($imageUrl));
            $mediaId = $this->createMediaFromFile($filePath, $fileName, $fileExtension, $context);
        }

        return $mediaId;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @param string $fileExtension
     * @param Context $context
     * @return string|null
     */
    public function createMediaFromFile(string $filePath, string $fileName, string $fileExtension, Context $context): ?string
    {
        $mediaId = null;

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);

        try {
            $mediaFile = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize);
            $mediaId = $this->mediaService->createMediaInFolder(self::MEDIA_FOLDER, $context, false);
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                self::FILE_PREFIX . $fileName,
                $mediaId,
                $context
            );
        } catch (\Exception $e) {
            $this->logger->log(Logger::ERROR, $e->getMessage());
            $this->mediaRepository->delete([['id' => $mediaId]], $context);
            $mediaId = null;
        }

        return $mediaId;
    }

    /**
     * @param array $dbMethod
     * @param array $method
     * @param Context $context
     * @return string
     */
    public function getSystemMethodLogo(array $dbMethod, array $method, Context $context): string
    {
        if (array_key_exists('mediaId', $dbMethod)) {
            $mediaId = $dbMethod['mediaId'] ?: $this->createSystemLogo($method['id'], $dbMethod['internalId'], $context);
        } else {
            return '';
        }
        if (is_null($mediaId)) {
            return '';
        }
        return MediaHelper::loadLogo($mediaId, $context) ?: '';
    }

    /**
     * @param string $logoName
     * @param string $paymentMethodId
     * @param Context $context
     * @return ?string
     */
    private function createSystemLogo(string $logoName, string $paymentMethodId, Context $context): ?string
    {
        $logoPath = \sprintf('%s/%s.png', PaymentProducts::PAYMENT_PRODUCT_MEDIA_DIR, $logoName);
        $mediaId = $this->createMediaFromFile($logoPath, $logoName, 'png', $context);

        $paymentMethod = [
            'id' => $paymentMethodId,
            'mediaId' => $mediaId
        ];
        $this->paymentRepository->update([$paymentMethod], $context);

        return $mediaId;
    }

    /**
     * @param string $mediaId
     * @param Context $context
     * @return string
     */
    public function loadLogo(string $mediaId, Context $context): string
    {
        $result = $this->mediaRepository->search(new Criteria([$mediaId]), $context);
        $url = '';
        /** @var MediaEntity $media */
        foreach ($result->getElements() as $media) {
            $url = $media->getUrl();
            break;
        }
        return $url;
    }

    /**
     * @param array $dbMethod
     * @param PaymentProduct $product
     * @param Context $context
     * @return string
     */
    public function getPaymentMethodLogo(array $dbMethod, PaymentProduct $product, Context $context): string
    {
        $mediaId = null;
        if (!is_null($dbMethod['mediaId'])) {
            $mediaId = $dbMethod['mediaId'];
        } elseif ($dbMethod['internalId']) {
            $mediaId = $this->createProductLogo($product, $context);
            $paymentMethod = [
                'id' => $dbMethod['internalId'],
                'mediaId' => $mediaId
            ];
            $this->paymentRepository->update([$paymentMethod], $context);
        }

        if (empty($mediaId)) {
            return $product->getDisplayHints()->getLogo();
        }
        return $this->loadLogo($mediaId, $context);
    }
}