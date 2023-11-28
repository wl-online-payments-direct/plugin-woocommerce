<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

class AdminTranslate
{
    static public function trans($locale, $id)
    {
        $path = __DIR__ . "/../Resources/snippet/storefront/worldline.$locale.json";
        if (!file_exists($path)) {
            return $id;
        }

        //Adding module prefix
        $id = "worldline.$id";

        $transJson = file_get_contents($path);
        $dictionary = json_decode($transJson, true);

        $exploded = explode('.', $id);

        $translation = $id;
        foreach ($exploded as $item) {
            $dictionary = self::getTranslation($item, $dictionary);
            if (is_string($dictionary)) {
                $translation = $dictionary;
            }
        }

        return $translation;
    }

    static private function getTranslation($id, $dictionary)
    {
        if (is_array($dictionary) && array_key_exists($id, $dictionary)) {
            return $dictionary[$id];
        }
        return false;
    }

    /**
     * @param string $statusCode
     * @param string $locale
     * @return string
     */
    public static function getReadableStatus(string $statusCode, string $locale): string
    {
        $label = AdminTranslate::trans($locale, 'unknownStatus');
        if (array_key_exists($statusCode, Payment::STATUS_LABELS)) {
            $label = AdminTranslate::trans($locale, "transactionStatus." . Payment::STATUS_LABELS[$statusCode]);
        }

        return $label . " ($statusCode)";
    }
}
