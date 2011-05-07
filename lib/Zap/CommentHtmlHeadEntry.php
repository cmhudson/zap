<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/HtmlHeadEntry.php';

/**
 * Stores and outputs an HTML head entry for an XML comment
 *
 * @package   Zap
 * @copyright 2006 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Zap_CommentHtmlHeadEntry extends Zap_HtmlHeadEntry
{
	// {{{ protected properties

	protected $comment;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new HTML head entry
	 *
	 * @param string  $comment the comment of this entry.
	 * @param integer $package_id the package id of the package this HTML head
	 *                             entry belongs to.
	 */
	public function __construct($comment, $package_id = null)
	{
		parent::__construct(md5($comment), $package_id);
		$this->comment = $comment;
	}

	// }}}
	// {{{ public function display()

	public function display($uri_prefix = '', $tag = null)
	{
		// double dashes are not allowed in XML comments
		$comment = str_replace('--', '—', $this->comment);
		printf('<!-- %s -->', $comment);
	}

	// }}}
	// {{{ public function displayInline()

	public function displayInline($path)
	{
		$this->display();
	}

	// }}}
}


