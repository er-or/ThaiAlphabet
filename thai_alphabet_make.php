<?
$outputFname = '../ThaiAlphabet.php';
$inputDir = '.';

function appendFileContents($inFname, $outHandle) {
	$inHandle = fopen($inFname, "r");
	if ($inHandle) {
		while (($line = fgets($inHandle)) !== false) {
			fwrite($outHandle, $line);
		}
		fclose($inHandle);
	} else {
		echo 'error opening file: ' . $inFname . "<br />\r\n";
		echo error_get_last();
	}
}

$outHandle = fopen($outputFname, "wb");
if (!$outHandle) {
	die('cannot open output file');
}
appendFileContents($inputDir . '/substitute_functions.php',			$outHandle);
appendFileContents($inputDir . '/ThaiSymbol.php',					$outHandle);
appendFileContents($inputDir . '/ThaiConsonant.php',				$outHandle);
appendFileContents($inputDir . '/ThaiVowel.php',					$outHandle);
appendFileContents($inputDir . '/ThaiToneMark.php',					$outHandle);
appendFileContents($inputDir . '/ThaiModifier.php',					$outHandle);
appendFileContents($inputDir . '/ThaiDigit.php',					$outHandle);
appendFileContents($inputDir . '/ThaiWord.php',						$outHandle);
appendFileContents($inputDir . '/ThaiAlphabet.php',					$outHandle);
appendFileContents($inputDir . '/ThaiToRomanTransliterator.php',	$outHandle);
appendFileContents($inputDir . '/ThaiWordList.php',					$outHandle);

fclose($outHandle);

echo 'done';

?>