<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/Widget.php';
require_once 'Zap/UIParent.php';
require_once 'Zap/YUI.php';
require_once 'Zap/NoteBookPage.php';
require_once 'Zap/NoteBookChild.php';
require_once 'Zap/HtmlTag.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';

/**
 * Notebook widget for containing {@link SwatNoteBookPage} pages
 *
 * @package   Zap
 * @copyright 2007 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SwatNoteBookPage
 */
class Zap_NoteBook extends Zap_Widget implements Zap_UIParent
{
	// {{{ constants

	/**
	 * Positions notebook tabs on the top of notebook pages.
	 */
	const POSITION_TOP    = 1;

	/**
	 * Positions notebook tabs on the right of notebook pages.
	 */
	const POSITION_RIGHT  = 2;

	/**
	 * Positions notebook tabs on the bottom of notebook pages.
	 */
	const POSITION_BOTTOM = 3;

	/**
	 * Positions notebook tabs on the left of notebook pages.
	 */
	const POSITION_LEFT   = 4;

	// }}}
	// {{{ public properties

	/**
	 * Position of tabs for this notebook
	 *
	 * @var integer
	 */
	public $tab_position = self::POSITION_TOP;

	/**
	 * Selected page
	 *
	 * The id of the {@link SwatNoteBookPage} to show as selected. By default,
	 * the first page is selected.
	 *
	 * @var string
	 */
	public $selected_page;

	// }}}
	// {{{ protected properties

	/**
	 * Note book child objects initally added to this widget
	 *
	 * @var array
 	 */
	protected $children = array();

	/**
	 * Pages affixed to this widget
	 *
	 * @var array
 	 */
	protected $pages = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new notebook
	 *
	 * @param string $id a non-visable unique id for this widget.
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('tabview'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addStyleSheet('packages/swat/styles/swat-note-book.css',
			Swat::PACKAGE_ID);
	}

	// }}}
	// {{{ public function addChild()

	/**
	 * Adds a {@link SwatNoteBookChild} to this notebook
	 *
	 * This method fulfills the {@link SwatUIParent} interface. It is used
	 * by {@link SwatUI} when building a widget tree and should not need to be
	 * called elsewhere. To add a notebook page to a notebook, use
	 * {@link SwatNoteBook::addPage()}.
	 *
	 * Note: This is the only way to add a SwatNoteBookChild that is not a
	 *       SwatNoteBookPage.
	 *
	 * @param SwatNoteBookChild $child the notebook child to add.
	 *
	 * @throws SwatInvalidClassException if the given object is not an instance
	 *                                    of SwatNoteBookChild.
	 *
	 * @see SwatUIParent
	 */
	public function addChild(SwatObject $child)
	{
		if ($child instanceof SwatNoteBookChild) {
			$this->children[] = $child;
			$child->parent = $this;
		} else {
			throw new SwatInvalidClassException(
				'Only SwatNoteBookChild objects may be nested within a '.
				'SwatNoteBook object.', 0, $child);
		}
	}

	// }}}
	// {{{ public function addPage()

