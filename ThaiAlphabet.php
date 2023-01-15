<? if (!class_exists('ThaiAlphabet')) {


/**
 * ThaiAlphabet provides general helper methods for client code to use.
 *
 *
 * From Wikipedia:
 *   Although commonly referred to as the "Thai alphabet", the script is in fact not a true alphabet
 *   but an abugida, a writing system in which the full characters represent consonants with diacritical
 *   marks for vowels; the absence of a vowel diacritic gives an implied 'a' or 'o'.
 *
 *   Consonants are written horizontally from left to right, and vowels following a consonant in speech 
 *   are written above, below, to the left or to the right of it, or a combination of those.
 */
class ThaiAlphabet {

	/**
	 * Minimum Thai unicode codepoint
	 * @var integer
	 */
	public static $TH_MIN_CODE = 0x0E01;

	/**
	 * Maximum Thai unicode codepoint
	 * @var integer
	 */
	public static $TH_MAX_CODE = 0x0E5B;

	/**
	 * Converts the Thai chars in the input string to its transliteration 
	 * using the spelling table in ThaiToRomanTransliterator::setSpellings()
	 *
	 * Romanization here is a misnomer, because the spelling table does not
	 * need to be derived from the Roman alphabet, but it was not specifically
	 * designed for other alphabets.
	 *
	 * @param string $s  The text to transliterate (Romanize)
	 *
	 * @return string A transliterated version of the Thai input
	 */
	public static function romanize($s) {
		return ThaiToRomanTransliterator::transliterate($s);
	}













	/**
	 * Returns if string contains Thai.  Helper function for client code.
	 *
	 * @param string $s string to test if it contains Thai.
	 *
	 * @return bool whether the given string contains Thai.
	 */
	public static function has_thai($s) {
		$n = mb_strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			if (ThaiSymbol::isCodeLetter(mb_ord($c))) {
				return true;
			}
		}
		return false;
	}




	/**
	 * Helper function for client code.
	 *
	 * @return int|false The index of the first Thai letter found, or false.
	 */
	public static function thai_index($s, $from_index = 0) {
		$n = mb_strlen($s);
		if ($from_index >= 0) {
			for ($i = $from_index; $i < $n; $i++) {
				$c = mb_substr($s, $i, 1);
				if (ThaiSymbol::isCodeLetter(mb_ord($c))) {
					return $i;
				}
			}
		} else {
			for ($i = $n + $from_index; $i >= 0; $i--) {
				$c = mb_substr($s, $i, 1);
				if (ThaiSymbol::isCodeLetter(mb_ord($c))) {
					return $i;
				}
			}
		}
		return false;
	}


	/**
	 * Returns if string is all Thai.  Helper function for client code.
	 *
	 * @return  bool  Whether the string is all thai or not.
	 */
	public static function all_thai($s, $allow_ascii_punctuation = false, $return_as_fraction = false) {
		$n = mb_strlen($s);
		$not_n = 0;
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			$o = mb_ord($c);
			if (!ThaiSymbol::isCodeLetter($o)) {
				if ($return_as_fraction) return false;
				$not_n++;
			}
			if ($allow_ascii_punctuation) {
				if ($o > 126) { // NOT IN ASCII
					if ($return_as_fraction) return false;
					$not_n++;
				}
				if ($o >= 97 && $o <= 122) {
					if ($return_as_fraction) return false; // UPPERCASE ALPHA
					$not_n++;
				}
				if ($o >= 65 && $o <= 90) {
					if ($return_as_fraction) return false; // UPPERCASE ALPHA
					$not_n++;
				}
			} else {
				if ($return_as_fraction) return false;
				$not_n++;
			}
		}
		if ($return_as_fraction) return true;
		return false;
	}






