<?

/**
 * Example for transliteration of Thai to English.
 * This example was specifically made for GitHub.
 *
 * All the code is put into a single file, for older PHP installations.
 * run thai_alphabet_make.php first.
 */
include('../ThaiAlphabet.php');

// DEFAULT INPUT IS THAI ("I like to program and learn Thai").
$thai_input = 'ผมชอบเขียนโปรแกรมและเรียนภาษาไทย';
if (isset($_REQUEST['thai'])) {
	$thai_input = $_REQUEST['thai'];
} else {
	// ECHO ENGLISH
	echo "From English: I like to program and learn Thai.<br /><br />\r\n";
}

// FILTER INPUT (THOUGH CODE INJECTION SHOULD NOT BE AN ISSUE HERE)
$thai_input = ThaiAlphabet::filter_thai($thai_input);

// ECHO ORIGINAL INPUT
echo 'Thai Input: ' . $thai_input . "<br /><br />\r\n";

// OUTPUT DEFAULT TRANSLITERATION
echo 'Romanized Output: <b>' . ThaiAlphabet::romanize($thai_input) . "</b><br /><br />\r\n";

// SET TRANSLITERATION SPELLINGS TO RTGS (Royal Thai General System of Transcription)
//include('transliteration_tables/rtgs_spelling.php');

// OUTPUT RTGS
//echo 'Official System: <b>' . ThaiAlphabet::romanize($thai_input) . "</b><br /><br />\r\n";




?>