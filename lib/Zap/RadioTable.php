<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Zap/RadioList.php';
require_once 'Zap/HtmlTag.php';

/**
 * Special radio-list that can display multi-line list items using a
 * tabular format
 *
 * @package   Zap
 * @copyright 2006 silverorange, 2011 Delta Systems
 */
class Zap_RadioTable extends Zap_RadioList
{
	// {{{ public function __construct()

	/**
	 * Creates a new radio table
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/swat/styles/swat-radio-table.css',
			Swat::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		$options = $this->getOptions();

		if (!$this->visible || $options === null)
			return;

		SwatWidget::display();

		// add a hidden field so we can check if this list was submitted on
		// the process step
		$this->getForm()->addHiddenField($this->id.'_submitted', 1);

		if ($this->show_blank)
			$options = array_merge(
				array(new SwatOption(null, $this->blank_title)),
				$options);

		$table_tag = new SwatHtmlTag('table');
		$table_tag->id = $this->id;
		$table_tag->class = $this->getCSSClassString();
		$table_tag->open();

		foreach ($options as $index => $option) {
			$this->displayRadioTableOption($option, $index);
		}

		$table_tag->close();
	}

	// }}}
	// {{{ protected function displayRadioTableOption()

	/**
	 * Displays a single option in this radio table
	 *
	 * @param SwatOption $option the option to display.
	 * @param integer $index the numeric index of the option in this list.
	 */
	protected function displayRadioTableOption(SwatOption $option, $index)
	{
		$tr_tag = $this->getTrTag($option, $index);

		// add option-specific CSS classes from option metadata
		$classes = $this->getOptionMetadata($option, 'classes');
		if (is_array($classes)) {
			$tr_tag->class = implode(' ', $classes);
		} elseif ($classes) {
			$tr_tag->class = strval($classes);
		}

		$tr_tag->open();

		if ($option instanceof SwatFlydownDivider) {
			echo '<td class="swat-radio-table-input">';
			echo '&nbsp;';
			echo '</td><td class="swat-radio-table-label">';
			$this->displayDivider($option);
			echo '</td>';
		} else {
			echo '<td class="swat-radio-table-input">';
			$this->displayOption($option);
			printf('</td><td id="%s" class="swat-radio-table-label">',
				$this->id.'_'.(string)$option->value.'_label');

			$this->displayOptionLabel($option);
			echo '</td>';
		}

		$tr_tag->close();
	}

	// }}}
	// {{{ protected function getTrTag()

	/**
	 * Gets the tr tag used to display a single option in this radio table
	 *
	 * @param SwatOption $option the option to display.
	 * @param integer $index the numeric index of the option in this list.
	 */
	protected function getTrTag(SwatOption $option, $index)
	{
		return new SwatHtmlTag('tr');
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this radio table
	 *
	 * @return array the array of CSS classes that are applied to this radio
	 *                table.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('swat-radio-table');
		$classes = array_merge($classes, $this->classes);
		return $classes;
	}

	// }}}
}


