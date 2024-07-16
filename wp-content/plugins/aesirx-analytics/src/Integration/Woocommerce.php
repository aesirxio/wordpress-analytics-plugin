<?php

namespace AesirxAnalytics\Integration;

use AesirxAnalytics\Log\LoggerInterface;
use AesirxAnalytics\Log\NullableLogger;
use AesirxAnalytics\Track\ConversionMessage;
use AesirxAnalytics\Track\AbstractTracker;
use DomainException;
use Throwable;
use WC_Order;
use WC_Product;

class Woocommerce {
    private AbstractTracker $tracker;
    private LoggerInterface $logger;
    /**
     * @var array|string[]
     */
    private array $ignoredStatus;

    private string $key_order_tracked = 'analytics-woo-order-tracked';
    private string $flowUuid;

    public function __construct(
        AbstractTracker $tracker,
        string $flowUuid,
        array $ignoredStatus = [ 'cancelled', 'failed', 'refunded' ],
        LoggerInterface $logger = null
    ) {
        $this->tracker       = $tracker;
        $this->logger        = $logger ?? new NullableLogger;
        $this->ignoredStatus = $ignoredStatus;
        $this->flowUuid      = $flowUuid;
    }

    public function registerHooks(): void {
        add_action( 'wp_head', [ $this, 'maybeTrackOrderComplete' ], 99999 );
        add_action( 'woocommerce_add_to_cart', [ $this, 'onCartUpdatedSafe' ], 99999, 0 );
        add_action( 'woocommerce_cart_item_removed', [ $this, 'onCartUpdatedSafe' ], 99999, 0 );
        add_action( 'woocommerce_cart_item_restored', [ $this, 'onCartUpdatedSafe' ], 99999, 0 );
        add_action( 'woocommerce_cart_item_set_quantity', [ $this, 'onCartUpdatedSafe' ], 99999, 0 );
        add_action( 'woocommerce_applied_coupon', [ $this, 'onCouponUpdatedSafe' ], 99999, 0 );
        add_action( 'woocommerce_removed_coupon', [ $this, 'onCouponUpdatedSafe' ], 99999, 0 );
    }

    public function onCartUpdatedSafe() {
        $this->onCartUpdated();

        return null;
    }

    public function onCouponUpdatedSafe() {
        $this->onCartUpdated( true );

        return null;
    }

    private function onCartUpdated( $is_coupon_update = false ): void {
        try {
            global $woocommerce, $wp;

            /** @var \WC_Cart $cart */
            $cart = $woocommerce->cart;
            if ( ! $is_coupon_update ) {
                // Can cause cart coupon not to be applied when WooCommerce Subscriptions is used.
                $cart->calculate_totals();
            }
            $conversion = new ConversionMessage(
                $this->flowUuid, 'woocommerce', home_url( $wp->request )
            );

            foreach ( $cart->get_cart() as $item ) {
                /** @var WC_Product $product */
                $product = wc_get_product( $item['product_id'] );

                if ( $this->isWC3() ) {
                    $productOrVariation = $product;

                    if ( ! empty( $item['variation_id'] ) ) {
                        $variation = wc_get_product( $item['variation_id'] );
                        if ( ! empty( $variation ) ) {
                            $productOrVariation = $variation;
                        }
                    }
                } else {
                    $order              = new WC_Order( null );
                    $productOrVariation = $order->get_product_from_item( $item );
                }

                if ( empty( $productOrVariation ) ) {
                    continue;
                }

                $price = 0;
                if ( isset( $item['line_total'] ) ) {
                    $price = floatval( $item['line_total'] ) / max( 1, $item['quantity'] );
                }

                $conversion->addItem(
                    $this->get_sku( $productOrVariation ),
                    $product->get_title(),
                    $price,
                    $item['quantity'] ?? 0
                );
            }
            $this->tracker->push( $conversion )->track();

            $this->logger->log( 'Tracked ecommerce cart update: ' . $conversion );
        }
        catch ( Throwable $e ) {
            $this->logger->error( $e->getMessage() );
        }
    }

