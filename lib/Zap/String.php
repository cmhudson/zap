<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/Object.php';
require_once 'Zap/Number.php';

/**
 * String Tools
 *
 * @package   Zap
 * @copyright 2005-2011 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Zap_String extends Zap_Object
{
	// {{{ public static properties

	/**
	 * Block level XHTML elements used when filtering strings
	 *
	 * @var array
	 */
	public static $blocklevel_elements = array(
		'p',          'pre',     'dl',     'div',
		'blockquote', 'form',    'h[1-6]', 'table',
		'fieldset',   'address', 'ul',     'ol',
	);

	/**
	 * These XHTML elements are not block-level but people often write
	 * markup treating these elements as block-level tags
	 *
	 * @var array
	 */
	public static $breaking_elements = array(
		'li', 'dd', 'dt',
	);

	/**
	 * These XHTML elements are used for tables
	 *
	 * @var array
	 */
	public static $table_elements = array(
		'thead', 'tfoot',    'tbody', 'tr', 'th', 'td',
		'col',   'colgroup',
	);

	/**
	 * All XHTML elements
	 *
	 * Taken from {@link http://www.w3.org/TR/html4/index/elements.html}.
	 *
	 * @var array
	 */
	public static $xhtml_elements = array(
		'a',      'abbr',    'acronym',  'address',  'applet',   'area',
		'b',      'base',    'basefont', 'bdo',      'big',      'blockquote',
		'body',   'br',      'button',   'caption',  'center',   'cite',
		'code',   'col',     'colgroup', 'dd',       'del',      'dfn',
		'dir',    'div',     'dl',       'dt',       'em',       'fieldset',
		'font',   'form',    'frame',    'frameset', 'h[1-6]',   'head',
		'hr',     'html',    'i',        'iframe',   'img',      'input',
		'ins',    'isindex', 'kbd',      'label',    'legend',   'li',
		'link',   'map',     'menu',     'meta',     'noframes', 'noscript',
		'object', 'ol',      'optgroup', 'option',   'p',        'param',
		'pre',    'q',       's',        'samp',     'script',   'select',
		'small',  'span',    'strike',   'strong',   'style',    'sub',
		'sup',    'table',   'tbody',    'td',       'textarea', 'tfoot',
		'th',     'thead',   'title',    'tr',       'tt',       'u',
		'ul',     'var',
	);

	/**
	 * XHTML elements where the content is pre-formatted and should not be
	 * modified
	 *
	 * @var array
	 */
	public static $preformatted_elements = array(
		'script', 'style', 'pre',
	);

	// }}}
	// {{{ public static function toXHTML()

	/**
	 * Intelligently converts a text block to XHTML
	 *
	 * The text is converted as follows:
	 *
	 * - text blocks delimited by double line breaks are wrapped in a paragraph
	 *   tags
	 * - unless they are already inside a blocklevel tag
	 * - single line breaks are converted to line break tags
	 *
	 * @param string $text the text block to convert to XHTML.
	 *
	 * @return string the text block converted to XHTML.
	 */
	public static function toXHTML($text)
	{
		$blocklevel_elements = implode('|', self::$blocklevel_elements);
		$breaking_elements = implode('|', self::$breaking_elements);
		$preformatted_elements = implode('|', self::$preformatted_elements);
		$xhtml_elements = implode('|', self::$xhtml_elements);
		$table_elements = implode('|', self::$table_elements);

		// regular expression to match all tags
		$all_tags = '/(<\/?(?:'.$xhtml_elements.')[^<>]*?>)/siu';

		// regular expressions to match blocklevel tags
		$starting_blocklevel = '/^<('.$blocklevel_elements.')[^<>]*?>/siu';
		$ending_blocklevel = '/<\/('.$blocklevel_elements.')[^<>]*?>$/siu';

		// convert input from windows and mac
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);

		// remove leading and trailing whitespace to prevent extra paragraphs
		$text = trim($text);

		// remove whitespace before table elements
		$text = preg_replace('/\s+(<\/?(?:'.$table_elements.')[^<>]*?>)/usi',
			'\1', $text);

		// remove whitespace after table elements
		$text = preg_replace('/(<\/?(?:'.$table_elements.')[^<>]*?>)\s+/usi',
			'\1', $text);

		// replace continuous strings of whitespace containing a
		// double lf with two line breaks
		$text = preg_replace('/[\xa0\s]*\n\n[\xa0\s]*/su', "\n\n", $text);

		// replace single line break followed by a starting block-level tag
		// with two line breaks
		$break_then_blocklevel =
			'/([^\n])\n(<('.$blocklevel_elements.')[^<>]*?>)/siu';

		$text = preg_replace($break_then_blocklevel, "\\1\n\n\\2", $text);

		// remove line break from starting block-level tag followed by a
		// line break
		$blocklevel_then_break =
			'/(<('.$blocklevel_elements.')[^<>]*?>)\n/siu';

		$text = preg_replace($blocklevel_then_break, "\\1", $text);

		// remove line break from line break followed by an ending block-level
		// tag
		$break_then_ending_blocklevel =
			'/\n(<\/('.$blocklevel_elements.')[^<>]*?>)/siu';

		$text = preg_replace($break_then_ending_blocklevel, "\\1", $text);

		// remove line break from ending breaking tag followed by a line break
		$ending_breaking_then_break =
			'/(<\/('.$breaking_elements.')[^<>]*?>)\n/siu';

		$text = preg_replace($ending_breaking_then_break, "\\1", $text);

		// temporarily remove preformatted content so it is not auto-formatted
		$preformatted_search = '/<('.$preformatted_elements.')[^<>]*?>'.
			'(.*?)'.
			'<\/('.$preformatted_elements.')>/usi';

		$preformatted_content = array();
		preg_match_all($preformatted_search, $text, $preformatted_content);

		// save preformatted content
		$preformatted_content = $preformatted_content[2];

		if (count($preformatted_content) > 0) {
			// replace preformatted content with sprintf place-holders
			$text = str_replace('%', '%%', $text);
			$preformat_replace = '/(<('.$preformatted_elements.')[^<>]*?>)'.
				'.*?'.
				'(<\/('.$preformatted_elements.')>)/usi';

			$text = preg_replace($preformat_replace, '\1%s\3', $text);
		}

		// match paragraphs that are entirely preformatted elements
		$preformat = '/^<('.$preformatted_elements.')[^<>]*?>'.
			'%s'.
			'<\/('.$preformatted_elements.')>$/ui';

		$paragraphs = explode("\n\n", $text);

		$in_blocklevel = false;
		foreach($paragraphs as &$paragraph) {
			// ignore paragraphs containing all whitespace or empty paragraphs.
			// this prevents empty paragraph tags from appearing in the
			// returned string
			if (trim($paragraph) == '')
				continue;

			$blocklevel_started =
				(preg_match($starting_blocklevel, $paragraph) == 1);

			$blocklevel_ended =
				(preg_match($ending_blocklevel, $paragraph) == 1);

			if ($blocklevel_started)
				$in_blocklevel = true;

			$is_preformatted = (preg_match($preformat, $paragraph) == 1);

			// don't format wrap this paragraph if it is a preformatted
			// element.
			if ($is_preformatted) {
				$paragraph = $paragraph."\n\n";
			} else {
				// split paragraph into tags and text
				$tags = array();
				preg_match_all($all_tags, $paragraph, $tags,
					PREG_OFFSET_CAPTURE);

				$tags = $tags[0];
				$text = preg_split($all_tags, $paragraph, -1,
					PREG_SPLIT_OFFSET_CAPTURE);

				$paragraph = '';

				// filter tags and text back into paragraph
				$tag_index = 0;
				$text_index = 0;
				$num_tags = count($tags);
				$num_text = count($text);
				while ($tag_index < $num_tags || $text_index < $num_text) {
					if (isset($tags[$tag_index]) &&
						$tags[$tag_index][1] < $text[$text_index][1]) {
						// assume tags are already formatted
						$paragraph.= $tags[$tag_index][0];
						$tag_index++;
					} elseif (isset($text[$text_index])) {
						// minimize entities for text
						$paragraph.=
							self::minimizeEntities($text[$text_index][0]);

						$text_index++;
					}
				}

				// if we are in a blocklevel element, we are done
				if ($in_blocklevel)
					$paragraph.= "\n\n";
			}

			// if we are not in a blocklevel element or a preformatted
			// element, wrap the paragraph in paragraph tags
			if (!$in_blocklevel && !$is_preformatted)
				$paragraph = '<p>'.$paragraph."</p>\n\n";

			if ($blocklevel_ended)
				$in_blocklevel = false;
		}

		$text = implode('', $paragraphs);

		$text = preg_replace('/([^\n])\n([^\n])/su', '\1<br />\2', $text);

		if (count($preformatted_content) > 0) {
			// replace preformatted content
			$text = vsprintf($text, $preformatted_content);
		}

		$text = rtrim($text);

		return $text;
	}

	// }}}
	// {{{ public static function minimizeEntities()

	/**
	 * Converts a UTF-8 text string to have the minimal number of entities
	 * necessary to output it as valid UTF-8 XHTML without ever double-escaping.
	 *
	 * The text is converted as follows:
	 *
	 * - any exisiting entities are decoded to their UTF-8 characaters
	 * - the minimal number of characters necessary are then escaped as entities:
	 *   - ampersands   (&) => &amp;
	 *   - less than    (<) => &lt;
	 *   - greater than (>) => &gt;
	 *   - double quote (") => &quot;
	 *
	 * @param string $text the UTF-8 text string to convert.
	 *
	 * @return string the UTF-8 text string with minimal entities.
	 */
	public static function minimizeEntities($text)
	{
		// decode any entities that might already exist
		$text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');

		// encode all ampersands (&), less than (<), greater than (>),
		// and double quote (") characters as their XML entities
		$text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');

		return $text;
	}

	// }}}
	// {{{ public static function minimizeEntitiesWithTags()

	/**
	 * Same as {@link SwatString::minimizeEntities()} but also accepts a list
	 * of tags to preserve.
	 *
	 * @param string $text the UTF-8 text string to convert.
	 * @param array $tags names of tags that should be preserved.
	 *
	 * @return string the UTF-8 text string with minimal entities.
	 */
	public static function minimizeEntitiesWithTags($text, $tags)
	{
		$pattern = sprintf('/(<\/?(%s) ?.*>)/uU', implode('|', $tags));

		$parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$output = '';

		foreach ($parts as $index => $part) {
			switch ($index % 3) {
			case 0:
				// the stuff in between
				$output.= self::minimizeEntities($part);
				break;
			case 1:
				// a valid tag
				$output.= $part;
				break;
			}
		}

		return $output;
	}

	// }}}
	// {{{ public static function condense()

	/**
	 * Takes a block of text and condenses it into a small fragment of XHTML.
	 *
	 * Condensing text removes inline XHTML tags and replaces line breaks and
	 * block-level elements with special characters.
	 *
	 * @param string $text the text to be condensed.
	 * @param integer $max_length the maximum length of the condensed text. If
	 *                             null is specified, there is no maximum
	 *                             length.
	 * @param string $ellipses the ellipses characters to append if the string
	 *                          is shortened. By default, this is a
	 *                          non-breaking space followed by a unicode
	 *                          ellipses character.
	 *
	 * @return string the condensed text. The condensed text is an XHTML
	 *                 formatted string.
	 */
	public static function condense($text, $max_length = 300, $ellipses = ' …')
	{
		// remove XML comments
		$xml_comments = '/<!--.*?-->/siu';
		$text = preg_replace($xml_comments, '', $text);

		// remove style tags
		$style_tags = '/<style[^<>]*?\>.*?<\/style[^<>]*?\>/siu';
		$text = preg_replace($style_tags, '', $text);

		// replace blocklevel tags with line breaks, but exclude blockquotes
		// because they are handled in a special case
		$blocklevel_elements = array_diff(
			self::$blocklevel_elements,
			array('blockquote')
		);
		$blocklevel_elements = implode('|', $blocklevel_elements);
		$blocklevel_tags = '/<\/?(?:'.$blocklevel_elements.')[^<>]*?\>/siu';
		$text = preg_replace($blocklevel_tags, "\n", $text);

		// replace <br /> and <hr /> tags with line breaks.
		$br_hr_tags = '/<[hb]r[^<>]*?\>/siu';
		$text = preg_replace($br_hr_tags, "\n", $text);

		// replace blockquote tags with curly quotation marks
		$search = array(
			'/<blockquote[^<>]*?\>\s*/siu',
			'/\s*<\/blockquote[^<>]*?\>/siu',
		);

		$replace = array("\n“", "”\n");
		$text = preg_replace($search, $replace, $text);

		// remove inline tags
		// (only tags remaining after blocklevel, br and hr tags removed)
		$text = strip_tags($text);

		// If there is a maxlength, truncate the text at twice the maxlength.
		// This is done for speed as the next three operations are slow for
		// long strings. This is not technically correct but should be correct
		// most of the time unless the string is composed mostly of spaces
		// and entities.
		if ($max_length !== null) {
			$truncated_text = substr($text, 0, $max_length * 2);

			// estimate whether or not the result will be correct after
			// minimizing and collapsing whitespace
			$counts = count_chars($truncated_text, 1);
			$count  = 0;

			// check for whitespace that could be condensed.
			// 0xa0 is a guestimate for 0xc2 0xa0 (non-breaking space) because
			// count_chars() does not consider utf-8 encoding.
			$chars  = array(0x20, 0x07, 0xa0);
			foreach ($chars as $char) {
				if (isset($counts[$char])) {
					$count += $counts[$char];
				}
			}

			// check for ampersands that could be entities
			if (isset($counts[0x26])) {
				// weight entities as six characters on average
				$count += $counts[0x26] * 6;
			}

			if ($count < $max_length) {
				// we should still have at least max_length real visible
				// characters left after truncating, so use the truncated text
				// and get a sweet speed increase.
				$text = $truncated_text;
			}
		}

		// replace whitespaces with single spaces
		// First replace unicode nbsp characters with spaces so we do not have
		// to match a unicode character in the regular expression.
		$text = str_replace('·', ' ', $text);
		$text = preg_replace('/[ \t]{2,}/', ' ', $text);

		$text = self::minimizeEntities($text);

		$text = trim($text);

		// replace continuous strings of whitespace containing either a
		// cr or lf with a non-breaking space padded bullet.
		// the spaces around the bullet are non-breaking spaces
		$search  = '/[\xa0\s]*[\n\r][\xa0\s]*/su';
		$replace = '  •  ';
		$text    = preg_replace($search, $replace, $text);

		if ($max_length !== null) {
			$text = SwatString::ellipsizeRight($text, $max_length, $ellipses);
		}

		return $text;
	}

	// }}}
	// {{{ public static function condenseToName()

	/**
	 * Condenses a string to a name
	 *
	 * The generated name can be used for things like database identifiers and
	 * site URI fragments.
	 *
	 * Example:
	 * <code>
	 * $string = 'The quick brown fox jumped over the lazy dogs.';
	 * // displays 'thequickbrown'
	 * echo SwatString::condenseToName($string);
	 * </code>
	 *
	 * @param string $string the string to condense to a name.
	 * @param integer $max_length the maximum length of the condensed name in
	 *                             characters.
	 *
	 * @return string the string condensed into a name.
	 */
	public static function condenseToName($string, $max_length = 15)
	{
		if (!is_string($string)) {
			$string = strval($string);
		}

		if ($string == '') {
			return $string;
		}

		// remove tags and make lowercase
		$string = strip_tags(strtolower($string));

		if (class_exists('Net_IDNA')) {
			// we have Net_IDNA, convert words to punycode

			// convert entities to utf-8
			$string = self::minimizeEntities($string);

			// convert non-alpha-numeric ascii characters to spaces and
			// condense whitespace
			$search = array(
				'/[\x00-\x1f\x21-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e]/u',
				'/\s+/u',
			);
			$replace = array(' ', ' ');
			$string = preg_replace($search, $replace, $string);

			// remove leading and tailing whitespace that may have been added
			// during preg_replace()
			$string = trim($string);

			$idna = Net_IDNA::getInstance();

			// split into words
			$string_utf8_exp = explode(' ', $string);

			// convert words into punycode
			$first = true;
			$string_out = '';
			foreach ($string_utf8_exp as $string_utf8) {

				$encoded_word = $idna->encode($string_utf8);

				if ($first) {
					// first word too long, so forced to chop it
					if (strlen($encoded_word) >= $max_length)
						return substr($encoded_word, 0 , $max_length);

					$first = false;
				}

				// this word would push us over the limit
				if (strlen($string_out) + strlen($encoded_word) > $max_length)
					return $string_out;

				$string_out .= $encoded_word;
			}

		} else {
			// remove html entities, convert non-alpha-numeric characters to
			// spaces and condense whitespace
			$search = array('/&#?\w+;/u', '/[^a-z0-9 ]/u', '/\s+/u');
			$replace = array('', ' ', ' ');

			$string = preg_replace($search, $replace, $string);

			// split into words
			$string_exp = explode(' ', $string);

			// first word too long, so forced to chop it
			if (strlen($string_exp[0]) >= $max_length)
				return substr($string_exp[0], 0 , $max_length);

			$string_out = '';

			// add words to output until it is too long
			foreach ($string_exp as $word) {
				// this word would push us over the limit
				if (strlen($string_out) + strlen($word) > $max_length)
					return $string_out;

				$string_out .= $word;
			}
		}

		return $string_out;
	}

	// }}}
	// {{{ public static function ellipsizeRight()

	/**
	 * Ellipsizes a string to the right
	 *
	 * The length of a string is calculated as the number of visible characters
	 * This method will properly account for any XHTML entities that may be
	 * present in the given string.
	 *
	 * Example:
	 * <code>
	 * $string = 'The quick brown fox jumped over the lazy dogs.';
	 * // displays 'The quick brown ...'
	 * echo SwatString::ellipsizeRight($string, 18, ' ...');
	 * </code>
	 *
	 * XHTML example:
	 * <code>
	 * $string = 'The &#8220;quick&#8221; brown fox jumped over the lazy dogs.';
	 * // displays 'The &#8220;quick&#8221; brown ...'
	 * echo SwatString::ellipsizeRight($string, 18, ' ...');
	 * </code>
	 *
	 * @param string $string the string to ellipsize.
	 * @param integer $max_length the maximum length of the returned string.
	 *                             This length does not account for any ellipse
	 *                             characters that may be appended. If the
	 *                             returned value must be below a certain
	 *                             number of characters, pass a blank string in
	 *                             the ellipses parameter.
	 * @param string $ellipses the ellipses characters to append if the string
	 *                          is shortened. By default, this is a
	 *                          non-breaking space followed by a unicode
	 *                          ellipses character.
	 * @param boolean &$flag an optional boolean flag passed by reference to
	 *                        the ellipsize function. If the given string is
	 *                        ellipsized, the flag is set to true. If no
	 *                        ellipsizing takes place, the flag is set to false.
	 *
	 * @return string the ellipsized string. The ellipsized string may be
	 *                 appended with ellipses characters if it was longer than
	 *                 <code>$max_length</code>.
	 */
	public static function ellipsizeRight($string, $max_length,
		// the space is a non-breaking space
		$ellipses = ' …', &$flag = null)
	{
		$matches = array();
		self::stripEntities($string, $matches);

		$string = trim($string);

		// don't ellipsize if the string is short enough
		if (strlen($string) <= $max_length) {
			self::insertEntities($string, $matches, strlen($string));
			$flag = false;
			return $string;
		}

		// note: if strrpos worked the same using mb_string overloading the
		// following code would be a lot simpler.

		// chop at max length
		$string = substr($string, 0, $max_length);

		// find the last space up to the max_length in the string
		$chop_pos = strrpos($string, ' ');

		if ($chop_pos !== false)
			$string = substr($string, 0, $chop_pos);

		$string = SwatString::removeTrailingPunctuation($string);

		self::insertEntities($string, $matches, strlen($string));

		$string .= $ellipses;

		$flag = true;
		return $string;
	}

	// }}}
	// {{{ public static function ellipsizeMiddle()

	/**
	 * Ellipsizes a string in the middle
	 *
	 * The length of a string is calculated as the number of visible characters
	 * This method will properly account for any XHTML entities that may be
	 * present in the given string.
	 *
	 * Example:
	 * <code>
	 * $string = 'The quick brown fox jumped over the lazy dogs.';
	 * // displays 'The quick ... dogs.'
	 * echo SwatString::ellipsizeMiddle($string, 18, ' ... ');
	 * </code>
	 *
	 * XHTML example:
	 * <code>
	 * $string = 'The &#8220;quick&#8221 brown fox jumped over the lazy dogs.';
	 * // displays 'The &#8220;quick&#8221; ... dogs.'
	 * echo SwatString::ellipsizeMiddle($string, 18, ' ... ');
	 * </code>
	 *
	 * @param string $string the string to ellipsize.
	 * @param integer $max_length the maximum length of the returned string.
	 *                             This length does not account for any ellipse
	 *                             characters that may be appended.
	 * @param string $ellipses the ellipses characters to insert if the string
	 *                          is shortened. By default, this is a unicode
	 *                          ellipses character padded by non-breaking
	 *                          spaces.
	 * @param boolean &$flag an optional boolean flag passed by reference to
	 *                        the ellipsize function. If the given string is
	 *                        ellipsized, the flag is set to true. If no
	 *                        ellipsizing takes place, the flag is set to false.
	 *
	 * @return string the ellipsized string. The ellipsized string may include
	 *                 ellipses characters in roughly the middle if it was
	 *                 longer than <code>$max_length</code>.
	 */
	public static function ellipsizeMiddle($string, $max_length,
		// the spaces are non-breaking spaces
		$ellipses = ' … ', &$flag = null)
	{
		$string = trim($string);

		$matches = array();
		self::stripEntities($string, $matches);

		// don't ellipsize if the string is short enough
		if (strlen($string) <= $max_length) {
			self::insertEntities($string, $matches, strlen($string));
			$flag = false;
			return $string;
		}

		// check if the string is all one giant word
		$has_space = strpos($string, ' ');

		// the entire string is one word
		if ($has_space === false) {

			// just take a piece of the string from both ends
			$first_piece = substr($string, 0, $max_length / 2);
			$last_piece = substr($string,
					-($max_length - strlen($first_piece)));

		} else {

			/*
			 * Implementation Note:
			 *
			 * Get last piece first as it makes it more likely for the first
			 * piece to be of greater length. This is done because usually the
			 * first piece of a string is more recognizable.
			 *
			 * The length of the last piece can be at most half of the maximum
			 * length and is potentially shorter. The last half can be as short
			 * as the last word.
			 */

			/*
			 * Get the last piece as half the max_length starting from
			 * the right.
			 */
			$last_piece = substr($string, -($max_length / 2));
			$last_piece = trim($last_piece);

			/*
			 * Find the last word in the last piece.
			 * TODO: We may want to change this to select more of the end of
			 *       the string than the last word.
			 */
			$last_space = strrpos($last_piece, ' ');
			if ($last_space !== false)
				$last_piece = substr($last_piece, $last_space + 1);

			/*
			 * Get the first piece by ellipsizing with a max_length of
			 * the max_length less the length of the last piece.
			 */
			$max_first_length = $max_length - strlen($last_piece);

			$first_piece =
				self::ellipsizeRight($string, $max_first_length, '');
		}

		$hole_start = strlen($first_piece);
		$hole_end = strlen($string) - strlen($last_piece);
		$hole_length = strlen($ellipses);

		$string = $first_piece.$ellipses.$last_piece;

		self::insertEntities($string, $matches,
			$hole_start, $hole_end, $hole_length);

		$flag = true;
		return $string;
	}

	// }}}
	// {{{ public static function removeTrailingPunctuation()

	/**
	 * Removes trailing punctuation from a string
	 *
	 * @param string $string the string to format remove punctuation from.
	 *
	 * @return string the string with trailing punctuation removed.
	 */
	public static function removeTrailingPunctuation($string)
	{
		return preg_replace('/\W+$/su', '', $string);
	}

	// }}}
	// {{{ public static function removeLeadingPunctuation()

	/**
	 * Removes leading punctuation from a string
	 *
	 * @param string $string the string to format remove punctuation from.
	 *
	 * @return string the string with leading punctuation removed.
	 */
	public static function removeLeadingPunctuation($string)
	{
		return preg_replace('/^\W+/su', '', $string);
	}

	// }}}
	// {{{ public static function removePunctuation()

	/**
	 * Removes both leading and trailing punctuation from a string
	 *
	 * @param string $string the string to format remove punctuation from.
	 *
	 * @return string the string with leading and trailing punctuation removed.
	 */
	public static function removePunctuation($string)
	{
		$string = SwatString::removeTrailingPunctuation($string);
		$string = SwatString::removeLeadingPunctuation($string);
		return $string;
	}

	// }}}
	// {{{ public static function moneyFormat()

	/**
	 * Formats a numeric value as currency
	 *
	 * Note: This method does not work in some operating systems and in such
	 *       cases, this method will throw an exception.
	 *
	 * Note: This method is deprecated. Use {@link SwatI18NLocale::formatCurrency()}
	 *       instead. The newer method is more flexible and works across more
	 *       platforms.
	 *
	 * @param float $value the numeric value to format.
	 * @param string $locale optional locale to use to format the value. If no
	 *                        locale is specified, the current locale is used.
	 * @param boolean $display_currency optional flag specifing whether or not
	 *                                   the international currency symbol is
	 *                                   appended to the output. If not
	 *                                   specified, the international currency
	 *                                   symbol is omitted from the output.
	 * @param integer $decimal_places optional number of decimal places to
	 *                                 display. If not specified, the locale's
	 *                                 default number of decimal places is used.
	 *
	 * @return string a UTF-8 encoded string containing the formatted currency
	 *                 value.
	 *
	 * @throws SwatException if the PHP money_format() function is undefined.
	 * @throws SwatException if the given locale could not be set.
	 * @throws SwatException if the locale-based output cannot be converted to
	 *                        UTF-8.
	 *
	 * @deprecated Use {@link SwatI18NLocale::formatCurrency()} instead. It is more
	 *             flexible and works across more platforms.
	 */
	public static function moneyFormat($value, $locale = null,
		$display_currency = false, $decimal_places = null)
	{
		if (!function_exists('money_format')) {
			throw new SwatException('moneyFormat() method is not available '.
				'on this operating system. See '.
				'http://php.net/manual/en/function.money-format.php for '.
				'details.');
		}

		if ($locale !== null) {
			$old_locale = setlocale(LC_ALL, 0);
			if (setlocale(LC_ALL, $locale) === false) {
				throw new SwatException(sprintf('Locale %s passed to the '.
					'moneyFormat() method is not valid for this operating '.
					'system.', $locale));
			}
		}

		// get character set of the locale that is used
		$character_set = nl_langinfo(CODESET);

		$format_string = ($decimal_places === null) ? '%n' :
			'%.'.((int)$decimal_places).'n';

		$output = money_format($format_string, $value);

		if ($display_currency) {
			$lc = localeconv();
			$output.= ' '.$lc['int_curr_symbol'];
		}

		// convert output to UTF-8
		if ($character_set !== 'UTF-8') {
			$output = iconv($character_set, 'UTF-8', $output);
			if ($output === false)
				throw new SwatException(sprintf('Could not convert %s output '.
					'to UTF-8', $character_set));
		}

		if ($locale !== null)
			setlocale(LC_ALL, $old_locale);

		return $output;
	}

	// }}}
	// {{{ public static function getInternationalCurrencySymbol()

	/**
	 * Gets the international currency symbol of a locale
	 *
	 * @param string $locale optional. Locale to get the international currency
	 *                        symbol for. If no locale is specified, the
	 *                        current locale is used.
	 *
	 * @return string the international currency symbol for the specified
	 *                 locale. The symbol is UTF-8 encoded and does not include
	 *                 the spacing character specified in the C99 standard.
	 *
	 * @throws SwatException if the given locale could not be set.
	 */
	public static function getInternationalCurrencySymbol($locale = null)
	{
		if ($locale !== null) {
			$old_locale = setlocale(LC_MONETARY, 0);
			if (setlocale(LC_MONETARY, $locale) === false) {
				throw new SwatException(sprintf('Locale %s passed to the '.
					'getInternationalCurrencySymbol() method is not valid '.
					'for this operating system.', $locale));
			}
		}

		// get character set of the locale that is used
		$character_set = nl_langinfo(CODESET);

		$lc = localeconv();
		$symbol = $lc['int_curr_symbol'];

		// convert output to UTF-8
		if ($character_set !== 'UTF-8') {
			$symbol = iconv($character_set, 'UTF-8', $symbol);
			if ($symbol === false)
				throw new SwatException(sprintf('Could not convert %s output '.
					'to UTF-8', $character_set));
		}

		// strip C99-defined spacing character
		$symbol = substr($symbol, 0, 3);

		if ($locale !== null)
			setlocale(LC_ALL, $old_locale);

		return $symbol;
	}

	// }}}
	// {{{ public static function numberFormat()

	/**
	 * Formats a number using locale-based separators
	 *
	 * @param float $value the numeric value to format.
	 * @param integer $decimals number of decimal places to display. By
	 * 	                         default, the full number of decimal places of
	 *                           the value will be displayed.
	 * @param string $locale an optional locale to use to format the value. If
	 *                        no locale is specified, the current locale is
	 *                        used.
	 * @param boolean $show_thousands_separator whether or not to display the
	 *                        thousands separator (default is true).
	 *
	 * @return string a UTF-8 encoded string containing the formatted number.
	 *
	 * @throws SwatException if the given locale could not be set.
	 * @throws SwatException if the locale-based output cannot be converted to
	 *                        UTF-8.
	 */
	public static function numberFormat($value, $decimals = null,
		$locale = null, $show_thousands_separator = true)
	{
		// look up decimal precision if none is provided
		if ($decimals === null)
			$decimals = SwatString::getDecimalPrecision($value);

		// number_format can't handle UTF-8 separators, so insert placeholders
		$output =  number_format($value, $decimals, '.',
			$show_thousands_separator ? ',' : '');

		if ($locale !== null) {
			$old_locale = setlocale(LC_ALL, 0);
			if (setlocale(LC_ALL, $locale) === false) {
				throw new SwatException(sprintf('Locale %s passed to the '.
					'numberFormat() method is not valid for this operating '.
					'system.', $locale));
			}
		}

		// get character set of the locale that is used
		$character_set = nl_langinfo(CODESET);

		// replace placeholder separators with locale-specific ones which
		// might contain non-ASCII
		$lc = localeconv();
		$output = str_replace('.', $lc['decimal_point'], $output);
		$output = str_replace(',', $lc['thousands_sep'], $output);

		// convert output to UTF-8
		if ($character_set !== 'UTF-8') {
			$output = iconv($character_set, 'UTF-8', $output);
			if ($output === false)
				throw new SwatException(sprintf('Could not convert %s output '.
					'to UTF-8', $character_set));
		}

		if ($locale !== null)
			setlocale(LC_ALL, $old_locale);

		return $output;
	}

	// }}}
	// {{{ public static function ordinalNumberFormat()

	/**
	 * Formats an integer as an ordinal number (1st, 2nd, 3rd)
	 *
	 * @param integer $value the numeric value to format.
	 *
	 * @see SwatNumber::ordinal()
	 *
	 * @deprecated Use {@link SwatNumber::ordinal()} instead.
	 */
	public static function ordinalNumberFormat($value)
	{
		return SwatNumber::ordinal($value);
	}

	// }}}
	// {{{ public static function byteFormat()

	/**
	 * Format bytes in human readible units
	 *
	 * By default, bytes are formatted using canonical, ambiguous, base-10
	 * prefixed units. Bytes may optionally be formatted using unambiguous IEC
	 * standard binary prefixes. See the National Institute of Standards and
	 * Technology's page on binary unit prefixes at
	 * {@link http://physics.nist.gov/cuu/Units/binary.html} for details.
	 *
	 * @param integer $value the value in bytes to format.
	 * @param integer $magnitude optional. The power of 2 to use as the unit
	 *                            base. This value will be rounded to the
	 *                            nearest ten if specified. If less than zero
	 *                            or not specified, the highest power less
	 *                            than <code>$value</code> will be used.
	 * @param boolean $iec_units optional. Whether or not to use IEC binary
	 *                            multiple prefixed units (Mebibyte). Defaults
	 *                            to using canonical units.
	 * @param integer $significant_digits optional. The number of significant
	 *                                     digits in the formatted result. If
	 *                                     null, the value will be rounded and
	 *                                     formatted one fractional digit.
	 *                                     Otherwise, the value is rounded to
	 *                                     the specified the number of digits.
	 *                                     By default, this is three. If there
	 *                                     are more integer digits than the
	 *                                     specified number of significant
	 *                                     digits, the value is rounded to the
	 *                                     nearest integer.
	 *
	 * @return string the byte value formated according to IEC units.
	 */
	public static function byteFormat($value, $magnitude = -1,
		$iec_units = false, $significant_digits = 3)
	{
		if ($iec_units) {
			$units = array(
				60 => 'EiB',
				50 => 'PiB',
				40 => 'TiB',
				30 => 'GiB',
				20 => 'MiB',
				10 => 'KiB',
				0  => 'bytes',
			);
		} else {
			$units = array(
				60 => 'EB',
				50 => 'PB',
				40 => 'TB',
				30 => 'GB',
				20 => 'MB',
				10 => 'KB',
				0  => 'bytes',
			);
		}

		$unit_magnitude = null;

		if ($magnitude >= 0) {
			$magnitude = intval(round($magnitude / 10) * 10);
			if (array_key_exists($magnitude, $units)) {
				$unit_magnitude = $magnitude;
			} else {
				$unit_magnitude = reset($units); // default magnitude
			}
		} else {
			if ($value == 0) {
				$unit_magnitude = 0;
			} else {
				$log = floor(log10($value) / log10(2)); // get log2()

				$unit_magnitude = reset($units); // default magnitude
				foreach ($units as $magnitude => $title) {
					if ($log >= $magnitude) {
						$unit_magnitude = $magnitude;
						break;
					}
				}
			}
		}

		$value = $value / pow(2, $unit_magnitude);

		if ($unit_magnitude == 0) {
			// 'bytes' are always formatted as integers
			$formatted_value = self::numberFormat($value, 0);
		} else {
			if ($significant_digits !== null) {
				// round to number of significant digits
				$integer_digits = floor(log10($value)) + 1;

				$fractional_digits =
					max($significant_digits - $integer_digits, 0);

				$formatted_value = self::numberFormat($value,
					$fractional_digits);
			} else {
				// just round to one fractional digit
				$formatted_value = self::numberFormat($value, 1);
			}
		}

		return $formatted_value . ' ' . $units[$unit_magnitude];
	}

	// }}}
	// {{{ public static function pad()

	/**
	 * Pads a string in a UTF-8 safe way.
	 *
	 * @param string $input the string to pad.
	 * @param int $pad_length length in characters to pad to.
	 * @param string $pad_string string to use for padding.
	 * @param int $pad_type type of padding to use: <code>STR_PAD_LEFT</code>,
	 *                       <code>STR_PAD_RIGHT</code>, or
	 *                       <code>STR_PAD_BOTH</code>.
	 *
	 * @return string the padded string.
	 */
	public static function pad($input, $pad_length, $pad_string = ' ',
		$pad_type = STR_PAD_RIGHT)
	{
		$output = '';
		$length = $pad_length - strlen($input);

		if ($pad_string === null || strlen($pad_string) == 0)
			$pad_string = ' ';

		if ($length > 0) {
			switch ($pad_type) {
			case STR_PAD_LEFT:
				$padding = str_repeat($pad_string, ceil($length / strlen($pad_string)));
				$output = substr($padding, 0, $length) . $input;
				break;

			case STR_PAD_BOTH:
				$left_length = floor($length / 2);
				$right_length = ceil($length / 2);
				$padding = str_repeat($pad_string,
					ceil($right_length / strlen($pad_string)));

				$output = substr($padding, 0, $left_length).$input.
					substr($padding, 0, $right_length);

				break;

			case STR_PAD_RIGHT:
			default:
				$padding = str_repeat($pad_string, ceil($length / strlen($pad_string)));
				$output = $input . substr($padding, 0, $length);
			}
		} else {
			$output = $input;
		}
		return $output;
	}

	// }}}
	// {{{ public static function toInteger()

	/**
	 * Convert a locale-formatted number and return it as an integer.
	 *
	 * If the string can not be converted to an integer, the method returns
	 * null. If the number has values after the decimal point, the value is
	 * rounded according to the rounding rules for PHP's
	 * {@link http://php.net/manual/en/function.intval.php intval} function.
	 *
	 * If the number is too large to fit in PHP's integer range (depends on
	 * system architecture), an exception is thrown.
	 *
	 * @param string $string the string to convert.
	 *
	 * @return integer the converted value or null if it could not be
	 *                  converted.
	 *
	 * @throws SwatException if the converted number is too large to fit in an
	 *                        integer.
	 */
	public static function toInteger($string)
	{
		$lc = localeconv();

		$string = self::parseNegativeNotation($string);

		// change all locale formatting to numeric formatting
		$remove_parts = array(
			$lc['positive_sign'] => '',
			$lc['thousands_sep'] => '',
		);

		$value = str_replace(array_keys($remove_parts),
			array_values($remove_parts), $string);

		// note: This might be done better with a regexp, though
		// checking too closely how well a number matches its locale
		// formatting could become annoying too. i.e. if 1000 was
		// rejected because it wasn't formatted 1,000

		if (is_numeric($value)) {
			if ($value > (float)PHP_INT_MAX)
				throw new SwatException(
					'Floating point value is too big to be an integer');

			if ($value < (float)(-PHP_INT_MAX - 1))
				throw new SwatException(
					'Floating point value is too small to be an integer');

			$value = intval($value);
		} else {
			$value = null;
		}

		return $value;
	}

	// }}}
	// {{{ public static function toFloat()

	/**
	 * Convert a locale-formatted number and return it as an float.
	 *
	 * If the string is not an float, the method returns null.
	 *
	 * @param string $string the string to convert.
	 *
	 * @return float The converted value.
	 */
	public static function toFloat($string)
	{
		$lc = localeconv();

		$string = self::parseNegativeNotation($string);

		// change all locale formatting to numeric formatting
		$remove_parts = array(
			$lc['decimal_point'] => '.',
			$lc['positive_sign'] => '',
			$lc['thousands_sep'] => '',
		);

		$value = str_replace(array_keys($remove_parts),
			array_values($remove_parts), $string);

		// note: This might be done better with a regexp, though
		// checking too closely how well a number matches its locale
		// formatting could become annoying too. i.e. if 1000 was
		// rejected because it wasn't formatted 1,000

		return (is_numeric($value)) ? floatval($value) : null;
	}
	// }}}
	// {{{ public static function toList()

	/**
	 * Convert an iterable object or array into a human-readable, delimited
	 * list.
	 *
	 * @param array|Iterator $iterator the object to convert to a list.
	 * @param string $conjunction the list's conjunction. Usually 'and' or
	 *                            'or'.
	 * @param string $delimiter the list delimiter. If list items should
	 *                          additionally be padded with a space, the delimiter
	 *                          should also include the space.
	 * @param boolean $display_final_delimiter whether or not the final list
	 *                          item should be separated from the list with a
	 *                          delimiter.
	 *
	 * @return string The formatted list.
	 *
	 * @throws SwatException if the iterator value is not an array or Iterator
	 *
	 * @todo Think about using a mask to make this as flexible as possible for
	 *       different locales.
	 */
	public static function toList($iterator, $conjunction = 'and',
		$delimiter = ', ', $display_final_delimiter = true)
	{
		if (is_array($iterator))
			$iterator = new ArrayIterator($iterator);

		if (!($iterator instanceof Iterator))
			throw new SwatException('Value is not an Iterator or array');

		if (count($iterator) == 1) {
			$iterator->rewind();
			$list = $iterator->current();
		} else {
			$count = 0;
			$list = '';

			foreach ($iterator as $value) {
				if ($count != 0) {
					if ($count == count($iterator) - 1) {
						$list.= ($display_final_delimiter
							&& count($iterator) > 2) ? $delimiter : ' ';

						$list.= $conjunction.' ';
					} else {
						$list.= $delimiter;
					}
				}

				$list.= $value;
				$count++;
			}
		}

		return $list;
	}
	// }}}
	// {{{ public static function toHumanReadableTimePeriod()

	/**
	 * Get a human-readable string representing the difference a time
	 * period.
	 *
	 * This method formats seconds as a time period such as
	 * "5 years, 3 months, 2 days, 5 seconds"
	 *
	 * @param integer $seconds Seconds to format
	 * @param boolean $largest_part If true, only the largest matching
	 *                              date part is returned. For the above
	 *                              example, "5 years" would be returned.
	 *
	 * @return string A human-readable time period
	 */
	public static function toHumanReadableTimePeriod($seconds,
		$largest_part = false)
	{
		// don't care about micro-seconds.
		$seconds = floor(abs($seconds));

		$minute = 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$month = $day * 30;
		$year = $day * 365;

		$periods = array();

		if ($seconds > $year) {
			$years = floor($seconds / $year);
			$seconds -= $year * $years;
			$periods[] = sprintf(
				Swat::ngettext('%s year', '%s years', $years),
				$years);
		}

		if ($seconds > $month) {
			$months = floor($seconds / $month);
			$seconds -= $month * $months;
			$periods[] = sprintf(
				Swat::ngettext('%s month', '%s months', $months),
				$months);
		}

		if ($seconds > $day) {
			$days = floor($seconds / $day);
			$seconds -= $day * $days;
			$periods[] = sprintf(
				Swat::ngettext('%s day', '%s days', $days),
				$days);
		}

		if ($seconds > $hour) {
			$hours = floor($seconds / $hour);
			$seconds -= $hour * $hours;
			$periods[] = sprintf(
				Swat::ngettext('%s hour', '%s hours', $hours),
				$hours);
		}

		if ($seconds > $minute) {
			$minutes = floor($seconds / $minute);
			$seconds -= $minute * $minutes;
			$periods[] = sprintf(
				Swat::ngettext('%s minute', '%s minutes', $minutes),
				$minutes);
		}

		if ($seconds > 0 || count($periods) == 0) {
			$periods[] = sprintf(
				Swat::ngettext('%s second', '%s seconds', $seconds),
				$seconds);
		}

		if ($largest_part && count($periods) > 0)
			return current($periods);
		else
			return self::toList($periods);
	}

	// }}}
	// {{{ public static function hash()

	/**
	 * Gets a unique hash of a string
	 *
	 * The hashing is as unique as md5 but the hash string is shorter than md5.
	 * This method is useful if hash strings will be visible to end-users and
	 * shorter hash strings are desired.
	 *
	 * @param string $string the string to get the unique hash for.
	 *
	 * @return string the unique hash of the given string. The returned string
	 *                 is safe to use inside a URI.
	 */
	public static function hash($string)
	{
		$hash = md5($string, true);
		$hash = base64_encode($hash);

		// remove padding characters
		$hash = str_replace('=', '', $hash);

		// use modified Base64 for URL varient
		$hash = str_replace('+', '*', $hash);
		$hash = str_replace('/', '-', $hash);

		return $hash;
	}

	// }}}
	// {{{ public static function getSalt()

	/**
	 * Gets a salt value of the specified length
	 *
	 * Useful for securing passwords or other one-way encrypted fields that
	 * may be succeptable to a dictionary attack.
	 *
	 * This method generates a random ASCII string of the specified length. All
	 * ASCII characters except the null character (0x00) may be included in
	 * the returned string.
	 *
	 * @param integer $length the desired length of the salt.
	 *
	 * @return string a salt value of the specified length.
	 */
	public static function getSalt($length)
	{
		$salt = '';
		for ($i = 0; $i < $length; $i++)
			$salt.= chr(mt_rand(1, 127));

		return $salt;
	}

	// }}}
	// {{{ public static function stripXHTMLTags()

	/**
	 * Removes all XHTML tags from a string
	 *
	 * This method is similar to the built-in
	 * {@link http://php.net/manual/en/function.strip-tags.php strip_tags}
	 * function in PHP but this method only strips XHTML tags. All other tags
	 * are left intact.
	 *
	 * @param string $string the string to remove XHTML tags from.
	 *
	 * @return string the given string with all XHTML tags removed.
	 */
	public static function stripXHTMLTags($string)
	{
		$elements = implode('|', self::$xhtml_elements);
		return preg_replace('/<\/?('.$elements.')[^<>]*?>/siu', '', $string);
	}

	// }}}
	// {{{ public static function linkify()

	/**
	 * Replaces all URI's in a string with anchor markup tags
	 *
	 * This method does not know if a URI is already inside markup so it is
	 * best to only use it on plain text.
	 *
	 * Only "http" and "https" URI's are currently supported.
	 *
	 * @param string $string the string to replace URI's in.
	 *
	 * @return string the given string with all URI's wrapped in anchor tags.
	 */
	public static function linkify($string)
	{
		return preg_replace ('@(https?://[^\s"\'\[\]]+\.[^\s"\'.\[\]]+)@iu',
			'<a href="\1">\1</a>', $string);
	}

	// }}}
	// {{{ public static function signedSerialize()

	/**
	 * Serializes and signs a value using a salt
	 *
	 * By signing serialized data, it is possible to detect tampering of
	 * serialized data. This is useful if serialized data is accepted from
	 * user editable <code>$_GET</code>, <code>$_POST</code> or
	 * <code>$_COOKIE data</code>.
	 *
	 * @param mixed $data the data to serialize.
	 * @param string $salt the signature salt.
	 *
	 * @return string the signed serialized value.
	 *
	 * @see SwatString::signedSerialize()
	 */
	public static function signedSerialize($data, $salt)
	{
		$serialized_data = serialize($data);
		$signature_data = self::hash($serialized_data.(string)$salt);

		return $signature_data.'|'.$serialized_data;
	}

	// }}}
	// {{{ public static function signedUnserialize()

	/**
	 * Unserializes a signed serialized value
	 *
	 * @param string $data the signed serialized data.
	 * @param string $salt the signature salt. This must be the same salt value
	 *                      used to serialize the value.
	 *
	 * @return mixed the unserialized value.
	 *
	 * @throws SwatInvalidSerializedDataException if the signed serialized data
	 *                                            has been tampered with.
	 *
	 * @see SwatString::signedSerialize()
	 */
	public static function signedUnserialize($data, $salt)
	{
		$data_exp = explode('|', (string)$data, 2);

		if (count($data_exp) != 2)
			throw new SwatInvalidSerializedDataException(
				"Invalid signed serialized data '{$data}'.", 0, $data);

		$signature_data = $data_exp[0];
		$serialized_data = $data_exp[1];

		if (self::hash($serialized_data.(string)$salt) != $signature_data)
			throw new SwatInvalidSerializedDataException(
				"Invalid signed serialized data '{$data}'.", 0, $data);

		return unserialize($serialized_data);
	}

	// }}}
	// {{{ public static function quoteJavaScriptString()

	/**
	 * Safely quotes a PHP string into a JavaScript string
	 *
	 * Strings are always quoted using single quotes. The characters documented
	 * at {@link http://code.google.com/p/doctype/wiki/ArticleXSSInJavaScript}
	 * are escaped to prevent XSS attacks.
	 *
	 * @param string $string the PHP string to quote as a JavaScript string.
	 *
	 * @return string the quoted JavaScript string. The quoted string is
	 *                 wrapped in single quotation marks and is safe to display
	 *                 in inline JavaScript.
	 */
	public static function quoteJavaScriptString($string)
	{
		$search = array(
			'\\',           // backslash quote
			'&',            // ampersand
			'<',            // less than
			'>',            // greater than
			'=',            // equal
			'"',            // double quote
			"'",            // single quote
			"\t",           // tab
			"\r\n",         // line ending (Windows)
			"\r",           // carriage return
			"\n",           // line feed
			"\xc2\x85",     // next line
			"\xe2\x80\xa8", // line separator
			"\xe2\x80\xa9", // paragraph separator
		);

		$replace = array(
			'\\\\',   // backslash quote
			'\x26',   // ampersand
			'\x3c',   // less than
			'\x3e',   // greater than
			'\x3d',   // equal
			'\x22',   // double quote
			'\x27',   // single quote
			'\t',     // tab
			'\n',     // line ending (Windows, transformed to line feed)
			'\n',     // carriage return (transformed to line feed)
			'\n',     // line feed
			'\u0085', // next line
			'\u2028', // line separator
			'\u2029', // paragraph separator
		);

		// escape XSS vectors
		$string = str_replace($search, $replace, $string);

		// quote string
		$string = "'".$string."'";

		return $string;
	}

	// }}}
	// {{{ public static function validateUtf8()

	/**
	 * Checks whether or not a string is valid UTF-8
	 *
	 * UTF-8 validation is adapted from Toby Inkster's PHP UTF-8 Validation
	 * Library found at
	 * {@link http://tobyinkster.co.uk/blog/2007/03/21/utf8-validation/}.
	 * Like Swat, Toby's library is available under the LGPL v2.1 or later.
	 *
	 * Unlike Toby's validation function, this method does not modify the
	 * original string.
	 *
	 * Limitations:
	 *
	 *   Note that in UTF-8, most characters have several alternative
	 *   representations. RFC 3629 says that the shortest representation
	 *   is the correct one. Other representations ("overlong forms")
	 *   are not valid. Earlier UTF-8 specifications did not prohibit
	 *   overlong forms, though suggest emitting a warning when one is
	 *   encountered. This function <stromg>does not check for overlong
	 *   forms!</strong>
	 *
	 * @param string $string the string to check.
	 *
	 * @return boolean true if the string is valid UTF-8 and false if it is not.
	 *
	 * @author    Michael Gauthier <mike@silverorange>
	 * @author    Toby Inkster <URL:http://tobyinkster.co.uk/>
	 * @copyright 2007 silverorange, 2011 Delta Systems
	 * @copyright 2007 Toby Inkster
	 */
	public static function validateUtf8($string)
	{
		$valid = true;
		$byte_length = mb_strlen($string, '8bit');
		$byte_position = 0;

		while ($byte_position < $byte_length && $valid) {
			// get current byte
			$z = ord(mb_substr($string, $byte_position, 1, '8bit'));

			// "Seven Z" notation - single byte character
			if ($z <= 0x7f) {
				// always valid

			// "Five Y, Six Z" notation - 2 byte character
			} elseif ($z >= 0xc2 && $z <= 0xdf) {
				if ($byte_position + 1 < $byte_length) {
					$byte_position++;
					$y = ord(mb_substr($string, $byte_position, 1, '8bit'));
					if ($y < 0x80 || $y > 0xbf) {
						$valid = false;
					}
				} else {
					$valid = false;
				}

			// "Four X, Six Y, Six Z" notation - 3 byte character
			} elseif ($z >= 0xe0 && $z <= 0xef) {
				if ($byte_position + 2 < $byte_length) {
					$byte_position++;
					$y = ord(mb_substr($string, $byte_position, 1, '8bit'));
					$byte_position++;
					$x = ord(mb_substr($string, $byte_position, 1, '8bit'));
					if (($y < 0x80 || $y > 0xbf) ||
						($x < 0x80 || $x > 0xbf)) {
						$valid = false;
					}
				} else {
					$valid = false;
				}

			// "Three W, Six X, Six Y, Six Z" notation - 4 byte character
			} elseif ($z >= 0xf0 && $z <= 0xf4) {
				if ($byte_position + 3 < $byte_length) {
					$byte_position++;
					$y = ord(mb_substr($string, $byte_position, 1, '8bit'));
					$byte_position++;
					$x = ord(mb_substr($string, $byte_position, 1, '8bit'));
					$byte_position++;
					$w = ord(mb_substr($string, $byte_position, 1, '8bit'));
					if (($y < 0x80 || $y > 0xbf) ||
						($x < 0x80 || $x > 0xbf) ||
						($w < 0x80 || $w > 0xbf)) {
						$valid = false;
					}
				} else {
					$valid = false;
				}

			// invalid start byte
			} else {
				$valid = false;
			}

			$byte_position++;
		}

		return $valid;
	}

	// }}}
	// {{{ private static function stripEntities()

	/**
	 * Strips entities from a string remembering their positions
	 *
	 * Stripped entities are replaces with a single special character. All
	 * parameters are passed by reference and nothing is returned by this
	 * function.
	 *
	 * @param string $string the string to strip entites from.
	 * @param array $matches the array to store matches in.
	 */
	private static function stripEntities(&$string, &$matches)
	{
		$reg_exp = '/&#?[a-z0-9]*?;/su';

		preg_match_all($reg_exp, $string, $matches, PREG_OFFSET_CAPTURE);

		$string = preg_replace($reg_exp, '*', $string);
	}

	// }}}
	// {{{ private static function insertEntities()

	/**
	 * Re-inserts stripped entities into a string in the correct positions
	 *
	 * The first two parameters are passed by reference and nothing is returned
	 * by this function.
	 *
	 * @param string $string the string to re-insert entites into.
	 * @param array $matches the array of stored matches.
	 * @param integer $hole_start ignore inserting entities between here
	 *                             and hole_end.
	 * @param integer $hole_end ignore inserting entities between here
	 *                             and hole_start.
	 * @param integer $hole_length the length of the new contents of the hole.
	 */
	private static function insertEntities(&$string, &$matches,
		$hole_start = -1, $hole_end = -1, $hole_length = 0)
	{
		for ($i = 0; $i < count($matches[0]); $i++) {
			$entity = $matches[0][$i][0];
			$position = $matches[0][$i][1];

			// offsets are byte offsets, not character offsets
			if (extension_loaded('mbstring') &&
				mb_internal_encoding() == 'UTF-8') {
				$substr = mb_substr($string, 0, $position, 'latin1');
				$byte_len = mb_strlen($substr, 'latin1');
				$char_len = mb_strlen($substr);
				$position -= ($byte_len - $char_len);
			}

			if ($position < $hole_start) {
				// this entity falls before the hole
				$string = substr($string, 0, $position).
					$entity.
					substr($string, $position + 1);

				$hole_start += strlen($entity);
				$hole_end += strlen($entity);
			} elseif ($hole_end <= $hole_start) {
				// break here because all remaining entities fall in the hole
				// extending infinitely to the right
				break;
			} elseif ($position >= $hole_end) {
				// this entity falls after the hole
				$offset = -$hole_end + $hole_length + $hole_start + 1;
				$string = substr($string, 0, $position + $offset).
					$entity.
					substr($string, $position + $offset + 1);

			} else {
				// this entity falls in the hole but we must account
				// for its unused size
				$hole_end += strlen($entity);
			}
		}
	}

	// }}}
	//{{{ private static function parseNegativeNotation()

	private static function parseNegativeNotation($string)
	{
		$lc = localeconv();

		switch ($lc['n_sign_posn']) {
		// negative sign shown as: (5.00)
		case 0:
			if (strpos($string, '(') !== false)
				return '-'.str_replace(
					array('(', ')'), array(), $string);
			break;

		// negative sign trails number: 5.00-
		case 2:
			if ($lc['negative_sign'] != '' &&
				strpos($string, $lc['negative_sign']) !== false)
				return '-'.str_replace(
					$lc['negative_sign'], '', $string);
			break;
		// negative sign prefixes number: -5.00
		default:
			if ($lc['negative_sign'] != '' &&
				strpos($string, $lc['negative_sign']) !== false)
				return str_replace(
					$lc['negative_sign'], '-', $string);
		}

		return $string;
	}

	// }}}
	// {{{ private static function getDecimalPrecision()

	private static function getDecimalPrecision($value)
	{
		$lc = localeconv();

		$decimal_pos = strpos((string) $value, $lc['decimal_point']);

		return ($decimal_pos !== false) ?
				strlen($value) - $decimal_pos - strlen($lc['decimal_point']) : 0;
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Don't allow instantiation of the SwatString object
	 *
	 * This class contains only static methods and should not be instantiated.
	 */
	private function __construct()
	{
	}

	// }}}
}