//-----------------------------------------------------------------
// HELPER FUNCTIONS FOR WORKING WITH THE THAI ALPHABET
//-----------------------------------------------------------------



	/**
	 * Just filters out special characters.
	 */
	public static function filter_text($s) {
		$t = null;
		$n = mb_strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			$o = mb_ord($c);
			if ($o == 32 || $o == 45 || ctype_alnum($c) || $o > 127) {
				if ($t !== null) {
					$t .= $c;
				}
			} else {
				if ($t === null) {
					if ($i > 0) {
						$t = mb_substr($s, 0, $i) . ' ';
					} else {
						$t = ' ';
					}
				}
			}
		}
		if ($t === null) {
			return $s; // all thai
		} else {
			return $t;
		}
	}




	/**
	 * Filters out only Thai characters.  Optional parameter to allow ascii punctuation chars.
	 *
	 * @return  string  Only the Thai characters from the input.
	 */
	public static function filter_thai($s, $allow_ascii_punctuation = false) {
		$t = null;
		$n = mb_strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			$o = mb_ord($c);
			$is_thai = ThaiSymbol::isCodeLetter($o);
			if (!$is_thai && $allow_ascii_punctuation) {
				if ($o > 126) { // NOT IN ASCII
				} else if ($o >= 97 && $o <= 122) { // LOWERCASE ALPHA
				} else if ($o >= 65 && $o <= 90) { // UPPERCASE ALPHA
				} else {
					$is_thai = true;  // PUNCTUATION AND NUMBERS
				}
			}
			if ($is_thai) {
				if ($t !== null) {
					$t .= $c;
				}
			} else {
				if ($t === null) {
					if ($i > 0) {
						$t = mb_substr($s, 0, $i);
					} else {
						$t = '';
					}
				}
			}

		}
		if ($t === null) {
			return $s; // all thai
		} else {
			return $t;
		}
	}



	/**
	 * Mandarin minimum unicode codepoint (for client reference).
	 * @var integer
	 */
	public static $ZH_MIN_CODE = 0x4E00;

	/**
	 * Mandarin maximum unicode codepoint (for client reference).
	 * @var integer
	 */
	public static $ZH_MAX_CODE = 0x9FFF;


	/**
	 * Filters only Chinese chars.
	 *
	 * @return string Only the Chinese chars from the input.
	 */
	public static function filter_chinese($s) {
		$t = null;
		$n = mb_strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			$o = mb_ord($c);
			if ($o >= self::$ZH_MIN_CODE && $o <= self::$ZH_MAX_CODE) {
				if ($t !== null) {
					$t .= $c;
				}
			} else {
				if ($t === null) {
					if ($i > 0) {
						$t = mb_substr($s, 0, $i);
					} else {
						$t = '';
					}
				}
			}
		}
		if ($t === null) {
			return $s; // all thai
		} else {
			return $t;
		}
	}


	/**
	 * Helper function to filter everything except whitespace
	 *
	 * @return  string  Only chars without whitespace.
	 */
	public static function filter_non_ws($s) {
		$t = null;
		$n = mb_strlen($s);
		for ($i = 0; $i < $n; $i++) {
			$c = mb_substr($s, $i, 1);
			if (!self::is_space($c)) {
				if ($t !== null) {
					$t .= $c;
				}
			} else {
				if ($t === null) {
					if ($i > 0) {
						$t = mb_substr($s, 0, $i);
					} else {
						$t = '';
					}
				}
			}
		}
		if ($t === null) {
			return $s;
		} else {
			return $t;
		}
	}


	/**
	 * Helper function to determine if a char is space or not
	 *
	 * @param  string  $c The char to test.
	 * @param  string  $e A string of additional chars that will count as whitespace.
	 *
	 * @return  bool  If specified char is space.
	 */
	public static function is_space($c, $e = null) {
		//if ($c === '0') {
		//    if ($e) {
		//        return mb_strpos($e, '0') !== false;
		//    }
		//    return false;
		//} else
		if ($c === false || $c === null || mb_strlen($c) === 0) {
			//return true;
			throw new Exception('is_space(' . var_export($c, true) . ') error');
		}
		$o = mb_ord($c);
		if ($o <= 32) {
			return true;
		} else {
			switch($o) {
			  case 160:
			  case 133:
			  case 5760:
			  case 6158:   // mongolian vowel separator
			  case 8232:   // general line separators
			  case 8233:   // general paragraph separator
			  case 8239:   // general narrow no-breaking space
			  case 8287:   // mathematical space (medium)
			  case 8288:   // general word joiner
			  case 12288:  // CJK space
			  case 65279:  // arabic invisible space
				return true;
			  default:
				break;
			}
		}
		if ($e) {
			//$n = mb_strlen($e);
			//for ($i = 0; $i < $n; $i++) {
			//    if (mb_ord(mb_substr($e, $i, 1)) == mb_ord($c)) {
			//        return true;
			//    }
			//}
			if (mb_strpos($e, $c) !== false) {
				return true;
			}
		}
		if ($o >= 8192 && $o <= 8205) {
			return true;  // various common
		}
		return false;
	}


	/**
	 * Helper function to determine if char $c is in string $e
	 */
	public static function is_in($c, $e) {
		//if ($c === '0') {
		//    if ($e) {
		//        return mb_strpos($e, '0') !== false;
		//    }
		//    return false;
		//} else
		if ($c === false || $c === null || mb_strlen($c) === 0) {
			//return true;
			throw new Exception('is_in(' . var_export($c, true) . ') error');
		}
		return (is_string($e) && mb_strpos($e, $c) !== false);
	}


	/**
	 * Helper function to do a multi-byte explode for some older installations.
	 */
	public static function mb_explode($strings = false, $s = null, $chars = null) {

		$a = array();
		if (!$s) return $a;
		$n = mb_strlen($s);
		if (!$n) return $a;
		$head = 0;
		$tail = 0;

		if ($strings === null) {
			// break by whitespace + $chars only
			while ($head < $n) {
				while ($head < $n && self::is_space(mb_substr($s, $head, 1), $chars)) {
					$head++;
				}
				$tail = $head;
				if ($head < $n) {
					$head++;
					while ($head < $n && !self::is_space(mb_substr($s, $head, 1), $chars)) {
						$head++;
					}
					$sub = self::mb_trim(mb_substr($s, $tail, $head - $tail));
					if (mb_strlen($sub)) {
						array_push($a, $sub);
					}
				} else {
					// ended in empty space...
					break;
				}
			}
			return $a;

		} else if ($strings) {
			if (is_string($strings)) {
				// it's a single separator
				if (mb_strlen($strings) == 1) {
					// just one char?  append to $e
					$chars .= $strings;
					$strings = false;
				} else {
					$strings = array(0 => $strings);
				}
			}
			if ($strings && is_array($strings)) {
				// put single chars into $chars
				// split $s by multi-char entries
				for ($i = 0; $i < count($strings); $i++) {
					if (mb_strlen($strings[$i]) == 1) {
						$chars .= $strings[$i];
					} else if (is_string($strings[$i])) {
						while ($j = mb_strpos($s, $strings[$i])) {
							throw new Exception('NOT IMPLEMENTED FOR STRINGS - mb_explode(' . var_export($strings, true) . ', ' . var_export($s, true) . ', ' . var_export($chars, true) . ')');
						}
					}
				}
			}
		}

		while ($head < $n) {
			while ($head < $n && self::is_in(mb_substr($s, $head, 1), $chars)) {
				$head++;
			}
			$tail = $head;
			if ($head < $n) {
				$head++;
				while ($head < $n && !self::is_in(mb_substr($s, $head, 1), $chars)) {
					$head++;
				}
				$sub = mb_substr($s, $tail, $head - $tail);
				if (mb_strlen($sub)) {
					array_push($a, $sub);
				}
			} else {
				// ended in empty space...
				break;
			}
		}

		return $a;
	}


	/**
	 * Separates tokens in string by whitespace to an array.
	 */
	public static function mb_ws_explode($s, $extra = null) {
		return self::mb_explode(null, $s, $extra);
	}

	/**
	 * Helper function to trim whitespace.
	 * Not strictly needed, but there are some whitespace characters other than ' '.
	 */
	public static function mb_trim($s, $extra = null) {
		if ($s === null || $s === false || !is_string($s)) {
			return $s;
		}
		$tail = mb_strlen($s) - 1;
		while ($tail >= 0 && self::is_space(mb_substr($s, $tail, 1), $extra)) {
			$tail--;
		}
		$tail++;
		if ($tail <= 0) {
			return '';
		}
		$head = 0;
		while ($head < $tail && self::is_space(mb_substr($s, $head, 1), $extra)) {
			$head++;
		}
		if ($head >= $tail) {
			return '';
		} else {
			return trim(mb_substr($s, $head, $tail - $head));
		}
	}

	/**
	 * Helper function to left-trim a string.
	 */
	public static function mb_ltrim($s, $extra = null) {
		$tail = mb_strlen($s);
		$head = 0;
		while ($head < $tail && self::is_space(mb_substr($s, $head, 1), $extra)) {
			$head++;
		}
		if ($head >= $tail) {
			return '';
		} else {
			return ltrim(mb_substr($s, $head, $tail - $head));
		}
	}

	/**
	 * Helper function to right-trim a string.
	 */
	public static function mb_rtrim($s, $extra = null) {
		$tail = mb_strlen($s) - 1;
		while ($tail >= 0 && self::is_space(mb_substr($s, $tail, 1), $extra)) {
			$tail--;
		}
		$tail++;
		if ($tail <= 0) {
			return '';
		}
		$head = 0;
		if ($head >= $tail) {
			return '';
		} else {
			return rtrim(mb_substr($s, $head, $tail - $head));
		}
	}


	/**
	 * Debug function to return a string of the char-codes of a string.
	 */
	public static function mb_codes($s) {
		$len = mb_strlen($s);
		$t = '';
		for ($i = 0; $i < $len; $i++) {
			$t .= '[' . mb_ord(mb_substr($s, $i, 1)) . ']';
		}
		return $t;
	}






	/**
	 * Getter method to return a ThaiSymbol instance by name
	 */
	public static function getSymbolByName($name) {
		$name = mb_strToLower($name);
		for ($c = ThaiAlphabet::$KO_KAI->code; $c <= ThaiAlphabet::$HO_NOKHUK->code; $c++) {
			$o = ThaiSymbol::fromCharCode($c);
			if (mb_strToLower($o->name) == $name) {
				return $o;
			}
		}
		return null;
	}


	//------------------------------------------------------------
	// Declare Thai symbols here inside the class.
	// Initialized at end of class.
	//------------------------------------------------------------



	/**
	 * The symbol for Thai currency, the Baht (฿)
	 *
	 * @var ThaiSymbol
	 */
	public static $BAHT = null;


	/**
	 * Traditional digit for zero (๐)
	 *
	 * @var ThaiDigit
	 */
	public static $ZERO = null;

	/**
	 * Traditional digit for one (๑)
	 *
	 * @var ThaiDigit
	 */
	public static $ONE = null;

	/**
	 * Traditional digit for two (๒)
	 *
	 * @var ThaiDigit
	 */
	public static $TWO = null;

	/**
	 * Traditional digit for three (๓)
	 *
	 * @var ThaiDigit
	 */
	public static $THREE = null;

	/**
	 * Traditional digit for four (๔)
	 *
	 * @var ThaiDigit
	 */
	public static $FOUR = null;

	/**
	 * Traditional digit for five (๕)
	 *
	 * @var ThaiDigit
	 */
	public static $FIVE = null;

	/**
	 * Traditional digit for six (๖)
	 *
	 * @var ThaiDigit
	 */
	public static $SIX = null;

	/**
	 * Traditional digit for seven (๗)
	 *
	 * @var ThaiDigit
	 */
	public static $SEVEN = null;

	/**
	 * Traditional digit for eight (๘)
	 *
	 * @var ThaiDigit
	 */
	public static $EIGHT = null;

	/**
	 * Traditional digit for nine (๙)
	 *
	 * @var ThaiDigit
	 */
	public static $NINE = null;







	/**
	 * Mai ek tone mark character ( ่) has the shape of a "1" from Pali or Sanskrit.
	 * Spellings are complicated, easier for people to memorize the words, but usually the falling tone.
	 *
	 * @var ThaiToneMark
	 */
	public static $MAI_EK = null;

	/**
	 * Mai tho tone mark char ( ้) has the shape of a "2" from Pali or Sanskrit.
	 * Spellings are less complicated, normally denotes a falling tone.
	 *
	 * @var ThaiToneMark
	 */
	public static $MAI_THO = null;

	/**
	 * Mai tri tone mark ( ๊) has the shape of a "3" from Pali or Sanskrit.
	 * Makes a syllable have a high tone.
	 *
	 * @var ThaiToneMark
	 */
	public static $MAI_TRI = null;

	/**
	 * Mai chattawa ( ๋) has the shape of a "+", or "4" from Pali or Sanskrit.
	 * Always makes a syllable have a rising tone.
	 *
	 * @var ThaiToneMark
	 */
	public static $MAI_CHATTAWA = null;



