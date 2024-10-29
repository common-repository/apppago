<?php

/**
 * Class AmountFactory
 */
class AmountFactory {

	/**
	 * The item factory.
	 *
	 * @var ItemFactory
	 */
	private $item_factory;

	/**
	 * The Money factory.
	 *
	 * @var MoneyFactory
	 */
	private $money_factory;

	/**
	 * 3-letter currency code of the shop.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * AmountFactory constructor.
	 *
	 * @param ItemFactory  $item_factory The Item factory.
	 * @param MoneyFactory $money_factory The Money factory.
	 * @param string       $currency 3-letter currency code of the shop.
	 */
	public function __construct(string $currency = 'EUR') {
		$this->item_factory  = new ItemFactory($currency);
		$this->currency      = $currency;
	}

	/**
	 * Returns an Amount object based off a WooCommerce cart.
	 *
	 * @param \WC_Cart $cart The cart.
	 *
	 * @return Amount
	 */
	public function from_wc_cart( \WC_Cart $cart ): Amount {
		$total = new Money( (float) $cart->get_total( 'numeric' ), $this->currency );

		$item_total = (float) $cart->get_subtotal() + (float) $cart->get_fee_total();
		$item_total = new Money( $item_total, $this->currency );
		$shipping   = new Money(
			(float) $cart->get_shipping_total(),
			$this->currency
		);

		$taxes = new Money(
			(float) $cart->get_total_tax(),
			$this->currency
		);

		$discount = null;
		if ( $cart->get_discount_total() ) {
			$discount = new Money(
				(float) $cart->get_discount_total(),
				$this->currency
			);
		}

		$breakdown = new AmountBreakdown(
			$item_total,
			$shipping,
			$taxes,
			null, // insurance?
			null, // handling?
			null, // shipping discounts?
			$discount
		);
		$amount    = new Amount(
			$total,
			$breakdown
		);
		return $amount;
	}

	/**
	 * Returns an Amount object based off a WooCommerce order.
	 *
	 * @param \WC_Order $order The order.
	 *
	 * @return Amount
	 */
	public function from_wc_order( \WC_Order $order ): Amount {
		$currency = $order->get_currency();
		$items    = $this->item_factory->from_wc_order( $order );

		$discount_value = array_sum(
			array(
				(float) $order->get_total_discount(), // Only coupons.
				$this->discounts_from_items( $items ),
			)
		);
		$discount       = null;
		if ( $discount_value ) {
			$discount = new Money(
				(float) $discount_value,
				$currency
			);
		}

		$total_value = (float) $order->get_total();
		$total = new Money( $total_value, $currency );

		$item_total = new Money(
			(float) $order->get_subtotal() + (float) $order->get_total_fees(),
			$currency
		);
		$shipping   = new Money(
			(float) $order->get_shipping_total(),
			$currency
		);
		$taxes      = new Money(
			(float) $order->get_total_tax(),
			$currency
		);

		$breakdown = new AmountBreakdown(
			$item_total,
			$shipping,
			$taxes,
			null, // insurance?
			null, // handling?
			null, // shipping discounts?
			$discount
		);
		$amount    = new Amount(
			$total,
			$breakdown
		);
		return $amount;
	}

	/**
	 * Returns the sum of items with negative amount;
	 *
	 * @param Item[] $items PayPal order items.
	 * @return float
	 */
	private function discounts_from_items( array $items ): float {
		$discounts = array_filter(
			$items,
			function ( Item $item ): bool {
				return $item->unit_amount()->value() < 0;
			}
		);
		return abs(
			array_sum(
				array_map(
					function ( Item $item ): float {
						return (float) $item->quantity() * $item->unit_amount()->value();
					},
					$discounts
				)
			)
		);
	}
}


/**
 * Class ItemFactory
 */
class ItemFactory {
	/**
	 * 3-letter currency code of the shop.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * ItemFactory constructor.
	 *
	 * @param string $currency 3-letter currency code of the shop.
	 */
	public function __construct( string $currency ) {
		$this->currency = $currency;
	}

