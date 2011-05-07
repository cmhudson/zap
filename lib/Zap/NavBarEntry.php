<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/Object.php';

/**
 * Entry for the navbar navigation tool
 *
 * @package   Zap
 * @copyright 2005-2006 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see SwatNavBar
 */
class Zap_NavBarEntry extends Zap_Object
{
	// {{{ public properties

	/**
	 * The visible title of this entry
	 *
	 * @var string
	 */
	public $title;

	/**
	 * The the web address that this navbar entry points to
	 *
	 * This property is optional. If it is not present this entry will not
	 * display as a hyperlink.
	 *
	 * @var string
	 */
	public $link;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new navbar entry
	 *
	 * @param string $title the title of this entry.
	 * @param string $link the web address this entry points to.
	 */
	public function __construct($title, $link = null)
	{
		$this->title = $title;
		$this->link = $link;
	}

	// }}}
}


