<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Orders;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class OrdersModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        $this->addBrandColumn();
        return \true;
    }
    private function addBrandColumn() : void
    {
        \add_filter('manage_edit-shop_order_columns', [$this, 'add_brand_column'], 20);
        \add_action('manage_shop_order_posts_custom_column', [$this, 'populate_brand_column'], 20, 2);
        \add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_brand_column'], 20);
        \add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'populate_brand_column_hpos'], 20, 2);
    }
    public function add_brand_column(array $columns) : array
    {
        $new_columns = [];
        $added = \false;
        foreach ($columns as $key => $column) {
            if ($key === 'order_actions' || $key === 'wc_actions') {
                $new_columns['brand'] = \__('Brand', 'worldline-for-woocommerce');
                $added = \true;
            }
            $new_columns[$key] = $column;
        }
        if (!$added) {
            $new_columns['brand'] = \__('Brand', 'worldline-for-woocommerce');
        }
        return $new_columns;
    }
    public function populate_brand_column(string $column, int $post_id) : void
    {
        if ($column === 'brand') {
            $order = \wc_get_order($post_id);
            if ($order instanceof WC_Order) {
                echo $this->get_brand_content($order);
            }
        }
    }
    public function populate_brand_column_hpos(string $column, $order) : void
    {
        if ($column === 'brand') {
            if (\is_numeric($order)) {
                $order = \wc_get_order($order);
            }
            if ($order instanceof WC_Order) {
                echo $this->get_brand_content($order);
            }
        }
    }
    protected function get_brand_content(WC_Order $order) : string
    {
        $wlopOrder = new WlopWcOrder($order);
        $brand = $wlopOrder->paymentMethodName();
        if (!$brand) {
            return '<span style="color: #999;">—</span>';
        }
        return \esc_html('Worldline Online Payments [' . $brand . ']');
    }
}
