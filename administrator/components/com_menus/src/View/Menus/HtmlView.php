<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_menus
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Menus\Administrator\View\Menus;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Menus\Administrator\Model\MenusModel;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The HTML Menus Menu Menus View.
 *
 * @since  1.6
 */
class HtmlView extends BaseHtmlView
{
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * List of all mod_mainmenu modules collated by menutype
     *
     * @var  array
     */
    protected $modules;

    /**
     * The pagination object
     *
     * @var  \Joomla\CMS\Pagination\Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \Joomla\Registry\Registry
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var    \Joomla\CMS\Form\Form
     *
     * @since  4.0.0
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     * @since  4.0.0
     */
    public $activeFilters;

    /**
     * Ordering of the items
     *
     * @var    array
     * @since  5.0.0
     */
    protected $ordering;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  void
     *
     * @since   1.6
     */
    public function display($tpl = null)
    {
        /** @var MenusModel $model */
        $model = $this->getModel();

        $this->items      = $model->getItems();
        $this->modules    = $model->getModules();
        $this->pagination = $model->getPagination();
        $this->state      = $model->getState();

        if ($this->getLayout() == 'default') {
            $this->filterForm    = $model->getFilterForm();
            $this->activeFilters = $model->getActiveFilters();
        }

        // Check for errors.
        if (\count($errors = $model->getErrors())) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     *
     * @since   1.6
     */
    protected function addToolbar()
    {
        $canDo   = ContentHelper::getActions('com_menus');
        $toolbar = $this->getDocument()->getToolbar();

        ToolbarHelper::title(Text::_('COM_MENUS_VIEW_MENUS_TITLE'), 'list menumgr');

        if ($canDo->get('core.create')) {
            $toolbar->addNew('menu.add');
        }

        if ($canDo->get('core.delete')) {
            $toolbar->divider();
            $toolbar->delete('menus.delete')
                ->message('COM_MENUS_MENU_CONFIRM_DELETE');
        }

        if ($canDo->get('core.admin') && $this->state->get('client_id') == 1) {
            $toolbar->standardButton('download', 'COM_MENUS_MENU_EXPORT_BUTTON', 'menu.exportXml')
                ->icon('icon-download')
                ->listCheck(true);
        }

        if ($canDo->get('core.admin') || $canDo->get('core.options')) {
            $toolbar->divider();
            $toolbar->preferences('com_menus');
        }

        $toolbar->divider();
        $toolbar->help('Menus');
    }
}
