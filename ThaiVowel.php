<? if (!class_exists('ThaiVowel')) {

/**
 * A Thai vowel.
 *
 * Thai vowels should not be confused with vowel patterns.  Vowels are unicode characters,
 * and vowel patterns are used for matching spellings to pronunciations and transliterations.
 *
 * Thai vowels are left, above, below, or right of the consonants.
 * Ordering in computer strings is left, above, below, right.
 * Tone marks go after an upper vowel and before any following vowel.
 */
class ThaiVowel extends ThaiSymbol {

	/**
	 * If this ThaiVowel has short pronunciation or not.
	 * @var  bool  If this ThaiVowel has short pronunciation or not.
	 */
	public $is_short = false;

	/**
	 * If this ThaiVowel has a long pronunciation or not.
	 * @var  bool  If this ThaiVowel has a long pronunciation or not.
	 */
	public $is_long = false;

	/**
	 * If this ThaiVowel occures to the left of the consonent or not.
	 * @var  bool  If this ThaiVowel occures to the left of the consonent or not.
	 */
	public $is_always_first = false;


	/*
	 * Constructs a new ThaiVowel.
	 *
	 * @param string $codepoint the unicode codepoint of this vowel.
	 * @param string $name_rtgs the RTGS ( Royal Thai General System of Transcription) name of this vowel.
	 * @param string $rtgs RTGS transcription of vowel.
	 * @param bool $is_short whether the vowel has short pronunciation or not (some are between and/or ambiguous).
	 * @param bool $is_long whether the vowel has long pronunciation or not (some are between and/or ambiguous).
	 * @param bool $always_first whether the vowel occurs in front of the consonant or not.
	 * @param array $thai_name_codes name of the symbol in Thai, explicitly in unicode codepoints.
	 */
	public function __construct($codepoint, $name_rtgs, $rtgs, $is_short = false, $is_long = false, $always_first = false, $thai_name_codes = null) {
		parent::__construct($codepoint, $name_rtgs, 'vowel for ' . $rtgs . '', $thai_name_codes, false, true);
		$this->is_short = $is_short;
		$this->is_long = $is_long;
		$this->is_always_first = $always_first;
	}
}


} ?>