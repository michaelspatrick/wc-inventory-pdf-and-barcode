<?php
namespace WCIPB;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class AdminPages {

    public function __construct() {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_post_wcipb_generate_pdf', [$this, 'handle_generate_pdf']);
        add_action('admin_post_wcipb_save_quantities', [$this, 'handle_save_quantities']);
        add_action('admin_post_wcipb_import_barcodes', [$this, 'handle_import_barcodes']);
    }

    public function menu() {
        $cap = 'manage_woocommerce';

        add_menu_page(
            __('Inventory PDF', 'wc-inventory-pdf-barcode'),
            __('Inventory PDF', 'wc-inventory-pdf-barcode'),
            $cap,
            'wcipb-pdf',
            [$this, 'page_pdf'],
            'dashicons-media-document',
            56
        );

        add_submenu_page(
            'wcipb-pdf',
            __('Bulk Update Quantities', 'wc-inventory-pdf-barcode'),
            __('Bulk Update', 'wc-inventory-pdf-barcode'),
            $cap,
            'wcipb-update',
            [$this, 'page_update']
        );

        add_submenu_page(
            'wcipb-pdf',
            __('Barcode Import', 'wc-inventory-pdf-barcode'),
            __('Barcode Import', 'wc-inventory-pdf-barcode'),
            $cap,
            'wcipb-barcode',
            [$this, 'page_barcodes']
        );
    }

