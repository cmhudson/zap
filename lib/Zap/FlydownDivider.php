<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/Option.php';

/**
 * A class representing a divider in a flydown
 *
 * This class is for semantic purposed only. The flydown handles all the
 * displaying of dividers and regular flydown options.
 *
 * @package   Zap
 * @copyright 2005-2006 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Zap_FlydownDivider extends Zap_Option
{
	// {{{ public function __construct()

	/**
	 * Creates a flydown option
	 *
	 * @param mixed $value value of the option. This defaults to null.
	 * @param string $title displayed title of the divider. This defaults to
	 *                       two em dashes.
	 */
	public function __construct($value = null, $title = null)
	{
		if ($title === null)
			$title = str_repeat('—', 6);

		$this->value = $value;
		$this->title = $title;
	}

	// }}}
}


