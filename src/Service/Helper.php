<?php

namespace MoptWorldline\Service;

use Shopware\Core\Kernel;

class Helper
{
    /**
     * @param string|null $salesChannelId
     * @return array|null[]
     */
    public static function getSalesChannelData(?string $salesChannelId): array
    {
        $connection = Kernel::getConnection();

        $qb = $connection->createQueryBuilder();
        $qb->select('c.iso3, cu.iso_code')
            ->from('sales_channel', 'sc')
            ->leftJoin('sc', 'country', 'c', 'c.id = sc.country_id')
            ->leftJoin('sc', 'currency', 'cu', 'cu.id = sc.currency_id');

        if (is_null($salesChannelId)) {
            $qb->setMaxResults(1);
        } else {
            $qb->where("sc.id = UNHEX(:salesChannelId)")
                ->setParameter('salesChannelId', $salesChannelId);
        }

        try {
            $salesChannelData = $qb->fetchAssociative();
        } catch (\Exception $e){
            return [null, null];
        }

        if (is_array($salesChannelData)
            && array_key_exists('iso3', $salesChannelData)
            && array_key_exists('iso_code', $salesChannelData)
        ) {
            return [$salesChannelData['iso3'], $salesChannelData['iso_code']];
        }
        return [null, null];
    }
}
