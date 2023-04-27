<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Logger;
use OnlinePayments\Sdk\Domain\PaymentProduct;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class MediaHelper
{
    const TEMP_NAME = 'image-import-from-url';
    const MEDIA_FOLDER = 'payment_method';

    private Logger $logger;
    private EntityRepositoryInterface $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;

    /**
     * @param EntityRepositoryInterface $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     * @param Logger $logger
     */
    public function __construct(
        EntityRepositoryInterface $mediaRepository,
        MediaService              $mediaService,
        FileSaver                 $fileSaver,
        Logger                    $logger
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->logger = $logger;
    }

    /**
     * @param PaymentProduct $product
     * @param Context $context
     * @return string|null
     */
    public function createLogo(PaymentProduct $product, Context $context): ?string
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

        $filePathParts = explode('/', $imageUrl);
        $fileNameParts = explode('.', array_pop($filePathParts));

        $fileName = $fileNameParts[0];
        $fileExtension = $fileNameParts[1];

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
                $fileName,
                $mediaId,
                $context
            );
        } catch (\Exception $e) {
            $this->logger->log(Logger::ERROR, $e->getMessage());
        }

        return $mediaId;
    }
}