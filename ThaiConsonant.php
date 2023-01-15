<? if (!class_exists('ThaiConsonant')) {

/**
 * Class ThaiConsonant represents a consonant in Thai script.
 *
 * Consonants are written horizontally from left to right.
 * Vowels following a consonant in speech are written to the left, above, below, or to the right of it,
 * or a combination of those.
 *
 * This class is used by ThaiToRomanTransliterator for setting spellings and getting spellings for transliteration.
 * The $use_head is the transliteration used when the consonant is used as an initial.
 * The $use_tail is the transliteration used when the consonant is used as the final. 
 */
class ThaiConsonant extends ThaiSymbol {

	/**
	 * The tone class, for reference: low = -1, mid = 0, high = 1.
	 * @var  integer  The tone class, for reference: low = -1, mid = 0, high = 1.
	 */
	public $tone_class;

	/**
	 * The RTGS transliteration used when this consonant is an initial (first in syllable).
	 * @var  string  The RTGS transliteration used when this consonant is an initial (first in syllable).
	 */
	public $rtgs_head;

	/**
	 * The RTGS transliteration letter used when this consonant is a final (last in syllable).
	 * @var  string  The RTGS transliteration letter used when this consonant is a final (last in syllable).
	 */
	public $rtgs_tail;

	/**
	 * The current transliteration letter used when this consonant is an initial (first in syllable).
	 * @var  string  The current transliteration letter used when this consonant is an initial (first in syllable).
	 */
	public $use_head;

	/**
	 * The current transliteration letter used when this consonant is a final (last in syllable).
	 * @var  string  The current transliteration letter used when this consonant is a final (last in syllable).
	 */
	public $use_tail;

	/**
	 * Whether this consonant is a dead final or not.
	 * @var  bool  Whether this consonant is a dead final or not.
	 */
	public $is_dead_final = false;

	/**
	 * Whether this consonant is a live final or not.
	 * @var  bool  Whether this consonant is a live final or not.
	 */
	public $is_live_final = false;

	/**
	 * Makes a new ThaiConsonant
	 *
	 * @param string $codepoint the unicode codepoint of this vowel.
	 * @param string $name_rtgs the RTGS (Royal Thai General System of Transcription) name of this vowel.
	 * @param string $meaning description in English, like, "a mark to make the letter silent".

	 * @param string $rtgs_initial The RTGS letter used when this consonant is an initial (first in syllable).
	 * @param string $rtgs_final The RTGS letter used when this consonant is a final (last in syllable).
	 *
	 * @param string $class The tone class, for reference: low = -1, mid = 0, high = 1.
	 *
	 * @param string $custom_initial The current letter used when this consonant is an initial (first in syllable).
	 * @param string $custom_final The current letter used when this consonant is a final (last in syllable).
	 *
	 * @param array $thai_name_codes name of the symbol in Thai, explicitly in unicode codepoints.
	 */
	public function __construct($codepoint, $name_rtgs, $meaning, $rtgs_initial, $rtgs_final, $class, $custom_initial, $custom_final, $thai_name_codes = null) {
		parent::__construct($codepoint, $name_rtgs, $meaning, $thai_name_codes, true);
		$this->tone_class = $class;
		$this->rtgs_head = $rtgs_initial;
		$this->rtgs_tail = $rtgs_initial;
		$this->use_head = $custom_initial;
		$this->use_tail = $custom_final;
	}

	/**
	 * Returns a string for debugging.
	 * @return  string  Returns a string for debugging.
	 */
	public function __toString() {
		return parent::__toString() . ' [' . $this->tone_class . ']';
	}

}

} ?>