<? if (!class_exists('ThaiToRomanTransliterator')) {

/**
 * Class that handles the transliteration from Thai to another spelling system,
 * specifically, it decomposes the Thai and reassembles it using a table of
 * strings.
 *
 * The output is always left-to-right text.
 *
 * But there isn't a specific constraint on transliteration table content.
 *
 * The Roman/Latin alphabets are preferable because they can use the accent marks
 * above the output to indicate tone and length of each syllable.
 *
 *
 */
class ThaiToRomanTransliterator {


	// Inherent vowel used in transliteration if none other is spelled
	public static $INHERENT_VOWEL = 'a';

	// Whether to use the word list to assist with transliteration
	public static $USE_WORD_LIST = true;

	// Syllable separator used in transliteration output
	public static $SYLLABLE_SEPARATOR = '-';

	// Word separator used in transliteration output
	public static $WORD_SEPARATOR = ' ';


	/**
	 * Flag indicating to parse all words (default).
	 * Possible value for $word_level in transliterateWords()
	 */
	public static $PARSE_ALL_WORDS = 0;

	/**
	 * Flag indicating for transliteration to not return words at all, but only syllables.
	 * Possible value for $word_level in transliterateWords()
	 */
	public static $PARSE_NO_WORDS = 1;

	/**
	 * Flag indicating to parse the constituent words of the biggest word of param $len.
	 * Possible value for $word_level in transliterateWords()
	 */
	public static $PARSE_SMALLER_WORDS_ONLY = 2;



	/**
	 * Special spellings HO NAM
	 * from: https://en.wikipedia.org/wiki/Thai_script
	 * Ho nam - a silent, high-class ho-hip"leads" low-class nasal stops and non-plosives which have no corresponding high-class phonetic match, 
	 * into the tone properties of a high-class consonant. In polysyllabic words, an initial mid- or high-class consonant with an implicit vowel
	 * similarly "leads" these same low-class consonants into the higher class tone rules, with the tone marker borne by the low-class consonant.
	 *
	 * Initialized below.
	 */
	public static $HO_NAM = null;


	/**
	 * These are letters which always occur at the beginning of a syllable.
	 * Initialized below.  Used for reducing problem space of transliteration.
	 */
	public static $SURE_STARTS = null;


	/**
	 * These are letters which always occur at the end of a syllable.
	 * Initialized below.  Used for reducing problem space of transliteration.
	 */
	public static $SURE_ENDERS = null;




	/**
	 * Getter method to return a vowel rule by it's name.
	 */
	public static function getVowelRuleByName($name, $is_short = 1, $is_long = 0) {
		$name = mb_strToLower($name);
		for ($i = 0; $i < count(self::$VERB_RULES); $i++) {
			if (((self::$VERB_RULES[$i]['short'] && $is_short) || (self::$VERB_RULES[$i]['long'] && $is_long)) && mb_strToLower(self::$VERB_RULES[$i]['name']) == $name) {
				return self::$VERB_RULES[$i];
			}
		}
		return null;
	}


	/**
	 * Getter method to return a Thai letter by it's name.
	 */
	public static function getThaiLetterByName($name) {
		return ThaiAlphabet::getSymbolByName($name);
	}






	/**
	 * Transliterates given string input to the designated spelling table.
	 */
	public static function transliterate($s) {

		$s = str_replace("\xc2\xa0", '', $s);
		$a = self::charify($s);
		$len = count($a);

		// SCAN SMALLEST PARTS, AND TRANSLITERATE EACH SEGMENT SEPARATELY
		$scan_head = 0;
		$scan_tail = 0;
		$output = '';
		while ($scan_head < $len) {


			// SCAN THROUGH OTHER UNICODE UNTIL THAI
			while ($scan_head < $len) {
				$c = $a[$scan_head];
				$is_thai = is_object($c);
				if ($is_thai && $c->isLetter()) {
					// TIME TO START SCANNING THAI
					if ($scan_head > $scan_tail) {
						$output .= mb_substr($s, $scan_tail, $scan_head - $scan_tail);
					}
					$scan_tail = $scan_head;
					break;
				} else if ($is_thai) {
					if ($scan_head > $scan_tail) {
						$output .= mb_substr($s, $scan_tail, $scan_head - $scan_tail);
					}
					$output .= self::transliterateSingle($c, true);
					$scan_head++;
					$scan_tail = $scan_head;
					$scan_head++;
				} else {
					$scan_head++;
				}
			}


			// SCAN THROUGH THAI
			while ($scan_head < $len) {
				$c = $a[$scan_head];
				$is_thai = is_object($c);
				if ($is_thai && $c->isLetter()) {
					// FINE, CONTINUE
					$scan_head++;
				} else if ($is_thai) {
					if ($scan_head > $scan_tail) {
						if ($output) $output .= ' ';
						$output .= self::transliterateWords($s, $a, $scan_tail, $scan_head - $scan_tail);
					}
					if ($output) $output .= ' ';
					$output .= self::transliterateSingle($c, true);
					$scan_head++;
					$scan_tail = $scan_head;
				} else {
					// NOT EVEN THAI...
					if ($scan_head > $scan_tail) {
						if ($output) $output .= ' ';
						$output .= self::transliterateWords($s, $a, $scan_tail, $scan_head - $scan_tail);
						//$output .= mb_substr($s, $scan_tail, $scan_head - $scan_tail);
						$scan_tail = $scan_head;
					}
					break;
				}
			}
		}
		$scan_head = $len;
		if ($scan_head > $scan_tail) {
			$c = $a[$scan_head - 1];
			$is_thai = is_object($c);
			if ($is_thai) {
				if ($output) $output .= ' ';
				$output .= self::transliterateWords($s, $a, $scan_tail, $scan_head - $scan_tail);
			} else {
				if ($output) $output .= ' ';
				$output .= mb_substr($s, $scan_tail, $scan_head - $scan_tail);
			}
		}
		return $output;
	}



	/**
	 * Sets the spelling table used for transliteration.
	 *
	 * For example:
	 * ThaiToRomanTransliterator::setSpellings([
	 *   // Consonants
	 *   "ko kai" => ["g", "k"],
	 *   "kho khai" => ["kh", "g"],
	 *   ....
	 *   "ho nok-huk" => ["h",""],
	 *   ....
	 *   // Vowels
	 *   "sara a" => ["a", "aa"],
	 *   "sara i" => ["i", "ee"],
	 *   "sara ue" => ["ue", "uea"],
	 *   ....
	 * ]);
	 * ThaiToRomanTransliterator::$INHERENT_VOWEL = 'a';// 'ā';
	 *
	 * Note that ThaiToRomanTransliterator::$INHERENT_VOWEL should be assigned to the same short vowel for 'sara a'
	 *
	 * @param $tab   An associative array of consonant and vowel names (strings) mapped tuples of strings.
	 *               The consonants map to a tuple of "initial" and "ending" pronunciations.
	 *               The vowels map to a tuple of "short" and "long" pronunciations.
	 *
	 *
	 * @return       The spelling table that was previously in use.
	 */
	public static function setSpellings($tab) {
		$old = array();
		for ($i = 0; $i < count(self::$VERB_RULES); $i++) {
			if (!isset($old[self::$VERB_RULES[$i]['name']])) {
				$old[self::$VERB_RULES[$i]['name']] = array();
			}
			$old[self::$VERB_RULES[$i]['name']][self::$VERB_RULES[$i]['long']] = self::$VERB_RULES[$i]['use'];
			if (isset($tab[self::$VERB_RULES[$i]['name']]) && isset($tab[self::$VERB_RULES[$i]['name']][(self::$VERB_RULES[$i]['long'] ? 1 : 0)])) {
				self::$VERB_RULES[$i]['use'] = $tab[self::$VERB_RULES[$i]['name']][(self::$VERB_RULES[$i]['long'] ? 1 : 0)];
				//if (self::$VERB_RULES[$i]['name'] == 'sara oe' && self::$VERB_RULES[$i]['long']) {
				//  throw new Exception('long oe: ' . $tab[self::$VERB_RULES[$i]['name']][(self::$VERB_RULES[$i]['long'] ? 1 : 0)]);
				//}
			}
		}
		for ($c = ThaiAlphabet::$KO_KAI->code; $c <= ThaiAlphabet::$HO_NOKHUK->code; $c++) {
			$o = ThaiSymbol::fromCharCode($c);
			$old[$o->name] = array(0=>$o->use_head, 0=>$o->use_tail);
			if (isset($tab[$o->name])) {
				if ($tab[$o->name][0]) $o->use_head = $tab[$o->name][0];
				if ($tab[$o->name][1]) $o->use_tail = $tab[$o->name][1];
			}
		}
		return $old;
	}

	/**
	 * Gets the current spelling table used for transliteration.
	 *
	 * @return       An associative array of consonant and vowel names (strings) mapped tuples of strings.
	 *               The consonants map to a tuple of "initial" and "ending" pronunciations.
	 *               The vowels map to a tuple of "short" and "long" pronunciations.
	 */
	public static function getSpellings($get_rtgs = false) {
		$old = array();
		for ($c = ThaiAlphabet::$KO_KAI->code; $c <= ThaiAlphabet::$HO_NOKHUK->code; $c++) {
			$o = ThaiSymbol::fromCharCode($c);
			if ($get_rtgs) {
				$old[$o->name] = array(0=>$o->rtgs_head, 1=>$o->rtgs_tail);
			} else {
				$old[$o->name] = array(0=>$o->use_head, 1=>$o->use_tail);
			}
		}
		for ($i = 0; $i < count(self::$VERB_RULES); $i++) {
			if (!isset($old[self::$VERB_RULES[$i]['name']])) {
				$old[self::$VERB_RULES[$i]['name']] = array();
			}
			$old[self::$VERB_RULES[$i]['name']][(self::$VERB_RULES[$i]['long'] ? 1 : 0)] = self::$VERB_RULES[$i][($get_rtgs ? 'rtgs' : 'use')];
		}
		return $old;
	}



	/**
	 * Prints the spelling table.  For debugging.
	 */
	public static function printSpellings($spellings = null, $indent = '    ') {
		if (!$spellings) {
			$spellings = self::getSpellings();
		}
		$names = array_keys($spellings);
		$values = array_values($spellings);
		for ($i = 0; $i < count($names); $i++) {
			echo $indent . '"' . $names[$i] . '"=>["' . $values[$i][0] . '","' . $values[$i][1] . '"],' . "\r\n";
		}
	}








	/**
	 * Returns a guess as to the minimum number of syllables.
	 * Does this by using $SURE_ENDERS and $SURE_STARTS in Thai parts.
	 *
	 * Used by client code for analysis on dictionary entries.
	 *
	 * @return The minimum of syllables.  There may be more.
	 */
	public static function getMinPossibleSyllables($s) {
		$scan_head = 0;
		$scan_tail = 0;
		$end = mb_strlen($s);
		$min_syllables = 0;
		while ($scan_head < $end) {
			// SCAN THROUGH OTHER UNICODE UNTIL THAI
			while ($scan_head < $end) {
				$c = mb_ord(mb_substr($s, $scan_head, 1));
				if (ThaiSymbol::isCodeLetter($c)) {
					// TIME TO START SCANNING THAI
					$scan_tail = $scan_head;
					$scan_head++;
					break;
				} else {
					$scan_head++;
				}
			}
			// SCAN THROUGH THAI
			while ($scan_head < $end) {
				$c = mb_ord(mb_substr($s, $scan_head, 1));
				if (ThaiSymbol::isCodeLetter($c)) {
					if (self::$SURE_STARTS[$c]) {
						if ($scan_head > $scan_tail) {
							$min_syllables++;
						}
						$scan_tail = $scan_head;
						$scan_head++;
					} else if (self::$SURE_ENDERS[$c]) {
						if ($scan_head > $scan_tail) {
							$min_syllables++;
						}
						$scan_head++;
						$scan_tail = $scan_head;
					} else {
						$scan_head++;
					}
				} else {
					if ($scan_head > $scan_tail) {
						$min_syllables++;
						$scan_tail = $scan_head;
					}
					break;
				}
			}
		}
		if ($scan_tail < $end) {
			$min_syllables++;
		}
		return $min_syllables;
	}



	/**
	 * Returns an array of integers with values of unicode codepoints of the input string.
	 * Javascript support around the world was ahead of many PHP installations.
	 */
	private static function charify($s) {
		$a = array();
		if (!$s) return $a;
		$len = mb_strlen($s);
		if (is_array($s)) {
			throw new Exception('should not be an array');
			return $s;
		}
		for ($i = 0; $i < $len; $i++) {
			$c = mb_ord(mb_substr($s, $i, 1));
			$o = ThaiSymbol::fromCharCode($c);
			if ($o) {
				$c = $o;
			}
			array_push($a, $c);
		}
		return $a;
	}




	/**
	 * Returns a user friendly string for the pattern array for debugging.
	 * This function is also referenced by client code.
	 */
	public static function arrayToString($pat) {
		if (!$pat) {
			if ($pat === false) return 'false';
			if ($pat === 0) return '0';
			return 'null';
		}
		$patlen = count($pat);
		$s = '';
		for ($i = 0; $i < $patlen; $i++) {
			if ($pat[$i] == 0) {
				$s .= mb_chr(9676);
			} else {
				$s .= mb_chr($pat[$i]);
			}
		}
		return $s;
	}



	/**
	 * ThaiToRomanTransliterator::transliterateSyllable()
	 * Helper function which transliterates a single syllable.
	 */
	public static function transliterateSyllable($s) {

		$s = str_replace("\xc2\xa0", '', ThaiAlphabet::mb_trim($s));
		$a = self::charify($s);
		$len = count($a);

		$restore_use_list = ThaiToRomanTransliterator::$USE_WORD_LIST;
		ThaiToRomanTransliterator::$USE_WORD_LIST = false;
		$matches = self::transliterateSegment($s, $a, 0, $len);
		ThaiToRomanTransliterator::$USE_WORD_LIST = $restore_use_list;

		if ($matches['matches']) {
			$matches = $matches['matches'];
		}
		//echo var_export($matches, true);
		for ($i = 0; $i < $len; $i++) {
			if ($matches[$i] && count($matches[$i])) {
				return $matches[$i][0];
			}
		}
		return null;
	}





	/**
	 * Unused reference function.  This is used in the javascript version.
	 *
	 * @param string $s The text to transliterate (romanize)
	 *
	 * @return A transliterated version of the thai input.
	 */
	public static function romanizeSyllable($s) {
		$sy = ThaiToRomanTransliterator::transliterateSyllable($s);
		if ($sy) {
			return ThaiToRomanTransliterator::applyTone($sy['roman'], $sy['tone'], $sy['vowel']['long']);
		}
		return '';
	}

	/**
	 * Transliterates syllabic spelling of a Thai word (used in Thai dictionaries for pronunciation).
	 * syllabic spellings commonly contain dashes and a special dot notations to indicate clusters.
	 *
	 * @param string $s the text to transliterate (romanize)
	 *
	 * @return a transliterated version of the thai input.
	 */
	public static function romanizeSyllables($s) {
		// USED BY CLIENT CODE, DATABASE LOADED WITH NON-BREAKING WHITESPACES, TOO LATE FOR A RELOAD
		$s = ThaiAlphabet::mb_trim(str_replace("\xc2\xa0", '', $s), "][");
		$t = '';
		$parts = ThaiAlphabet::mb_explode(null, $s);
		$num_parts = count($parts);
		for ($i = 0; $i < $num_parts; $i++) {
			$sub_parts = ThaiAlphabet::mb_explode('-', $parts[$i]);
			$num_sub_parts = count($sub_parts);
			$t2 = '';
			for ($j = 0; $j < $num_sub_parts; $j++) {
				$sy = ThaiToRomanTransliterator::transliterateSyllable($sub_parts[$j]);
				if ($sy) {
					if ($t2) $t2 .= '-';
					$t2 .= ThaiToRomanTransliterator::applyTone($sy['roman'], $sy['tone'], $sy['vowel']['long']);
				}
			}
			if ($t2) {
				if ($t) $t .= ' ';
				$t .= $t2;
			} else if ($parts[$i]) {
				$sy = ThaiToRomanTransliterator::transliterateSyllable($parts[$i]);
				if ($sy) {
					if ($t) $t .= ' ';
					$t .= ThaiToRomanTransliterator::applyTone($sy['roman'], $sy['tone'], $sy['vowel']['long']);
				} else {
					//$t .= '?';
				}
			} else {
				//$t .= '!';
			}
		}
		//$t .= $num_parts;
		return $t;
	}
















	/**
	 * Main function is as a helper, but can also be used by more integrated client code.
	 *
	 * Looks ahead, returns a single transliteration and the length transliterated
	 *
	 * Note:
	 * Returns a string for now, but in future should return a transliteration object instance
	 * so that the first few words can be transliterated in an excessively long sentence or stream.
	 *
	 * @param string   $str          The unicode UTF8 input string.
	 * @param array    $arg          The array of Thai characters generated by function codify() in this file.
	 * @param integer  $off          The offset into the string and array
	 * @param integer  $len          The length to attempt to transliterate
	 * @param integer  $word_level   The mode in which to parse the words: {$PARSE_ALL_WORDS | $PARSE_NO_WORDS | $PARSE_SMALLER_WORDS_ONLY}
	 *    1) The default is $PARSE_ALL_WORDS, which grabs biggest word possible.
	 *    2) Another is $PARSE_SMALLER_WORDS_ONLY, which parses the consituent words of the biggest word of length $len.
	 *    3) Another is $PARSE_NO_WORDS, which does not return words at all, but only syllables.
	 * @param ThaiWord $target_word        When matching with the target word, try to return the same number of syllables as the target word
	 * @param integer  $max_combo_checks   $maximum number of combo checks (optional).  Default $len * $len * 25.
	 *
	 */
	public static function transliterateWords($str, $arg, $off, $len, $matchlist = null, $word_level = 0, $target_word = null, $max_combo_checks = 0) {


		//echo 'transliterateWords(' . $str . ')<br />';
		$word_separator = ' ';
		$syllable_separator = '-';


		// SEARCH FOR WHAT
		$max_wordlen = $len;
		$match_with_words = true;
		if (!self::$USE_WORD_LIST) {
			$word_level = self::$PARSE_NO_WORDS;
		}
		if ($word_level == self::$PARSE_SMALLER_WORDS_ONLY) {
			$max_wordlen--;
		} else if ($word_level == self::$PARSE_ALL_WORDS) {
			// default
		} else if ($word_level == self::$PARSE_NO_WORDS) {
			$match_with_words = false;
		}


		// MAX COMBINATIONS TO CHECK
		if ($max_combo_checks <= 0) {
			$max_combo_checks = $len * $len * 25;
		}


		// MATCH WITH TONES?
		$target_tones = null;
		$target_tone_count = 0;
		if ($target_word) {
			$target_tones = $target_word->tones;
			$target_tone_count = count($target_tones);
		}


		//
		// PUT MATCHES HERE
		//
		$matches = null;
		$sorting = null;
		$lengths = null;
		$counter = array();
		$changed = array();

		//
		// MATCHLIST SHOULD BE SPECIFIED IF SEARCHING FOR SUB-WORDS AND SYLLABLES OF A LARGER WORD
		//
		if ($matchlist) {
			for ($i = 0; $i < $len; $i++) {
				$counter[$i] = 0;
				$changed[$i] = 0;
			}
		} else {
			$matches = array();
			$sorting = array();
			$lengths = array();
			for ($i = 0; $i < $len; $i++) {
				if (!isset($matches[$i])) {
					$matches[$i] = array();
					$sorting[$i] = array();
					$lengths[$i] = 0;
				}
				$counter[$i] = 0;
				$changed[$i] = 0;
			}


			if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
			   // echo '<pre>';
				echo '--- MATCHING VERBS ----------------------------------<br />';
			}

			// SCAN SMALLEST PARTS, AND TRANSLITERATE EACH SEGMENT SEPARATELY
			$scan_head = $off;
			$scan_tail = $off;
			$output = '';
			$end = $off + $len;
			while ($scan_head < $end) {

				// SCAN THROUGH OTHER UNICODE UNTIL THAI
				while ($scan_head < $end) {
					$c = $arg[$scan_head];
					$is_thai = is_object($c);
					if ($is_thai && $c->isLetter()) {
						// TIME TO START SCANNING THAI
						if ($scan_head > $scan_tail) {
							//$output .= mb_substr($s, $scan_tail, $scan_head - $scan_tail);
							echo 'ERROR: unexpected non-thai: ' . mb_substr($s, $scan_tail, $scan_head - $scan_tail) . '<br />';
						}
						$scan_tail = $scan_head;
						$scan_head++;
						break;
					} else if ($is_thai) {
						if ($scan_head > $scan_tail) {
							echo 'ERROR: unexpected non-thai: ' . mb_substr($s, $scan_tail, $scan_head - $scan_tail) . '<br />';
							$scan_tail = $scan_head;
						}
						echo 'ERROR: non-letter thai character: "' . $c->char . '" [' . $c->code . ']<br />';
						//$output .= self::transliterateSingle($c);
						$scan_head++;
						$scan_tail = $scan_head;
						$scan_head++;
					} else {
						$scan_head++;
					}
				}
				if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
					echo 'tail=' . $scan_tail . ': "' . $c->char . '" [' . $c->code . ']<br />';
				}

				// SCAN THROUGH THAI
				while ($scan_head < $end) {
					$c = $arg[$scan_head];
					$is_thai = $c instanceof ThaiSymbol;
					if ($is_thai && isset(self::$SURE_STARTS[$c->code]) && self::$SURE_STARTS[$c->code]) {
						if ($scan_head > $scan_tail) {
							$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
							self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
						}
						$scan_tail = $scan_head;
						$scan_head++;
					} else if ($is_thai && isset(self::$SURE_ENDERS[$c->code]) && self::$SURE_ENDERS[$c->code]) {
						//if ($output) $output .= ' ';
						//$output .= self::transliterateSegment($str, $arg, $scan_tail, $scan_head + 1 - $scan_tail, $target_word);
						$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head + 1 - $scan_tail, $target_word);
						self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);

						$scan_head++;
						$scan_tail = $scan_head;
					} else {
						if ($is_thai && $c->isLetter()) {
							// FINE, CONTINUE
							$scan_head++;
						} else if ($is_thai) {
							echo 'thai but not letter (' . $c->char . ')<br />';
							if ($scan_head > $scan_tail) {
								//if ($output) $output .= ' ';
								//$output .= self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
								$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
								self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
								$scan_tail = $scan_head;
								$scan_head++;
							}
							$index_matches = self::transliterateSingle($c, false);
							self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
							$scan_head++;
							$scan_tail = $scan_head;
						} else {
							echo 'not even thai (' . $c . ':' . mb_substr($str, $off, $len) . ')<br />';
							// NOT EVEN THAI...
							if ($scan_head > $scan_tail) {
								//if ($output) $output .= ' ';
								//$output .= self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
								$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
								self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
								$scan_tail = $scan_head;
							}
							break;
						}
					}
				}
			}
			$scan_head = $end;
			if ($scan_head > $scan_tail) {
				$c = $arg[$scan_head - 1];
				$is_thai = is_object($c);
				if ($is_thai) {
					//if ($output) $output .= ' ';
					//$output .= self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
					$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
					self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
				} else {
					echo ('!!!! end 2 error: [' . $scan_tail . ', ' . $scan_head . ']: ' . mb_substr($str, $scan_tail, $scan_head - $scan_tail) . '<br />');
					//if ($output) $output .= ' ';
					//$output .= mb_substr($str, $scan_tail, $scan_head - $scan_tail);
					//$index_matches = self::transliterateSegment($str, $arg, $scan_tail, $scan_head - $scan_tail, $target_word);
					//self::add_segment_matches($str, $arg, $scan_tail - $off, $index_matches, $matches, $sorting, $lengths);
				}
			}





			//
			//
			// ADD WORDS FOR MATCHING
			//
			//
			if ($match_with_words) {
				//
				// ADD WORDS TO LISTS FOR SORTING AND COMBINATING
				//
				if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
					echo '--- MATCHING WORDS --- TARGET TONE COUNT: ' . $target_tone_count . ' --------------------<br />';
				}
				for ($i = 0; $i < $len; $i++) {
					$words = ThaiWordList::getMatchingWords($str, $off + $i);//, min($len - 1, $max_wordlen));
					$num_words = ($words ? count($words) : 0);
					for ($j = 0; $j < $num_words; $j++) {
						$word = $words[$j];
						if ($word->length > $max_wordlen) {
							continue;
						}
						$match = array(
							'word' => $word,
							'length' => $word->length,
						);
						$matches[$i][$lengths[$i]] = $match;
						$sorting[$i][$lengths[$i]] = 0; // initialize for something...
						$lengths[$i]++;

						$match = null;
						$word = null;
						$words[$j] = null;
					}
					$words = null;
				}


			}



			//
			// SET MATCHLIST FOR SUB-WORDS AND SUB-VERBS
			// WE DON'T WANT TO RECOMPUTE MATCHING VERBS AND WORDS MULTIPLE TIMES
			//
			$matchlist = array(
				'matches' => $matches,
				'sorting' => $sorting,
				'lengths' => $lengths
			);
			for ($i = 0; $i < $len; $i++) {
				if (!isset($matchlist['matches'][$i])) {
					$matchlist['matches'][$i] = array();
					$matchlist['sorting'][$i] = array();
					$matchlist['lengths'][$i] = 0;
				}
			}

		} // END OF MATCHLIST CREATION



		//
		// MATCH WITH SUB-WORDS AND VERBS
		// BECAUSE ABOVE NOT ALL OF THE MATCHES WERE COMPUTED FOR THIS YET
		//
		if ($match_with_words) {
			for ($i = 0; $i < $len; $i++) {
				$sub_matchlist = array(
					'matches' => array_slice($matchlist['matches'], $i),
					'sorting' => array_slice($matchlist['sorting'], $i),
					'lengths' => array_slice($matchlist['lengths'], $i)
				);
				for ($j = 0; $j < $matchlist['lengths'][$i]; $j++) {
					if (isset($matchlist['matches'][$i][$j]['word']) && $matchlist['matches'][$i][$j]['word'] && $matchlist['matches'][$i][$j]['length'] <= $max_wordlen && $matchlist['matches'][$i][$j]['length'] <= $len - $i) {
						if (!$matchlist['matches'][$i][$j]['word']->parts) {
							$matchlist['matches'][$i][$j]['word']->parts = self::transliterateWords(
								$str, $arg, $off + $i, $matchlist['matches'][$i][$j]['length'],
								$sub_matchlist, self::$PARSE_SMALLER_WORDS_ONLY, //self::$PARSE_VERBS_ONLY,
								$matchlist['matches'][$i][$j]['word']
							);
							if ($matchlist['matches'][$i][$j]['word']->parts) {
								$matchlist['matches'][$i][$j]['word']->count = count($matchlist['matches'][$i][$j]['word']->parts);
							} else {
								$matchlist['matches'][$i][$j]['word']->count = 0;
							}
						}
					}
				}
				$sub_matchlist = null;
			}
		}


		//
		// COMPUTE SCORES AND TONE COUNTS
		//
		for ($i = 0; $i < $len; $i++) {
			for ($j = 0; $j < $matchlist['lengths'][$i]; $j++) {
				$matchlist['matches'][$i][$j]['tone_count'] = self::getToneCount($matchlist['matches'][$i][$j]); // put this before score...
				$matchlist['matches'][$i][$j]['syllable_sort_score'] = self::getSyllableScore($matchlist['matches'][$i][$j]);
				$matchlist['sorting'][$i][$j] = $matchlist['matches'][$i][$j]['syllable_sort_score'];
			}
		}


		//
		// SORT BEFORE COMBINATING, SO THAT WE DON'T HAVE TO GO THROUGH ALL COMBOS
		// MAKE SURE TO INITIALIZE THE UNINITIALIZED TO PREVENT ERRORS
		//
		for ($i = 0; $i < $len; $i++) {
			if (!isset($matchlist['matches'][$i])) {
				$matchlist['matches'][$i] = array();
				$matchlist['sorting'][$i] = array();
				$matchlist['lengths'][$i] = 0;
			} else if ($matchlist['lengths'][$i]) {
				array_multisort($matchlist['sorting'][$i], SORT_NUMERIC, SORT_DESC, $matchlist['matches'][$i]);
			}
			$counter[$i] = 0;
		}




