<?
/**
 * DEFINE SUBSTITUTE FUNCTIONS FOR OLDER VERSIONS OF PHP
 */
if (!function_exists('mb_ord')) {
	function mb_ord($c) {
		mb_language('Neutral');
		mb_internal_encoding('UTF-8');
		mb_detect_order(array('UTF-8', 'ISO-8859-15', 'ISO-8859-1', 'ASCII'));
		$result = unpack('N', mb_convert_encoding($c, 'UCS-4BE', 'UTF-8'));
		if (is_array($result) === true) {
			return $result[1];
		}
		return ord($c);
	}
}
if (!function_exists('mb_html_entity_decode')) {
	function mb_html_entity_decode($string) {
		if (extension_loaded('mbstring') === true) {
			mb_language('Neutral');
			mb_internal_encoding('UTF-8');
			mb_detect_order(array('UTF-8', 'ISO-8859-15', 'ISO-8859-1', 'ASCII'));
			return mb_convert_encoding($string, 'UTF-8', 'HTML-ENTITIES');
		}
		return html_entity_decode($string, ENT_COMPAT, 'UTF-8');
	}
}
if (!function_exists('mb_chr')) {
	function mb_chr($n) { return mb_html_entity_decode('&#' . intval($n) . ';'); }
}
?>