/////////////
//------------------------------------------------------------
// INITIALIZE THAI Consonants
//------------------------------------------------------------


	/**
	 * Ko kai (ก) consonant - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KO_KAI = null;

	/**
	 * Kho khuai (ข) consonant - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KHO_KHAI = null;



	/**
	 * Kho khuat (ฃ) consonant (obsolete) - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KHO_KHUAT = null;

	/**
	 * Kho khwai (ค) consonant - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KHO_KHWAI = null;

	/**
	 * Kho khon (ฅ) consonant (obsolete) - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KHO_KHON = null;


	/**
	 * Kho ra-khang (ฆ) consonant - sounds like "K"
	 *
	 * @var ThaiConsonant
	 */
	public static $KHO_RA_KHANG = null;


	/**
	 * Ngo ngu (ง) consonant - sounds like "ING" without the "I".
	 *
	 * @var ThaiConsonant
	 */
	public static $NGO_NGU = null;

	/**
	 * Cho chan (จ) consonant - sounds like a "J" in American English
	 *
	 * @var ThaiConsonant
	 */
	public static $CHO_CHAN = null;


	/**
	 * Cho ching (ฉ) consonant - sounds like a "CH" in American English
	 *
	 * @var ThaiConsonant
	 */
	public static $CHO_CHING = null;


	/**
	 * Cho chang (ช) consonant - sounds like a "CH" in American English
	 *
	 * @var ThaiConsonant
	 */
	public static $CHO_CHANG = null;


	/**
	 * So so (ซ) consonant - sounds like "S"
	 *
	 * @var ThaiConsonant
	 */
	public static $SO_SO = null;


	/**
	 * Cho choe (ฌ) consonant - sounds like "CH"
	 *
	 * @var ThaiConsonant
	 */
	public static $CHO_CHOE = null;

	/**
	 * Yo ying (ญ) consonant - sounds like "Y" if at beginning, or "N" if in the middle or end
	 *
	 * @var ThaiConsonant
	 */
	public static $YO_YING = null;

	/**
	 * Do chada (ฎ) consonant - sounds like a "D"
	 *
	 * @var ThaiConsonant
	 */
	public static $DO_CHADA = null;

	/**
	 * To patak (ฏ) consonant - sounds like a mix between "D" and "TH", with emphasis on the "D"
	 *
	 * @var ThaiConsonant
	 */
	public static $TO_PATAK = null;

	/**
	 * Tho than (ฐ) consonant - sounds like a "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_THAN = null;

	/**
	 * Tho nangmontho (ฑ) consonant - sounds like a "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_NANGMONTHO = null;

	/**
	 * To phuthao (ฒ) consonant - sounds like a "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_PHUTHAO = null;

	/**
	 * No nen (ณ) consonant - sounds like a "N"
	 *
	 * @var ThaiConsonant
	 */
	public static $NO_NEN = null;

	/**
	 * Do dek (ด) consonant - sounds like a "D"
	 *
	 * @var ThaiConsonant
	 */
	public static $DO_DEK = null;

	/**
	 * To tao (ต) consonant - sounds like a mix between "D" and "TH", with emphasis on the "D"
	 *
	 * @var ThaiConsonant
	 */
	public static $TO_TAO = null;

	/**
	 * Tho thung (ถ) consonant - sounds like "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_THUNG = null;

	/**
	 * Tho thahan (ท) consonant - sounds like "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_THAHAN = null;

	/**
	 * Tho thong (ธ) consonant - sounds like "T"
	 *
	 * @var ThaiConsonant
	 */
	public static $THO_THONG = null;

	/**
	 * No nu (น) consonant - sounds like "N"
	 *
	 * @var ThaiConsonant
	 */
	public static $NO_NU = null;

	/**
	 * Bo baimai (บ) consonant - sounds like "B"
	 *
	 * @var ThaiConsonant
	 */
	public static $BO_BAIMAI = null;

	/**
	 * Po pla (ป) consonant - sounds like very strong "B" with lips initially between teeth
	 *
	 * @var ThaiConsonant
	 */
	public static $PO_PLA = null;

	/**
	 * Pho phung (ผ) consonant - sounds like a "P"
	 *
	 * @var ThaiConsonant
	 */
	public static $PHO_PHUNG = null;


	/**
	 * Fo fa (ฝ) consonant - sounds like an "F"
	 *
	 * @var ThaiConsonant
	 */
	public static $FO_FA = null;

	/**
	 * Pho phan (พ) consonant - sounds like a "P"
	 *
	 * @var ThaiConsonant
	 */
	public static $PHO_PHAN = null;

	/**
	 * Fo fan (ฟ) consonant - sounds like a "F"
	 *
	 * @var ThaiConsonant
	 */
	public static $FO_FAN = null;

	/**
	 * Pho samphao (ภ) consonant - sounds like a "P"
	 *
	 * @var ThaiConsonant
	 */
	public static $PHO_SAMPHAO = null;

	/**
	 * Mo ma (ม) consonant - sounds like an "M"
	 *
	 * @var ThaiConsonant
	 */
	public static $MO_MA = null;

	/**
	 * Yo yak (ย) "consonant" - sounds like a "Y"
	 *
	 * @var ThaiConsonant
	 */
	public static $YO_YAK = null;

	/**
	 * Ro rua (ร) consonant - sounds like a spanish "R", sometimes a soft "L", sometimes "r", sometimes with a trill
	 *
	 * @var ThaiConsonant
	 */
	public static $RO_RUA = null;

	/**
	 * Tua rue, or ru, (ฤ)  - sounds like an "R", independent vowel letter used to write Sanskrit
	 *
	 * @var ThaiConsonant
	 */
	public static $RU = null;

	/**
	 * Lo ling (ลิ) consonant - sounds like an "L"
	 *
	 * @var ThaiConsonant
	 */
	public static $LO_LING = null;

	/**
	 * Tua lue, or lu, (ฦ) - obsolete letter of the Thai alphabet; it is usually regarded as a vowel
	 *
	 * @var ThaiConsonant
	 */
	public static $LU = null;


	/**
	 * Wo waen (ว) consonant that sounds like "W" at beginning, or "Y" at the end of a syllable
	 *
	 * @var ThaiConsonant
	 */
	public static $WO_WAEN = null;

	/**
	 * So sala (ศ) consonant that sounds like an "S" at the beginning, or "T" at the end of a syllable
	 *
	 * @var ThaiConsonant
	 */
	public static $SO_SALA = null;

	/**
	 * So ru-si (ษ) consonant that sounds like an "S" at the beginning, or "T" at the end of a syllable
	 *
	 * @var ThaiConsonant
	 */
	public static $SO_RUSI = null;

	/**
	 * So suea (ส) consonant that sounds like an "S" at the beginning, or "T" at the end of a syllable
	 *
	 * @var ThaiConsonant
	 */
	public static $SO_SUEA = null;

	/**
	 * Ho hip (ห) consonant that sounds like an "H"
	 *
	 * @var ThaiConsonant
	 */
	public static $HO_HIP = null;

	/**
	 * Lo chu-la (ฬ)  consonant that sounds like an "L" at the beginning, or "N" at the end of a syllable
	 *
	 * @var ThaiConsonant
	 */
	public static $LO_CHULA = null;

	/**
	 * O ang (อ) sounds like an open "O"
	 *
	 * @var ThaiConsonant
	 */
	public static $O_ANG = null;

	/**
	 * Ho no-khuk (ฮ) consonant that sounds like an "H"
	 *
	 * @var ThaiConsonant
	 */
	public static $HO_NOKHUK = null;

	//------------------------------------------------------------
	// Vowels
	//------------------------------------------------------------

	/**
	 * Mai han-akat (◌ั) sounds like a short "A" like in "AHA" and placed on top of it's matching consonant(s)
	 *
	 * @var ThaiVowel
	 */
	public static $MAI_HAN_AKAT = null;

	/**
	 * Sara a (ะ) sounds like a short "A" like in "AHA" and placed to the right of it's matching consonant(s)
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_A = null;

	/**
	 * Sara aa (า)  sounds like a long "A" like in "father"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_AA = null;

	/**
	 * Sara am (ำ) sounds like "AM" in "shampoo", is sometimes long, short, or in between
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_AM = null;

	/**
	 * Sara i (◌ิ) sounds like a short "eee", or an "i" in Spanish "pico"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_I = null;


	/**
	 * Sara ii (◌ี) is a longer "ee" like in "see"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_II = null;

	/**
	 * Sara ue (◌ึ) is a short "eau" like in "beautiful", or a German ü
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_UE = null;

	/**
	 * Sara ue (◌ื) is a long "eau" like in "beautiful", or a German ü
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_UEE = null;

	/**
	 * Sara u (◌ุ) is a short "oo" like in "boot"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_U = null;

	/**
	 * Sara uu (◌ู) is a long "oo" like in "too"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_UU = null;


	// These vowels precede a consonant in visual order

	/**
	 * Sara e (เ) occurs left of the consonant and sounds like "a" in "Jake", but kind of different
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_E = null;

	/**
	 * Sara ae (แ) occurs left of the consonant and sounds more like "a" in "at"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_AE = null;

	/**
	 * Sara o (โ) is a vowel which sounds like "O" in "boat"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_O = null;

	/**
	 * Sara ai mai muan (ใ) is exactly the same as sara ai mai malai, but just looks prettier and used in 20 words
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_AI_MAIMUAN = null;

	/**
	 * Sara ai mai malai (ไ) precedes it's consonant and sounds like "I" in "hi"
	 *
	 * @var ThaiVowel
	 */
	public static $SARA_AI_MAIMALAI = null;


	/**
	 * The lakkhang yao character (ๅ) is specifically used to make tua rue long
	 *
	 * @var ThaiVowel
	 */
	public static $LAKKHANGYAO = null;

	/**
	 * In Thai this is written as an open circle above the consonant, known as nikkhahit (นิคหิต), from Pali niggahīta.
	 * The character nikhahit (ํ) is not used except sometimes used by mistake with sara aa (า).
	 *
	 * @var ThaiVowel
	 */
	public static $NIKHAHIT = null;

	/**
	 * Phinthu characters are used in syllabic spelling to indicate clusters
	 *
	 * @var ThaiModifier
	 */
	public static $PHINTHU = null;


	/**
	 * Thai character mai taikhu makes the syllable short
	 *
	 * @var ThaiVowel
	 */
	public static $MAITAIKHU = null;


	/**
	 * The Thai character thanthakhat, or karan, (◌์) makes a letter silent (or optional) and is used often with loanwords
	 *
	 * @var ThaiModifier
	 */
	public static $THANTHAKHAT = null;


	// Other symbols

	/**
	 * The Thai character maiyamok (ๆ) ambiguously indicates that the previous word or syllable should be repeated
	 *
	 * @var ThaiSymbol
	 */
	public static $MAIYAMOK = null;

	/**
	 * The pai-yan noi (ฯ) abbreviation character marks formal phrase shortened by convention
	 *
	 * @var ThaiSymbol
	 */
	public static $PAIYANNOI = null;

	/**
	 * Char angkhan kuu (๚) is obsolete and was formerly used to mark the end of a chapter
	 *
	 * @var ThaiSymbol
	 */
	public static $ANGKHANKHU = null;


	/**
	 * The character khomut, or sutnarai (๛) can mark end of a chapter or document, or story
	 *
	 * @var ThaiSymbol
	 */
	public static $KHOMUT = null;

	/**
	 * The yamakkan character (๎) is an obsolete symbol used to mark the beginning of consonant clusters
	 *
	 * @var ThaiModifier
	 */
	public static $YAMAKKAN = null;

	/**
	 * The fong man, or ta kai, (๏) obsolete character previously marked beginning of a sentence, paragraph, or stanza
	 *
	 * @var ThaiSymbol
	 */
	public static $FONGMAN = null;



	/**
	 * Reference hash of live, or sonorant, finals in Thai.  If the syllable contains no ending consonants, it is considered alive.
	 * Live finals are yet another way to determine the tone of a Thai syllable.
	 * (In unicode codepoints).
	 *
	 * @var array<integer,bool>
	 */
	public static $LIVE_FINALS = array(3591=>true, 3609=>true, 3603=>true, 3597=>true, 3619=>true, 3621=>true, 3628=>true, 3617=>true, 3618=>true, 3623=>true);


	/**
	 * Reference hash of dead finals in Thai.  If the syllable ends quickly, or abruptly, it is considered dead.
	 * Dead finals are yet another way to determine the tone of a Thai syllable.
	 * (In unicode codepoints).
	 *
	 * @var array  Map of codepoints to true
	 */
	public static $DEAD_FINALS = array(3585=>true, 3586=>true, 3588=>true, 3590=>true, 3604=>true, 3598=>true, 3605=>true, 3599=>true, 3592=>true, 3594=>true, 3596=>true, 3606=>true, 3607=>true, 3608=>true, 3602=>true, 3600=>true, 3624=>true, 3625=>true, 3626=>true, 3595=>true, 3610=>true, 3611=>true, 3614=>true, 3616=>true, 3615=>true);


	/**
	 * Inits the live final and dead final reference hashtables.
	 */
	public static function initLiveAndDeadFinals() {
		$codes = array_keys(ThaiAlphabet::$DEAD_FINALS);
		$len = count($codes);
		for ($i = 0; $i < $len; $i++) {
			if (isset(ThaiSymbol::$REGISTRAR[$codes[$i]])) {
				ThaiSymbol::$REGISTRAR[$codes[$i]]->is_dead_final = true;
			}
		}
		$codes = array_keys(ThaiAlphabet::$LIVE_FINALS);
		$len = count($codes);
		for ($i = 0; $i < $len; $i++) {
			if (isset(ThaiSymbol::$REGISTRAR[$codes[$i]])) {
				ThaiSymbol::$REGISTRAR[$codes[$i]]->is_live_final = true;
			}
		}
	}


	//---------------------------------------------------------------------------------
	// Reference tables grouping some Thai letter properties for initial consonants.
	//---------------------------------------------------------------------------------

	/**
	 * Reference hash containing Thai character codepoints of nasal initials, where air flows through the nose initially:
	 * ม (m), ณ (n),น (n) and ง (ŋ).	
	 * @var array  Map of codepoints to true
	 */
	public static $NASAL_INITIALS = array(3617=>true, 3603=>true, 3609=>true, 3591=>true);

	/**
	 * Reference hash containing Thai character codepoints of plosive initials:
	 * ป (p), พ (pʰ), ภ (pʰ), ผ (pʰ) and บ (b).  The first, ป, is the most plosive.
	 * @var array  Map of codepoints to true
	 */
	public static $PLOSIVE_INITIALS = array(3611=>true, 3612=>true, 3614=>true, 3616=>true, 3610=>true, 3599=>true, 3605=>true, 3600=>true, 3601=>true, 3602=>true, 3606=>true, 3607=>true, 3608=>true, 3598=>true, 3604=>true, 3585=>true, 3586=>true, 3587=>true, 3588=>true, 3589=>true, 3590=>true, 3629=>true);

	/**
	 * Reference hash ontaining Thai character codepoints of affricate initials, where the syllable
	 * begins with something like a "j" or "ch": จ (j or tɕ) and ฉ, ช, ฌ (ch or t͡ɕʰ).
	 * @var array  Map of codepoints to true
	 */
	public static $AFFRICATE_INITIALS = array(3592=>true, 3593=>true, 3594=>true, 3596=>true);

	/**
	 * Reference hash containing Thai character codepoints of frictive initials: ฝ, ฟ (f), ซ, ศ, ษ, ส (s), and ห, ฮ (h).
	 * @var array  Map of codepoints to true
	 */
	public static $FRICTIVE_INITIALS = array(3613=>true, 3615=>true, 3595=>true, 3624=>true, 3625=>true, 3626=>true, 3627=>true, 3630=>true);

	/**
	 * Reference hash containing the Thai character codepoint with a starting trill: ร (r) 
	 * which is sometimes pronounced with a trill but not always necessary.
	 * @var array  Map of codepoints to true
	 */
	public static $TRILL_INITIALS = array(3619=>true);

	/**
	 * Reference hash containing Thai approximate initials codepoints, 
	 * which are close but not quite there: ว (w) and ญ, ย (y).
	 * @var array  Map of codepoints to true
	 */
	public static $APPROXIMANT_INITIALS = array(3623=>true, 3597=>true, 3618=>true);

	/**
	 * Reference hash containing the Thai characters starting with lateral approximate initials: ล and ฬ (L).
	 * @var array  Map of codepoints to true
	 */
	public static $LATERAL_APPROXIMANT_INITIALS = array(3621=>true, 3628=>true);

	/**
	 * Reference hash containing the Thai character codepoints for bilabial initials - two lips:
	 * ม (m), ป (p), ผ, พ, ภ (pʰ), บ (b) and ว (w).
	 * @var array  Map of codepoints to true
	 */
	public static $BILABIAL_INITIALS = array(3611=>true, 3612=>true, 3614=>true, 3616=>true, 3610=>true, 3617=>true, 3623=>true);

	/**
	 * Reference hash containing the Thai codepoints for labio-dental initials:
	 * ฝ and ฟ (f).
	 * @var array  Map of codepoints to true
	 */
	public static $LABIO_DENTAL_INITIALS = array(3613=>true, 3615=>true);

	/**
	 * Reference hash containing the Thai codepoints for alveolar initials, tongue close to or touching the superior alveolar ridge:
	 * ณ, น (n), ฏ, ต (t), ฐ, ฑ, ฒ, ถ, ท, ธ (tʰ), ฎ, ด (d), ซ, ศ, ษ, ส (s), ร (r), and ล, ฬ (L).
	 * @var array  Map of codepoints to true
	 */
	public static $ALVEOLAR_INITIALS = array(3603=>true, 3609=>true, 3599=>true, 3605=>true, 3600=>true, 3601=>true, 3602=>true, 3606=>true, 3607=>true, 3608=>true, 3598=>true, 3604=>true, 3595=>true, 3624=>true, 3625=>true, 3626=>true, 3619=>true, 3621=>true, 3628=>true);

	/**
	 * Reference hash containing Thai codepoints for alveolo palatal initials:
	 * จ (j, tɕ), ฉ,  ช,  ฌ  (ch, t͡ɕʰ).
	 * @var array  Map of codepoints to true
	 */
	public static $ALVEOLO_PALATAL_INITIALS = array(3592=>true, 3593=>true, 3594=>true, 3596=>true);

	/**
	 * Reference hash containing Thai palatal initials: ญ, ย (y).
	 * @var array  Map of codepoints to true
	 */
	public static $PALATAL_INITIALS = array(3597=>true, 3618=>true);

	/**
	 * Reference hash containing Thai codepoints for velar initials: ก (g) ข, ฃ, ค, ฅ, ฆ (k), ง (ŋ).
	 * @var array  Map of codepoints to true
	 */
	public static $VELAR_INITIALS = array(3591=>true, 3585=>true, 3586=>true, 3587=>true, 3588=>true, 3589=>true, 3590=>true);

	/**
	 * Reference hash containing Thai coodepoints for glottal initials: อ (ah), ห, ฮ (h).
	 * @var array  Map of codepoints to true
	 */
	public static $GLOTTAL_INITIALS = array(3629=>true, 3627=>true, 3630=>true);


	/**
	 * Reference hash containing the Thai codepoints for nasal finals: ม (m), ณ (n) ,น (n), ญ (n), ร (n), ล (n), ฬ (n), and ง (ŋ).
	 * @var array  Map of codepoints to true
	 */
	public static $NASAL_FINALS = array(3617=>true, 3603=>true, 3609=>true, 3597=>true, 3619=>true, 3621=>true, 3628=>true, 3591=>true);

	/**
	 * Reference hash containing the Thai codepoints for plosive finals: บ (p̚), ป (p̚), พ (p̚), ฟ (p̚), ภ (p̚), 
	  * จ (t̚), ช (t̚), ซ (t̚), ฌ (t̚), ฎ (t̚), ฏ (t̚), ฐ (t̚), ฑ (t̚), ฒ (t̚), ด (t̚), ต (t̚), ถ (t̚), ท (t̚), ธ (t̚), ศ (t̚), ษ (t̚), ส (t̚),
	 * ก (k̚), ข (k̚), ค (k̚), and ฆ (k̚).
	 * @var array  Map of codepoints to true
	 */
	public static $PLOSIVE_FINALS = array(3610=>true, 3611=>true, 3614=>true, 3615=>true, 3616=>true, 3592=>true, 3594=>true, 3595=>true, 3596=>true, 3598=>true, 3599=>true, 3600=>true, 3601=>true, 3602=>true, 3604=>true, 3605=>true, 3606=>true, 3607=>true, 3608=>true, 3624=>true, 3625=>true, 3626=>true, 3585=>true, 3586=>true, 3588=>true, 3590=>true);

	/**
	 * Reference hash containing the Thai codepoints for approximate finals: ว (w) and ย (y).
	 * @var array  Map of codepoints to true
	 */
	public static $APPROXIMANT_FINALS = array(3623=>true, 3618=>true);

	/**
	 * Reference hash containing the Thai codepoints for bilabial finals: ม (m), บ (p̚), ป (p̚), พ (p̚), ฟ (p̚), ภ (p̚), ว (w).
	 * @var array  Map of codepoints to true
	 */
	public static $BILABIAL_FINALS = array(3617=>true, 3610=>true, 3611=>true, 3614=>true, 3615=>true, 3616=>true, 3623=>true);

	/**
	 * Reference hash containing the Thai codepoints for alveolar finals: จ (t̚), ช (t̚), ซ (t̚), ฌ (t̚), ฎ (t̚), ฏ (t̚), ฐ (t̚), ฑ (t̚),
	  * ฒ (t̚), ด (t̚), ต (t̚), ถ (t̚), ท (t̚), ธ (t̚), ศ (t̚), ษ (t̚), ส (t̚),
	 * ก (k̚), ข (k̚), ค (k̚) and ฆ (k̚).
	 * @var array  Map of codepoints to true
	 */
	public static $ALVEOLAR_FINALS = array(3603=>true, 3609=>true, 3597=>true, 3619=>true, 3621=>true, 3628=>true, 3592=>true, 3594=>true, 3595=>true, 3596=>true, 3598=>true, 3599=>true, 3600=>true, 3601=>true, 3602=>true, 3604=>true, 3605=>true, 3606=>true, 3607=>true, 3608=>true, 3624=>true, 3625=>true, 3626=>true);

	/**
	 * Reference hash containing palatal finals: ย (y, j).
	 * @var array  Map of codepoints to true
	 */
	public static $PALATAL_FINALS = array(3618=>true);

	/**
	 * Reference hash containing velar finals: ก (k̚), ข (k̚), ค (k̚), ฆ (k̚), and ง (ŋ).
	 * @var array  Map of codepoints to true
	 */
	public static $VELAR_FINALS = array(3591=>true, 3585=>true, 3586=>true, 3588=>true, 3590=>true);





}




