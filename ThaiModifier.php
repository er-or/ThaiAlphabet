<? if (!class_exists('ThaiModifier')) {

/**
 * Modifier chars are for special purposes, but do not change the verb matching rules.
 * For example, syllabic spelling:
 *   *)  Phinthu - makes implied vowel silent.
 *   *)  Thanthakhat - makes a consonant silent, a cancellation mark.  Often used in loanwords. 
 *   *)  Yamakkan - Obsolete, used to mark the beginning of consonant clusters, replaced by pinthu.
 */
class ThaiModifier extends ThaiSymbol {

	/**
	 * Makes a new modifier.
	 *
	 * @param string $codepoint the unicode codepoint of this symbol.
	 * @param string $name_rtgs the RTGS ( Royal Thai General System of Transcription) name of this symbol.
	 * @param string $meaning description in English, like, "a mark to make the letter silent".
	 * @param array $thai_name_codes name of the symbol in Thai, explicitly in unicode codepoints.
	 */
	public function __construct($codepoint, $name_rtgs, $meaning, $thai_name_codes = null) {
		parent::__construct($codepoint, $name_rtgs, $meaning, $thai_name_codes, false, false, false, false, true);
	}
}

} ?>