////////////////////////////////////////////////////////////////
///// COMBINATE ////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

		if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
			echo '--- SCORING COMBINATIONS -----------------------------------------<br />';
		}

		$all_combos = array();
		$all_word_char_counts = array();
		$all_verb_char_counts = array();
		$all_words_counts = array();
		$all_verbs_counts = array();
		$all_stray_counts = array();
		$all_score_counts = array();
		$all_toned_counts = array();
		$combo_count = 0;


		//
		// GO THROUGH EACH WORD COMBO
		//
		$got_next = 1;
		$check_count = 0;
		$breadth_first_counter_level = 0;
		while ($got_next > 0) {

			$combo = array();
			$combo_len = 0;
			$num_verbs = 0;
			$num_words = 0;
			$num_strays = 0;
			$sort_score = 0;
			$tone_count = 0; // compare with $target_tone_count
			$tone_score = 0;
			$num_word_chars = 0;
			$num_verb_chars = 0;

			if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
				echo '***** ';
				for ($i = 0; $i < $len; $i++) {
					if ($i) {
						echo ',';
					}
					echo ($counter[$i] ? $counter[$i] : '0');
				}
				echo ' ************************************************<br />';
			}

			for ($i = 0; $i < $len; $i++) {
				$changed[$i] = false;
			}

			$i = 0;
			while ($i < $len) {
				$changed[$i] = true;
				if ($matchlist['matches'][$i] && $counter[$i] < $matchlist['lengths'][$i]) {
					$m = $matchlist['matches'][$i][$counter[$i]];
					$can_use = $m && $m['length'] && $m['length'] <= $len - $i;
					if ($can_use) {
						if ($match_with_words) {
							if (isset($m['word']) && $m['word']) {
								$can_use = $m['length'] <= $max_wordlen;
							}
						} else {
							if (isset($m['word']) && $m['word']) {
								$can_use = false;
							}
						}
					}
					if ($can_use) {
						$combo[$combo_len++] = $m;
						$sort_score += $matchlist['sorting'][$i][$counter[$i]];
						if (isset($m['word']) && $m['word']) {
							$num_words++;
							$num_word_chars += $m['length'];
							if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
								echo '[' . $m['word']->chars . '] => word[' . $i . ':' . $m['length'] . '=' . $m['word']->english . '] => ' . ' tones=' . $m['tone_count'] . '; score=' . $matchlist['sorting'][$i][$counter[$i]] . '<br />';
							}
							if ($target_tones) {
								$checklen = max($target_tone_count, count($m['word']->tones));
								for ($j = 0; $j < $checklen; $j++) {
									if ($target_tones[$j] != $m['word']->tones[$j]) $tone_score++;
								}
							}
						} else {
							$num_verbs++;
							$num_verb_chars += $m['length'];
							if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
								echo '[' . $m['chars'] . '] => verb[' . $i . ':' . $m['length'] . '=' . $m['roman'] . '] => ' . self::arrayToString($m['pattern']) . ' => ' . $m['chars'] . '; tones=' . $m['tone_count'] . '; score=' . $matchlist['sorting'][$i][$counter[$i]] . '<br />';
							}
							if ($target_tones) {
								$tone_score += $target_tone_count - 1;
								if ($target_tones[0] != $m['tone']) $tone_score++;
							}
						}
						$tone_count += $m['tone_count'];
						$i += $m['length'];
					} else {
						$combo[$combo_len++] = null;
						$i++;
						$num_strays += max(1, ($len - $i));  // strays at left are worse
					}
					$m = null;
				} else {
					$combo[$combo_len++] = null;
					$i++;
					$num_strays += max(1, ($len - $i));
				}
			}
			if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
				echo 'len=' . $combo_len . '; words: ' . $num_words . '; verbs: ' . $num_verbs . '; strays: ' . $num_strays . '' . ($target_tone_count ? '; toned:' . $tone_count . '-' . $target_tone_count . '=' . abs($tone_count - $target_tone_count) : '; top') . '; score: ' . $sort_score . '<br />';
				echo '-----------------------------------------------------<br />';
			}

			$already_got = false;
			for ($i = 0; $i < $combo_count; $i++) {
				if ($all_combos[$i] == $combo) {
					$already_got = true;
					break;
				}
			}


			if (!$already_got) {
				$all_combos[$combo_count] = $combo;
				$all_word_char_counts[$combo_count] = $num_word_chars;
				$all_verb_char_counts[$combo_count] = $num_verb_chars;
				$all_words_counts[$combo_count] = $num_words;
				$all_verbs_counts[$combo_count] = $num_verbs;
				$all_stray_counts[$combo_count] = $num_strays;
				$all_score_counts[$combo_count] = $sort_score;
				$all_toned_counts[$combo_count] = abs($tone_count - $target_tone_count);
				$combo_count++;

				$check_count++;
				if ($check_count >= $max_combo_checks) {
					break;
				}

			}
			$combo = null;


			$got_next--;
			/*
			// NEXT COMBO - LEFT COMBOS FIRST
			for ($i = 0; $i < $len; $i++) {
				if ($changed[$i] && $counter[$i] < $matchlist['lengths'][$i]) {
					$counter[$i]++;
					for ($j = $i - 1; $j >= 0; $j--) {
						$counter[$j] = 0;
					}
					$got_next = 1;
					break;
				}
			}
			*/
			//
			// NEXT COMBO - BREADTH FIRST
			//
			$got_next = 0;
			while (true) {
				$higher_level_available = false;
				$found_in_search_level = false;
				$maxed_out = true;
				for ($i = $len - 1; $i >= 0; $i--) {
					//echo $i . '<br />';
					if ($changed[$i] && $counter[$i] < $breadth_first_counter_level && $counter[$i] < $matchlist['lengths'][$i]) {
					//if ($counter[$i] < $breadth_first_counter_level && $counter[$i] < $matchlist['lengths'][$i]) {
						$counter[$i]++;
						for ($j = $i + 1; $j < $len; $j++) {
							$counter[$j] = 0;
						}
						$got_next = 1;
						$maxed_out = false;
					}
					if ($counter[$i] == $breadth_first_counter_level) {
						$found_in_search_level = true;
					}
					if ($breadth_first_counter_level < $matchlist['lengths'][$i]) {
						$higher_level_available = true;
					}
					if ($got_next) {
						break;
					}
				}
				if ($maxed_out) {
					if ($higher_level_available) {
						$breadth_first_counter_level++;
						//for ($i = 0; $i < $len; $i++) {
						//    $counter[$i] = 0;
						//}
					} else {
						$got_next = 0;
						break;
					}
				} else if ($found_in_search_level && $got_next) {
					break; // try this combo which has at least one bit in the search level
				} else {
					// we need to go through next combo and check if it's in the search level...
				}
			}

		}


		$matchlist = NULL;

		$sorting = null;
		$matches = null;
		$lengths = null;
		$counter = null;
		$changed = null;



		// SORT FOR BEST COMBO
		if ($combo_count > 0) {
			if ($target_tone_count > 0) {
				array_multisort(
					$all_toned_counts, SORT_NUMERIC,
					$all_word_char_counts, SORT_NUMERIC, SORT_DESC,
					$all_stray_counts, SORT_NUMERIC,
					$all_score_counts, SORT_NUMERIC, SORT_DESC,
					$all_verb_char_counts, SORT_NUMERIC, SORT_DESC, $all_verbs_counts, SORT_NUMERIC,
					$all_words_counts, SORT_NUMERIC,
					$all_combos
				);
				// echo 'words: ' . $all_words_counts[0] . '; verbs: ' . $all_verbs_counts[0] . '; strays: ' . $all_stray_counts[0] . '' . ($target_tone_count ? '; toned:' . $all_toned_counts[0] : '') . '; score: ' . $all_score_counts[0] . '<br />';
				// echo '==========================================================<br />';
			} else {
				//array_multisort($all_stray_counts, SORT_NUMERIC, $all_words_counts, SORT_NUMERIC, SORT_DESC, $all_score_counts, SORT_NUMERIC, SORT_DESC, $all_verbs_counts, SORT_NUMERIC, $all_words_counts, SORT_NUMERIC, $all_combos);
				array_multisort(
					$all_stray_counts, SORT_NUMERIC,
					$all_word_char_counts, SORT_NUMERIC, SORT_DESC, $all_words_counts, SORT_NUMERIC,
					$all_verb_char_counts, SORT_NUMERIC, SORT_DESC, $all_verbs_counts, SORT_NUMERIC,
					$all_score_counts, SORT_NUMERIC, SORT_DESC,
					$all_combos
				);
				// echo 'words: ' . $all_words_counts[0] . '; verbs: ' . $all_verbs_counts[0] . '; strays: ' . $all_stray_counts[0] . '' . ($target_tone_count ? '; toned:' . $all_toned_counts[0] : '') . '; score: ' . $all_score_counts[0] . '<br />';
				// echo '++++++++++++++++++++++++++++++++++++++++++++++++++++++++++<br />';
			}
		}

		$all_words_counts = null;
		$all_verbs_counts = null;
		$all_stray_counts = null;
		$all_toned_counts = null;


		// IF THERE'S NOTHING TO RETURN, RETURN FALSE
		if ($combo_count == 0) {
			return false;
		}


		if (!ThaiToRomanTransliterator::$USE_WORD_LIST || $word_level == self::$PARSE_ALL_WORDS) {
			// PARSE ALL AT TOP LEVEL
			if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
				echo '--- ROMANIZING --------------------------------------<br />';
				flush();
				ob_flush();
			}
			return self::reassembleCombos($all_combos[0], $str, $arg, $off, $len);
		} else {
			// IF NOT AT TOP LEVEL, RETURN FIRST COMBO AS ARRAY,
			// IT IS THE PARTS OF A WORD
			return $all_combos[0];
		}
	}





	/**
	 * Helper for transliterateWords()
	 * Adds a possible match for a possible verb rule, with a related sorting score to be used as a way of comparing
	 * with other possible matches.
	 */
	private static function add_segment_matches(&$str, &$arg, $offset, &$new_matches, &$matches, &$sorting, &$lengths) {
		if ($new_matches) {
			$count = count($new_matches['lengths']);
			for ($x = 0; $x < $count; $x++) {
				$length = $new_matches['lengths'][$x];
				for ($y = 0; $y < $length; $y++) {
					if (!isset($matches[$offset + $x])) {
						$matches[$offset + $x] = array();
						$sorting[$offset + $x] = array();
						$lengths[$offset + $x] = 0;
					}
					$matches[$offset + $x][$lengths[$offset + $x]] = $new_matches['matches'][$x][$y];
					$sorting[$offset + $x][$lengths[$offset + $x]] = $new_matches['sorting'][$x][$y];
					$lengths[$offset + $x]++;
				}
			}
		}
	}










	/**
	 * Reassembles the syllables of the specified $combo
	 * of words, syllables, and separators to target spellings.
	 *
	 * Recursively searches for the closest matching spelling combinations.
	 * When at top-level of recursion, it will return reassembled output in target spellings.
	 * Levels of recursion below top will return either words, separators, or syllables structures.
	 *
	 * @param array    $combo  Particular combination of syllables to reassemble.
	 * @param string   $str    Input string for separators, non-Thai characters and debugging.
	 * @param array    $arg    The array of Thai characters generated by function codify() in this file.
	 * @param integer  $off    The offset into the string and array
	 * @param integer  $len    The length to attempt to transliterate
	 * @param ThaiWord $top_level_word        When matching with the target word, try to return the same number of syllables as the target word
	 * @param array    $top_level_syllable    The syllable structure that we are reassemling
	 * @param integer  $level  The current level of recursion (zero is top).
	 *
	 * @return  recursion top-level returns a reassembled string, lower levels return syllables of reassembled combinations.
	 */
	private static function reassembleCombos($combo, $str, $arg, $off, $len, $top_level_word = null, $top_level_syllable = 0, $level = 0) {

		$output = '';

		$length = count($combo);
		$stroff = $off;
		$syllable_off = 0;
		$c = null;  // THE CURRENT LETTER ON THE STRING
		$b = null;  // STRING OF CHARACTERS FROM THE STRING YET TO BE PROCESSED TO OUTPUT (THOSE WITHOUT TONES WHICH ARE GROUPED WITH OTHER SYLLABLES WITHOUT A SEPARATOR)
		for ($i = 0; $i < $length; $i++) {
			$c = $combo[$i];
			if ($c && isset($c['length']) && $c['length']) {
				if (isset($c['word']) && $c['word']) {
					if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
						$output .= '{' . $c['word']->chars . ' => ';
					}
					$syllabs = $c['word']->getSyllables();
					if ($syllabs) {
						self::romanizeSeparator($output, $b, $level);
						$b = null;
						for ($j = 0; $j < count($syllabs); $j++) {
							$sy = self::transliterateSyllable($syllabs[$j]);
							$b2 = self::applyTone($sy['roman'], $sy['tone'], $sy['vowel']['long']);
							if ($j > 0) {
								$output .= self::$SYLLABLE_SEPARATOR;
							}
							$output .= $b2;
						}
					} else {
						self::romanizeSeparator($output, $b, $level);
						$output .= self::reassembleCombos(
							$c['word']->parts, $str, $arg,
							$stroff, $c['length'],
							($top_level_word ? $top_level_word : $c),
							($top_level_word ? $top_level_syllable + $syllable_off : $syllable_off),
							$level + 1
						);
					}
					if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
						$output .= ' => ' . $c['word']->english . '}';
					}
				} else if ($c['roman']) {
					$tone = $c['tone'];
					if ($tone) {
						if ($top_level_word && $top_level_word['tone_count'] > ($top_level_syllable + $syllable_off)) {
							$over_tone = $top_level_word['word']->tones[($top_level_syllable + $syllable_off)];
							if ($over_tone) {
								$tone = $over_tone;
							}
							//echo '[' . $top_level_word['word']->chars . ':' . ($top_level_syllable + $syllable_off) . '=>' . $tone . ']';
						}
						if ($b) {
							$b2 = self::applyTone($b . $c['roman'], $tone, $c['vowel']['long']);
							$b = null;
							self::romanizeSeparator($output, $b, $level);
							$output .= $b2;
						} else {
							$b2 = self::applyTone($c['roman'], $tone, $c['vowel']['long']);
							self::romanizeSeparator($output, $b, $level);
							$output .= $b2;
						}
						if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
							$output .=
								''
						//      . '('
						//      . $c['vowel']['name'] . ' '
							  . '['
							  . self::arrayToString($c['pattern'])
							  . ' => ' . mb_substr($str, $stroff, $c['length'])
							  . ']'
						//      . ($c['vowel']['short'] ? '/short' : ($c['vowel']['long'] ? '/long' : ''))
						//      . ')'
							;
						}
					} else {
						// NO TONE SO WE WANT TO CLUSTER IT WITH A SUBSEQUENT SYLLABLE, STORE IN B (BEFORE)
						if ($b) {
							$b .= $c['roman'];
						} else {
							$b = $c['roman'];
						}
					}
				}
				$stroff += $c['length'];
				$syllable_off += $c['tone_count'];
			} else {
				//echo '<pre>' . var_export($c, true) . '</pre>';
				if ($c && is_object($c) && $c->char) {
					self::romanizeSeparator($output, $b, $level);
					$output .= self::transliterateSingle($c, true);
				} else if ($stroff < $off + $len) {
					// THIS IS SOME SORT OF FREAKY ERROR... TRANSLITERATE SHOULD NOT BE CALLED ON NON-THAI STRINGS
					$c = mb_substr($str, $stroff, 1);
					$co = '[' . ord($c) . '] ' . self::transliterateSingle($c, true);
					if ($co) {
						self::romanizeSeparator($output, $b, $level);
						$output .= $co;
					}
				}
				$stroff++;
			}
		}

		return $output;
	}



	/**
	 * Just returns a word separator if below top level, or a word separator if at top level.
	 */
	private static function romanizeSeparator(&$output, &$b, $level) {
		if ($b) {
			if ($output) {
				if ($level >= 1) $output .= self::$SYLLABLE_SEPARATOR;
				else $output .= self::$WORD_SEPARATOR;
			}
			$output .= $b;
			$b = null;
		}
		if ($output) {
			if ($level >= 1) $output .= self::$SYLLABLE_SEPARATOR;
			else $output .= self::$WORD_SEPARATOR;
		}
	}



















	/**
	 * We can place some more special patterns here if not using a word list.
	 */
	private static $JING_PATTERN = array(3592, 3619, 3636, 3591);
	private static $SARA_I_SHORT = null;
	private static $SARA_UA_LONG = null;
	private static $SILENT_PATTERN = array(0, 3660);
	private static $SILENT_PINTHU_PATTERN = array(0, 3642);




	/**
	 * Transliterates a given segment of thai characters.
	 *
	 * Returns all syllable matches found, for each character offset in 
	 * the substring, with it's respective sorting and length for caller to 
	 * use to determine the best combination.
	 *
	 * @param string  $original_input  The string we are transliterating.
	 * @param array   $a               The array of ThaiSymbols/char codes we are transliterating.
	 * @param integer $off             The offset into above two that we are working from.
	 * @param integer $len             The length of current window
	 * @param bool    $can_replicate   If search can check both wordlist and search for syllable
	 *
	 * @return    All combinations
	 */
	public static function transliterateSegment($original_input, $a, $off, $len, $can_replicate = false) {

		if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
			echo '<br />' . $len . '(' . mb_substr($original_input, $off, $len) . ')<br />';
		}

		$matches = array();
		$sorting = array();
		$lengths = array();
		$counter = array();
		$changed = array();

		$num_verb_rules = count(self::$VERB_RULES);

		//
		// LOOP AND BUILD A LIST OF $matches AT EACH OFFSET ($off + $i)
		//
		for ($i = 0; $i < $len; $i++) {

			$matches[$i] = array();
			$sorting[$i] = array();
			$counter[$i] = 0;
			$lengths[$i] = 0;

			//
			// GO THROUGH ALL THE VERB RULES
			//
			for ($j = 0; $j < $num_verb_rules; $j++) {

				if (!self::$VERB_RULES[$j]['active']) {
					continue;
				}

				$patterns = self::$VERB_RULES[$j]['patterns'];
				$num_patterns = count($patterns);
				for ($k = 0; $k < $num_patterns; $k++) {
					self::segmatch_callback($original_input, $a, $off, $len, $i, $matches, $sorting, $lengths, self::$VERB_RULES[$j], $patterns[$k], false, false, true, $can_replicate);
				}

				// SCAN AHEAD 4 CHARACTERS FOR CONSONANT CLUSTERS
				// MOST CONSONANT CLUSTERS ALWAYS OCCUR AS INITIALS, SO THEY CAN'T BE FAR AHEAD
				for ($k = $i + $off; $k < $i + $off + 4; $k++) {
					if ($k <= $off + $len - 3
						&& $a[$k] && $a[$k + 1] && $a[$k + 2]
						&& isset($a[$k]) && isset($a[$k + 1]) && isset($a[$k + 2])
						&& $a[$k] instanceof ThaiConsonant && $a[$k + 1] instanceof ThaiModifier && $a[$k + 2] instanceof ThaiConsonant
						&& $a[$k + 1]->code == ThaiAlphabet::$PHINTHU->code
						&& isset(self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 2]->code])
						&& isset(self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 2]->code][$a[$k]->code])
					) {
						$cluster_parts = self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 2]->code][$a[$k]->code];
						$cluster_part_len = count($cluster_parts);
						for ($m = 0; $m < $cluster_part_len; $m++) {
							self::segmatch_callback($original_input, $a, $off, $len, $i, $matches, $sorting, $lengths, self::$VERB_RULES[$j], $cluster_parts[$m], true, false, true, $can_replicate);
						}
						$cluster_parts = null;
					} else if ($k <= $off + $len - 2
						&& isset($a[$k]) && isset($a[$k + 1])
						&& $a[$k] instanceof ThaiConsonant && $a[$k + 1] instanceof ThaiConsonant
						&& isset(self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 1]->code])
						&& isset(self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 1]->code][$a[$k]->code])
					) {
						$cluster_parts = self::$VERB_RULES[$j]['cluster_patterns'][$a[$k + 1]->code][$a[$k]->code];
						$cluster_part_len = count($cluster_parts);
						for ($m = 0; $m < $cluster_part_len; $m++) {
							self::segmatch_callback($original_input, $a, $off, $len, $i, $matches, $sorting, $lengths, self::$VERB_RULES[$j], $cluster_parts[$m], true, false, true, $can_replicate);
						}
						$cluster_parts = null;
					}

				}
			}

			//
			// SCAN FOR SPECIAL CASE "JING"
			//
			if ($a[$off+$i]->code == 3592 && $i <= $off + $len - 4 && $a[$off+$i+1]->code == 3619 && $a[$off+$i+2]->code == 3636 && $a[$off+$i+3]->code == 3591) {
				self::segmatch_callback($original_input, $a, $off, $len, $i, $matches, $sorting, $lengths, self::$SARA_I_SHORT, self::$JING_PATTERN, true, true, false, $can_replicate);
			}
		}


		for ($i = 0; $i < $len; $i++) {
			if ($lengths[$i]) {
				array_multisort($sorting[$i], SORT_NUMERIC, SORT_DESC, $matches[$i]);
			}
		}


		//
		// DO NOT NEED TO COMBINATE SYLLABLES HERE
		// NEED TO DIRECTLY RETURN
		//
		return array(
			'matches' => $matches,
			'sorting' => $sorting,
			'lengths' => $lengths
		);

	}




	/**
	 * Finds the tone number of a specified Thai syllable.  Thai has 5 tones:
	 *  1) common tone
	 *  2) low tone
	 *  3) falling tone
	 *  4) high tone
	 *  5) rising tone
	 *
	 * Left some old code commented out below, might want to check it some day.
	 *
	 * @param ThaiConsonant $init the first consonant of the Thai syllable.
	 * @param ThaiConsonant $final the optional last consonant of the Thai syllable.
	 * @param integer $tone the default tone to return if no other matches found.
	 * @param boolean $is_long whether the syllable is long or not (short = false)
	 * @param boolean $vowel the optional vowel matched with the syllable spelling.
	 *
	 * @return the tone number of the syllable.
	 */
	private static function getToneNumber($init, $final = null, $tone = null, $is_long = false, $vowel = null) {
		//echo "getToneNumber($init->, $final, $tone, $is_long, " . var_export($vowel, true) . ")<br />";
		if ($tone) {
			$tone = $tone->code;
		} else {
			$tone = 0;
		}
		$is_dead_final = $final && $final->is_dead_final;
		$is_short_open = !$final && !$is_long;
		if ($init->tone_class > 0) {
			switch ($tone) {
			  case 0:
				if (!($is_dead_final || $is_short_open)) {
					// a. Syllables beginning with a high class consonant and have no tone mark take the Rising Tone 
					//    unless they end with K, P or T sounds or a short open vowel.
					return 5; // rising tone
				} else {
					// b. Syllables beginning with a high class consonant and having no tone mark take the Low Tone
					//    if they end in K, P or T sound or with a short open vowel.
					return 2; // low tone
				}
				break;
			  case ThaiAlphabet::$MAI_EK->code:
				// c. Syllables beginning with a high class consonant and having the 
				//    tone mark MYAYK over the consonant take the Low Tone irrespective of ending.
				return 2;   // low tone
			  case ThaiAlphabet::$MAI_THO->code:
				// d. Syllables beginning with a high class consonant and having the
				//    tone mark MYTO over the consonant take the Dropped Tone irrespective of ending.
				return 3;  // dropped/falling tone
			  case ThaiAlphabet::$MAI_TRI->code:   // wikipedia right
				return 4;  // high tone
			  case ThaiAlphabet::$MAI_CHATTAWA->code:  // wikipedia right
				return 5;  // rising tone
			}
		} else if ($init->tone_class < 0) {
			switch ($tone) {
			  case 0:
				if (!($is_dead_final || $is_short_open)) {
					// a. Syllables beginning with a low class consonant and having no tone mark take the Common Tone
					//    unless they end with K, P or T sounds or in a short vowel.
					return 1;  // common tone
				} else if ($is_dead_final) {
					// b. Syllables beginning with a low class consonant and having no tone mark but ending in K, P or T sounds, take:
					if ($is_long) {
						// i. the Dropped Tone if the vowel is long.
						return 3;  // dropped/falling tone
					} else {
						// ii. the High Tone if the vowel is short.
						return 4;  // high tone
					}
/////////////////////////
  //              } else if ($vowel['name'] == 'sara e + wo waen' && $init && $init->tone_class < 0) {
	//                return 1;



				} else if ($is_short_open) {
					// c. Syllables beginning with a low class consonant and having a short open vowel take the High Tone.
					//    There are very few complete words of this type.
					return 4;  // high tone
				}
				break;
			  case ThaiAlphabet::$MAI_EK->code:
				// d. Syllables beginning with a low class consonant and having the 
				//    tone mark MYAYK over the initial consonant take the Dropped Tone.
// if ($vowel['name'] == 'sara ua + yo yak') return 3;

				if (!$is_dead_final || !$is_short_open) { // ADDED TO FIX ERROR HERE
					return 3;
				} else {
					return 4;
				}
			  case ThaiAlphabet::$MAI_THO->code:
				// e. Syllables beginning with a low class consonant and having the 
				//    tone mark MYTO (-?) over the initial consonant take the High Tone.
			  case ThaiAlphabet::$MAI_TRI->code:  // wikipedia corrected
				return 4;  // high tone
			  case ThaiAlphabet::$MAI_CHATTAWA->code:   // wikipedia corrected
				return 5;  // rising tone
			}
		} else {
			switch ($tone) {
			  case 0:
				if (!($is_dead_final || $is_short_open)) {
					// a. Syllables beginning with a middle class consonant and having no tone mark take the Common Tone
					//    unless they end with K, P or T sounds or in a short vowel.
					return 1;
				} else if ($is_dead_final) {
					// b. Syllables beginning with a middle class consonant and having no tone mark take the
					//    Low Tone if they end in K, P or T sounds.
					return 2; // low tone
				} else if ($is_short_open) {
					// c. Syllables beginning with a middle class consonant and ending with a short open vowel take the 
					//    Low Tone.  There are not many words of this type.
					return 2; // low tone
				}
			  case ThaiAlphabet::$MAI_EK->code:
				// d. Syllables beginning with a middle class consonant and having the 
				//    tone mark MYAYK over the initial consonant take the Low Tone.
				return 2;   // low tone
			  case ThaiAlphabet::$MAI_THO->code:
				// e. Syllables beginning with a middle class consonant and having the
				//    tone mark MYTO over the initial consonant take the Dropped Tone.
				return 3;  // dropped/falling tone
			  case ThaiAlphabet::$MAI_TRI->code:
				// f. Syllables beginning with a middle class consonant and having the tone mark MYDTREE
				//    over the initial consonant take the High Tone. There are very few of these words.
				return 4;  // high tone
			  case ThaiAlphabet::$MAI_CHATTAWA->code:
				// g. Syllables beginning with a middle class consonant and having the tone mark MYJUDTAWAH
				//    over the initial consonant take the Rising Tone. There are very few of these words.
				return 5;  // rising tone
			}
		}
		// ERROR???
		return 0;
	}




	/**
	 * Same as getToneNumber(), but using a different decision tree.
	 * Used to detect errors / validate input.
	 *
	 * Details can be found at (though the source chart is slightly incorrect):
	 *    https://en.wikipedia.org/wiki/Thai_script#/media/File:Thai-tones-flowchart.svg
	 *
	 * Finds the tone number of a specified Thai syllable.  Thai has 5 tones:
	 *  1) common tone
	 *  2) low tone
	 *  3) falling tone
	 *  4) high tone
	 *  5) rising tone
	 *
	 * @param ThaiConsonant $init the first consonant of the Thai syllable.
	 * @param ThaiConsonant $final the optional last consonant of the Thai syllable.
	 * @param boolean $is_long whether the syllable is long or not (short = false)
	 * @param boolean $vowel the optional vowel matched with the syllable spelling.
	 *
	 * @return the tone number of the syllable.
	 */
	private static function getToneNumberFromParams($tone_char, $init_cons, $final_cons, $is_long, $vowel = null) {
		$is_dead_final = $final_cons && $final_cons->is_dead_final;
		$is_short_open = (!$final_cons && !$is_long);

		if ($tone_char) {
			if ($tone_char->code == ThaiAlphabet::$MAI_EK->code) {
				// DIFFERENT THAN WIKIPEDIA
				if ($init_cons->tone_class >= 0) {
					// LOW TONE = 2
					return 2;
				} else if (!$is_dead_final || !$is_short_open) { // $is_short_open
					return 3;
				} else {
					return 4;
				}
				//return self::getSubTreeToneFromParams($tone_char, $init_cons, $final_cons, $is_long);
			} else if ($tone_char->code == ThaiAlphabet::$MAI_THO->code) {
				if ($init_cons && $init_cons->tone_class >= 0) {
					// FALLING TONE = 3
					return 3;
				} else {
					// HIGH TONE = 4
					return 4;
				}
			} else if ($tone_char->code == ThaiAlphabet::$MAI_TRI->code) {
				// HIGH TONE = 4
				return 4;
			} else if ($tone_char->code == ThaiAlphabet::$MAI_CHATTAWA->code) {
				// RISING TONE = 5
				return 5;
			} else {
				// UNKNOWN
				return 0;
			}
		} else if ($final_cons) {
			$sonorant = !isset(ThaiAlphabet::$PLOSIVE_FINALS[$final_cons->code]) || !ThaiAlphabet::$PLOSIVE_FINALS[$final_cons->code];
			if ($sonorant) {
				return self::getSubTreeToneLeft($init_cons);
			} else {
				return self::getSubTreeToneFromParams($tone_char, $init_cons, $final_cons, $is_long);
			}
		} else {
			if ($is_long) {
				return self::getSubTreeToneLeft($init_cons);
			} else {
				return self::getSubTreeToneFromParams($tone_char, $init_cons, $final_cons, $is_long);
			}
		}
	}


	/**
	 * Factored out helper function for above getToneNumberFromParams().
	 *
	 * @param ThaiConsonant $init_cons the first consonant of the Thai syllable.
	 *
	 * @return the tone number for the initial consonant given context.
	 */
	private static function getSubTreeToneLeft($init_cons) {
		if ($init_cons && $init_cons->tone_class <= 0) {
			// MIDDLE TONE = 1
			return 1;
		} else {
			// RISING TONE = 5
			return 5;
		}
	}

	/**
	 * Factored out helper function for above getToneNumberFromParams().
	 *
	 * @param ThaiToneMark $tone_char one of the four tone characters in the Thai alphabet.
	 *
	 * @return the tone number for the given context.
	 */
	private static function getSubTreeToneFromParams($tone_char, $init_cons, $final_cons, $is_long) {
		if ($init_cons->tone_class >= 0) {
			// LOW TONE = 2
			return 2;
		} else {
			//    return 3;
			if ($is_long) {
				// FALLING TONE = 3
				return 3;
			} else {
				// HIGH TONE = 4
				return 4;
			}
		}
	}














	/**
	 * Not yet implemented.  Placeholder for special tones.
	 * But the algorithms work for all tested spellings so far.
	 */
	private static $SPECIAL_TONES = array(
		1 => array(),
		2 => array(),
		3 => array(),
		4 => array(),
		5 => array(),
	);


	/**
	 * This is a backup for the other two tone algorithms.  But so far, has not been needed.
	 * Returns the tone number that the word/syllable should take, or zero if unknown,
	 * or false on empty input.
	 */
	private static function getOverrideTone($s) {

		$len = mb_strlen($s);
		if ($len == 0) {
			return false;
		}

		$o = static function($s, $i) { return mb_ord(mb_substr($s, $i, 1)); };
		$c0 = $o($s, 0);

		for ($t = 1; $t <= 5; $t++) {
			$num = count(self::$SPECIAL_TONES[$t]);
			$got_first = false;
			for ($i = 0; $i < $num; $i++) {
				if ($c0 == self::$SPECIAL_TONES[$t][$i][0]) {
					if (count(self::$SPECIAL_TONES[$t][$i]) == $len) {
						$all_match = true;
						for ($j = 1; $j < $len; $j++) {
							if ($o($s, $j) != self::$SPECIAL_TONES[$t][$i][$j]) {
								$all_match = false;
								break;
							}
						}
						if ($all_match) {
							return $t;
						}
					}
					$got_first = true;
				} else if ($got_first) {
					break; // assume they are in order...
				}
			}
		}
		return 0;

	}








	/**
	 * Match handler method for loop in transliterateSegment().
	 *
	 * Adds the match to the $matches array, sets the $sorting value, and increments, the $lengths array.
	 * This saves the candidate matches and allows for selection of best match by heuristic selection.
	 *
	 * @param string  $original_input  The string we are transliterating.
	 * @param array   $a               The array of ThaiSymbols/char codes we are transliterating.
	 * @param integer $off             The offset into above two that we are working from.
	 * @param integer $len             The length of current window
	 * @param integer $i               The offset of segment to check
	 * @param array   $matches         The array to put matching "atomic" syllables into
	 * @param array   $sorting         The array to put sorting scores into
	 * @param array   $lengths         The array to put the length of match into
	 * @param array   $vowel           The matching vowel
	 * @param array   $pattern         The matching vowel pattern
	 * @param bool    $is_cluster      Whether the match starts with a cluster
	 * @param bool    $silent_second   Whether second consonant is silent or not
	 * @param bool    $do_reduce_honam Whether to reduce hoham for vowel or not
	 * @param bool    $can_replicate   If search can check both wordlist and search for syllable
	 *
	 * @return void - the needed values are put into the input arrays.
	 */
	private static function segmatch_callback (
		&$original_input, &$a, &$off, &$len, &$i, &$matches, &$sorting, &$lengths,
		&$vowel, &$pattern, $is_cluster, $silent_second, $do_reduce_honam = true, $can_replicate = false
	) {

		$got_reduced_hoham = false;
		$match_atoms = self::matchVerbPattern($original_input, $a, $off + $i, $len - $i, $vowel, $pattern, $is_cluster, $silent_second, $do_reduce_honam, $can_replicate);
		if ($match_atoms) {
			$num_atoms = count($match_atoms);
			for ($z = 0; $z < $num_atoms; $z++) {
				$atom = $match_atoms[$z];
				$got = false;
				for ($j = 0; $j < $lengths[$i]; $j++) {
					if (self::matchesEqual($atom, $matches[$i][$j])) {
						$got = true;
						break;
					}
				}
				if ($got) {
					continue;
				}
				if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
					echo 'match[' . $i . '][' . $lengths[$i] . ']: ';
					echo self::arrayToString($atom['pattern']) . ' => ' . mb_substr($original_input, $i + $off, $atom['length']);
					echo ' => score=' . $atom['syllable_sort_score'];
					echo '<br />';
				}
				$matches[$i][$lengths[$i]] = $atom;
				$sorting[$i][$lengths[$i]] = $atom['syllable_sort_score'];
				$lengths[$i]++;

				// WE MAY NEED TO DO ANOTHER LOOKUP WITHOUT HOHAM REDUCTION
				if ($atom['reduced_hoham']) {
					$got_reduced_hoham = true;
				}
				$atom = null;
				$match_atoms[$z] = null;
			}
		}
		$match_atoms = null;

		if ($do_reduce_honam && !$got_reduced_hoham) {
			self::segmatch_callback (
				$original_input, $a, $off, $len, $i, $matches, $sorting, $lengths,
				$vowel, $pattern, $is_cluster, $silent_second, false, $can_replicate
			);
		}

	}










	/**
	 *
	 * Checks if a verb pattern matches specified input at position off
	 *
	 * Returns a list of matches if it does.
	 *
	 * @param string  $str             The string we are transliterating
	 * @param array   $arg             The array of ThaiSymbols/char codes we are transliterating
	 * @param integer $off             The offset into above two that we are working from
	 * @param integer $len             The length of current window
	 * @param array   $ver             The verb pattern to match, with name and 
	 * @param array   $pat             The pattern as an array, zero's being placeholders for any
	 * @param bool    $is_cluster      Whether the pattern starts with a cluster
	 * @param bool    $silent_second   If the second letter is silent or not
	 * @param bool    $can_replicate   If search can check both wordlist and search for syllable
	 *
	 * @return  A list of matches, if any
	 */
	private static function matchVerbPattern($str, $arg, $off, $len, $vow, $pat, $is_cluster = false, $silent_second = false, $can_reduce_hoham = true, $can_replicate = false, $apply_vowel_to_next = false) {

		//if ($vow['name'] == 'sara ai') {
		//    echo 'checking ' . self::arrayToString($pat) . '<br />';
		//}

		//if ($is_cluster) {
		//	echo 'checking cluster: ' . self::arrayToString($pat) . '<br />';
		//}

		if ($can_replicate && !ThaiToRomanTransliterator::$USE_WORD_LIST) {
			$can_replicate = false;
		}


		$patlen = count($pat);
		if ($patlen > $len) {
			// IMPOSSIBLE
			return false;
		}


		// RETURN EMPTY PATTERN FOR SILENT CHARACTERS AT BEGINNING
		if ($len > 1 && is_object($arg[$off + 1])) {
			if ($arg[$off + 1]->code == ThaiAlphabet::$THANTHAKHAT->code) {
				$match = array(
					'length' => 2,
					'spelling_length' => 2,
					'chars' => mb_substr($str, 0, 2),
					'roman' => null,
					'tone' => 0,
					'vowel' => null,
					'pattern' => self::$SILENT_PATTERN,
					'reduced_hoham' => false,
					'is_cluster' => false,
					'consonant_count' => 1,
				);
				$match['tone_count'] = self::getToneCount($match);
				$match['syllable_sort_score'] = self::getSyllableScore($match);
				return array($match);
			}
		}


		$var_consonant_count = 0;
		for ($i = 0; $i < $patlen; $i++) {
			if ($pat[$i] == 0) $var_consonant_count++;
		}

		$init_consonant = null;
		$consonants = array();
		$consonant_vowels = array();
		$all_matches = array();
		$consonant_silenced = array();
		$reduced_hoham = false;
		$final_consonant = null;
		$vowelized_final = null;
		$final_position = false;

		$has_maitaikhu = 0;
		$pat_has_maitaikhu = 0;


		$tone_char = null;



		$pat_i = 0;
		$arg_i = 0;

		while ($pat_i < $patlen && $arg_i < $len) {

			if ($pat_i >= 0 && $pat[$pat_i] == 0) {
				// ANY THAI CONSONANT

				if (is_object($arg[$off + $arg_i])) {
					$o = $arg[$off + $arg_i];

					// REDECE HO NAM
					if ($can_reduce_hoham && !$init_consonant && $arg_i < $len - 1 && $o->code == ThaiAlphabet::$HO_HIP->code && is_object($arg[$off + $arg_i + 1])) {
						$c1 = $o;
						$c2 = $arg[$off + $arg_i + 1];
						if (self::$HO_NAM[$c2->code]) {
							$init_consonant = $o;
							array_push($all_matches, $o);
							$arg_i++;
							$reduced_hoham = true; // set it to true anyway!
							continue;
						} else if ($c2->code == ThaiAlphabet::$PHINTHU->code && $arg_i < $len - 2 && is_object($arg[$off + $arg_i + 2])) {
							$c3 = $arg[$off + $arg_i + 2];
							if (self::$HO_NAM[$c3->code]) {
								$init_consonant = $o;
								array_push($all_matches, $o);
								$arg_i += 2;
								$reduced_hoham = true;
								continue;
							}
						}
					}
						//if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
						//    $pat_has_maitaikhu++;
						//}

					//
					// REDUCE O NAM - 4 WORDS ONLY:
					//   [3629,3618,3634,3585],
					//   [3629,3618,3641,3656],
					//   [3629,3618,3656,3634],
					//   [3629,3618,3656,3634,3591]
					//
					if (!$init_consonant && $o->code == ThaiAlphabet::$O_ANG->code
					 && $arg_i < $len - 3 && is_object($arg[$off + $arg_i + 1]) && $arg[$off + $arg_i + 1]->code == ThaiAlphabet::$YO_YAK->code
					 && $vow['long'] && ($vow['name'] == 'sara a' || $vow['name'] == 'sara u')
					) {
						if (is_object($arg[$off + $arg_i + 2]) && is_object($arg[$off + $arg_i + 3])) {
							if (
								($arg[$off + $arg_i + 2]->code == 3634 && $arg[$off + $arg_i + 3]->code == 3585)
							 || ($arg[$off + $arg_i + 2]->code == 3641 && $arg[$off + $arg_i + 3]->code == 3656)
							 || ($arg[$off + $arg_i + 2]->code == 3656 && $arg[$off + $arg_i + 3]->code == 3634)
							 || (is_object($arg[$off + $arg_i + 4]) && ($arg[$off + $arg_i + 2]->code == 3656 && $arg[$off + $arg_i + 3]->code == 3634 && $arg[$off + $arg_i + 4]->code == 3691))
							) {
								$init_consonant = $o;
								array_push($all_matches, $o);
								$arg_i++;
								continue;
							}
						}
					}



					if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
						// SHORTENS VOWEL
						$has_maitaikhu++;
						$arg_i++;
					} else if ($o->is_consonant) {
						// MATCH
						// echo '[' . $o->char . ']';
						array_push($all_matches, $o);
						if (!$init_consonant) {
							$init_consonant = $o;
						}

						if ($apply_vowel_to_next) {
							if (!isset($consonant_vowels[count($consonants)]) || !$consonant_vowels[count($consonants)]) {
								$consonant_vowels[count($consonants)] = array(0=>$apply_vowel_to_next);
							} else {
								array_push($consonant_vowels[count($consonants)], $apply_vowel_to_next);
							}
							$apply_vowel_to_next = null;
						}
						array_push($consonants, $o);

						$pat_i++;
						$arg_i++;

					} else if ($o->is_tone_mark) {
						if (!$init_consonant) return false;
						$tone_char = $o;
						$arg_i++;
					} else if ($o->is_modifier) {
						if ($o->code == ThaiAlphabet::$THANTHAKHAT->code) {
							array_pop($consonants);
							array_pop($all_matches);
							$pat_i--;
						} else if ($o->code == ThaiAlphabet::$PHINTHU->code) {
							$consonant_silenced[count($consonants)-1] = true; 
							return false;
						}
						$arg_i++;
					} else if ($o->is_vowel || $o->is_numeric || $o->code == ThaiAlphabet::$BAHT->code) {
						// DOES NOT MATCH
						return false;
					} else {
						// FAILURE
						return false;
					}




				} else {
					// FAILURE
					return false;
				}
			} else {
				if (is_object($arg[$off + $arg_i])) {
					$o = $arg[$off + $arg_i];

					if ($pat_i >= 0 && $o->code == $pat[$pat_i]) {
						// GOT MATCHING
						// echo '(' . $o->char . ')';
						array_push($all_matches, $o);
						if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
							// SHORTENS VOWEL
							$pat_has_maitaikhu++;
						}
						if ($o->is_vowel) {
							if ($o->is_always_first) {
								$apply_vowel_to_next = $o;
							} else {
								if (!isset($consonant_vowels[count($consonants)-1]) || !$consonant_vowels[count($consonants)-1]) {
									$consonant_vowels[count($consonants)-1] = array($o); 
								} else {
									array_push($consonant_vowels[count($consonants)-1], $o);
								}
							}
						} else if ($o->is_consonant) {
							array_push($consonants, $o);
							if (!$init_consonant) {
								$init_consonant = $o;

								// REDUCE EXPLICIT INIT CONSONANT CLUSTERS SEPARATED BY PHINTHU
								if ($arg_i < $len - 2 && is_object($arg[$off + $arg_i + 1]) && is_object($arg[$off + $arg_i + 2])
								 && $arg[$off + $arg_i + 1]->code == ThaiAlphabet::$PHINTHU->code
								 && $pat[$pat_i + 1] == $arg[$off + $arg_i + 2]->code
								) {
									array_push($all_matches, $arg[$off + $arg_i + 1]);
									array_push($all_matches, $arg[$off + $arg_i + 2]);
									array_push($consonants, $arg[$off + $arg_i + 2]);
									$arg_i += 2;
									$pat_i++;
								} else {
								}
							}
						} else if ($o->is_tone_mark) {
							if (!$init_consonant) return false;
							$tone_char = $o;
						} else if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
							// SHORTENS VOWEL
							$has_maitaikhu++;
						} else if ($o->is_modifier) {
							if ($o->code == ThaiAlphabet::$THANTHAKHAT->code) {
								if (!$init_consonant) return false;
								array_pop($consonants);
								array_pop($all_matches);
								$pat_i--;
							} else if ($o->code == ThaiAlphabet::$PHINTHU->code) {
								//$consonant_silenced[count($consonants)-1] = true; 
								return false;
							}
						}

						$arg_i++;
						$pat_i++;
					} else if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
						// SHORTENS VOWEL
						$has_maitaikhu++;
						$arg_i++;
					} else if ($o->is_vowel || $o->is_consonant || $o->is_numeric || $o->code == ThaiAlphabet::$BAHT->code) {
						// DOES NOT MATCH
						return false;
					} else {
						// IGNORE, TONE MARK OR SOMETHING
						if ($o->is_tone_mark) {
							if (!$init_consonant) return false;
							$tone_char = $o;
						} else if ($o->is_modifier) {
							if ($o->code == ThaiAlphabet::$THANTHAKHAT->code) {
								if (!$init_consonant) return false;
								array_pop($consonants);
								array_pop($all_matches);
								$pat_i--;
							} else if ($o->code == ThaiAlphabet::$PHINTHU->code) {
								//$consonant_silenced[count($consonants)-1] = true;
								//echo 'HELLO!!!<br />';
								//echo $consonants[count($consonants)-1]->char . '<br />';
								return false;
							}
						}
						$arg_i++;
					}
				} else {
					// NOT A MATCHING THAI VAL ON INPUT
					return false;
				}
			}

			//
			// CHECK AS IF IT'S THE END INSIDE THE LOOP
			// IF IT IS SILENT, MORE CONSONANTS WILL BE NEEDED
			//
			if ($pat_i == $patlen) {
				$all_match_count = count($all_matches);
				$consonant_count = count($consonants);
				if ($consonant_count > 1 && $all_match_count > 1 && $all_matches[$all_match_count - 1]->is_consonant) {
					// WHEN WO WAEN ENDS A SYLLABLE, IT'S ALWAYS PART OF THE VOWEL
					// WHEN YO YAK ENDS A SYLLABLE, IT'S USUALLY PART OF THE VOWEL
					if (ThaiAlphabet::$WO_WAEN->code != $consonants[$consonant_count - 1]->code
						&& ThaiAlphabet::$YO_YAK->code != $consonants[$consonant_count - 1]->code
						&& ThaiAlphabet::$O_ANG->code != $consonants[$consonant_count - 1]->code
					) {
						$final_consonant = $consonants[$consonant_count - 1];
						$final_position = $arg_i - 1;
						while ($final_position > 0) {
							if ($arg[$off + $final_position]->code == $final_consonant->code) {
								break;
							}
							$final_position--;
						}
						if ($pat[$patlen - 1] == $final_consonant->code) {
							if ($consonant_count == 2 && $pat[$patlen - 2] == $consonants[0]->code) {
								$final_consonant = null;
							}
						} else if (!$final_consonant->use_tail) {
							return false;
						}
					} else {
						$vowelized_final = $consonants[$consonant_count - 1];
						$final_position = $arg_i - 1;
						while ($final_position > 0) {
							if ($arg[$off + $final_position]->code == $vowelized_final->code) {
								break;
							}
							$final_position--;
						}
					}
				}

				// ABSORB EXTRA MODIFIERS
				while ($arg_i < $len) {
					if (is_object($arg[$off + $arg_i])) {
						$o = $arg[$off + $arg_i];
						if ($o->is_tone_mark) {
							if ($final_consonant && $pat[$patlen - 1] != $final_consonant->code) {
								return false;  // IMPOSSIBLE. FINAL CONSONANT HAS NO TONE UNLESS ON A CLUSTER
							}
							$tone_char = $o;
							$arg_i++;
						} else if ($o->code == ThaiAlphabet::$MAITAIKHU->code) {
							// SHORTENS VOWEL
							$has_maitaikhu++;
							$arg_i++;
						} else if ($o->is_modifier) {
							if ($o->code == ThaiAlphabet::$THANTHAKHAT->code) {
								//$consonant_silenced[count($consonants)-1] = true; 
								array_pop($consonants);
								array_pop($all_matches);
								$pat_i--;
							} else if ($o->code == ThaiAlphabet::$PHINTHU->code) {
								if ($consonant_silenced[$consonant_count - 1]) {
									if ($vowelized_final || $final_consonant) {
										// Final silenced with PHINTHU
										array_pop($consonant_silenced);
										array_pop($consonants);
										array_pop($all_matches);
										$vowelized_final = null;
										$final_consonant = null;
										$final_position = false;
									}
								}
							}
							$arg_i++;
						} else {
							break;
						}
					} else {
						break;
					}
				}
			}


		}


		if ($pat_i < $patlen) {
			return false;  // sheeeit
		}
		if (!$init_consonant) {
			return false;
		}
		if ($has_maitaikhu + $pat_has_maitaikhu > 1) {
			return false;
		}




		//
		// GOT VOWEL
		// COMPUTE LENGTH AND TONE
		//
		$is_dead_final = $final_consonant && $final_consonant->is_dead_final;
		$is_short_open = (!$final_consonant && !$vow['long']);


		$tone_number = 0;


		//
		// CHECK FOR SPECIAL CASES SHORT OR LONG
		//
		$chars = mb_substr($str, $off, $arg_i);
		$override_length = self::getSpecialVowelLength($chars);
		$is_long_for_tone = $vow['long'];
		$final_for_tone = $final_consonant;


		if ($vow['long'] && $has_maitaikhu) {// && $override_length === false) {
			$vow = self::getShortVowelRule($vow);
			//$is_long_for_tone = true;
			//$override_length = -1;
		}

		if ($override_length !== false) {
			if ($override_length < 0 && $vow['long']) {
				$vow = self::getShortVowelRule($vow);
				$is_long_for_tone = false;
			} else if ($override_length > 0 && $vow['short']) {
				$vow = self::getLongVowelRule($vow);
				$is_long_for_tone = true;
			}

		} else if ( // WHEN ANY TONE MARK APPEARS WITH SARA E,
			($vow['name'] == 'sara e') // || $vow['name'] == 'sara oe')
			&& $vow['long'] && $tone_char && $patlen > 2
		) {
			// THE SYLLABLE HAS A SHORT VOWEL SOUND
			$vow = self::getShortVowelRule($vow);
			$is_long_for_tone = false;

		} else if ($vow['name'] == 'sara e + wo waen' && (!$has_maitaikhu && $pat_has_maitaikhu)) {// $init_consonant && $init_consonant->tone_class < 0) {
			$tone_number = 1;
		} else if ($vow['name'] == 'sara ae + wo waen' && $vow['short'] && $init_consonant && $init_consonant->tone_class >= 0) {
			$tone_number = 1;

		} else if ( // TONE MARK APPEARS IN A LIVE SYLLABLE WITH SARA AE AND A MID- OR HIGH-CLASS INITIAL,
			($vow['name'] == 'sara ae' || $vow['name'] == 'sara ae + wo waen')              
		 // && $patlen > 2//($is_cluster ? 3 : 2)
		 && $vow['long'] && !($final_consonant && $final_consonant->is_dead_final)
		 && $tone_char && $tone_char->code == ThaiAlphabet::$MAI_EK->code
		 && $init_consonant && $init_consonant->tone_class >= 0
		) { // THE SYLLABLE HAS A SHORT VOWEL SOUND
			$vow = self::getShortVowelRule($vow);
			$is_long_for_tone = false;

		} else {
			//
			// For the purposes of the tone rules, most short/open vowels generate a dead syllable; however,
			// the short/open vowels marked "short_open_live" => 1 are treated as a live consonant ending.
			//
			$is_long_for_tone =
				$vow['long']
				|| ((isset($vow['short_open_live']) && $vow['short_open_live']))
				//|| (isset($vow['always_long_for_tone']) && $vow['always_long_for_tone'])
				//|| ($patlen >= 2 && ($pattern[0] == 3652 || $pattern[0] == 3651 || $pattern[0] == 3648) && $pattern[1] == 0)
			;
		}





		//
		//
		// ROMANIZE HERE
		// PUT ROMAN LETTERS TOGETHER HERE
		//
		//
		$roman = '';

		if ($var_consonant_count == 1 && $patlen == 3 && $pat[0] == 0 && $pat[1] == 3619 && $pat[2] == 3619) {
			// FOR RO HAN
			$roman = $consonants[0]->use_head . self::$INHERENT_VOWEL . 'n';

		} else if ($consonant_count == 1) {
			// SINGLE CONSONANT
			$roman = $consonants[0]->use_head . $vow['use'];

		} else {
			if ( // SO SALA AND SO SUEA make SILENT SECONDS
				$is_cluster
			 && ($consonants[0]->code == ThaiAlphabet::$SO_SALA->code || $consonants[0]->code == ThaiAlphabet::$SO_SUEA->code)
			 && ($consonants[1]->code != ThaiAlphabet::$RO_RUA->code && $consonants[1]->code != ThaiAlphabet::$RU->code)
			) { // SECOND CONSONANT IS NOT COMBINED, BUT SILENT
				$silent_second = 1;
			}

			$cluster_end = 0;
			if ($silent_second) {
				$cluster_end = 1;
				$roman .= $consonants[0]->use_head;
			} else if ($is_cluster) {
				$cluster_end = 2;
				if ($consonants[1]->code == ThaiAlphabet::$WO_WAEN->code) {
					$roman .= $consonants[0]->use_head . $consonants[1]->use_head;// . ' {' . $vow['name'] . '}';
				} else {
					$roman .= $consonants[0]->use_head . $consonants[1]->use_head;// . ' (' . $vow['name'] . ')';
				}
			} else {
				$cluster_end = 0;
				$roman .= $consonants[0]->use_head;
			}


			if ($is_cluster) {
				//if (isset($consonent_vowels[$is_cluster]) && $consonent_vowels[$is_cluster]) {
				  //  $roman .= $vow['use'] . $consonants[1 + $is_cluster]->use_head . self::$INHERENT_VOWEL;
				//} else
				$roman .= $vow['use'];// . ' (' . $vow['name'] . ')';
				if ($final_consonant) {
					$roman .= $final_consonant->use_tail;
				} else if ($vowelized_final) {
					$roman .= $vowelized_final->use_tail;// . ' (' . $vow['name'] . ')';
				}
			} else {
				if ($final_consonant) {
					$roman .= $vow['use'] . $final_consonant->use_tail;
				} else {
					if (ThaiAlphabet::$SARA_E->code == $pat[0] && ThaiAlphabet::$SARA_A->code == $pat[$patlen - 1]) {
						$roman .= $vow['use'];// . ' (' . $vow['name'] . ')';
					} else if ($vowelized_final) {
						$roman .= $vow['use'] . $vowelized_final->use_tail;// . ' (' . $vow['name'] . ')';
					} else if ($patlen > 1 && $consonants[1]->code == self::nextConsonant($pat, 1) ) {
						$roman .= $vow['use'];// . ' (' . $vow['name'] . ')';
					} else {
						$roman .= $consonants[1]->use_head . $vow['use'];// . ' (' . $vow['name'] . ')';
					}
				}
			}
		}


		if (!$tone_number) {
			$tone_number = self::getOverrideTone($chars);
		}



		if (!$tone_number) {
			$tone_number = self::getToneNumberFromParams($tone_char, $init_consonant, $final_for_tone, $is_long_for_tone, $vow);
			$tone_number2 = self::getToneNumber($init_consonant, $final_for_tone, $tone_char, $is_long_for_tone, $vow);
			if ($tone_number != $tone_number2) {
				echo '<b style="color:red">!!! Tone number mismatch: ' . $chars . '/' . $roman . ' - ' . $tone_number2 . ' != ' . $tone_number . '</b><br />';
				//return false;
			}
		}

		if (isset($GLOBALS['THAI_DEBUG_TRANSLITERATE'])) {
			$roman .= '{' . $vow['name'] . ($vow['long'] ? ',long' : ',short') . ',' . $tone_number . ($final_position && $can_replicate ? ',redup=' . $final_position : '') . '}:';
		}


		$match = array(
			'length' => ($arg_i),
			'spelling_length' => ($arg_i),
			'chars' => $chars,
			//'codes' => $all_matches,
			'roman' => $roman,
			'tone' => $tone_number,
			'vowel' => $vow,
			//'long' => $is_long_for_tone,
			'pattern' => $pat,
			'is_cluster' => $is_cluster,
			'reduced_hoham' => ($reduced_hoham ? true : false),
			'consonant_count' => $var_consonant_count
		);
		$match['tone_count'] = self::getToneCount($match);
		$match['syllable_sort_score'] = self::getSyllableScore($match);

		$matches = array();
		array_push($matches, $match);

		if ($can_replicate && $final_position > 0 && $final_position < $arg_i) {
			// ALSO RETURN SYLLABLE WHICH CAN BE REDUPLICATED
			$match = array(
				'length' => ($final_position),
				'spelling_length' => ($arg_i),
				'chars' => $chars,
				//'codes' => $all_matches,
				'roman' => $roman,
				'tone' => $tone_number,
				'vowel' => $vow,
				//'long' => $is_long_for_tone,
				'pattern' => $pat,
				'is_cluster' => $is_cluster,
				'final' => ($final_consonant ? $final_consonant->charcode : null),
				'reduced_hoham' => ($reduced_hoham ? true : false),
				'consonant_count' => $var_consonant_count,
				'reduplicated_final' => ($final_consonant ? $final_consonant : $vowelized_final),
			);
			$match['tone_count'] = self::getToneCount($match);
			$match['syllable_sort_score'] = self::getSyllableScore($match);

			array_push($matches, $match);
		}

		return $matches;
	}


	/**
	 * Special list of words that are spelled like shorts but are longs
	 * @see http://www.thai-language.com/ref/irregular-words
	 */
	public static $SPECIAL_LONGS = array(
		array(3588,3634,3648,3615,3629,3636,3609),
		array(3592,3619,3632,3648,3586,3657),
		array(3592,3632,3648,3586,3657),
		array(3605,3632,3648,3586,3657),
		array(3609,3657,3635),
		array(3621,3636,3595,3656,3634),
		array(3623,3657,3634,3648,3627,3623,3656),
		array(3648,3607,3657,3634),      // not sure about this one, might be a rule = "thao"
		array(3648,3611,3621,3656,3634),
		array(3648,3611,3642,3621,3656,3634),

		array(3648,3626,3609,3656,3627,3660),
		array(3648,3627,3623,3656),
		array(3648,3585,3657,3634),

		array(3648,3594,3657,3634), // sara ow, high-class initial with mai-tho, chao=morning is long alone, short in compound
		array(3649,3605,3656), // sara ae, to-tao, with mai-ek
		array(3649,3627,3656), // sara ae, ho-hip, with mai-ek

		array(3652,3604,3657),
		array(3652,3617,3657),
	);


	/**
	 * Special list of words that are spelled like longs but are shorts
	 * @see http://www.thai-language.com/ref/irregular-words
	 */
	public static $SPECIAL_SHORTS = array(
		array(3585,3619,3632,3648,3594,3657,3634),
		array(3585,3658,3629,3585), // gowk H (like in faucet) is short
		array(3588,3657,3635),

		array(3594,3656,3634,3591),
		array(3594,3657,3635),
		array(3595,3657,3635),
		array(3605,3657,3629,3591),
		array(3607,3656,3634,3609),
		array(3610,3619,3636,3585,3634,3619),
		array(3618,3656,3629,3591),
		array(3621,3656,3629,3585,3649,3621,3656,3585),
		array(3621,3657,3635),

		array(3648,3585,3656,3591),
		array(3648,3586,3656,3591),
		array(3648,3586,3656,3609),
		array(3648,3591,3636,3609),
		array(3648,3592,3636,3656,3591),
		array(3648,3593,3656,3591),
		array(3648,3594,3657,3591),
		array(3648,3604,3657,3591),
		array(3648,3614,3594,3619),
		array(3648,3614,3594,3619,3610,3641,3619,3603,3660),
		array(3648,3619,3656),
		array(3648,3621,3656,3609),
		array(3648,3621,3656,3617),
		array(3648,3623,3657,3609),
		array(3648,3623,3657,3634),

		array(3649,3627,3623,3656,3591),
		array(3649,3629,3621),
	);


	/**
	 * Helper function used to check if a tone has an irregular length.
	 * This also sometimes happens in multi-syllabic words, which are not detected.
	 *
	 * Also externally referenced.
	 *
	 * @param string $s vowel to check if there's a special tone.
	 *
	 * @return
	 *     false if not special length
	 *     greater than zero for special long
	 *     less than zero for special short
	 */
	public static function getSpecialVowelLength($s) {

		$len = mb_strlen($s);
		if ($len == 0) {
			return false;
		}

		$o = static function($s, $i) { return mb_ord(mb_substr($s, $i, 1)); };
		$c0 = $o($s, 0);

		$num_make_shorts = count(self::$SPECIAL_SHORTS);
		$got_first = false;
		for ($i = 0; $i < $num_make_shorts; $i++) {
			if ($c0 == self::$SPECIAL_SHORTS[$i][0]) {
				if (count(self::$SPECIAL_SHORTS[$i]) == $len) {
					$all_match = true;
					for ($j = 1; $j < $len; $j++) {
						if ($o($s, $j) != self::$SPECIAL_SHORTS[$i][$j]) {
							$all_match = false;
							break;
						}
					}
					if ($all_match) {
						return -1;
					}
				}
				$got_first = true;
			} else if ($got_first) {
				break; // assume they are in order...
			}
		}

		$num_make_longs = count(self::$SPECIAL_LONGS);
		$got_first = false;
		for ($i = 0; $i < $num_make_longs; $i++) {
			if ($c0 == self::$SPECIAL_LONGS[$i][0]) {
				$alen = count(self::$SPECIAL_LONGS[$i]);
				if ($alen == $len) {
					$all_match = true;
					for ($j = 1; $j < $len; $j++) {
						if ($o($s, $j) != self::$SPECIAL_LONGS[$i][$j]) {
							$all_match = false;
							break;
						}
					}
					if ($all_match) {
						return 1;
					}
				}
				$got_first = true;
			} else if ($got_first) {
				break; // assume they are in order...
			}
		}

		return false;
	}



	/**
	 * Helper function which retnurs the codepoint of the next consonent in given pattern.
	 */
	private static function nextConsonant($pattern, $offset) {
		$length = count($pattern);
		while ($offset < $length) {
			if ($pattern[$offset] > 0 && ThaiSymbol::isCodeConsonant($pattern[$offset])) {
				return $pattern[$offset];
			}
			$offset++;
		}
		return false;
	}


	/**
	 * Heuristic to make a score for given syllable.
	 */
	public static function getSyllableScore($molecule) {
		if (!$molecule) {
			return -1;
		}
		//if ($molecule['syllable_sort_score']) {
		//    return $molecule['syllable_sort_score'];
		//}
		if (isset($molecule['word']) && $molecule['word']) {
			$syllable_sort_score = pow($molecule['length']*2, 2);
			for ($k = 0; $k < $molecule['word']->count; $k++) {
				$syllable_sort_score += self::getSyllableScore($molecule['word']->parts[$k]);
			}
			$syllable_sort_score -= 8 * abs(
				count($molecule['word']->tones) - self::getToneCount($molecule)
			);
			$molecule['syllable_sort_score'] = $syllable_sort_score;
		} else {
			$molecule['syllable_sort_score'] =
				($molecule['tone'] ? 1 : 0)
			  + ($molecule['is_cluster'] ? -1 : 0)
			  + ($molecule['reduced_hoham'] ? -1 : 0)
			  + pow(count($molecule['pattern']), 2)
			  + pow($molecule['spelling_length'], 2)
			  - $molecule['consonant_count']
			;
		}
		//echo '<br />' . $molecule['syllable_sort_score'] . '<br />';
		return $molecule['syllable_sort_score'];
	}




	/**
	 * Returns the number of tones in given Thai word.
	 */
	public static function getToneCount($o) {
		if (!$o) {
			return 0;
		}
		//if ($o['tone_count']) {
		//    return $o['tone_count'];
		//}
		if (isset($o['word']) && $o['word']) {
			if ($o['word']->tones) {
				$o['tone_count'] = count($o['word']->tones);
			} else if ($o['word']->count) {
				$tone_count = 0;
				for ($i = 0; $i < $o['word']->count; $i++) {
					$tone_count += self::getToneCount($o['word']->parts[$i]);
				}
				$o['tone_count'] = $tone_count;
			} else {
				$o['tone_count'] = 1;
			}
		} else {
			if ($o['length']) {
				$o['tone_count'] = 1;
			} else {
				$o['tone_count'] = 0;
			}
		}
		return $o['tone_count'];
	}


	/**
	 * Helper function for segment_matches()
	 * But placed here because it's related to the match structures it is comparing.
	 */
	private static function matchesEqual($m1, $m2) {
		//echo 'm1{chars:' . $m1['chars'] . ', roman:' . $m1['roman'] . ', tone:' . $m1['tone'] . ', length:' . $m1['length'] . ', spelling_length:' . $m1['spelling_length'] . '}<br />';
		//echo 'm2{chars:' . $m2['chars'] . ', roman:' . $m2['roman'] . ', tone:' . $m2['tone'] . ', length:' . $m2['length'] . ', spelling_length:' . $m2['spelling_length'] . '}<br />';
		return 
			$m1['chars'] == $m2['chars'] && $m1['roman'] == $m2['roman']  && $m1['tone'] == $m2['tone']
		 && $m1['length'] == $m2['length'] && $m1['spelling_length'] == $m2['spelling_length']
		;
	}











	/**
	 * There are a few tone marker styles.
	 * This is the current one.  Did not make defines cause, this is a solo project.
	 *
	 * Can be one of the following:
	 *  *) 'accented_latin' (default): this is like Mandarin pinyin,
	 *      with the addition of an acute for the falling tone.
	 *      for example, 'a' for common, 'à' for low, 'â' for falling, 'á' for high, and 'ă' for the rising tone.
	 *  *) 'thaiphon': from the Volubilis dictionary,
	 *      where a syllable is prefixed with one of the following:
	 *      '-' for common, '_' for low, '/' for rising, '¯' for high, and '^' for the falling tone.
	 *  *) 'alphabetic: each syllable is suffixed with one of the following:
	 *     	'<sup>M</sup>' for common, '<sup>L</sup>' for low, '<sup>R</sup> for rising,
	 *      '<sup>H</sup>' for high, and '<sup>F</sup>', for the falling tone
	 *  *) 'numeric':  each syllable is suffixed with
	 *      '1' for common, '2' for low, '3' for falling, '4' for 'high', and '5' for the rising tone.
	 *  *) 'none': will not apply a tone.
	 */
	public static $tone_marker_style = null; //'thaiphon';

	/**
	 * Basic getter method returning if specified string is an accepted tone marker style or not.
	 */
	public static function isToneStyle($s) {
		return 
			$s == 'accented_latin'
		 || $s == 'thaiphon'
		 || $s == 'alphabetic'
		 || $s == 'numeric'
		 || $s == 'none'
		 || !$s
		;
	}


	/**
	 * Helper function to apply the Thai tone indicator to the transliterated $vowel string.
	 *
	 * @param  $vowel        The transliterated output of the vowel before the tone has been applied.
	 * @param  $tone_number  The Thai tone number: 1 = common, 2 = low, 3 = falling, 4 = high, 5 = rising
	 * @param  $is_long      Whether the vowel is long or short.
	 *
	 * @return  A string with the tone applied.
	 */
	private static function applyTone($vowel, $tone_number = 1, $is_long = false) {
		if (self::$tone_marker_style == 'thaiphon') {
			return self::applyThaiPhonTone($vowel, $tone_number, $is_long);
		} else if (self::$tone_marker_style == 'none') {
			return $vowel;
		} else if (self::$tone_marker_style == 'accented_latin') {
			return self::applyCustomTone($vowel, $tone_number, $is_long);
		} else if (self::$tone_marker_style == 'alphabetic') {
			return self::applyAlphabeticTone($vowel, $tone_number, $is_long);
		} else if (self::$tone_marker_style == 'numeric') {
			return self::applyNumericTone($vowel, $tone_number, $is_long);
		} else {
			return self::applyCustomTone($vowel, $tone_number, $is_long);
		}
	}



	/**
	 * Helper function to apply thaiphon style to input vowel.
	 *
	 * Thaiphon is a transliteration used by the Volubilis dictionary,
	 * where a syllable is prefixed with one of the following:
	 *     '-' for common, '_' for low, '/' for rising, '¯' for high, and '^' for the falling tone.
	 *
	 * @param  $vowel        The transliterated output of the vowel before the tone has been applied.
	 * @param  $tone_number  The Thai tone number: 1 = common, 2 = low, 3 = falling, 4 = high, 5 = rising
	 * @param  $is_long      Whether the vowel is long or short.
	 *
	 * @return  A string with the tone applied.
	 */
	private static function applyThaiPhonTone($vowel, $tone_number = 1, $is_long = false) {
		if (!$is_long && ($tone_number < 1 || $tone_number > 5)) {
			return ThaiAlphabet::mb_trim($vowel);
		}
		$slen = mb_strlen($vowel);
		if ($slen == 0) {
			return '';
		}
		switch($tone_number) {
		  case 1: return mb_convert_encoding(mb_chr(45), 'utf-8') . $vowel;
		  case 2: return mb_convert_encoding(mb_chr(95), 'utf-8') . $vowel;
		  case 3: return mb_convert_encoding(mb_chr(94), 'utf-8') . $vowel;
		  case 4: return mb_convert_encoding(mb_chr(175), 'utf-8') . $vowel;
		  case 5: return mb_convert_encoding(mb_chr(47), 'utf-8') . $vowel;
		};
		return $vowel;
	}



	/**
	 * Helper function to apply numeric style to input vowel.
	 *
	 * Suffixes the Romanized $vowel with it's $tone_number.  Simple concat.
	 *
	 * @param  $vowel        The transliterated output of the vowel before the tone has been applied.
	 * @param  $tone_number  The Thai tone number: 1 = common, 2 = low, 3 = falling, 4 = high, 5 = rising
	 * @param  $is_long      Whether the vowel is long or short.
	 *
	 * @return  A string with the tone applied.
	 */
	public static function applyNumericTone($vowel, $tone_number = 1, $is_long = false) {
		if (!$is_long && ($tone_number < 1 || $tone_number > 5)) {
			return ThaiAlphabet::mb_trim($vowel);
		}
		$slen = mb_strlen($vowel);
		if ($slen == 0) {
			return '';
		}
		return $vowel . '' . $tone_number;
	}


	/**
	 * Helper function to apply thaiphon style to input vowel.
	 *
	 * Thaiphon is a transliteration used by the Volubilis dictionary,
	 * where a syllable is prefixed with one of the following:
	 *     '-' for common, '_' for low, '/' for rising, '¯' for high, and '^' for the falling tone.
	 *
	 * @param  $vowel        The transliterated output of the vowel before the tone has been applied.
	 * @param  $tone_number  The Thai tone number: 1 = common, 2 = low, 3 = falling, 4 = high, 5 = rising
	 * @param  $is_long      Whether the vowel is long or short.
	 *
	 * @return  A string with the tone applied.
	 */
	public static function applyAlphabeticTone($vowel, $tone_number = 1, $is_long = false) {
		if (!$is_long && ($tone_number < 1 || $tone_number > 5)) {
			return ThaiAlphabet::mb_trim($vowel);
		}
		$slen = mb_strlen($vowel);
		if ($slen == 0) {
			return '';
		}
		$tone_letter = '';
		switch($tone_number) {
		  case 1: return $vowel . '<sup>M</sup>';
		  case 2: return $vowel . '<sup>L</sup>';
		  case 3: return $vowel . '<sup>F</sup>';
		  case 4: return $vowel . '<sup>H</sup>';
		  case 5: return $vowel . '<sup>R</sup>';
		}
		return $vowel;
	}






	/**
	 * Applies tone (and length) marker to roman vowel {a, e, i, o, u}.
	 * And long vowel symbol if applicable vowel present after tone.
	 */
	public static function applyCustomTone($vowel, $tone_number = 1, $is_long = false) {
		if (!$is_long && ($tone_number < 2 || $tone_number > 5)) {
			return $vowel;
		}

		$slen = mb_strlen($vowel);
		if ($slen == 0) {
			return $vowel;
		}

		// IF LONG, TRY TO APPLY LONG VOWEL BAR
		if ($is_long) {
			$first_i = -1;
			$first_c = null;
			$c1 = null;
			$c2 = null;
			for ($i = 0; $i < $slen; $i++) {
				$c1 = $c2;
				$c2 = mb_substr($vowel, $i, 1);
				if (isset(self::$tone_tab[$c2])) {
					if ($first_i < 0) {
						$first_i = $i;
						$first_c = $c2;
					}
				} else {
					$c2 = null;
				}
				if ($c1 && $c2) {
					return
						($i > 1 ? mb_substr($vowel, 0, $i - 1) : '')
					  . mb_chr(self::$tone_tab[$c1][$tone_number])
					  . mb_chr(self::$tone_tab[$c2][6])
					  . ($i < $slen - 1 ? mb_substr($vowel, $i + 1) : '')
					;
				}
			}
			if ($first_c) {
				return
					($first_i > 0 ? mb_substr($vowel, 0, $first_i) : '')
				  . mb_chr(self::$tone_tab[$first_c][$tone_number])
				  . mb_chr(self::$tone_tab[$first_c][6])
				  . ($first_i < $slen - 1 ? mb_substr($vowel, $first_i + 1) : '')
				;
			}
			return $vowel;
		}

		// IF HERE, EITHER SHORT OR CANNOT APPLY LONG TONE BAR
		for ($i = 0; $i < $slen; $i++) {
			$c = mb_substr($vowel, $i, 1);
			if (isset(self::$tone_tab[$c])) {
				return
					($i > 0 ? mb_substr($vowel, 0, $i) : '')
				  . mb_chr(self::$tone_tab[$c][$tone_number])
				  . ($i < $slen - 1 ? mb_substr($vowel, $i + 1) : '')
				;
			}
		}
		return $vowel;
	}


	/**
	 * This tone table maps unaccented Roman letters to accented Roman letters,
	 * similar to that used in Mandarin Pinyin, except the flat tone marker is used
	 * to denote long tones instead of common tones.
	 *
	 * If a vowel is long, the the $tone_tab[letter][6] will contain the appropriate straight tone.
	 */
	private static $tone_tab = array(
		'a' => array(97, 97, 224, 226, 225, 259, 257),
		'e' => array(101, 101, 232, 234, 233, 277, 275),
		'i' => array(105, 105, 236, 238, 237, 301, 299),
		'o' => array(111, 111, 242, 244, 243, 335, 333),
		'u' => array(117, 117, 249, 251, 250, 365, 363),
		'A' => array(65, 65, 192, 194, 193, 258, 256),
		'E' => array(69, 69, 200, 202, 201, 276, 274),
		'I' => array(73, 73, 204, 206, 205, 300, 298),
		'O' => array(79, 79, 210, 212, 211, 334, 332),
		'U' => array(85, 85, 217, 219, 218, 364, 362),

		'ü' => array(252, 252, 476, 367, 472, 474, 470),
		'Ü' => array(220, 220, 475, 366, 471, 473, 469),
	);


	/**
	 * This was used for representing long tones by placing a bar over the Roman letters.
	 *
	 * @deprecated    Moved to index 6 of each branch of $tone_tab
	 */
	public static $long_tab = null;









	/**
	 * Clusters, like English "tr" or "st".
	 */
	public static $CONSONANT_CLUSTERS = array (
		array(3624,3619),  // silent second
		array(3626,3619),  // silent second
		array(3585,3619),
		array(3585,3621),
		array(3585,3623),
		array(3586,3619),
		array(3588,3619),
		array(3586,3621),
		array(3588,3621),
		array(3586,3623),
		array(3588,3623),
		array(3611,3619),
		array(3611,3621),
		array(3614,3619),
		array(3612,3621),
		array(3614,3621),
		array(3605,3619),
		array(3615,3619),
		array(3607,3619),
		array(3610,3619),

		array(3604,3619),  // added for thai pronunciation syllable phoneme
		array(3592,3619),  // added for just one f-d up word that doesn't even have a definition.........
		array(3615,3621),  // added for another thai pronunciation syllable phoneme
		array(3614,3620),  // for "preud": eg in november and may
	);


	/**
	 * Initializes Thai spelling clusters into each verb rule.
	 */
	public static function initClusterPatterns() {

		$num_verb_rules = count(self::$VERB_RULES);
		$num_clusters = count(self::$CONSONANT_CLUSTERS);

		for ($j = 0; $j < $num_verb_rules; $j++) {
			if (!self::$VERB_RULES[$j]['active']) {
				continue;
			}
			if (isset(self::$VERB_RULES[$j]['cluster_patterns'])) {
				continue;
			}

			$patterns = self::$VERB_RULES[$j]['patterns'];
			$num_patterns = count($patterns);

			for ($k = 0; $k < $num_patterns; $k++) {

				//self::$VERB_RULES[$j], $patterns[$k]
				for ($m = 0; $m < $num_clusters; $m++) {
					$new_part = null;
					$pat = $patterns[$k];
					$patlen = count($pat);
					for ($n = 0; $n < $patlen; $n++) {
						if ($pat[$n] == 0) {
							if (!$new_part) {
								$new_part = array();
								for ($p = 0; $p < $n; $p++) {
									array_push($new_part, $pat[$p]);
								}
							}
							array_push($new_part, self::$CONSONANT_CLUSTERS[$m][0]);
							array_push($new_part, self::$CONSONANT_CLUSTERS[$m][1]);
							$n++;
							while ($n < count($pat)) {
								array_push($new_part, $pat[$n++]);
							}

							//  if ($n > 0) {
							//if (self::$VERB_RULES[$j]['name'] == 'sara ai') {
							//    echo self::arrayToString($new_part) . '<br />';
							//}
							break;
						}
					}

					//if (self::$VERB_RULES[$j]['name'] == 'sara ai') {
					//    echo 'DONE: ' . self::arrayToString($pat) . ' => ' . self::arrayToString($new_part) . '<br />';
					//}

					if ($new_part) {
						if (!isset(self::$VERB_RULES[$j]['cluster_patterns'])) {
							self::$VERB_RULES[$j]['cluster_patterns'] = array();
						}
						if (!isset(self::$VERB_RULES[$j]['cluster_patterns'][self::$CONSONANT_CLUSTERS[$m][1]])) {
							self::$VERB_RULES[$j]['cluster_patterns'][self::$CONSONANT_CLUSTERS[$m][1]] = array();
						}
						if (!isset(self::$VERB_RULES[$j]['cluster_patterns'][self::$CONSONANT_CLUSTERS[$m][1]][self::$CONSONANT_CLUSTERS[$m][0]])) {
							self::$VERB_RULES[$j]['cluster_patterns'][self::$CONSONANT_CLUSTERS[$m][1]][self::$CONSONANT_CLUSTERS[$m][0]] = array();
						}
						array_push(self::$VERB_RULES[$j]['cluster_patterns'][self::$CONSONANT_CLUSTERS[$m][1]][self::$CONSONANT_CLUSTERS[$m][0]], $new_part);
					}
					$new_part = null;
				}
			}


			// INIT sara E, NEEDED FOR SPECIAL CASE "JING"
			if (self::$SARA_I_SHORT == null && self::$VERB_RULES[$j]['short'] && self::$VERB_RULES[$j]['name'] == 'sara i') {
				self::$SARA_I_SHORT = self::$VERB_RULES[$j];
			} else if (self::$SARA_UA_LONG == null && self::$VERB_RULES[$j]['long'] && self::$VERB_RULES[$j]['name'] == 'sara ua') {
				self::$SARA_UA_LONG = self::$VERB_RULES[$j];
			}


			//echo '<pre>';
			//var_dump(self::$VERB_RULES[$j]['cluster_patterns']);
			//echo '</pre>';


		}
	}






	
	private static function getShortVowelRule($vow) {
		if ($vow['short']) {
			return $vow;
		}
		$len = count(self::$VERB_RULES);
		for ($i = 0; $i < $len; $i++) {
			if (self::$VERB_RULES[$i]['name'] == $vow['name'] && self::$VERB_RULES[$i]['long'] && self::$VERB_RULES[$i]['ex'] == $vow['ex']) {
				return self::$VERB_RULES[$i - 1];
			}
		}
		return $vow;
	}


	private static function getLongVowelRule($vow) {
		if ($vow['long']) {
			return $vow;
		}
		$len = count(self::$VERB_RULES);
		for ($i = 0; $i < $len; $i++) {
			if (self::$VERB_RULES[$i]['name'] == $vow['name'] && self::$VERB_RULES[$i]['short'] && self::$VERB_RULES[$i]['ex'] == $vow['ex']) {
				return self::$VERB_RULES[$i + 1];
			}
		}
		return $vow;
	}





	static $VERB_RULES = array(
	  array(
		"name" => "sara a",
		"thai" => array(3626,3619,3632,3629,3632),
		"rtgs" => "a",
		"use" => "a",
		"ex" => "u in \"nut\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3632), 
		  array(0), 
		  array(0, 3633, 0),
		  array(0, 3619, 3619), // ro han
		  array(0, 3619, 3619, 0), // ro han
		),
	  ),
	  array(
		"name" => "sara a",
		"thai" => array(3626,3619,3632,3629,3634),
		"rtgs" => "a",
		"use" => "aa",
		"ex" => "a in \"father\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3634), 
		  array(0, 3634, 0)
		)
	  ),
	  array(
		"name" => "sara i",
		"rtgs" => "i",
		"thai" => array(3626,3619,3632,3629,3636),
		"use" => "i",
		"ex" => "y in \"greedy\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3636), 
		  array(0, 3636, 0)
		)
	  ),
	  array(
		"name" => "sara i",
		"thai" => array(3626,3619,3632,3629,3637),
		"rtgs" => "i",
		"use" => "ee",
		"ex" => "ee in \"see\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3637), 
		  array(0, 3637, 0)
		)
	  ),
	  array(
		"name" => "sara ue",
		"thai" => array(3626,3619,3632,3629,3638),
		"rtgs" => "ue ",
		"use" => "eu",
		"ex" => "u in French \"du\" (short)",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3638), 
		  array(0, 3638, 0)
		)
	  ),
	  array(
		"name" => "sara ue",
		"thai" => array(3626,3619,3632,3629,3639,3629),
		"rtgs" => "ue",
		"use" => "eu",
		"ex" => "u in French \"dur\" (long)",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3639, 3629), 
		  array(0, 3639, 0)
		)
	  ),
	  array(
		"name" => "sara u",
		"thai" => array(3626,3619,3632,3629,3640),
		"rtgs" => "u",
		"use" => "u",
		"ex" => "oo in \"look\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3640), 
		  array(0, 3640, 0)
		)
	  ),
	  array(
		"name" => "sara u",
		"thai" => array(3626,3619,3632,3629,3641),
		"rtgs" => "u",
		"use" => "oo",
		"ex" => "oo in \"too\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3641), 
		  array(0, 3641, 0)
		)
	  ),
	  array(
		"name" => "sara e",
		"thai" => array(3626,3619,3632,3648,3629,3632),
		"rtgs" => "e",
		"use" => "e",
		"ex" => "e in \"neck\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3632), 
		  array(3648, 0, 3655, 0)
		)
	  ),
	  array(
		"name" => "sara e",
		"thai" => array(3626,3619,3632,3648,3629),
		"rtgs" => "e",
		"use" => "aey",
		"ex" => "a in \"lame\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0),
		  array(3648, 0, 0)
		)
	  ),
	  array(
		"name" => "sara ae",
		"thai" => array(3626,3619,3632,3649,3629,3632),
		"rtgs" => "ae",
		"use" => "ae",
		"ex" => "a in \"at\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3649, 0, 3632), 
		  array(3649, 0, 3655, 0)
		)
	  ),
	  array(
		"name" => "sara ae",
		"thai" => array(3626,3619,3632,3649,3629),
		"rtgs" => "ae",
		"use" => "aae",
		"ex" => "a in \"ham\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3649, 0), 
		  array(3649, 0, 0)
		)
	  ),
	  array(
		"name" => "sara o",
		"thai" => array(3626,3619,3632,3650,3629,3632),
		"rtgs" => "o",
		"use" => "o",
		"ex" => "oa in \"boat\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3650, 0, 3632), 
		  array(0, 0)
		)
	  ),
	  array(
		"name" => "sara o",
		"thai" => array(3626,3619,3632,3650,3629),
		"rtgs" => "o",
		"use" => "ouh",
		"ex" => "o in \"go\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3650, 0), 
		  array(3650, 0, 0)
		)
	  ),
	  array(
		"name" => "sara ow",
		"thai" => array(3626,3619,3632,3648,3629,3634,3632),
		"rtgs" => "o",
		"use" => "aw",
		"ex" => "o in \"not\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3634, 3632), 
		  array(0, 3655, 3629, 0)
		)
	  ),
	  array(
		"name" => "sara ow",
		"thai" => array(3626,3619,3632,3629,3629),
		"rtgs" => "o",
		"use" => "aaw",
		"ex" => "aw in \"saw\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3629), 
		  array(0, 3629, 0),
		  array(0, 0, 3619), 
		  //array(0, 3655), // mai taikhu
		  array(3585,3655)  // gae (goh gai with mai taikhu)
		)
	  ),
	  array(
		"name" => "sara oe",
		"thai" => array(3626,3619,3632,3648,3629,3629,3632),
		"rtgs" => "oe",
		"use" => "e",
		"ex" => "e in \"the\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3629, 3632)
		)
	  ),
	  array(
		"name" => "sara oe",
		"thai" => array(3626,3619,3632,3648,3629,3629),
		"rtgs" => "oe",
		"use" => "eer",
		"ex" => "u in \"burn\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3629), 
		  array(3648, 0, 3636, 0), // often short
		  array(3648, 0, 3629, 0)
		)
	  ),
	  array(
		"name" => "sara ia",
		"thai" => array(3626,3619,3632,3648,3629,3637,3618,3632),
		"rtgs" => "ia",
		"use" => "ia",
		"ex" => "ea in \"ear\" with glottal stop",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3637, 3618, 3632)
		)
	  ),
	  array(
		"name" => "sara ia",
		"thai" => array(3626,3619,3632,3648,3629,3637,3618),
		"rtgs" => "ia",
		"use" => "ia",
		"ex" => "ea in \"ear\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3637, 3618), 
		  array(3648, 0, 3637, 3618, 0)
		)
	  ),
	  array(
		"name" => "sara uea",
		"thai" => array(3626,3619,3632,3648,3629,3639,3629,3632),
		"rtgs" => "uea",
		"use" => "ua",
		"ex" => "ure in \"pure\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3639, 3629, 3632)
		)
	  ),
	  array(
		"name" => "sara uea",
		"thai" => array(3626,3619,3632,3648,3629,3639,3629),
		"rtgs" => "uea",
		"use" => "eua",
		"ex" => "ure in \"pure\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3639, 3629), 
		  array(3648, 0, 3639, 3629, 0)
		)
	  ),
	  array(
		"name" => "sara ua",
		"thai" => array(3626,3619,3632,3629,3633,3623,3632),
		"rtgs" => "ua",
		"use" => "ua",
		"ex" => "ewe in \"sewer\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3633, 3623, 3632)
		)
	  ),
	  array(
		"name" => "sara ua",
		"thai" => array(3626,3619,3632,3629,3633,3623),
		"rtgs" => "ua",
		"use" => "uua",
		"ex" => "ewe in \"newer\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3633, 3623), 
		  array(0, 3623, 0)
		)
	  ),
	  array(
		"name" => "sara i + wo waen",
		"thai" => array(3626,3619,3632,3629,3636,32,43,32,3623),
		"rtgs" => "io",
		"use" => "ew",
		"ex" => "ew in \"new\"",
		"short" => 1,
		"long" => 0,
		"always_long_for_tone" => 1,
		"short_open_live" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3636, 3623)
		)
	  ),

	  // placeholder for conversion to long
	  array(
		"name" => "sara i + wo waen",
		"thai" => array(3626,3619,3632,3629,3636,32,43,32,3623),
		"rtgs" => "io",
		"use" => "ew",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 0,
		"patterns" => array(
		  array(0, 3636, 3623)
		)
	  ),



	  array(
		"name" => "sara e + wo waen",
		"thai" => array(3626,3619,3632,3648,3629,3632,32,43,32,3623),
		"rtgs" => "eo",
		"use" => "eu",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"always_long_for_tone" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3655, 3623)
		)
	  ),
	  array(
		"name" => "sara e + wo waen",
		"thai" => array(3626,3619,3632,3648,3629,32,43,32,3623),
		"rtgs" => "eo",
		"use" => "aiu",
		"ex" => "ai + ow in \"rainbow\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3623)
		)
	  ),

	  // NOT ON WIKIPEDIA, BUT FOUND ON THAI-LANGUAGE, SHORT SARA AE + WO WAEN
	  array(
		"name" => "sara ae + wo waen",
		"thai" => array(3626,3619,3632,3649,3629,3632,32,43,32,3623),
		"rtgs" => "aeo",
		"use" => "aou",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		//"always_long_for_tone" => 1,
		//"short_open_live" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3649, 0, 3655, 3623)
		)
	  ),

	  array(
		"name" => "sara ae + wo waen",
		"thai" => array(3626,3619,3632,3649,3629,32,43,32,3623),
		"rtgs" => "aeo",
		"use" => "aou",
		"ex" => "a in \"ham\" + ow in \"low\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3649, 0, 3623)
		)
	  ),


	  array(
		"name" => "sara ao",
		"thai" => array(3626,3619,3632,3648,3629,3634),
		"rtgs" => "ao",
		"use" => "au",
		"ex" => "ow in \"cow\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"always_long_for_tone" => 1,
		"short_open_live" => 1,
		"patterns" => array(
		  array(3648, 0, 3634)
		)
	  ),
	  // PLACEHOLDER FOR CONVERSION
	  array(
		"name" => "sara ao",
		"thai" => array(3626,3619,3632,3648,3629,3634),
		"rtgs" => "ao",
		"use" => "au",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 0,
		"patterns" => array(
		  array(3648, 0, 3634)
		)
	  ),


	  array(
		"name" => "sara a + wo waen",
		"thai" => array(3626,3619,3632,3629,3634,32,43,32,3623),
		"rtgs" => "ao",
		"use" => "aao",
		"ex" => "ow in \"now\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3634, 3623)
		)
	  ),


	  // PLACEHOLDER TO SHORTEN
	  array(
		"name" => "sara ia + wo waen",
		"thai" => array(3626,3619,3632,3648,3629,3637,3618,3632,32,43,32,3623),
		"rtgs" => "ieow",
		"use" => "iow",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"active" => 0,
		"patterns" => array(
		  array(3648, 0, 3637, 3618, 3623)
		)
	  ),
	  array(
		"name" => "sara ia + wo waen",
		"thai" => array(3626,3619,3632,3648,3629,3637,3618,32,43,32,3623),
		"rtgs" => "ieow",
		"use" => "iow",
		"ex" => "io in \"trio\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3637, 3618, 3623)
		)
	  ),




	  array(
		"name" => "sara a + yo yak",
		"thai" => array(3626,3619,3632,3629,3632,32,43,32,3618),
		"rtgs" => "ai",
		"use" => "ai",
		"ex" => "i in \"hi\"",
		"short" => 1,
		"long" => 0,
		"short_open_live" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3633, 3618)
		)
	  ),
	  array(
		"name" => "sara a + yo yak",
		"thai" => array(3626,3619,3632,3629,3634,32,43,32,3618),
		"rtgs" => "ai",
		"use" => "aai",
		"ex" => "ye in \"bye\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3634, 3618)
		)
	  ),



	  array(
		"name" => "sara ai",
		"thai" => array(3626,3619,3632,3652,3629), // ???
		"rtgs" => "ai",
		"use" => "ai",
		"ex" => "i in \"hi\"",
		"short" => 1,
		"long" => 0,
		"short_open_live" => 1,
		"always_long_for_tone" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3651, 0), 
		  array(3652, 0), 
		  array(3652, 0, 3618)
		)
	  ),

	  // PLACEHOLDER FOR LONG VERSION
	  array(
		"name" => "sara ai",
		"thai" => array(3626,3619,3632,3652,3629), // ???
		"rtgs" => "ai",
		"use" => "ai",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 0,
		"patterns" => array(
		  array(3651, 0), 
		  array(3652, 0), 
		  array(3652, 0, 3618)
		)
	  ),


	  array(
		"name" => "sara o + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3634,3632,32,43,32,3618),
		"rtgs" => "oi",
		"use" => "oi",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(0, 3655, 3629, 3618)
		)
	  ),
	  array(
		"name" => "sara o + yo yak",
		"thai" => array(3626,3619,3632,3629,3629,32,43,32,3618),
		"rtgs" => "oi",
		"use" => "oi",
		"ex" => "oy in \"boy\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3629, 3618)
		)
	  ),

	  // SHORT VERSION AGAIN
	  array(
		"name" => "sara o + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3634,3632,32,43,32,3618),
		"rtgs" => "oi",
		"use" => "oi",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"active" => 0,
		"patterns" => array(
		  array(3650, 0, 3618)
		)
	  ),
	  array(
		"name" => "sara o + yo yak",
		"thai" => array(3626,3619,3632,3650,3629,32,43,32,3618),
		"rtgs" => "oi",
		"use" => "oi",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3650, 0, 3618)
		)
	  ),



	  array(
		"name" => "sara u + yo yak",
		"thai" => array(3626,3619,3632,3629,3640,32,43,32,3618),
		"rtgs" => "ui",
		"use" => "ui",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"short_open_live" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3640, 3618)
		)
	  ),

	  // LONG VERSION DUPLICATE
	  array(
		"name" => "sara u + yo yak",
		"thai" => array(3626,3619,3632,3629,3641,32,43,32,3618),
		"rtgs" => "ui",
		"use" => "ui",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 0,
		"patterns" => array(
		  array(0, 3640, 3618)
		)
	  ),


	  // SHORT PLACEHOLDER
	  array(
		"name" => "sara oe + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3629,3632,32,43,32,3618),
		"rtgs" => "oei",
		"use" => "oey",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"active" => 0,
		"patterns" => array(
		  array(3648, 0, 3618)
		)
	  ),
	  array(
		"name" => "sara oe + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3629,32,43,32,3618),
		"rtgs" => "oei",
		"use" => "oey",
		"ex" => "u in \"burn\" + y in \"boy\"",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3618)
		)
	  ),

	  array( // SHORT VERSION
		"name" => "sara ua + yo yak",
		"thai" => array(3626,3619,3632,3629,3633,3623,3632,32,43,32,3618),
		"rtgs" => "uai",
		"use" => "uay",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"short_open_live" => 1,
		"active" => 1,
		"patterns" => array(
		  array(0, 3623, 3618)
		)
	  ),
	  array(
		"name" => "sara ua + yo yak",
		"thai" => array(3626,3619,3632,3629,3633,3623,32,43,32,3618),
		"rtgs" => "uai",
		"use" => "uay",
		"ex" => "uoy in \"buoy\"",
		"short" => 0,
		"long" => 1,
		"active" => 0,
		"patterns" => array(
		  array(0, 3623, 3618)
		)
	  ),


	  array( // SHORT VERSION
		"name" => "sara uea + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3639,3629,3632,32,43,32,3618),
		"rtgs" => "ueai",
		"use" => "uai",
		"ex" => "",
		"short" => 1,
		"long" => 0,
		"short_open_live" => 1,
		"active" => 0,
		"patterns" => array(
		  array(3648, 0, 3639, 3629, 3618)
		)
	  ),
	  array(
		"name" => "sara uea + yo yak",
		"thai" => array(3626,3619,3632,3648,3629,3639,3629,32,43,32,3618),
		"rtgs" => "ueai",
		"use" => "uai",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3648, 0, 3639, 3629, 3618)
		)
	  ),



	  array(
		"name" => "sara am",
		"thai" => array(3626,3619,3632,3629,3635),
		"rtgs" => "am",
		"use" => "am",
		"ex" => "um in \"sum\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"always_long_for_tone" => 1,
		"short_open_live" => 1,
		"patterns" => array(
		  array(0, 3635)
		)
	  ),

	  // LONG VERSION FOR SARA AM
	  array(
		"name" => "sara am",
		"thai" => array(3626,3619,3632,3629,3635),
		"rtgs" => "aam",
		"use" => "aam",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"always_long_for_tone" => 1,
		"active" => 0,
		"patterns" => array(
		  array(0, 3635)
		)
	  ),

	  array(
		"name" => "rue",
		"thai" => array(0=>3620),
		"rtgs" => "rue",
		"use" => "ru",
		"ex" => "rew in \"grew\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3620)
		)
	  ),
	  array(
		"name" => "rue",
		"thai" => array(0=>3620),
		"rtgs" => "rue",
		"use" => "ri",
		"ex" => "ry in \"angry\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3620)
		)
	  ),
	  array(
		"name" => "rue",
		"thai" => array(3620,3653),
		"rtgs" => "rue",
		"use" => "reu",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3620, 3653)
		)
	  ),

	  array(
		"name" => "lue",
		"thai" => array(0=>3622),
		"rtgs" => "lue",
		"use" => "lu",
		"ex" => "lew in \"blew\"",
		"short" => 1,
		"long" => 0,
		"active" => 1,
		"patterns" => array(
		  array(3622)
		)
	  ),
	  array(
		"name" => "lue",
		"thai" => array(3622,3653),
		"rtgs" => "lue",
		"use" => "luu",
		"ex" => "",
		"short" => 0,
		"long" => 1,
		"active" => 1,
		"patterns" => array(
		  array(3622, 3653)
		)
	  )
	);

	public static function transliterateArray($codes, $syllable_separator = '-', $word_separator = ' ') {
		$s = '';
		$a = array();
		$c = null;
		$len = count($codes);
		for ($i = 0; $i < $len; $i++) {
			$s .= mb_chr($codes[$i]);
			$c = ThaiSymbol::fromCharCode($codes[$i]);
			if ($c) {
				array_push($a, $c);
			} else {
				array_push($codes[$i]);
			}
			$c = null;
		}
		return self::transliterateSegment($s, $a, 0, $len, $syllable_separator, $word_separator);
	}


	//
	// TRANSLITERATE OTHER CHARACTERS
	//
	public static function transliterateSingle($c, $return_as_string = false) {
		//throw new Exception($c);

		if (!$c) {
			return null;
		}
		$tc = null;
		if (is_string($c)) {
			$tc = ThaiSymbol::fromCharCode(mb_ord($c));
		} else if (!is_object($c)) {
			$tc = ThaiSymbol::fromCharCode($c);
		} else if ($c->code) {
			$tc = $c;
		}
		if (!$tc) {
			if ($return_as_string) {
				return $c;
			}
		}
		$c = $tc;
		$tc = null;

		if (is_object($c) && $c->code && $c->thai_name) {
			$a = self::charify($c->thai_name);
			if ($return_as_string) {
				return self::transliterate($c->thai_name, $a, 0, mb_strlen($c->thai_name));
			} else {
				return self::transliterateSegment($c->thai_name, $a, 0, mb_strlen($c->thai_name));
			}
		} else {
			if ($return_as_string) {
				return null;//$c;
			} else {
				return null;
			}
		}
	}

}