//------------------------------------------------------------
// INITIALIZE THAI SYMBOLS
//------------------------------------------------------------



// The symbol for Thai currency, the Baht
ThaiAlphabet::$BAHT				= new ThaiSymbol(0x0E3F, 'baht', 'Thai currency symbol', array(3610,3634,3607));


//------------------------------------------------------------
// INITIALIZE THAI DIGITS
//------------------------------------------------------------

// Traditional digit for zero
ThaiAlphabet::$ZERO				= new ThaiDigit(0x0E50, 'sun',   0, array(3624,3641,3609,3618,3660));

// Traditional digit for one
ThaiAlphabet::$ONE				= new ThaiDigit(0x0E51, 'nueng', 1, array(3627,3609,3638,3656,3591));

// Traditional digit for two
ThaiAlphabet::$TWO				= new ThaiDigit(0x0E52, 'song',  2, array(3626,3629,3591));

// Traditional digit for three
ThaiAlphabet::$THREE			= new ThaiDigit(0x0E53, 'sam',   3, array(3626,3634,3617));

// Traditional digit for four
ThaiAlphabet::$FOUR				= new ThaiDigit(0x0E54, 'si',    4, array(3626,3637,3656));

// Traditional digit for five
ThaiAlphabet::$FIVE				= new ThaiDigit(0x0E55, 'ha',    5, array(3627,3657,3634));

