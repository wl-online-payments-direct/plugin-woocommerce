<?php declare(strict_types=1);

namespace MoptWorldline\Subscriber;

use Shopware\Core\Content\Media\Event\MediaFileExtensionWhitelistEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SupportFormSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MediaFileExtensionWhitelistEvent::class => 'addEntryToFileExtensionWhitelist'
        ];
    }

    public function addEntryToFileExtensionWhitelist(MediaFileExtensionWhitelistEvent $event): void
    {
        $whiteList = $event->getWhitelist();
        $whiteList[] = 'zip';

        $event->setWhitelist($whiteList);
    }
}