/**
 * Must initialize the cluster patterns.
 */
ThaiToRomanTransliterator::initClusterPatterns();



/**
 * This was used for placing a bar over the Roman letters.
 * Placed here because to initialize with functions.
 *
 * @deprecated    Moved to index 6 of each branch of $tone_tab
 */
ThaiToRomanTransliterator::$long_tab = array(
	'a' => mb_chr(257),
	'e' => mb_chr(275),
	'i' => mb_chr(299),
	'o' => mb_chr(248), //mb_chr(333),
	'u' => mb_chr(363),
	'A' => mb_chr(256),
	'E' => mb_chr(274),
	'I' => mb_chr(298),
	'O' => mb_chr(216), //mb_chr(332),
	'U' => mb_chr(362),
	mb_chr(248) => mb_chr(511),
	mb_chr(216) => mb_chr(510),
);



/**
 * These are letters which always occur at the end of a syllable.
 * Used for reducing problem space of transliteration.
 *
 * Certain words could also be added,
 *    "krap" and "krap-pom" are unique in that no other words start with "krap"
 */
ThaiToRomanTransliterator::$SURE_ENDERS = array(
    ThaiAlphabet::$SARA_A->code => true,
    ThaiAlphabet::$SARA_AM->code => true
);

/**
 * These are letters which always occur at the beginning of a syllable.
 * Used for reducing problem space of transliteration.
 */
