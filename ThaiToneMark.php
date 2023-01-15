<? if (!class_exists('ThaiToneMark')) {


/**
 * Reflects a Thai tone mark.
 *
 * Only two tone marks indicate an actual tone.
 * The other three have spelling rules reflecting the historic drift between
 * the marks and the tones.
 *
 * These are initialized in ThaiAlphabet.php
 */
class ThaiToneMark extends ThaiSymbol {

	/**
	 * Makes a new Thai tone mark.
	 *
	 * @param string $codepoint the unicode codepoint of this symbol.
	 * @param string $name_rtgs the RTGS ( Royal Thai General System of Transcription) name of this symbol.
	 * @param string $meaning description in English, like, "a mark to make the letter silent".
	 * @param array $thai_name_codes name of the symbol in Thai, explicitly in unicode codepoints.
	 */
	public function __construct($codepoint, $name_rtgs, $meaning, $thai_name_codes = null) {
		parent::__construct($codepoint, $name_rtgs, $meaning, $thai_name_codes, false, false, true);
	}

}



} ?>