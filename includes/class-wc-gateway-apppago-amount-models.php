<?php

/**
 * Class Amount
 */
class Amount {

	/**
	 * The money.
	 *
	 * @var Money
	 */
	private $money;

	/**
	 * The breakdown.
	 *
	 * @var AmountBreakdown
	 */
	private $breakdown;

	/**
	 * Currencies that does not support decimals.
	 *
	 * @var array
	 */
	private $currencies_without_decimals = array( 'HUF', 'JPY', 'TWD' );

	/**
	 * Amount constructor.
	 *
	 * @param Money                $money The money.
	 * @param AmountBreakdown|null $breakdown The breakdown.
	 */
	public function __construct( Money $money, AmountBreakdown $breakdown = null ) {
		$this->money     = $money;
		$this->breakdown = $breakdown;
	}

	/**
	 * Returns the currency code.
	 *
	 * @return string
	 */
	public function currency_code(): string {
		return $this->money->currency_code();
	}

	/**
	 * Returns the value.
	 *
	 * @return float
	 */
	public function value(): float {
		return $this->money->value();
	}

	/**
	 * The value formatted as string for API requests.
	 *
	 * @return string
	 */
	public function value_str(): string {
		return $this->money->value_str();
	}

	/**
	 * Returns the breakdown.
	 *
	 * @return AmountBreakdown|null
	 */
	public function breakdown() {
		return $this->breakdown;
	}

	/**
	 * Returns the object as array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		$amount = $this->money->to_array();
		if ( $this->breakdown() && count( $this->breakdown()->to_array() ) ) {
			$amount['breakdown'] = $this->breakdown()->to_array();
		}
		return $amount;
	}
}


/**
 * Class AmountBreakdown
 */
class AmountBreakdown {

	/**
	 * The item total.
	 *
	 * @var Money|null
	 */
	private $item_total;

	/**
	 * The shipping.
	 *
	 * @var Money|null
	 */
	private $shipping;

	/**
	 * The tax total.
	 *
	 * @var Money|null
	 */
	private $tax_total;

	/**
	 * The handling.
	 *
	 * @var Money|null
	 */
	private $handling;

	/**
	 * The insurance.
	 *
	 * @var Money|null
	 */
	private $insurance;

	/**
	 * The shipping discount.
	 *
	 * @var Money|null
	 */
	private $shipping_discount;

	/**
	 * The discount.
	 *
	 * @var Money|null
	 */
	private $discount;

	/**
	 * AmountBreakdown constructor.
	 *
	 * @param Money|null $item_total The item total.
	 * @param Money|null $shipping The shipping.
	 * @param Money|null $tax_total The tax total.
	 * @param Money|null $handling The handling.
	 * @param Money|null $insurance The insurance.
	 * @param Money|null $shipping_discount The shipping discount.
	 * @param Money|null $discount The discount.
	 */
	public function __construct(
		?Money $item_total = null,
		?Money $shipping = null,
		?Money $tax_total = null,
		?Money $handling = null,
		?Money $insurance = null,
		?Money $shipping_discount = null,
		?Money $discount = null
	) {

		$this->item_total        = $item_total;
		$this->shipping          = $shipping;
		$this->tax_total         = $tax_total;
		$this->handling          = $handling;
		$this->insurance         = $insurance;
		$this->shipping_discount = $shipping_discount;
		$this->discount          = $discount;
	}

	/**
	 * Returns the item total.
	 *
	 * @return Money|null
	 */
	public function item_total() {
		return $this->item_total;
	}

	/**
	 * Returns the shipping.
	 *
	 * @return Money|null
	 */
	public function shipping() {
		return $this->shipping;
	}

	/**
	 * Returns the tax total.
	 *
	 * @return Money|null
	 */
	public function tax_total() {
		return $this->tax_total;
	}

	/**
	 * Returns the handling.
	 *
	 * @return Money|null
	 */
	public function handling() {
		return $this->handling;
	}

	/**
	 * Returns the insurance.
	 *
	 * @return Money|null
	 */
	public function insurance() {
		return $this->insurance;
	}

	/**
	 * Returns the shipping discount.
	 *
	 * @return Money|null
	 */
	public function shipping_discount() {
		return $this->shipping_discount;
	}

	/**
	 * Returns the discount.
	 *
	 * @return Money|null
	 */
	public function discount() {
		return $this->discount;
	}

	/**
	 * Returns the object as array.
	 *
	 * @return array
	 */
	public function to_array() {
		$breakdown = array();
		if ( $this->item_total ) {
			$breakdown['item_total'] = $this->item_total->to_array();
		}
		if ( $this->shipping ) {
			$breakdown['shipping'] = $this->shipping->to_array();
		}
		if ( $this->tax_total ) {
			$breakdown['tax_total'] = $this->tax_total->to_array();
		}
		if ( $this->handling ) {
			$breakdown['handling'] = $this->handling->to_array();
		}
		if ( $this->insurance ) {
			$breakdown['insurance'] = $this->insurance->to_array();
		}
		if ( $this->shipping_discount ) {
			$breakdown['shipping_discount'] = $this->shipping_discount->to_array();
		}
		if ( $this->discount ) {
			$breakdown['discount'] = $this->discount->to_array();
		}

		return $breakdown;
	}
}


/**
 * Class Item
 */
class Item {

