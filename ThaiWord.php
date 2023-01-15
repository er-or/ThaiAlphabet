<? if (!class_exists('ThaiWord')) {

/**
 * ThaiWord is used to wrap multiple syllables together.
 *
 * Used for checking transliterations against the word list, and used for looking up words in the word list.
 * But actually, if a database is available, should just go for the database.
 * Also used so that transliteration can get tone mark exceptions.
 */
class ThaiWord {

	/**
	 * Unicode String of the Thai word.
	 * @var  string  Unicode String of the Thai word.
	 */
	public $chars = null;

	/**
	 * Unicode codepoints in an array of integers.
	 * @var  array  Unicode codepoints in an array of integers.
	 */
	public $codes = null;

	/**
	 * The length of the string, and codepoint arrays.
	 * @var  integer  The length of the string, and codepoint arrays.
	 */
	public $length = null;

	/**
	 * Array of integers of the tones for each syllable in the Thai word.
	 * @var  array  Array of integers of the tones for each syllable in the Thai word.
	 */
	public $tones = null;

	/**
	 * Array of lengths for each syllable: 1 = short, 2 = long.
	 * @var  array  Array of lengths for each syllable: 1 = short, 2 = long.
	 */
	public $longs = null;

	/**
	 * The English translation of this Thai word.
	 * @var  string  The English translation of this Thai word.
	 */
	public $english = null;

	/**
	 * The syllabic spelling of this word as an array of codepoints.
	 * @var  array  The syllabic spelling of this word as an array of codepoints.
	 */
	private $syllab = null;

	/**
	 * An array of syllabic spelling strings - a buffer used for reconstructing the syllabic spelling in getSyllables().
	 * @var  string   An array of syllabic spelling strings - a buffer used for reconstructing the syllabic spelling in getSyllables().
	 */
	private $syllab_arg = null;

	/**
	 * Used by ThaiToRomanTransliterator to store words for heuristic spelling comparisons.
	 * @internal  For use by ThaiToRomanTransliterator.
	 * @var  array  Used by ThaiToRomanTransliterator to store words for heuristic spelling comparisons.
	 */
	public $parts = null;   // constituent syllable matches and words

	/**
	 * Number of parts.
	 * @internal  For use by ThaiToRomanTransliterator.
	 * @var  integer  Number of syllable parts in this word.
	 */
	public $count = 0;      // number of parts


	/**
	 * Instantiates a new ThaiWord.
	 *
	 * @param  array   $codes     Array of codepoints which makes the word.
	 * @param  array   $longs     Array of syllable lengths denoting pronunciation of syllables in the word.
	 * @param  array   $tones     Array of syllable tones denoting the pronunciation of the syllables in the word.
	 * @param  string  $english   English translation of word.
	 * @param  array   $syllab    The syllabic spelling of this word as an array of codepoints.
	 */
	public function __construct($codes, $longs, $tones, $english, $syllab = null) {
		$this->codes = $codes;
		$this->longs = $longs;
		$this->tones = $tones;
		$this->english = $english;
		$this->syllab = $syllab;
		$this->syllab_arg = null;
		$this->length = count($codes);
		$this->chars = '';
		for ($i = 0; $i < $this->length; $i++) {
			$this->chars .= mb_chr($this->codes[$i]);
		}
	}


	/**
	 * Helper function used for transliteration.
	 * @return  array  Array of codepoints for the syllabic spelling of this Thai word.
	 */
	public function getSyllables() {
		if (!$this->syllab) {
			return null;
		}
		if (!$this->syllab_arg) {
			$this->syllab_arg = array();
			$sin = 0;
			$last_was_space = true;
			for ($i = 0; $i < count($this->syllab); $i++) {
				$c = mb_chr($this->syllab[$i]);
				if ($c == ' ' || $c == '-') {
					if (!$last_was_space) {
						$sin++;
					}
					$last_was_space = true;
				} else {
					if ($this->syllab_arg[$sin]) {
						$this->syllab_arg[$sin] .= $c;
					} else {
						$this->syllab_arg[$sin] = $c;
					}
					$last_was_space = false;
				}
			}
		}
		return $this->syllab_arg;
	}


	/**
	 * Returns if this word equals another.
	 * @param  $o  ThaiWord  The other ThaiWord to compare this one against.
	 * @return  bool  If this word equals another.
	 */
	public function equals($o) {
		return (
			$o && $o->length == $this->length && $o->chars == $this->chars 
		  && $o->roman == $this->roman && $o->english == $this->english && $o->parts == $this->parts
		);
	}



}


}?>