// Traditional digit for six
ThaiAlphabet::$SIX				= new ThaiDigit(0x0E56, 'hok',   6, array(3627,3585));

// Traditional digit for seven
ThaiAlphabet::$SEVEN			= new ThaiDigit(0x0E57, 'chet',  7, array(3648,3592,3655,3604));

// Traditional digit for eight
ThaiAlphabet::$EIGHT			= new ThaiDigit(0x0E58, 'paet',  8, array(3649,3611,3604));

// Traditional digit for nine
ThaiAlphabet::$NINE				= new ThaiDigit(0x0E59, 'kao',   9, array(3648,3585,3657,3634));


//------------------------------------------------------------
// INITIALIZE THAI TONE MARKS
//------------------------------------------------------------

// Mai ek tone mark (has the shape of a "1" from Pali or Sanskrit) - spellings are complicated, easier for people to memorize the words, but usually the falling tone.
ThaiAlphabet::$MAI_EK			= new ThaiToneMark(0x0E48, 'mai ek',       'changes tone to "low" tone, or "falling" tone if consonant is "low" class',  array(3652,3617,3657,3648,3629,3585));

// Mai tho tone mark (has the shape of a "2" from Pali or Sanskrit) - spellings are less complicated, normally denotes a falling tone.
ThaiAlphabet::$MAI_THO			= new ThaiToneMark(0x0E49, 'mai tho',      'changes tone to "falling" tone, or "high" tone if consonent is "low" class', array(3652,3617,3657,3650,3607));

