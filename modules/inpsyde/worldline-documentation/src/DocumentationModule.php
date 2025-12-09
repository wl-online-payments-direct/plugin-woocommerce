<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Documentation;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Documentation\Renderer\LinksRenderer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
class DocumentationModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action(
            'woocommerce_sections_checkout',
            // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
            static function () use($container) : void {
                global $current_section;
                if (GatewayIds::HOSTED_CHECKOUT !== $current_section) {
                    return;
                }
                $documentationLinksRenderer = $container->get('documentation.links_renderer');
                \assert($documentationLinksRenderer instanceof LinksRenderer);
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $documentationLinksRenderer->render();
            }
        );
        return \true;
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return $services();
    }
}
