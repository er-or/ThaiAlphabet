<? if (!class_exists('ThaiSymbol')) {

/**
 * Top-level class for Thai characters.
 */
class ThaiSymbol {


	/**
	 * Single-char UTF-8 string of this character/symbol.
	 * @var  string  single-char string of the Thai symbol.
	 */
	public $char;

	/**
	 * Unicode codepoint of this character/symbol
	 * @var  integer  the Thai codepoint.
	 */
	public $code;

	/**
	 * English / RTGS (Royal Thai General System of Transcription) name of symbol.
	 * @var  string  English / RTGS (Royal Thai General System of Transcription) name of symbol.
	 */
	public $name;

	/**
	 * Array of UTF-8 codepoints for the Thai name of this symbol
 	 * @var  integer[]  Array of UTF-8 codepoints for the Thai name of this symbol
	 */
	public $thai_name;

	/**
	 * Array of unicode character codepoints.
	 * @var  string  Array of unicode character codepoints.
	 */
	public $thai_name_codes;

	/**
	 * Description of this symbol in English.
	 * @var  string  Description of this symbol in English.
	 */
	public $meaning;

	/**
	 * Whether this symbol is a Thai digit or not.
	 * @var  bool  Whether this symbol is a Thai digit or not.
	 */
	public $is_numeric = false;

	/**
	 * Whether this symbol is a Thai consonant or not.
	 * @var  bool  Whether this symbol is a Thai consonant or not.
	 */
	public $is_consonant = false;

	/**
	 * Whether this symbol is a Thai vowel or not.
	 * @var  bool  Whether this symbol is a Thai vowel or not.
	 */
	public $is_vowel = false;

	/**
	 * Whether this symbol is a Thai tone mark or not.
	 * @var  bool  Whether this symbol is a Thai tone mark or not.
	 */
	public $is_tone_mark = false;

	/**
	 * Whether this symbol is a Thai modifier or not (no sound).
	 * @var  bool  Whether this symbol is a Thai modifier or not (no sound)
	 */
	public $is_modifier = false;


	/**
	 * Registrar for instantiated ThaiSymbols.
	 * All ThaiSymbols are instantiated in ThaiAlphabet.php
	 * @var  array  Maps codepoints to ThaiSymbol objects.
	 */
	public static $REGISTRAR = array();


	/**
	 * Makes a new ThaiSymbol object.
	 *
	 * @param string $codepoint the unicode codepoint of this symbol.
	 * @param string $name_rtgs the RTGS ( Royal Thai General System of Transcription) name of this symbol.
	 * @param string $meaning description in English, like, "a mark to make the letter silent".
	 * @param array $thai_name_codes name of the symbol in Thai, explicitly in unicode codepoints.
	 * @param bool $is_consonant whether the ThaiSymbol is a consonant.
	 * @param bool $is_vowel whether the ThaiSymbol is a vowel.
	 * @param bool $is_tone whether the ThaiSymbol is a tone mark.
	 * @param bool $is_numeric whether the ThaiSymbol is a digit.
	 * @param bool $is_modifier whether the ThaiSymbol is a modifier used specifically for changing the pronounciation.
	 */
	public function __construct($codepoint, $name_rtgs, $meaning, $thai_name_codes = null, $is_consonant = false, $is_vowel = false, $is_tone = false, $is_numeric = false, $is_modifier = false) {

		$this->code = $codepoint;
		$this->char = mb_chr($codepoint);
		$this->name = $name_rtgs;
		$this->meaning = $meaning;
		$this->is_consonant = $is_consonant;
		$this->is_vowel = $is_vowel;
		$this->is_tone_mark = $is_tone;
		$this->is_numeric = $is_numeric;
		$this->is_modifier = $is_modifier;

		if ($thai_name_codes) {
			$this->thai_name_codes = $thai_name_codes;
			$this->thai_name = '';
			$len = count($thai_name_codes);
			for ($i = 0; $i < $len; $i++) {
				$this->thai_name .= mb_chr($thai_name_codes[$i]);
			}
		}

		if (!isset(self::$REGISTRAR[$codepoint])) {
			self::$REGISTRAR[$codepoint] = $this;
		}
	}



	/**
	 * Minimum Thai unicode codepoint
	 * @var integer 0x0E01
	 */
	public static $MIN_CODE = 0x0E01;

	/**
	 * Maximum Thai unicode codepoint
	 * @var integer 0x0E5B
	 */
	public static $MAX_CODE = 0x0E5B;

	/**
	 * Beginning of gap in Thai unicode block.
	 * @var integer 0x0E3A
	 */
	public static $GAP_MIN_CODE = 0x0E3A;

	/**
	 * Beginning of gap in Thai unicode block.
	 * @var integer 0x0E3F
	 */
	public static $GAP_MAX_CODE = 0x0E3F;


	/**
	 * Returns the ThaiSymbol from the unicode codepoint.
	 * @param  integer  $c  The integer to get the ThaiSymbol object for
	 * @return  ThaiSymbol  The ThaiSymbol from the unicode codepoint.
	 */
	public static function fromCharCode($c) {
		if ($c < self::$MIN_CODE || $c > self::$MAX_CODE || ($c < self::$GAP_MAX_CODE && $c > self::$GAP_MIN_CODE)) {
			return false;
		}
		if (!isset(self::$REGISTRAR[$c])) {
			return false;
		}
		return self::$REGISTRAR[$c];
	}

	/**
	 * Returns if this ThaiSymbol is a spelling letter of any type or not or not.
	 * @return   bool  If this ThaiSymbol is a spelling letter of any type or not or not.
	 */
	public function isLetter() {
		return $this->is_consonant || $this->is_vowel || $this->is_tone_mark || $this->is_modifier;
	}

	/**
	 * Returns if specified unicode codepoint a Thai letter of any type or not.
	 * @return   bool  If specified unicode codepoint a Thai letter of any type or not.
	 */
	public static function isCodeLetter($c) {
		if ($c < self::$MIN_CODE || $c > self::$MAX_CODE || ($c < self::$GAP_MAX_CODE && $c > self::$GAP_MIN_CODE)) {
			return false;
		}
		return self::$REGISTRAR[$c]->isLetter();
	}

	/**
	 * Returns if this ThaiSymbol is a consonant or not.
	 * @return   bool  If this ThaiSymbol is a consonant or not.
	 */
	public function isConsonant() {
		return $this->is_consonant;
	}

	/**
	 * Returns if specified unicode codepoint is a Thai consonant or not.
	 *
	 * @param    $c    The Unicode codepoint of the consonant to test.
	 * @return   bool  True iff the codepoint is a consonant, false otherwise.
	 */
	public static function isCodeConsonant($c) {
		if ($c < self::$MIN_CODE || $c > self::$MAX_CODE || ($c < self::$GAP_MAX_CODE && $c > self::$GAP_MIN_CODE)) {
			return false;
		}
		return self::$REGISTRAR[$c]->isConsonant();
	}


	/**
	 * Returns a string representing this ThaiSymbol for debugging.
	 * @return  string  Returns a string representing this ThaiSymbol for debugging.
	 */
	public function __toString() {
		return $this->char . '(' . $this->code . '/' . $this->name . ')';
	}
}



} ?>