// Mai tri tone mark (has the shape of a "3" from Pali or Sanskrit) - always makes a high tone.
ThaiAlphabet::$MAI_TRI			= new ThaiToneMark(0x0E4A, 'mai tri',      'changes tone to "high" tone',                                                array(3652,3617,3657,3605,3619,3637));

// Mai chattawa (has the shape of a "+", or "4" from Pali or Sanskrit) - akways makes a rising tone.
ThaiAlphabet::$MAI_CHATTAWA		= new ThaiToneMark(0x0E4B, 'mai chattawa', 'changes tone to "rising" tone',                                              array(3652,3617,3657,3592,3633,3605,3623,3634));



//------------------------------------------------------------
// INITIALIZE THAI Consonants
//------------------------------------------------------------

// Ko kai (ก) consonant - sounds like "K"
ThaiAlphabet::$KO_KAI			= new ThaiConsonant(0x0E01, 'ko kai',       'chicken',        'k',  'k',  0, 'g',  'k');

// Kho khuai (ข) consonant - sounds like "K"
ThaiAlphabet::$KHO_KHAI			= new ThaiConsonant(0x0E02, 'kho khai',     'egg',            'kh', 'k',  1, 'k',  'k');

// Kho khuat (ฃ) consonant (obsolete) - sounds like "K"
ThaiAlphabet::$KHO_KHUAT		= new ThaiConsonant(0x0E03, 'kho khuat',    'bottle',         'kh', 'k',  1, 'k',  '');

