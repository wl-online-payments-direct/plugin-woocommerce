<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Payment;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class ReturnUrlController extends AbstractController
{
    public SystemConfigService $systemConfigService;

    private Session $session;

    const RETURN_URL_PATH = 'worldline/payment/finalize-transaction';
    const PAYMENT_PAGES = [
        '/checkout/confirm',
        '/account/order'
    ];

    /**
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        SystemConfigService $systemConfigService
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->session = new Session();
    }

    /**
     * @Route("/worldline_serverUrl", name="worldline.serverUrl", defaults={"XmlHttpRequest"=true}, methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function saveServerUrl(Request $request): JsonResponse
    {
        $serverUrl = $request->get('serverUrl') ?: null;

        foreach (self::PAYMENT_PAGES as $page) {
            if (stripos($serverUrl, $page)) {
                $url = explode($page, $serverUrl);
                $this->session->set(Form::SESSION_SERVER_URL, $url[0]);
                break;
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * @return string
     */
    private function getServerUrl(): string
    {
        return $this->session->get(Form::SESSION_SERVER_URL) ?: '';
    }

    /**
     * @param WorldlineSDKAdapter $adapter
     * @param bool $isLiveMode
     * @return string
     */
    public function getReturnUrl(WorldlineSDKAdapter $adapter, bool $isLiveMode): string
    {
        $server = $this->getServerUrl();
        if (empty($server)) {
            if ($isLiveMode) {
                $server = $adapter->getPluginConfig(Form::LIVE_MAIN_RETURN_SERVER_FIELD);
            } else {
                $server = $adapter->getPluginConfig(Form::MAIN_RETURN_SERVER_FIELD);
            }
        }

        $server = trim(trim($server), '/');

        return $server . '/' . self::RETURN_URL_PATH;
    }
}
