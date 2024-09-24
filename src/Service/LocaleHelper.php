<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Shopware\Core\Kernel;

class LocaleHelper
{
    /**
     * @param string|null $localeId
     * @return string
     */
    public static function getCode(?string $localeId): string
    {
        $connection = Kernel::getConnection();

        $qb = $connection->createQueryBuilder();
        $qb->select('l.code')
            ->from('locale', 'l')
            ->where("l.id = UNHEX(:localeId)")
            ->setParameter('localeId', $localeId);

        try {
            $locale = $qb->fetchOne();
        } catch (\Exception $e){
            return '';
        }

        return str_replace('-', '_', $locale);
    }
}