// Kho khwai (ค) consonant - sounds like "K"
ThaiAlphabet::$KHO_KHWAI		= new ThaiConsonant(0x0E04, 'kho khwai',    'buffalo',        'kh', 'k', -1, 'k',  'k');

// Kho khon (ฅ) consonant (obsolete) - sounds like "K"
ThaiAlphabet::$KHO_KHON			= new ThaiConsonant(0x0E05, 'kho khon',     'person',         'kh', 'k', -1, 'k',  '');

// Kho ra-khang (ฆ) consonant - sounds like "K"
ThaiAlphabet::$KHO_RA_KHANG		= new ThaiConsonant(0x0E06, 'kho ra-khang', 'bell',           'kh', 'k', -1, 'k',  'k');

// Ngo ngu (ง) consonant - sounds like "ING" without the "I".
ThaiAlphabet::$NGO_NGU			= new ThaiConsonant(0x0E07, 'ngo ngu',      'snake',          'ng', 'ng',-1, 'ng', 'ng');

// Cho chan (จ) consonant - sounds like a "J" in American English
ThaiAlphabet::$CHO_CHAN			= new ThaiConsonant(0x0E08, 'cho chan',     'plate',          'ch', 't',  0, 'zh', 't');

// Cho ching (ฉ) consonant - sounds like a "CH" in American English
ThaiAlphabet::$CHO_CHING		= new ThaiConsonant(0x0E09, 'cho ching',    'cymbals',        'ch', '',   1, 'ch', '' );

// Cho chang (ช) consonant - sounds like a "CH" in American English
ThaiAlphabet::$CHO_CHANG		= new ThaiConsonant(0x0E0A, 'cho chang',    'elephant',       'ch', 't', -1, 'ch', 't');

// So so (ซ) consonant - sounds like "S"
ThaiAlphabet::$SO_SO			= new ThaiConsonant(0x0E0B, 'so so',        'chain',          's',  't', -1, 's',  's');

// Cho choe (ฌ) consonant - sounds like "CH"
ThaiAlphabet::$CHO_CHOE			= new ThaiConsonant(0x0E0C, 'cho choe',     'tree',           'ch', '',  -1, 'ch', '' );

// Yo ying (ญ) consonant - sounds like "Y" if at beginning, or "N" if in the middle or end
ThaiAlphabet::$YO_YING			= new ThaiConsonant(0x0E0D, 'yo ying',      'woman',          'y',  'n', -1, 'y',  'n');

// Do chada (ฎ) consonant - sounds like a "D"
ThaiAlphabet::$DO_CHADA			= new ThaiConsonant(0x0E0E, 'do cha-da',    'headdress',      'd',  't',  0, 'd',  't');

// To patak (ฏ) consonant - sounds like a mix between "D" and "TH", with emphasis on the "D"
ThaiAlphabet::$TO_PATAK			= new ThaiConsonant(0x0E0F, 'to pa-tak',    'goad, javelin',  't',  't',  0, 'dt', 't');

// Tho than (ฐ) consonant - sounds like a "T"
ThaiAlphabet::$THO_THAN			= new ThaiConsonant(0x0E10, 'tho than',     'pedestal',       'th', 't',  1, 't',  't');

// Tho nangmontho (ฑ) consonant - sounds like a "T"
ThaiAlphabet::$THO_NANGMONTHO	= new ThaiConsonant(0x0E11, 'tho montho',   'Montho',         'th', 't', -1, 't',  '');

// To phuthao (ฒ) consonant - sounds like a "T"
ThaiAlphabet::$THO_PHUTHAO		= new ThaiConsonant(0x0E12, 'tho phu-thao', 'elder',          'th', 't', -1, 't',  '');

// No nen (ณ) consonant - sounds like a "N"
ThaiAlphabet::$NO_NEN			= new ThaiConsonant(0x0E13, 'no nen',       'samanera',       'n',  'n', -1, 'n',  'n');

// Do dek (ด) consonant - sounds like a "D"
ThaiAlphabet::$DO_DEK			= new ThaiConsonant(0x0E14, 'do dek',       'child',          'd',  't',  0, 'd',  't');

// To tao (ต) consonant - sounds like a mix between "D" and "TH", with emphasis on the "D"
ThaiAlphabet::$TO_TAO			= new ThaiConsonant(0x0E15, 'to tao',       'turtle',         't',  't',  0, 'dt', 't');

// Tho thung (ถ) consonant - sounds like "T"
ThaiAlphabet::$THO_THUNG		= new ThaiConsonant(0x0E16, 'tho thung',    'sack',           'th', 't',  1, 't',  't');

// Tho thahan (ท) consonant - sounds like "T"
ThaiAlphabet::$THO_THAHAN		= new ThaiConsonant(0x0E17, 'tho thahan',   'soldier',        'th', 't', -1, 't',  't');

// Tho thong (ธ) consonant - sounds like "T"
ThaiAlphabet::$THO_THONG		= new ThaiConsonant(0x0E18, 'tho thong',    'flag',           'th', 't', -1, 't',  't');

// No nu (น) consonant - sounds like "N"
ThaiAlphabet::$NO_NU			= new ThaiConsonant(0x0E19, 'no nu',        'mouse',          'n',  'n', -1, 'n',  'n');

// Bo baimai (บ) consonant - sounds like "B"
ThaiAlphabet::$BO_BAIMAI		= new ThaiConsonant(0x0E1A, 'bo baimai',    'leaf',           'b',  'p',  0, 'b',  'p');

// Po pla (ป) consonant - sounds like very strong "B" with lips initially between teeth
ThaiAlphabet::$PO_PLA			= new ThaiConsonant(0x0E1B, 'po pla',       'fish',           'p',  'p',  0, 'bp', 'p');

// Pho phung (ผ) consonant - sounds like a "P"
ThaiAlphabet::$PHO_PHUNG		= new ThaiConsonant(0x0E1C, 'pho phueng',   'bee',            'ph', '',   1, 'p',  '');

// Fo fa (ฝ) consonant - sounds like an "F"
ThaiAlphabet::$FO_FA			= new ThaiConsonant(0x0E1D, 'fo fa',        'lid',            'f',  '',   1, 'f',  '');

// Pho phan (พ) consonant - sounds like a "P"
ThaiAlphabet::$PHO_PHAN			= new ThaiConsonant(0x0E1E, 'pho phan',     'phan',           'ph', 'p', -1, 'p',  'p');

