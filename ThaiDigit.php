<? if (!class_exists('ThaiDigit')) {
/**
 * A Thai number.  Oddly enough, base 10.
 */
class ThaiDigit extends ThaiSymbol {

	/**
	 * The numeric value of the digit (0 - 9)
	 */
	private $value = 0;

	/**
	 * Makes a new ThaiDigit.
	 */
	public function __construct($codepoint, $name_rtgs, $value, $thai_name_codes = null) {
		parent::__construct($codepoint, $name_rtgs, $value, $thai_name_codes, false, false, false, true);
		$this->value = $value;
	}

	/**
	 * Returns the base-10 value of this base-10 Thai digit.
	 */
	public function getValue() {
		return $this->value;
	}
}

} ?>