	/**
	 * Creates items based off a WooCommerce cart.
	 *
	 * @param \WC_Cart $cart The cart.
	 *
	 * @return Item[]
	 */
	public function from_wc_cart( \WC_Cart $cart ): array {
		$items = array_map(
			function ( array $item ): Item {
				$product = $item['data'];

				/**
				 * The WooCommerce product.
				 *
				 * @var \WC_Product $product
				 */
				$quantity = (int) $item['quantity'];

				$price = (float) $item['line_subtotal'] / (float) $item['quantity'];
				return new Item(
					mb_substr( $product->get_name(), 0, 127 ),
					new Money( $price, $this->currency ),
					$quantity,
					substr( wp_strip_all_tags( $product->get_description() ), 0, 127 ) ?: '',
					null,
					$product->get_sku(),
					( $product->is_virtual() ) ? Item::DIGITAL_GOODS : Item::PHYSICAL_GOODS
				);
			},
			$cart->get_cart_contents()
		);

		$fees              = array();
		$fees_from_session = WC()->session->get( 'ppcp_fees' );
		if ( $fees_from_session ) {
			$fees = array_map(
				function ( \stdClass $fee ): Item {
					return new Item(
						$fee->name,
						new Money( (float) $fee->amount, $this->currency ),
						1,
						'',
						null
					);
				},
				$fees_from_session
			);
		}

		return array_merge( $items, $fees );
	}

	/**
	 * Creates Items based off a WooCommerce order.
	 *
	 * @param \WC_Order $order The order.
	 * @return Item[]
	 */
	public function from_wc_order( \WC_Order $order ): array {
		$items = array_map(
			function ( \WC_Order_Item_Product $item ) use ( $order ): Item {
				return $this->from_wc_order_line_item( $item, $order );
			},
			$order->get_items( 'line_item' )
		);

		$fees = array_map(
			function ( \WC_Order_Item_Fee $item ) use ( $order ): Item {
				return $this->from_wc_order_fee( $item, $order );
			},
			$order->get_fees()
		);

		return array_merge( $items, $fees );
	}

	/**
	 * Creates an Item based off a WooCommerce Order Item.
	 *
	 * @param \WC_Order_Item_Product $item The WooCommerce order item.
	 * @param \WC_Order              $order The WooCommerce order.
	 *
	 * @return Item
	 */
	private function from_wc_order_line_item( \WC_Order_Item_Product $item, \WC_Order $order ): Item {
		$product                   = $item->get_product();
		$currency                  = $order->get_currency();
		$quantity                  = (int) $item->get_quantity();
		$price_without_tax         = (float) $order->get_item_subtotal( $item, false );
		$price_without_tax_rounded = round( $price_without_tax, 2 );

		return new Item(
			mb_substr( $item->get_name(), 0, 127 ),
			new Money( $price_without_tax_rounded, $currency ),
			$quantity,
			substr( wp_strip_all_tags( $product instanceof WC_Product ? $product->get_description() : '' ), 0, 127 ) ?: '',
			null,
			$product instanceof WC_Product ? $product->get_sku() : '',
			( $product instanceof WC_Product && $product->is_virtual() ) ? Item::DIGITAL_GOODS : Item::PHYSICAL_GOODS
		);
	}

	/**
	 * Creates an Item based off a WooCommerce Fee Item.
	 *
	 * @param \WC_Order_Item_Fee $item The WooCommerce order item.
	 * @param \WC_Order          $order The WooCommerce order.
	 *
	 * @return Item
	 */
	private function from_wc_order_fee( \WC_Order_Item_Fee $item, \WC_Order $order ): Item {
		return new Item(
			$item->get_name(),
			new Money( (float) $item->get_amount(), $order->get_currency() ),
			$item->get_quantity(),
			'',
			null
		);
	}
}