	/**
	 * Adds a {@link SwatNoteBookPage} to this notebook
	 *
	 * @param SwatNoteBookPage $page the notebook page to add.
	 */
	public function addPage(SwatNoteBookPage $page)
	{
		$this->pages[] = $page;
		$page->parent = $this;
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this notebook
	 */
	public function init()
	{
		parent::init();

		foreach ($this->children as $child) {
			$child->init();
			foreach ($child->getPages() as $page) {
				$this->addPage($page);
			}
		}

		foreach ($this->pages as $page) {
			$page->init();
		}
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this notebook
	 */
	public function process()
	{
		parent::process();
		foreach ($this->pages as $page) {
			$page->process();
		}
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this notebook
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$li_counter = 0;
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'yui-navset';
		$div_tag->open();

		echo'<ul class="yui-nav">';
		foreach ($this->pages as $page) {
			if (!$page->visible)
				continue;

			$li_counter++;
			$li_tag = new SwatHtmlTag('li');

			if (($this->selected_page=== null && $li_counter == 1) ||
				($page->id == $this->selected_page))
				$li_tag->class = 'selected';

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = '#'.$page->id;

			$em_tag = new SwatHtmlTag('em');
			$em_tag->setContent($page->title);

			$li_tag->open();
			$anchor_tag->open();
			$em_tag->display();
			$anchor_tag->close();
			$li_tag->close();
		}
		echo'</ul>';

		echo '<div class="yui-content">';
		foreach ($this->pages as $page)
			$page->display();

		echo '</div>';
		$div_tag->close();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function printWidgetTree()

	public function printWidgetTree()
	{
		echo get_class($this), ' ', $this->id;

		$children = $this->getChildren();
		if (count($children) > 0) {
			echo '<ul>';
			foreach ($children as $child) {
				echo '<li>';
				$child->printWidgetTree();
				echo '</li>';
			}
			echo '</ul>';
		}
	}

	// }}}
	// {{{ public function getMessages()

	/**
	 * Gets all messaages
	 *
	 * Gathers all messages from pages of this notebook and from this notebook
	 * itself.
	 *
	 * @return array an array of {@link SwatMessage} objects.
	 *
	 * @see SwatMessage
	 */
	public function getMessages()
	{
		$messages = parent::getMessages();

		foreach ($this->pages as $page)
			$messages = array_merge($messages, $page->getMessages());

		return $messages;
	}

	// }}}
	// {{{ public function hasMessage()

	/**
	 * Checks for the presence of messages
	 *
	 * @return boolean true if this notebook or the subtree below this notebook
	 *                  has one or more messages.
	 */
	public function hasMessage()
	{
		$has_message = parent::hasMessage();

		foreach ($this->pages as $page) {
			if ($page->hasMessage()) {
				$has_message = true;
				break;
			}
		}

		return $has_message;
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the {@link SwatHtmlHeadEntry} objects needed by this notebook
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this notebook and any UI objects in this
	 *                               notebook's widget subtree.
	 *
	 * @see SwatUIObject::getHtmlHeadEntrySet()
	 */
	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();

		foreach ($this->pages as $page)
			$set->addEntrySet($page->getHtmlHeadEntrySet());

		return $set;
	}

	// }}}
	// {{{ public function getDescendants()

	/**
	 * Gets descendant UI-objects
	 *
	 * @param string $class_name optional class name. If set, only UI-objects
	 *                            that are instances of <i>$class_name</i> are
	 *                            returned.
	 *
	 * @return array the descendant UI-objects of this notebook. If descendant
	 *                objects have identifiers, the identifier is used as the
	 *                array key.
	 *
	 * @see SwatUIParent::getDescendants()
	 */
	public function getDescendants($class_name = null)
	{
		if (!($class_name === null ||
			class_exists($class_name) || interface_exists($class_name)))
			return array();

		$out = array();

		foreach ($this->pages as $page) {
			if ($class_name === null || $page instanceof $class_name) {
				if ($page->id === null)
					$out[] = $page;
				else
					$out[$page->id] = $page;
			}

			if ($page instanceof SwatUIParent)
				$out = array_merge($out,
					$page->getDescendants($class_name));
		}

		return $out;
	}

	// }}}
	// {{{ public function getFirstDescendant()

	/**
	 * Gets the first descendant UI-object of a specific class
	 *
	 * @param string $class_name class name to look for.
	 *
	 * @return SwatUIObject the first descendant UI-object or null if no
	 *                       matching descendant is found.
	 *
	 * @see SwatUIParent::getFirstDescendant()
	 */
	public function getFirstDescendant($class_name)
	{
		if (!class_exists($class_name) && !interface_exists($class_name))
			return null;

		$out = null;

		foreach ($this->pages as $page) {
			if ($page instanceof $class_name) {
				$out = $page;
				break;
			}

			if ($page instanceof SwatUIParent) {
				$out = $page->getFirstDescendant($class_name);
				if ($out !== null)
					break;
			}
		}

		return $out;
	}

	// }}}
	// {{{ public function getDescendantStates()

	/**
	 * Gets descendant states
	 *
	 * Retrieves an array of states of all stateful UI-objects in the widget
	 * subtree below this notebook.
	 *
	 * @return array an array of UI-object states with UI-object identifiers as
	 *                array keys.
	 */
	public function getDescendantStates()
	{
		$states = array();

		foreach ($this->getDescendants('SwatState') as $id => $object)
			$states[$id] = $object->getState();

		return $states;
	}

	// }}}
	// {{{ public function setDescendantStates()

	/**
	 * Sets descendant states
	 *
	 * Sets states on all stateful UI-objects in the widget subtree below this
	 * notebook.
	 *
	 * @param array $states an array of UI-object states with UI-object
	 *                       identifiers as array keys.
	 */
	public function setDescendantStates(array $states)
	{
		foreach ($this->getDescendants('SwatState') as $id => $object)
			if (isset($states[$id]))
				$object->setState($states[$id]);
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

		foreach ($this->pages as $key => $page) {
			$copy_page = $page->copy($id_suffix);
			$copy_page->parent = $copy;
			$copy->pages[$key] = $copy_page;
		}

		return $copy;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript used by this notebook
	 *
	 * @return string the inline JavaScript used by this notebook.
	 */
	protected function getInlineJavaScript()
	{
		switch ($this->tab_position) {
		case self::POSITION_RIGHT:
			$position = 'right';
			break;
		case self::POSITION_LEFT:
			$position = 'left';
			break;
		case self::POSITION_BOTTOM:
			$position = 'bottom';
			break;
		case self::POSITION_TOP:
		default:
			$position = 'top';
			break;
		}

		return sprintf("var %s_obj = new YAHOO.widget.TabView(".
				"'%s', {orientation: '%s'});",
				$this->id, $this->id, $position);
	}

	// }}}
}