ThaiToRomanTransliterator::$SURE_STARTS = array(
    ThaiAlphabet::$SARA_E->code => true,
    ThaiAlphabet::$SARA_AE->code => true,
    ThaiAlphabet::$SARA_O->code => true,
    ThaiAlphabet::$SARA_AI_MAIMUAN->code => true,
    ThaiAlphabet::$SARA_AI_MAIMALAI->code => true
);


/**
 * Special spellings HO NAM
 * from: https://en.wikipedia.org/wiki/Thai_script
 * Ho nam - a silent, high-class ho-hip"leads" low-class nasal stops and non-plosives which have no corresponding high-class phonetic match, 
 * into the tone properties of a high-class consonant. In polysyllabic words, an initial mid- or high-class consonant with an implicit vowel
 * similarly "leads" these same low-class consonants into the higher class tone rules, with the tone marker borne by the low-class consonant.
 */
ThaiToRomanTransliterator::$HO_NAM = array(
    ThaiAlphabet::$NGO_NGU->code => true,
    ThaiAlphabet::$YO_YING->code => true,
    ThaiAlphabet::$NO_NU->code => true,
    ThaiAlphabet::$MO_MA->code => true,
    ThaiAlphabet::$LO_LING->code => true,
    ThaiAlphabet::$WO_WAEN->code => true,
    ThaiAlphabet::$YO_YAK->code => true,
    ThaiAlphabet::$RO_RUA->code => true
);


} ?>