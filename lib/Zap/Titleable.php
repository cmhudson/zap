<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * Objects that are titleable have a title that may be gotten
 *
 * @package   Zap
 * @copyright 2006-2010 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SwatTitleable::getTitle()
 */
interface Zap_Titleable
{
	// {{{ public function getTitle()

	/**
	 * Gets the title of this object
	 *
	 * @return string the title of this object.
	 */
	public function getTitle();

	// }}}
	// {{{ public function getTitleContentType()

	/**
	 * Gets the content-type of the title of this object
	 *
	 * @return string the content-type of the title of this object. Returns
	 *                 'text/xml' for XHTML fragments and 'text/plain' for
	 *                 plain text.
	 */
	public function getTitleContentType();

	// }}}
}

