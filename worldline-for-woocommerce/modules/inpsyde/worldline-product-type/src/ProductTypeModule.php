<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ProductType;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
class ProductTypeModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    private \wpdb $db;
    public function run(ContainerInterface $container) : bool
    {
        global $wpdb;
        $this->db = $wpdb;
        \add_action('init', function () {
            $this->createProductTypeTable();
        });
        \add_action('add_meta_boxes', [$this, 'addProductMetaBox'], 10, 2);
        \add_action('save_post_product', [$this, 'saveProductType']);
        \add_action('wp_ajax_save_worldline_product_type', [$this, 'ajaxSaveProductType']);
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
    /**
     * Creates a custom table for storing product types if it does not already exist.
     */
    private function createProductTypeTable() : void
    {
        $table_name = $this->db->prefix . 'product_type';
        $charset_collate = $this->db->get_charset_collate();
        $query = "CREATE TABLE IF NOT EXISTS `{$table_name}` (\n            `id` INT NOT NULL AUTO_INCREMENT,\n            `product_id` BIGINT UNSIGNED NOT NULL,\n            `type` VARCHAR(255) NOT NULL,\n            PRIMARY KEY (`id`),\n            KEY `product_id_index` (`product_id`)\n        ) {$charset_collate};";
        require_once \ABSPATH . 'wp-admin/includes/upgrade.php';
        \dbDelta($query);
    }
    /**
     * Adds a meta box for product type selection to the product edit page.
     */
    public function addProductMetaBox($page, $post) : void
    {
        \add_meta_box('worldline-product-type', \__('Worldline Direct', 'worldline-for-woocommerce'), [$this, 'renderProductMetaBox'], 'product', 'side', 'core');
    }
    /**
     * Renders the product type meta box on the product edit page.
     */
    public function renderProductMetaBox($post) : void
    {
        $table = $this->db->prefix . 'product_type';
        $product_id = (int) $post->ID;
        // Dohvati trenutni tip proizvoda iz baze
        $current_type = $this->db->get_var($this->db->prepare("SELECT `type` FROM `{$table}` WHERE product_id = %d", $product_id));
        ?>
        <p><label for="worldline_mealvoucher_type">
                <?php 
        \_e('Worldline Mealvouchers Product Type:', 'worldline-for-woocommerce');
        ?>
            </label></p>

        <select name="worldline_mealvoucher_type" id="worldline_mealvoucher_type" style="width: 100%;">
            <option value="none" <?php 
        \selected($current_type, null);
        ?>><?php 
        \_e('None', 'worldline-for-woocommerce');
        ?></option>
            <option value="food" <?php 
        \selected($current_type, 'food');
        ?>><?php 
        \_e('Food and drink', 'worldline-for-woocommerce');
        ?></option>
            <option value="home" <?php 
        \selected($current_type, 'home');
        ?>><?php 
        \_e('Home and garden', 'worldline-for-woocommerce');
        ?></option>
            <option value="gift" <?php 
        \selected($current_type, 'gift');
        ?>><?php 
        \_e('Gift and flowers', 'worldline-for-woocommerce');
        ?></option>
        </select>

        <p style="text-align: center;">
            <button type="button" class="button button-primary" id="worldline_save_product_type">
                <?php 
        \_e('Save', 'worldline-for-woocommerce');
        ?>
            </button>
        </p>

        <script type="text/javascript">
            jQuery(document).ready(function($){
                $('#worldline_save_product_type').on('click', function(){
                    var type = $('#worldline_mealvoucher_type').val();
                    var post_id = <?php 
        echo (int) $post->ID;
        ?>;
                    var nonce = $('#worldline_mealvoucher_nonce').val();

                    $.post(ajaxurl, {
                        action: 'save_worldline_product_type',
                        product_id: post_id,
                        type: type,
                        nonce: nonce
                    }, function(response) {
                        alert(response.data.message);
                    });
                });
            });
        </script>

        <?php 
        \wp_nonce_field('save_worldline_mealvoucher_type', 'worldline_mealvoucher_nonce');
    }
    /**
     * Saves the selected product type when a product is saved.
     */
    public function saveProductType(int $post_id) : void
    {
        if (!isset($_POST['worldline_mealvoucher_nonce']) || !\wp_verify_nonce($_POST['worldline_mealvoucher_nonce'], 'save_worldline_mealvoucher_type')) {
            return;
        }
        if (\defined('DOING_AUTOSAVE') && \DOING_AUTOSAVE) {
            return;
        }
        if (isset($_POST['worldline_mealvoucher_type'])) {
            $type = \sanitize_text_field($_POST['worldline_mealvoucher_type']);
            $table = $this->db->prefix . 'product_type';
            if ($type === 'none') {
                // Briši postojeći zapis ako postoji
                $this->db->delete($table, ['product_id' => $post_id], ['%d']);
                return;
            }
            $exists = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE product_id = %d", $post_id));
            if ($exists) {
                $this->db->update($table, ['type' => $type], ['product_id' => $post_id], ['%s'], ['%d']);
            } else {
                $this->db->insert($table, ['product_id' => $post_id, 'type' => $type], ['%d', '%s']);
            }
        }
    }
    /**
     * AJAX handler for saving product type via the meta box Save button.
     */
    public function ajaxSaveProductType() : void
    {
        if (!isset($_POST['nonce']) || !\wp_verify_nonce($_POST['nonce'], 'save_worldline_mealvoucher_type')) {
            \wp_send_json_error(['message' => \__('Invalid nonce.', 'worldline-for-woocommerce')]);
        }
        $productId = (int) $_POST['product_id'];
        $type = \sanitize_text_field($_POST['type']);
        $table = $this->db->prefix . 'product_type';
        if ($type === 'none') {
            $this->db->delete($table, ['product_id' => $productId], ['%d']);
            \wp_send_json_success(['message' => \__('Product type removed.', 'worldline-for-woocommerce')]);
        }
        $exists = $this->db->get_var($this->db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE product_id = %d", $productId));
        if ($exists) {
            $this->db->update($table, ['type' => $type], ['product_id' => $productId], ['%s'], ['%d']);
        } else {
            $this->db->insert($table, ['product_id' => $productId, 'type' => $type], ['%d', '%s']);
        }
        \wp_send_json_success(['message' => \__('Product type saved successfully.', 'worldline-for-woocommerce')]);
    }
}
