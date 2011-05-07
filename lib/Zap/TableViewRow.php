<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/UIObject.php';

/**
 * Base class for a extra row displayed at the bottom of a table view
 *
 * @package   Zap
 * @copyright 2005-2006 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class Zap_TableViewRow extends Zap_UIObject
{
	// {{{ public properties

	/**
	 * The {@link SwatTableView} associated with this row
	 *
	 * @var SwatTableView
	 */
	public $view = null;

	/**
	 * Unique identifier of this row
	 *
	 * @param string
	 */
	public $id = null;

	// }}}
	// {{{ protected properties

	/**
	 * Whether or not this row has been processed
	 *
	 * @var boolean
	 *
	 * @see SwatTableViewRow::process()
	 */
	protected $processed = false;

	/**
	 * Whether or not this row has been displayed
	 *
	 * @var boolean
	 *
	 * @see SwatTableViewRow::display()
	 */
	protected $displayed = false;

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this row
	 *
	 * This method does nothing and is implemented here so subclasses do not
	 * need to implement it.
	 *
	 * Row initialization happens during table-view initialization. Rows are
	 * initialized after columns.
	 */
	public function init()
	{
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this row
	 *
	 * This method does nothing and is implemented here so subclasses do not
	 * need to implement it.
	 *
	 * Row processing happens during table-view processing. Rows are processed
	 * after columns.
	 */
	public function process()
	{
		$this->processed = true;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this row
	 */
	public function display()
	{
		$this->displayed = true;
	}

	// }}}
	// {{{ public function isProcessed()

	/**
	 * Whether or not this row is processed
	 *
	 * @return boolean whether or not this row is processed.
	 */
	public function isProcessed()
	{
		return $this->processed;
	}

	// }}}
	// {{{ public function isDisplayed()

	/**
	 * Whether or not this row is displayed
	 *
	 * @return boolean whether or not this row is displayed.
	 */
	public function isDisplayed()
	{
		return $this->displayed;
	}

	// }}}
	// {{{ public function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required by this row
	 *
	 * All inline JavaScript is displayed after the table-view has been
	 * displayed.
	 *
	 * @return string the inline JavaScript required by this row.
	 */
	public function getInlineJavaScript()
	{
		return '';
	}

	// }}}
	// {{{ public function getVisibleByCount()

	/**
	 * Gets whether or not to show this row based on a count of rows
	 *
	 * By default if there are no entries in the table model, this row is not
	 * shown.
	 *
	 * @param integer $count the number of entries in this row's view's model.
	 *
	 * @return boolean true if this row should be shown and false if it should
	 *                  not.
	 */
	public function getVisibleByCount($count)
	{
		if ($count == 0)
			return false;

		return true;
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the SwatHtmlHeadEntry objects needed by this row
	 *
	 * If this row has not been displayed, an empty set is returned to reduce
	 * the number of required HTTP requests.
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this row.
	 */
	public function getHtmlHeadEntrySet()
	{
		if ($this->isDisplayed())
			$set = new SwatHtmlHeadEntrySet($this->html_head_entry_set);
		else
			$set = new SwatHtmlHeadEntrySet();

		return $set;
	}

	// }}}
	// {{{ public function copy()

	/**
	 * Performs a deep copy of the UI tree starting with this UI object
	 *
	 * @param string $id_suffix optional. A suffix to append to copied UI
	 *                           objects in the UI tree.
	 *
	 * @return SwatUIObject a deep copy of the UI tree starting with this UI
	 *                       object.
	 *
	 * @see SwatUIObject::copy()
	 */
	public function copy($id_suffix = '')
	{
		$copy = parent::copy($id_suffix);

		if ($id_suffix != '' && $copy->id !== null)
			$copy->id = $copy->id.$id_suffix;

		return $copy;
	}

	// }}}
}


