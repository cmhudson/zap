<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Concentrate/Concentrator.php';
require_once 'Zap/Object.php';
require_once 'Zap/HtmlHeadEntry.php';
require_once 'Zap/StyleSheetHtmlHeadEntry.php';
require_once 'Zap/JavaScriptHtmlHeadEntry.php';

/**
 * A collection of HTML head entries
 *
 * This collection class manages all the sorting, merging and globbing
 * of entries.
 *
 * @package   Zap
 * @copyright 2006-2010 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Zap_HtmlHeadEntrySet extends Zap_Object
{
	// {{{ protected properties

	/**
	 * HTML head entries managed by this collection
	 *
	 * Entries are indexed by URI.
	 *
	 * @var array
	 */
	protected $entries = array();

	/**
	 * Maps HTML head entry URIs to {@link SwatHtmlHeadEntry} class names
	 *
	 * @see SwatHtmlHeadEntrySet::addEntry()
	 * @see SwatHtmlHeadEntrySet::addTypeMapping()
	 */
	protected $type_map = array(
		'/\.js$/'  => 'Zap_JavaScriptHtmlHeadEntry',
		'/\.css$/' => 'Zap_StyleSheetHtmlHeadEntry',
	);

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new HTML head entry collection
	 *
	 * @param SwatHtmlHeadEntrySet $set an optional existing HTML head entry
	 *                                   set to build this set from.
	 */
	public function __construct(Zap_HtmlHeadEntrySet $set = null)
	{
		if ($set !== null) {
			$this->addEntrySet($set);
		}
	}

	// }}}
	// {{{ public function addEntry()

	/**
	 * Adds a HTML head entry to this set
	 *
	 * @param SwatHtmlHeadEntry|string $entry the entry to add.
	 */
	public function addEntry($entry, $package_id = null)
	{
		if (is_string($entry)) {
			$class = $this->getClassFromType($entry);

			if ($class === null) {
				throw new SwatClassNotFoundException(
					'SwatHtmlHeadEntry class not found for entry string of "'.
					$entry.'".');
			}

			$entry = new $class($entry, $package_id);
		}

		if (!($entry instanceof Zap_HtmlHeadEntry)) {
			throw new SwatInvalidTypeException(
				'Added entry must be either a string or an instance of a'.
				'SwatHtmlHeadEntry.', 0, $entry);
		}

		$uri = $entry->getUri();
		if (!array_key_exists($uri, $this->entries)) {
			$this->entries[$uri] = $entry;
		}
	}

	// }}}
	// {{{ public function addEntrySet()

	/**
	 * Adds a set of HTML head entries to this set
	 *
	 * @param SwatHtmlHeadEntrySet $set the set to add.
	 */
	public function addEntrySet(Zap_HtmlHeadEntrySet $set)
	{
		$this->entries = array_merge($this->entries, $set->entries);
	}

	// }}}
	// {{{ public function toArray()

	public function toArray()
	{
		return $this->entries;
	}

	// }}}
	// {{{ public function addTypeMapping()

	public function setTypeMapping($type, $class = null)
	{
		if (is_string($type)) {
			if ($class === null) {
				throw new InvalidArgumentException(
					'If $type is specified, $class is required');
			}
			$type = array($type => (string)$class);
			$class = null;
		}

		if (!is_array($type)) {
			throw new InvalidArgumentException(
				'Type must either be an array or a string.');
		}

		if ($class !== null) {
			throw new InvalidArgumentException(
				'If $type is an array, $class must not be specified.');
		}

		$this->type_map = array_merge($this->type_map, $type);
	}

	// }}}
	// {{{ protected function getClassFromType()

	protected function getClassFromType($entry)
	{
		$class = null;

		foreach ($this->type_map as $type => $type_class) {
			if (preg_match($type, $entry) === 1) {
				$class = $type_class;
				break;
			}
		}

		return $class;
	}

	// }}}
}


