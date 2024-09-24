<?php

namespace MoptWorldline\Service;

use Monolog\Level;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Controller\UserController;
use Shopware\Core\Framework\Api\Response\Type\Api\JsonType;
use Shopware\Core\Framework\Context;
use Shopware\Core\Kernel;
use Symfony\Component\HttpFoundation\Request;

class SupportAccount
{

    private JsonType $jsonType;
    private UserController $userController;

    private const SUPPORT_ACCOUNT = [
        'admin' => true,
        'active' => true,
        'email' => 'support@mediaopt.de',
        'firstName' => 'Support',
        'lastName' => "User",
        'timeZone' => "Europe/Berlin",
        'username' => "payment_support_account",
    ];

    private const DEFAULT_LOCAL_CODE = 'en-GB';

    public function __construct(JsonType $jsonType, UserController $userController)
    {
        $this->jsonType = $jsonType;
        $this->userController = $userController;
    }

    public function getSupportCredentials(): array
    {
        $username = self::SUPPORT_ACCOUNT['username'];

        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('lower(hex(id)) as id')
            ->from('user')
            ->where("username = '$username'");

        $id = '';
        try {
            $id = $qb->fetchOne() ?: null;
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage());
        }

        return $this->createSupportAccount($id);
    }

    /**
     * @param string|null $id
     * @return array
     */
    private function createSupportAccount(?string $id): array
    {
        $source = new AdminApiSource(null);
        $source->setPermissions(['user:update']);
        $context = new Context($source);

        $accountData = self::SUPPORT_ACCOUNT;
        $accountData['password'] = $this->generatePassword();

        if (is_null($id)) {
            $accountData['localeId'] = $this->getLocale();
            $request = new Request([], $accountData);
            $this->userController->upsertUser($id, $request, $context, $this->jsonType);
        } else {
            $request = new Request([], $accountData);
            $this->userController->updateUser($id, $request, $context, $this->jsonType);
        }

        return [
            'login' => self::SUPPORT_ACCOUNT['username'],
            'password' => $accountData['password'],
        ];
    }

    /**
     * @return string
     */
    private function getLocale(): string
    {

        $code = self::DEFAULT_LOCAL_CODE;
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('lower(hex(id)) as id')
            ->from('locale')
            ->where("code = '$code'");

        $id = '';

        try {
            $id = $qb->fetchOne();

            if (empty($id)) {
                $qb->orWhere('code IS NOT NULL');
            }
            $id = $qb->fetchOne();
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage());
        }

        return $id;
    }

    /**
     * @return string
     */
    private function generatePassword(): string
    {
        return substr(md5(time() . Defaults::LANGUAGE_SYSTEM), 0, 8);
    }
}