    /* -------------------- PDF PAGE -------------------- */
    public function page_pdf() {
        if ( ! current_user_can('manage_woocommerce') ) { return; }
        $selected = isset($_GET['cat']) ? array_map('intval', (array) $_GET['cat']) : [];
        $cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Generate Inventory PDF', 'wc-inventory-pdf-barcode'); ?></h1>
            <?php $this->maybe_show_dompdf_notice(); ?>
            <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wcipb_generate_pdf">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e('Categories to include', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <select name="cat[]" multiple size="8" style="min-width:320px;">
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $selected)); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Hold CTRL/CMD to select multiple. Leave empty to include all.', 'wc-inventory-pdf-barcode'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Sort', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <label><input type="radio" name="sort" value="title" checked> <?php esc_html_e('Title (A→Z)', 'wc-inventory-pdf-barcode'); ?></label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="sort" value="id"> <?php esc_html_e('ID (ascending)', 'wc-inventory-pdf-barcode'); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Generate PDF', 'wc-inventory-pdf-barcode')); ?>
            </form>

            <hr>
            <p><strong><?php esc_html_e('No PDF library?', 'wc-inventory-pdf-barcode'); ?></strong>
            <?php esc_html_e('If Dompdf is not installed, this page will still render a printer-friendly HTML table you can print to PDF from your browser.', 'wc-inventory-pdf-barcode'); ?></p>
        </div>
        <?php
    }

    /* -------------------- UPDATE PAGE -------------------- */
    public function page_update() {
        if ( ! current_user_can('manage_woocommerce') ) { return; }
        $cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
        $selected = isset($_GET['cat']) ? array_map('intval', (array) $_GET['cat']) : [];
        $per_page = isset($_GET['per_page']) ? max(10, intval($_GET['per_page'])) : 50;
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $sort = isset($_GET['sort']) && $_GET['sort'] === 'id' ? 'id' : 'title';

        $all_items = ProductQuery::get_products($selected, $sort);
        $total = count($all_items);
        $items = array_slice($all_items, ($paged-1)*$per_page, $per_page);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bulk Update Inventory Quantities', 'wc-inventory-pdf-barcode'); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="wcipb-update">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e('Categories to include', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <select name="cat[]" multiple size="8" style="min-width:320px;">
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $selected)); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Hold CTRL/CMD to select multiple. Leave empty to include all.', 'wc-inventory-pdf-barcode'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Sort', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <label><input type="radio" name="sort" value="title" <?php checked($sort==='title'); ?>> <?php esc_html_e('Title (A→Z)', 'wc-inventory-pdf-barcode'); ?></label>
                            &nbsp;&nbsp;
                            <label><input type="radio" name="sort" value="id" <?php checked($sort==='id'); ?>> <?php esc_html_e('ID (ascending)', 'wc-inventory-pdf-barcode'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Items per page', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td><input type="number" name="per_page" value="<?php echo esc_attr($per_page); ?>" min="10" max="1000"></td>
                    </tr>
                </table>
                <?php submit_button(__('Apply Filters', 'wc-inventory-pdf-barcode'), 'secondary'); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('wcipb_save_quantities', 'wcipb_nonce'); ?>
                <input type="hidden" name="action" value="wcipb_save_quantities">
                <table class="widefat fixed striped wcipb-table">
                    <thead>
                        <tr>
                            <th class="wcipb-id-col">ID</th>
                            <th class="wcipb-title-col">Title</th>
                            <th class="wcipb-ms-col">Manage stock</th>
                            <th class="wcipb-qty-col">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td class="nowrap wcipb-id-col"><?php echo esc_html($it['id']); ?></td>
                                <td class="wcipb-title-col"><?php echo esc_html($it['title']); ?></td>
                                <td class="wcipb-ms-col">
                                    <select name="manage_stock[<?php echo esc_attr($it['id']); ?>]">
                                        <option value="no" <?php selected($it['manage_stock']==='no'); ?>>no</option>
                                        <option value="yes" <?php selected($it['manage_stock']==='yes'); ?>>yes</option>
                                    </select>
                                </td>
                                <td class="wcipb-qty-col">
                                    <input class="wcipb-qty" type="number" name="quantity[<?php echo esc_attr($it['id']); ?>]" value="<?php echo esc_attr($it['quantity']); ?>" step="1" min="0" inputmode="numeric">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <?php
                    $total_pages = max(1, ceil($total / $per_page));
                    if ($total_pages > 1) {
                        echo '<div class="tablenav"><div class="tablenav-pages">';
                        for ($p=1; $p<=$total_pages; $p++) {
                            $url = add_query_arg(['page'=>'wcipb-update','paged'=>$p,'per_page'=>$per_page,'sort'=>$sort,'cat'=>$selected]);
                            $class = $p==$paged ? ' class="button button-primary"' : ' class="button"';
                            echo '<a'.$class.' href="'.esc_url($url).'">'.$p.'</a> ';
                        }
                        echo '</div></div>';
                    }
                    ?>
                </p>
                <?php submit_button(__('Save Changes', 'wc-inventory-pdf-barcode')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_save_quantities() {
        if ( ! current_user_can('manage_woocommerce') ) { wp_die('Access denied'); }
        check_admin_referer('wcipb_save_quantities', 'wcipb_nonce');

        $manage_stock = isset($_POST['manage_stock']) ? (array) $_POST['manage_stock'] : [];
        $quantity     = isset($_POST['quantity']) ? (array) $_POST['quantity'] : [];

        $updated = 0;
        foreach ($quantity as $pid => $qty) {
            $pid = intval($pid);
            $qty = max(0, intval($qty));
            $ms  = isset($manage_stock[$pid]) && $manage_stock[$pid]==='yes' ? 'yes' : 'no';

            update_post_meta($pid, '_manage_stock', $ms);
            update_post_meta($pid, '_stock', $qty);
            // Optional: stock status
            $status = $qty > 0 ? 'instock' : 'outofstock';
            update_post_meta($pid, '_stock_status', $status);
            $updated++;
        }

        wp_safe_redirect( add_query_arg(['page'=>'wcipb-update','updated'=>$updated], admin_url('admin.php')) );
        exit;
    }

    /* -------------------- BARCODE PAGE -------------------- */
    public function page_barcodes() {
        if ( ! current_user_can('manage_woocommerce') ) { return; }
        $mode = isset($_GET['mode']) ? sanitize_text_field($_GET['mode']) : 'set';
        $enable_ms = isset($_GET['enable_ms']) ? (bool) $_GET['enable_ms'] : false;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Barcode Import (_global_unique_id)', 'wc-inventory-pdf-barcode'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('wcipb_import_barcodes', 'wcipb_nonce'); ?>
                <input type="hidden" name="action" value="wcipb_import_barcodes" />
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e('Barcodes (one per line)', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <textarea name="barcodes" rows="15" cols="80" placeholder="Scan or paste barcodes here..."></textarea>
                            <p class="description"><?php esc_html_e('Each line should be the exact value stored in the _global_unique_id meta key.', 'wc-inventory-pdf-barcode'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Mode', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <label><input type="radio" name="mode" value="set" <?php checked($mode==='set'); ?>> <?php esc_html_e('Set quantity to the counted occurrences', 'wc-inventory-pdf-barcode'); ?></label><br>
                            <label><input type="radio" name="mode" value="add" <?php checked($mode==='add'); ?>> <?php esc_html_e('Add the counted occurrences to current quantity', 'wc-inventory-pdf-barcode'); ?></label><br>
                            <label><input type="radio" name="mode" value="subtract" <?php checked($mode==='subtract'); ?>> <?php esc_html_e('Subtract the counted occurrences from current quantity (won’t go below 0)', 'wc-inventory-pdf-barcode'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e('Options', 'wc-inventory-pdf-barcode'); ?></label></th>
                        <td>
                            <label><input type="checkbox" name="enable_ms" value="1" <?php checked($enable_ms); ?>> <?php esc_html_e('Enable “manage stock” for updated items', 'wc-inventory-pdf-barcode'); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Process Barcodes', 'wc-inventory-pdf-barcode')); ?>
            </form>
            <?php if ( isset($_GET['summary']) ): ?>
                <h2><?php esc_html_e('Last Import Summary', 'wc-inventory-pdf-barcode'); ?></h2>
                <div class="notice notice-info"><pre style="white-space:pre-wrap;"><?php echo esc_html(base64_decode($_GET['summary'])); ?></pre></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_import_barcodes() {
        if ( ! current_user_can('manage_woocommerce') ) { wp_die('Access denied'); }
        check_admin_referer('wcipb_import_barcodes', 'wcipb_nonce');

        $raw = isset($_POST['barcodes']) ? trim(wp_unslash($_POST['barcodes'])) : '';
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'set';
        $enable_ms = ! empty($_POST['enable_ms']);

        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
        $counts = [];
        foreach ($lines as $code) {
            if ($code === '') continue;
            $counts[$code] = isset($counts[$code]) ? $counts[$code]+1 : 1;
        }

        $summary = [];
        $not_found = [];

        foreach ($counts as $code => $cnt) {
            global $wpdb;
            $pid = $wpdb->get_var( $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
                '_global_unique_id', $code
            ) );

            if ( ! $pid ) {
                $not_found[] = $code;
                continue;
            }

            $pid = intval($pid);
            $current_qty = intval( get_post_meta($pid, '_stock', true) );
            $new_qty = $current_qty;

            if ($mode === 'set') {
                $new_qty = $cnt;
            } elseif ($mode === 'add') {
                $new_qty = $current_qty + $cnt;
            } elseif ($mode === 'subtract') {
                $new_qty = max(0, $current_qty - $cnt);
            }

            if ( $enable_ms ) {
                update_post_meta($pid, '_manage_stock', 'yes');
            }

            update_post_meta($pid, '_stock', $new_qty);
            update_post_meta($pid, '_stock_status', $new_qty > 0 ? 'instock' : 'outofstock');

            $title = get_the_title($pid);
            $summary[] = sprintf('#%d – %s | %s %d → %d',
                $pid, $title, strtoupper($mode), $cnt, $new_qty
            );
        }

        if (!empty($not_found)) {
            $summary[] = '';
            $summary[] = 'Barcodes NOT FOUND:';
            foreach ($not_found as $nf) {
                $summary[] = ' - ' . $nf;
            }
        }

        $sum = implode("\n", $summary);
        $sum_b64 = base64_encode($sum);

        wp_safe_redirect( add_query_arg(['page'=>'wcipb-barcode','summary'=>$sum_b64], admin_url('admin.php')) );
        exit;
    }

    private function render_template($file, $vars=[]) {
        $path = WCIPB_PATH . 'templates/' . $file;
        if ( ! file_exists($path) ) return '';
        extract($vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }

    private function maybe_show_dompdf_notice() {
        if ( ! class_exists('\\Dompdf\\Dompdf') ) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('Dompdf library not detected. To export true PDFs server-side, install Dompdf via Composer inside this plugin folder:', 'wc-inventory-pdf-barcode');
            echo '</p><code>composer install</code>';
            echo '<p>';
            echo esc_html__('Until then, the page will show a printer-friendly HTML table (use your browser’s “Print → Save as PDF”).', 'wc-inventory-pdf-barcode');
            echo '</p></div>';
        }
    }
}