    public function maybeTrackOrderComplete() {
        global $wp;

        if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
            $order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
            if ( ! empty( $order_id ) && $order_id > 0 ) {
                $this->onOrder( $order_id );
            }
        }
    }

    private function hasOrderBeenTrackedAlready( $order_id ): bool {
        return get_post_meta( $order_id, $this->key_order_tracked, true ) == 1;
    }

    private function setOrderBeenTracked( $order_id ): void {
        update_post_meta( $order_id, $this->key_order_tracked, 1 );
    }

    private function onOrder( $order_id ): void {
        try {
            if ( $this->hasOrderBeenTrackedAlready( $order_id ) ) {
                $this->logger->log( sprintf( 'Ignoring already tracked order %d', $order_id ) );

                return;
            }

            $this->logger->log( sprintf( 'Analytics new order %d', $order_id ) );

            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                return;
            }
            $orderIdToTrack = $order_id;
            if ( method_exists( $order, 'get_order_number' ) ) {
                $orderIdToTrack = $order->get_order_number();
            }

            $orderStatus = $order->get_status();

            $this->logger->log( sprintf( 'Order %s with order number %s has status: %s', $order_id, $orderIdToTrack, $orderStatus ) );

            if ( in_array( $orderStatus, $this->ignoredStatus, true ) ) {
                $this->logger->log( 'Ignoring ecommerce order ' . $order_id . ' because of status: ' . $orderStatus );

                return;
            }
            global $wp;
            $conversion = new ConversionMessage(
                $this->flowUuid, 'woocommerce', home_url( $wp->request )
            );
            $items      = $order->get_items();

            if ( $items ) {
                foreach ( $items as $item ) {
                    /** @var \WC_Order_Item_Product $item */

                    $this->addProductDetails( $order, $item, $conversion );
                }
            }

            $conversion->setOrderDetails(
                $orderIdToTrack,
                $this->isWC3() ? $order->get_shipping_total() : $order->get_total_shipping(),
                $order->get_total_discount(),
                $order->get_cart_tax(),
            );

            $this->tracker->push( $conversion )->track();

            $this->setOrderBeenTracked( $order_id );

            $this->logger->log( sprintf( 'Tracked ecommerce order %s with number %s', $order_id, $orderIdToTrack ) );
        }
        catch ( Throwable $e ) {
            $this->logger->error( $e->getMessage() );
        }
    }

    private function addProductDetails( $order, $item, ConversionMessage $conversion ): void {
        $productOrVariation = false;
        if ( $this->isWC3() && ! empty( $item ) && is_object( $item ) && method_exists( $item, 'get_product' ) && is_callable(
                [
                    $item,
                    'get_product',
                ]
            ) ) {
            $productOrVariation = $item->get_product();
        } elseif ( method_exists( $order, 'get_product_from_item' ) ) {
            // eg woocommerce 2.x
            $productOrVariation = $order->get_product_from_item( $item );
        }

        if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
            // woocommerce 3
            $productId = $item->get_product_id();
        } elseif ( isset( $item['product_id'] ) ) {
            // woocommerce 2.x
            $productId = $item['product_id'];
        } else {
            return;
        }

        $product = wc_get_product( $productId );

        $conversion->addItem(
            $this->get_sku( $productOrVariation ?: $product ),
            $product->get_title(),
            $order->get_item_total( $item, false, false ),
            $item['qty'],
        );
    }

    private function isWC3(): bool {
        global $woocommerce;

        return version_compare( $woocommerce->version, '3.0', '>=' );
    }

    /**
     * @param WC_Product $product
     */
    private function get_sku( $product ): string {
        if ( $product && $product->get_sku() ) {
            return $product->get_sku();
        }

        return $this->get_product_id( $product );
    }

    /**
     * @param WC_Product $product
     */
    private function get_product_id( $product ): int {
        if ( ! $product ) {
            throw new DomainException( 'Product not found' );
        }

        if ( $this->isWC3() ) {
            return $product->get_id();
        }

        return $product->{'id'};
    }
}