// Fo fan (ฟ) consonant - sounds like a "F"
ThaiAlphabet::$FO_FAN			= new ThaiConsonant(0x0E1F, 'fo fan',       'teeth',          'f',  'p', -1, 'f',  'v');

// Pho samphao (ภ) consonant - sounds like a "P"
ThaiAlphabet::$PHO_SAMPHAO		= new ThaiConsonant(0x0E20, 'pho sam-phao', 'junk',           'ph', 'p', -1, 'p',  'p');

// Mo ma (ม) consonant - sounds like an "M"
ThaiAlphabet::$MO_MA			= new ThaiConsonant(0x0E21, 'mo ma',        'horse',          'm',  'm', -1, 'm',  'm');

// Yo yak (ย) "consonant" - sounds like a "Y"
ThaiAlphabet::$YO_YAK			= new ThaiConsonant(0x0E22, 'yo yak',       'giant, yaksha',  'y',  '',  -1, 'y',  '');

// Ro rua (ร) consonant - sounds like a spanish "R", sometimes a soft "L", sometimes "r", sometimes with a trill
ThaiAlphabet::$RO_RUA			= new ThaiConsonant(0x0E23, 'ro ruea',      'boat',           'r',  'n', -1, 'r',  'n');

ThaiAlphabet::$RU				= new ThaiConsonant(0x0E24, 'ru',           '',               '',  '',   0, '',  ''); // � independent vowel letter used to write Sanskrit

// Lo ling (ลิ) consonant - sounds like "L"
ThaiAlphabet::$LO_LING			= new ThaiConsonant(0x0E25, 'lo ling',      'monkey',         'l',  'n', -1, 'l',  'n');
ThaiAlphabet::$LU				= new ThaiConsonant(0x0E26, 'lu',           '',               '',   '',   0, '',   ''); // � independent vowel letter used to write Sanskrit
ThaiAlphabet::$WO_WAEN			= new ThaiConsonant(0x0E27, 'wo waen',      'ring',           'w',  '',  -1, 'w',  '');
ThaiAlphabet::$SO_SALA			= new ThaiConsonant(0x0E28, 'so sala',      'pavilion, sala', 's',  't',  1, 's',  't');
ThaiAlphabet::$SO_RUSI			= new ThaiConsonant(0x0E29, 'so rue-si',    'hermit',         's',  't',  1, 's',  't');
ThaiAlphabet::$SO_SUEA			= new ThaiConsonant(0x0E2A, 'so suea',      'tiger',          's',  't',  1, 's',  't');
ThaiAlphabet::$HO_HIP			= new ThaiConsonant(0x0E2B, 'ho hip',       'chest, box',     'h',  '',   1, 'h',  '');
ThaiAlphabet::$LO_CHULA			= new ThaiConsonant(0x0E2C, 'lo chu-la',    'kite',           'l',  'n', -1, 'l',  'n');
ThaiAlphabet::$O_ANG			= new ThaiConsonant(0x0E2D, 'o ang',        'basin',          '',   '',   0, '',   '');
ThaiAlphabet::$HO_NOKHUK		= new ThaiConsonant(0x0E2E, 'ho nok-huk',   'owl',            'h',  '',  -1, 'h',  '');


// Vowels
ThaiAlphabet::$MAI_HAN_AKAT		= new ThaiVowel(0x0E31, 'mai han a-kat', 'a', true, false);

ThaiAlphabet::$SARA_A			= new ThaiVowel(0x0E30, 'sara a', 'a', false, true);
ThaiAlphabet::$SARA_AA			= new ThaiVowel(0x0E32, 'sara aa', 'a', true, false);
ThaiAlphabet::$SARA_AM			= new ThaiVowel(0x0E33, 'sara am', 'am', false, true);

ThaiAlphabet::$SARA_I			= new ThaiVowel(0x0E34, 'sara i', 'i', false, true);
ThaiAlphabet::$SARA_II			= new ThaiVowel(0x0E35, 'sara ii', 'i', true, false);
ThaiAlphabet::$SARA_UE			= new ThaiVowel(0x0E36, 'sara ue', 'ue', false, true);
ThaiAlphabet::$SARA_UEE			= new ThaiVowel(0x0E37, 'sara uee', 'ue', true, false);

ThaiAlphabet::$SARA_U			= new ThaiVowel(0x0E38, 'sara u', 'u', false, true);
ThaiAlphabet::$SARA_UU			= new ThaiVowel(0x0E39, 'sara uu', 'u', true, false);

// These vowels precede a consonant in visual order
ThaiAlphabet::$SARA_E			= new ThaiVowel(0x0E40, 'sara e', 'e', false, false, true);
ThaiAlphabet::$SARA_AE			= new ThaiVowel(0x0E41, 'sara ae', 'ae', false, false, true);
ThaiAlphabet::$SARA_O			= new ThaiVowel(0x0E42, 'sara o', 'o', false, false, true);

ThaiAlphabet::$SARA_AI_MAIMUAN	= new ThaiVowel(0x0E43, 'sara ai mai muan', 'ai', false, false, true);
ThaiAlphabet::$SARA_AI_MAIMALAI	= new ThaiVowel(0x0E44, 'sara ai mai malai', 'ai', false, false, true);

// Vowel length sign
ThaiAlphabet::$LAKKHANGYAO		= new ThaiVowel(0x0E45, 'lakkhang yao', '', false, true);
ThaiAlphabet::$NIKHAHIT			= new ThaiVowel(0x0E4D, 'nikkhahit', 'final nasal stop', true, false);

ThaiAlphabet::$MAITAIKHU		= new ThaiVowel(0x0E47, 'mai taikhu', 'shortens vowel', true, false);



ThaiAlphabet::$PHINTHU			= new ThaiModifier(0x0E3A, 'phinthu', 'makes implied vowel silent');
ThaiAlphabet::$THANTHAKHAT		= new ThaiModifier(0x0E4C, 'karan', 'silent/cancellation mark');
ThaiAlphabet::$YAMAKKAN			= new ThaiModifier(0x0E4E, 'yamakkan', 'obsolete, used to mark the beginning of consonant clusters, replaced by pinthu');


// Other symbols
ThaiAlphabet::$MAIYAMOK			= new ThaiSymbol(0x0E46, 'mai ya-mok', 'preceding word or phrase is repeated', array(40,3618,3617,3585,41));
ThaiAlphabet::$PAIYANNOI		= new ThaiSymbol(0x0E2F, 'pai-yan noi', 'ellipsis, abbreviation to shorten phrases', array(40,3648,3611,3618,3618,3634,3621,3609,3657,3629,3618,41));
ThaiAlphabet::$ANGKHANKHU		= new ThaiSymbol(0x0E5A, 'angkhan khu', 'used to mark end of long sections of a verse');
ThaiAlphabet::$KHOMUT			= new ThaiSymbol(0x0E5B, 'khomut', 'used to mark end of chapter or document');
ThaiAlphabet::$FONGMAN			= new ThaiSymbol(0x0E4F, 'fong man, ta kai', 'obsolete, used as a bullet');


// Initialize live and dead finals
ThaiAlphabet::initLiveAndDeadFinals();

} ?>