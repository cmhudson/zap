<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/DisplayableContainer.php';
require_once 'Zap/Titleable.php';
require_once 'Zap/HtmlTag.php';
require_once 'Zap/String.php';
require_once 'Zap/Message.php';

/**
 * A container to use around control widgets in a form
 *
 * Adds a label and space to output messages.
 *
 * @package   Zap
 * @copyright 2004-2010 silverorange, 2011 Delta Systems
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Zap_FormField extends Zap_DisplayableContainer implements Zap_Titleable
{
	// {{{ constants

	const DISPLAY_REQUIRED = 1;
	const DISPLAY_OPTIONAL = 2;

	// }}}
	// {{{ public properties

	/**
	 * The visible name for this field, or null
	 *
	 * @var string
	 */
	public $title = null;

	/**
	 * Optional content type for the title
	 *
	 * Default text/plain, use text/xml for XHTML fragments.
	 *
	 * @var string
	 */
	public $title_content_type = 'text/plain';

	/*
	 * Display a visible indication that this field is required
	 *
	 * @var boolean
	 */
	public $required = false;

	/**
	 * Display the required status of this field
	 *
	 * Bitwise display options:
	 * Zap_FormField::DISPLAY_REQUIRED = display "required" if this field
	 *                                   is required
	 * Zap_FormField::DISPLAY_OPTIONAL = display "optional" if this field
	 *                                   is not required
	 *
	 * @var integer
	 */
	public $required_status_display = self::DISPLAY_REQUIRED;

	/**
	 * Optional note of text to display with the field
	 *
	 * @var string
	 */
	public $note = null;

	/**
	 * Optional content type for the note
	 *
	 * Default text/plain, use text/xml for XHTML fragments.
	 *
	 * @var string
	 */
	public $note_content_type = 'text/plain';

	/**
	 * Access key
	 *
	 * Sets an access key for the label of this form field, if one exists.
	 *
	 * @var string
	 */
	public $access_key = null;

	/**
	 * Whether or not to show a colon after the title of this form field
	 *
	 * By default, a colon is shown.
	 *
	 * @var boolean
	 */
	public $show_colon = true;

	/*
	 * Display the title of the form field after the widget code
	 *
	 * This is automatically set for some widget types, but defaults to null
	 * (which we treat the same as false) to allow the value to be manually set
	 * for said widgets.
	 *
	 * @var boolean
	 */
	public $title_reversed = null;

	/**
	 * Whether or not to display validation messages in this form field
	 *
	 * Defaults to true. Set to false to prevent the displaying of messages in
	 * this form field.
	 *
	 * @var boolean
	 */
	public $display_messages = true;

	// }}}
	// {{{ protected properties

	/**
	 * Container tag to use
	 *
	 * Subclasses can change this to change their appearance.
	 *
	 * @var string
	 */
	protected $container_tag = 'div';

	/**
	 * Contents tag to use
	 *
	 * Subclasses can change this to change their appearance.
	 *
	 * @var string
	 */
	protected $contents_tag = 'div';

	/**
	 * A CSS class name set by the subwidgets in this form field
	 *
	 * @var string
	 *
	 * @see Zap_FormField::notifyOfAdd()
	 */
	protected $widget_class;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new form field
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see Zap_Widget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/swat/styles/swat-message.css',
			Zap::PACKAGE_ID);
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this form field
	 *
	 * Satisfies the {Zap_Titleable::getTitle()} interface.
	 *
	 * @return string the title of this form field.
	 */
	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function getTitleContentType()

	/**
	 * Gets the title content-type of this form field
	 *
	 * Implements the {@link Zap_Titleable::getTitleContentType()} interface.
	 *
	 * @return string the title content-type of this form field.
	 */
	public function getTitleContentType()
	{
		return $this->title_content_type;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this form field
	 *
	 * Associates a label with the first widget of this container.
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->getFirst() === null)
			return;

		Zap_Widget::display();

		$container_tag = new Zap_HtmlTag($this->container_tag);
		$container_tag->id = $this->id;
		$container_tag->class = $this->getCSSClassString();

		$container_tag->open();

		if ($this->title_reversed === true) {
			$this->displayContent();
			$this->displayTitle();
		} else {
			$this->displayTitle();
			$this->displayContent();
		}

		$this->displayMessages();
		$this->displayNotes();
		$container_tag->close();
	}

	// }}}
	// {{{ protected function displayTitle()

	protected function displayTitle()
	{
		if ($this->title === null && $this->access_key === null)
			return;

		$title_tag = $this->getTitleTag();
		$title_tag->open();
		$title_tag->displayContent();
		$this->displayRequiredStatus();
		$title_tag->close();
	}

	// }}}
	// {{{ protected function displayRequiredStatus()

	protected function displayRequiredStatus()
	{
		if ($this->required &&
			$this->required_status_display & self::DISPLAY_REQUIRED) {

			$span_tag = new Zap_HtmlTag('span');
			$span_tag->class = 'swat-required';
			$span_tag->setContent(sprintf(' (%s)', Zap::_('required')));
			$span_tag->display();
		} elseif (!$this->required &&
			$this->required_status_display & self::DISPLAY_OPTIONAL) {

			$span_tag = new Zap_HtmlTag('span');
			$span_tag->class = 'swat-optional';
			$span_tag->setContent(sprintf(' (%s)', Zap::_('optional')));
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$contents_tag = new Zap_HtmlTag($this->contents_tag);
		$contents_tag->class = 'swat-form-field-contents';

		$contents_tag->open();
		$this->displayChildren();
		$contents_tag->close();
	}

	// }}}
	// {{{ protected function displayMessages()

	protected function displayMessages()
	{
		if (!$this->display_messages || !$this->hasMessage())
			return;

		$messages = $this->getMessages();

		$message_ul = new Zap_HtmlTag('ul');
		$message_ul->class = 'swat-form-field-messages';
		$message_li = new Zap_HtmlTag('li');

		$message_ul->open();

		foreach ($messages as $message) {
			$message_li->class = $message->getCSSClassString();
			$message_li->setContent($message->primary_content,
				$message->content_type);

			if ($message->secondary_content !== null) {
				$secondary_span = new Zap_HtmlTag('span');
				$secondary_span->setContent($message->secondary_content,
					$message->content_type);

				$message_li->open();
				$message_li->displayContent();
				echo ' ';
				$secondary_span->display();
				$message_li->close();
			} else {
				$message_li->display();
			}
		}

		$message_ul->close();
	}

	// }}}
	// {{{ protected function displayNotes()

	protected function displayNotes()
	{
		$notes = array();

		if ($this->note !== null) {
			$note = new Zap_Message($this->note);
			$note->content_type = $this->note_content_type;
			$notes[] = $note;
		}

		$control = $this->getFirstDescendant('Zap_Control');
		if ($control !== null) {
			$note = $control->getNote();
			if ($note !== null)
				$notes[] = $note;
		}

		if (count($notes) == 1) {
			$note = reset($notes);
			$note_div = new Zap_HtmlTag('div');
			$note_div->class = 'swat-note';
			$note_div->setContent($note->primary_content, $note->content_type);
			$note_div->display();
		} elseif (count($notes) > 1) {
			$note_list = new Zap_HtmlTag('ul');
			$note_list->class = 'swat-note';
			$note_list->open();

			$li_tag = new Zap_HtmlTag('li');
			foreach ($notes as $note) {
				$li_tag->setContent($note->primary_content,
					$note->content_type);

				$li_tag->display();
			}

			$note_list->close();
		}
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this form field
	 *
	 * @return array the array of CSS classes that are applied to this form
	 *                field.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('swat-form-field');

		if ($this->widget_class !== null)
			$classes[] = $this->widget_class;

		if ($this->display_messages && $this->hasMessage())
			$classes[] = 'swat-form-field-with-messages';

		if ($this->required)
			$classes[] = 'swat-required';

		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getTitleTag()

	/**
	 * Get a Zap_HtmlTag to display the title
	 *
	 * Subclasses can change this to change their appearance.
	 *
	 * @return Zap_HtmlTag a tag object containing the title.
	 */
	protected function getTitleTag()
	{
		$label_tag = new Zap_HtmlTag('label');

		if ($this->title !== null) {
			if ($this->show_colon)
				$label_tag->setContent(sprintf(Zap::_('%s: '), $this->title),
					$this->title_content_type);
			else
				$label_tag->setContent($this->title, $this->title_content_type);
		}

		$label_tag->for = $this->getFocusableHtmlId();
		$label_tag->accesskey = $this->access_key;

		return $label_tag;
	}

	// }}}
	// {{{ protected function notifyOfAdd()

	/**
	 * Notifies this widget that a widget was added
	 *
	 * This sets class propertes on this form field when certain classes of
	 * widgets are added.
	 *
	 * @param Zap_Widget $widget the widget that has been added.
	 *
	 * @see Zap_Container::notifyOfAdd()
	 */
	protected function notifyOfAdd($widget)
	{
		if (class_exists('Zap_Checkbox') && $widget instanceof Zap_Checkbox) {
			$this->widget_class = 'swat-form-field-checkbox';

			// don't set these properties if title_reversed is explicitly set in
			// the xml
			if ($this->title_reversed === null) {
				$this->title_reversed = true;
				$this->show_colon = false;
			}
		} elseif (class_exists('Zap_SearchEntry') &&
			$widget instanceof Zap_SearchEntry) {
			$this->show_colon = false;
		}
	}

	// }}}

	// deprecated
	// {{{ protected function displayRequired()

	/**
	 * @deprecated use the displayRequiredStatus() method instead
	 */
	protected function displayRequired()
	{
		if ($this->required) {
			$span_tag = new Zap_HtmlTag('span');
			$span_tag->class = 'swat-required';
			$span_tag->setContent(sprintf(' (%s)', Zap::_('required')));
			$span_tag->display();
		}
	}

	// }}}
}


