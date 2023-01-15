<?


  ThaiToRomanTransliterator::$tone_marker_style = 'thaiphon';
  ThaiToRomanTransliterator::setSpellings([
    "ko kai"=>["k","k"],
    "kho khai"=>["kh","k"],
    "kho khuat"=>["kh","k"],
    "kho khwai"=>["kh","k"],
    "kho khon"=>["kh","k"],
    "kho ra-khang" => ["kh","k"],
    "ngo ngu"=>["ng","ng"],
    "cho chan"=>["j","t"],
    "cho ching"=>["ch","t"],
    "cho chang"=>["ch","t"],
    "so so"=>["s","s"],
    "cho choe"=>["ch","t"],
    "yo ying"=>["y","n"],
    "do cha-da"=>["d","d"],
    "to pa-tak"=>["t","t"],
    "tho than"=>["th","t"],
    "tho montho"=>["th","t"],
    "tho phu-thao"=>["th","t"],
    "no nen"=>["n","n"],
    "do dek"=>["d","t"],
    "to tao"=>["t","t"],
    "tho thung"=>["th","t"],
    "tho thahan"=>["th","t"],
    "tho thong"=>["th","t"],
    "no nu"=>["n","n"],
    "bo baimai"=>["b","p"],
    "po pla"=>["p","p"],
    "pho phueng"=>["ph","p"],
    "fo fa"=>["f","f"],
    "pho phan"=>["ph","p"],
    "fo fan"=>["f","f"],
    "pho sam-phao"=>["ph","p"],
    "mo ma"=>["m","m"],
    "yo yak"       => ["y",""],
    "ro ruea"=>["r","n"],
    "ru"=>["",""],
    "lo ling"=>["l","l"],
    "lu"=>["",""],
    "wo waen"=>["w",""],
    "so sala"=>["s","t"],
    "so rue-si"=>["s","t"],
    "so suea"=>["s","s"],
    "ho hip"=>["h","h"],
    "lo chu-la"=>["l","l"],
    "o ang"=>["",""],
    "ho nok-huk"=>["h","h"],


    "sara a"            => ["a",   "ā"],
    "sara i"            => ["i",   "ī"],
    "sara ue"           => ["eu",  "eū"],
    "sara u"            => ["u",   "ū"],
    "sara e"            => ["e",   "ē"],
    "sara ae"           => ["ae",  "aē"],
    "sara o"            => ["o",   "ō"],
    "sara ow"           => ["ǿ",   "ø"],
    "sara oe"           => ["oe",  "oē"],
    "sara ia"           => ["ie",  "īe"],
    "sara uea"          => ["uea", "eūa"],
    "sara ua"           => ["ua",  "ūa"],
    "sara i + wo waen"  => ["iu",  "iū"],
    "sara e + wo waen"  => ["eo",  "ēo"],
    "sara ae + wo waen" => ["aeo", "aēo"],
    "sara ao"           => ["ao",  "āo"],
    "sara a + wo waen"  => ["ao",  "āo"],
    "sara ia + wo waen" => ["ao",  "īo"],
    "sara a + yo yak"   => ["ai",  "āi"],
    "sara ai"           => ["ai",  "āi"],
    "sara o + yo yak"   => ["ǿi",  "øi"],
    "sara u + yo yak"   => ["ui",  "ūi"],
    "sara oe + yo yak"  => ["oei", "oēi"],
    "sara ua + yo yak"  => ["uay", "ūay"],
    "sara uea + yo yak" => ["euay","eūay"],
    "sara am"           => ["am",  "ām"],
    "rue"               => ["rue", "rūe"],
    "lue"               => ["lue", "lūe"],
  ]);
  ThaiToRomanTransliterator::$INHERENT_VOWEL = 'a';// 'ā';


