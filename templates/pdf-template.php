<?php
// $items provided
?>
<table>
    <thead>
        <tr>
            <th style="width:60px;"><?php esc_html_e('ID', 'wc-inventory-pdf-barcode'); ?></th>
            <th><?php esc_html_e('Title', 'wc-inventory-pdf-barcode'); ?></th>
            <th style="width:110px;"><?php esc_html_e('Manage stock', 'wc-inventory-pdf-barcode'); ?></th>
            <th style="width:100px;"><?php esc_html_e('Quantity', 'wc-inventory-pdf-barcode'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $it): ?>
        <tr>
            <td class="nowrap"><?php echo esc_html($it['id']); ?></td>
            <td><?php echo esc_html($it['title']); ?></td>
            <td class="nowrap"><?php echo esc_html($it['manage_stock']); ?></td>
            <td class="nowrap"><?php echo esc_html($it['quantity']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
