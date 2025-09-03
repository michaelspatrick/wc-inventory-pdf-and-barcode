<?php
namespace WCIPB;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class ProductQuery {

    /**
     * Get physical products and variations filtered by categories.
     * @param array $category_ids
     * @param string $sort 'title' or 'id'
     * @return array of arrays: id,title,manage_stock,quantity
     */
    public static function get_products( $category_ids = [], $sort = 'title' ) {
        $args = [
            'status' => ['publish','private'],
            'limit'  => -1,
            'type'   => ['simple','variable'], // do NOT prefetch 'variation' to avoid duplicates
            'return' => 'objects'
        ];

        $products = wc_get_products($args);
        $items = [];
        $seen = [];

        foreach ($products as $product) {
            if ( $product->is_type('variable') ) {
                // Only include variations, not the parent product
                foreach ( $product->get_children() as $vid ) {
                    if ( isset($seen[$vid]) ) { continue; }
                    $variation = wc_get_product($vid);
                    if ( ! $variation ) { continue; }
                    if ( self::is_excluded($variation, $category_ids) ) { continue; }
                    $items[] = self::as_row($variation);
                    $seen[$vid] = true;
                }
            } else {
                if ( self::is_excluded($product, $category_ids) ) { continue; }
                $pid = $product->get_id();
                if ( isset($seen[$pid]) ) { continue; }
                $items[] = self::as_row($product);
                $seen[$pid] = true;
            }
        }

        // Sort
        usort($items, function($a,$b) use ($sort){
            if ($sort === 'id') return $a['id'] <=> $b['id'];
            // title
            return strcasecmp($a['title'], $b['title']);
        });

        return $items;
    }

    private static function is_excluded( \WC_Product $product, $category_ids ) {
        // Physical only
        if ( $product->is_virtual() ) return true;

        // Filter by categories if provided (for variations, check parent categories)
        if ( ! empty($category_ids) ) {
            $pid = $product->get_id();
            if ( $product->is_type('variation') ) {
                $pid = $product->get_parent_id();
            }
            $terms = wp_get_post_terms($pid, 'product_cat', ['fields'=>'ids']);
            if ( empty(array_intersect($terms, $category_ids)) ) {
                return true;
            }
        }
        return false;
    }

    private static function as_row( \WC_Product $p ) {
        $id = $p->get_id();
        $title = $p->get_name();
        // Make variation title include attributes cleanly
        if ( $p->is_type('variation') ) {
            $parent = wc_get_product( $p->get_parent_id() );
            if ( $parent ) {
                $attrs = wc_get_formatted_variation( $p, true );
                $title = $parent->get_name() . ( $attrs ? ' – ' . wp_strip_all_tags($attrs) : '' );
            }
        }
        $manage_stock = $p->get_manage_stock() ? 'yes' : 'no';
        $qty = (int) $p->get_stock_quantity();
        return [
            'id' => $id,
            'title' => $title,
            'manage_stock' => $manage_stock,
            'quantity' => max(0, $qty)
        ];
    }
}