/*
2890 / 32016 checked did not match
got 4569 syllables of 5024 distinct syllabs parsed
mismatched syllables (932):

2740 / 32015 checked did not match
got 4584 syllables of 5023 distinct syllabs parsed
mismatched syllables (918):

2737 / 32015 checked did not match
got 4586 syllables of 5023 distinct syllabs parsed
mismatched syllables (918):

2735 / 32015 checked did not match
got 4587 syllables of 5023 distinct syllabs parsed
mismatched syllables (917):

2694 / 32015 checked did not match
got 4589 syllables of 5023 distinct syllabs parsed
mismatched syllables (912):

2683 / 32015 checked did not match
got 4589 syllables of 5023 distinct syllabs parsed
mismatched syllables (911):

2656 / 32015 checked did not match
got 4589 syllables of 5023 distinct syllabs parsed
mismatched syllables (910)

2655 / 32015 checked did not match
got 4590 syllables of 5023 distinct syllabs parsed
mismatched syllables (910):

2637 / 32015 checked did not match
got 4593 syllables of 5022 distinct syllabs parsed
mismatched syllables (908):

2582 / 32014 checked did not match
got 4600 syllables of 5016 distinct syllabs parsed
mismatched syllables (892):
----------------------
2713 / 32014 checked did not match
got 4576 syllables of 5014 distinct syllabs parsed
mismatched syllables (907):


-------------------------
2719 / 32014 checked did not match
got 4574 syllables of 5014 distinct syllabs parsed
mismatched syllables (907):

2704 / 32014 checked did not match
got 4580 syllables of 5014 distinct syllabs parsed
mismatched syllables (904):

2678 / 32014 checked did not match
got 4587 syllables of 5014 distinct syllabs parsed
mismatched syllables (897):

2699 / 32014 checked did not match
got 4591 syllables of 5014 distinct syllabs parsed

2593 / 32014 checked did not match
got 4595 syllables of 5015 distinct syllabs parsed

2567 / 32014 checked did not match
got 4596 syllables of 5013 distinct syllabs parsed
mismatched syllables (882):

2549 / 32014 checked did not match
got 4599 syllables of 5013 distinct syllabs parsed
mismatched syllables (881):

2536 / 32014 checked did not match
got 4601 syllables of 5013 distinct syllabs parsed
mismatched syllables (879):

2494 / 32014 checked did not match
got 4614 syllables of 5013 distinct syllabs parsed

2479 / 32014 checked did not match
got 4618 syllables of 5013 distinct syllabs parsed
mismatched syllables (873):

2420 / 32014 checked did not match
got 4625 syllables of 5005 distinct syllabs parsed
mismatched syllables (850):

2355 / 32013 checked did not match
got 4629 syllables of 5007 distinct syllabs parsed
mismatched syllables (842):

2303 / 32013 checked did not match
got 4646 syllables of 5003 distinct syllabs parsed
mismatched syllables (833):

2268 / 32013 checked did not match
got 4659 syllables of 5003 distinct syllabs parsed
mismatched syllables (827):

2265 / 32013 checked did not match
got 4660 syllables of 5003 distinct syllabs parsed
mismatched syllables (825):

2262 / 32013 checked did not match
got 4659 syllables of 5002 distinct syllabs parsed
mismatched syllables (824):

2259 / 32013 checked did not match
got 4659 syllables of 5002 distinct syllabs parsed
mismatched syllables (825):

2250 / 32013 checked did not match
got 4662 syllables of 5001 distinct syllabs parsed
mismatched syllables (821):

2245 / 32013 checked did not match
got 4662 syllables of 5001 distinct syllabs parsed
mismatched syllables (821):

2241 / 32013 checked did not match
got 4666 syllables of 5001 distinct syllabs parsed
mismatched syllables (821):

2204 / 32012 checked did not match
got 4666 syllables of 5000 distinct syllabs parsed
mismatched syllables (816):

2195 / 32012 checked did not match
got 4667 syllables of 4998 distinct syllabs parsed
mismatched syllables (815):

2160 / 32012 checked did not match
got 4688 syllables of 4997 distinct syllabs parsed
mismatched syllables (808):

2154 / 32012 checked did not match
got 4689 syllables of 4997 distinct syllabs parsed
mismatched syllables (807):

2134 / 32012 checked did not match
got 4698 syllables of 4995 distinct syllabs parsed
mismatched syllables (804):

2091 / 32012 checked did not match
got 4700 syllables of 4995 distinct syllabs parsed
mismatched syllables (801):

2073 / 32012 checked did not match
got 4712 syllables of 4992 distinct syllabs parsed
mismatched syllables (795):

2073 / 32012 checked did not match
got 4709 syllables of 4988 distinct syllabs parsed
mismatched syllables (795):

2045 / 32012 checked did not match
got 4719 syllables of 4987 distinct syllabs parsed
mismatched syllables (790):

2043 / 32012 checked did not match
got 4721 syllables of 4987 distinct syllabs parsed
mismatched syllables (790):

1964 / 32012 checked did not match
got 4729 syllables of 4992 distinct syllabs parsed
mismatched syllables (778):

1950 / 32012 checked did not match
got 4738 syllables of 4992 distinct syllabs parsed

1931 / 32012 checked did not match
got 4742 syllables of 4995 distinct syllabs parsed
missing 253 syllable spellings:

1924 / 32012 checked did not match
got 4742 syllables of 4987 distinct syllabs parsed
missing 245 syllable spellings:

1922 / 32012 checked did not match
got 4743 syllables of 4985 distinct syllabs parsed
missing 242 syllable spellings:

1840 / 32010 checked did not match
got 4764 syllables of 4976 distinct syllabs parsed
missing 212 syllable spellings:

1780 / 32039 checked did not match
got 4791 syllables of 4963 distinct syllabs parsed
missing 172 syllable spellings

1767 / 32055 checked did not match
got 4797 syllables of 4961 distinct syllabs parsed
missing 164 syllable spellings:

1738 / 32064 checked did not match
got 4813 syllables of 4952 distinct syllabs parsed
missing 139 syllable spellings:

1720 / 32065 checked did not match
got 4822 syllables of 4951 distinct syllabs parsed
missing 129 syllable spellings:

1692 / 32069 checked did not match
got 4835 syllables of 4948 distinct syllabs parsed
missing 113 syllable spellings

1676 / 32069 checked did not match
got 4852 syllables of 4947 distinct syllabs parsed
missing 95 syllable spellings:

1673 / 32069 checked did not match
got 4853 syllables of 4947 distinct syllabs parsed
missing 94 syllable spellings:

1670 / 32069 checked did not match
got 4854 syllables of 4947 distinct syllabs parsed
missing 93 syllable spellings:

1666 / 32069 checked did not match
got 4856 syllables of 4946 distinct syllabs parsed
missing 90 syllable spellings:

1657 / 32069 checked did not match
got 4865 syllables of 4942 distinct syllabs parsed
missing 77 syllable spellings:

1644 / 32088 checked did not match
got 4876 syllables of 4942 distinct syllabs parsed
missing 66 syllable spellings:

1621 / 32091 checked did not match
got 4898 syllables of 4942 distinct syllabs parsed
missing 44 syllable spellings:

18621 / 52444 checked did not match
got 4936 syllables of 4940 distinct syllabs parsed
missing 4 syllable spellings:

20195 / 57920 checked did not match
got 4940 syllables of 4961 distinct syllabs parsed
missing 21 syllable spellings:

20157 / 57900 checked did not match
got 4950 syllables of 4954 distinct syllabs parsed
missing 4 syllable spellings:

20143 / 57900 checked did not match
got 4930 syllables of 4930 distinct syllabs parsed
missing 0 syllable spellings:

20118 / 57900 checked did not match
got 4752 syllables of 4753 distinct syllabs parsed
missing 1 syllable spellings:

20117 / 57900 checked did not match
got 4753 syllables of 4753 distinct syllabs parsed

20102 / 57900 checked did not match
got 4737 syllables of 4737 distinct syllabs parsed

20074 / 57900 checked did not match
got 4692 syllables of 4692 distinct syllabs parsed

20062 / 57900 checked did not match
got 4675 syllables of 4675 distinct syllabs parsed

20038 / 57900 checked did not match
got 4598 syllables of 4598 distinct syllabs parsed

20038 / 57900 checked did not match
got 4597 syllables of 4597 distinct syllabs parsed

20025 / 57900 checked did not match
got 4597 syllables of 4597 distinct syllabs parsed

20024 / 57900 checked did not match
got 4598 syllables of 4598 distinct syllabs parsed

20000 / 57900 checked did not match
got 4597 syllables of 4597 distinct syllabs parsed

19987 / 57900 checked did not match
got 4597 syllables of 4597 distinct syllabs parsed

19905 / 57900 checked did not match
got 4602 syllables of 4602 distinct syllabs parsed

19901 / 57900 checked did not match
got 4603 syllables of 4603 distinct syllabs parsed

19870 / 57900 checked did not match
got 4603 syllables of 4603 distinct syllabs parsed

*/
?>