	const PHYSICAL_GOODS = 'PHYSICAL_GOODS';
	const DIGITAL_GOODS  = 'DIGITAL_GOODS';

	/**
	 * The name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The unit amount.
	 *
	 * @var Money
	 */
	private $unit_amount;

	/**
	 * The quantity.
	 *
	 * @var int
	 */
	private $quantity;

	/**
	 * The description.
	 *
	 * @var string
	 */
	private $description;

	/**
	 * The tax.
	 *
	 * @var Money|null
	 */
	private $tax;

	/**
	 * The SKU.
	 *
	 * @var string
	 */
	private $sku;

	/**
	 * The category.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * The tax rate.
	 *
	 * @var float
	 */
	protected $tax_rate;

	/**
	 * Item constructor.
	 *
	 * @param string     $name The name.
	 * @param Money      $unit_amount The unit amount.
	 * @param int        $quantity The quantity.
	 * @param string     $description The description.
	 * @param Money|null $tax The tax.
	 * @param string     $sku The SKU.
	 * @param string     $category The category.
	 * @param float      $tax_rate The tax rate.
	 */
	public function __construct(
		string $name,
		Money $unit_amount,
		int $quantity,
		string $description = '',
		Money $tax = null,
		string $sku = '',
		string $category = 'PHYSICAL_GOODS',
		float $tax_rate = 0
	) {

		$this->name        = $name;
		$this->unit_amount = $unit_amount;
		$this->quantity    = $quantity;
		$this->description = $description;
		$this->tax         = $tax;
		$this->sku         = $sku;
		$this->category    = ( self::DIGITAL_GOODS === $category ) ? self::DIGITAL_GOODS : self::PHYSICAL_GOODS;
		$this->category    = $category;
		$this->tax_rate    = $tax_rate;
	}

	/**
	 * Returns the name of the item.
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Returns the unit amount.
	 *
	 * @return Money
	 */
	public function unit_amount(): Money {
		return $this->unit_amount;
	}

	/**
	 * Returns the quantity.
	 *
	 * @return int
	 */
	public function quantity(): int {
		return $this->quantity;
	}

	/**
	 * Returns the description.
	 *
	 * @return string
	 */
	public function description(): string {
		return $this->description;
	}

	/**
	 * Returns the tax.
	 *
	 * @return Money|null
	 */
	public function tax() {
		return $this->tax;
	}

	/**
	 * Returns the SKU.
	 *
	 * @return string
	 */
	public function sku() {
		return $this->sku;
	}

	/**
	 * Returns the category.
	 *
	 * @return string
	 */
	public function category() {
		return $this->category;
	}

	/**
	 * Returns the tax rate.
	 *
	 * @return float
	 */
	public function tax_rate():float {
		return round( (float) $this->tax_rate, 2 );
	}

	/**
	 * Returns the object as array.
	 *
	 * @return array
	 */
	public function to_array() {
		$item = array(
			'name'        => $this->name(),
			'unit_amount' => $this->unit_amount()->to_array(),
			'quantity'    => $this->quantity(),
			'description' => $this->description(),
			'sku'         => $this->sku(),
			'category'    => $this->category(),
		);

		if ( $this->tax() ) {
			$item['tax'] = $this->tax()->to_array();
		}

		if ( $this->tax_rate() ) {
			$item['tax_rate'] = (string) $this->tax_rate();
		}

		return $item;
	}
}


/**
 * Class Money
 */
class Money {

	/**
	 * The currency code.
	 *
	 * @var string
	 */
	private $currency_code;

	/**
	 * The value.
	 *
	 * @var float
	 */
	private $value;

	/**
	 * The MoneyFormatter.
	 *
	 * @var MoneyFormatter
	 */
	private $money_formatter;

	/**
	 * Money constructor.
	 *
	 * @param float  $value The value.
	 * @param string $currency_code The currency code.
	 */
	public function __construct( float $value, string $currency_code = 'EUR' ) {
		$this->value         = $value;
		$this->currency_code = $currency_code;

		$this->money_formatter = new MoneyFormatter();
	}

	/**
	 * The value.
	 *
	 * @return float
	 */
	public function value(): float {
		return $this->value;
	}

	/**
	 * The value formatted as string for API requests.
	 *
	 * @return string
	 */
	public function value_str(): string {
		return str_replace('.', '', $this->money_formatter->format( $this->value, $this->currency_code ));
	}

	/**
	 * The currency code.
	 *
	 * @return string
	 */
	public function currency_code(): string {
		return $this->currency_code;
	}

	/**
	 * Returns the object as array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'currency_code' => $this->currency_code(),
			'value'         => $this->value_str(),
		);
	}
}


/**
 * Class MoneyFormatter
 */
class MoneyFormatter {
	/**
	 * Currencies that does not support decimals.
	 *
	 * @var array
	 */
	private $currencies_without_decimals = array( 'HUF', 'JPY', 'TWD' );

	/**
	 * Returns the value formatted as string for API requests.
	 *
	 * @param float  $value The value.
	 * @param string $currency The 3-letter currency code.
	 *
	 * @return string
	 */
	public function format( float $value, string $currency ): string {
		return in_array( $currency, $this->currencies_without_decimals, true )
			? (string) round( $value, 0 )
			: number_format( $value, 2, '.', '' );
	}
}
