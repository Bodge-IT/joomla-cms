<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  User.terms
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\User\Terms\Extension;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Actionlogs\Administrator\Model\ActionlogModel;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * An example custom terms and conditions plugin.
 *
 * @since  3.9.0
 */
final class Terms extends CMSPlugin
{
    /**
     * Adds additional fields to the user registration form
     *
     * @param   Form   $form  The form to be altered.
     * @param   mixed  $data  The associated data for the form.
     *
     * @return  boolean
     *
     * @since   3.9.0
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        // Check we are manipulating a valid form - we only display this on user registration form.
        $name = $form->getName();

        if (!\in_array($name, ['com_users.registration'])) {
            return true;
        }

        // Load plugin language files
        $this->loadLanguage();

        // Add the terms and conditions fields to the form.
        FormHelper::addFieldPrefix('Joomla\\Plugin\\User\\Terms\\Field');
        FormHelper::addFormPath(JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms');
        $form->loadFile('terms');

        $termsarticle = $this->params->get('terms_article');
        $termsnote    = $this->params->get('terms_note');

        // Push the terms and conditions article ID into the terms field.
        $form->setFieldAttribute('terms', 'article', $termsarticle, 'terms');
        $form->setFieldAttribute('terms', 'note', $termsnote, 'terms');

        return true;
    }

    /**
     * Method is called before user data is stored in the database
     *
     * @param   array    $user   Holds the old user data.
     * @param   boolean  $isNew  True if a new user is stored.
     * @param   array    $data   Holds the new user data.
     *
     * @return  boolean
     *
     * @since   3.9.0
     * @throws  \InvalidArgumentException on missing required data.
     */
    public function onUserBeforeSave($user, $isNew, $data)
    {
        // // Only check for front-end user registration
        if ($this->getApplication()->isClient('administrator')) {
            return true;
        }

        $userId = ArrayHelper::getValue($user, 'id', 0, 'int');

        // User already registered, no need to check it further
        if ($userId > 0) {
            return true;
        }

        // Load plugin language files
        $this->loadLanguage();

        // Check that the terms is checked if required ie only in registration from frontend.
        $input  = $this->getApplication()->getInput();
        $option = $input->get('option');
        $task   = $input->post->get('task');
        $form   = $input->post->get('jform', [], 'array');

        if ($option == 'com_users' && \in_array($task, ['registration.register']) && empty($form['terms']['terms'])) {
            throw new \InvalidArgumentException($this->getApplication()->getLanguage()->_('PLG_USER_TERMS_FIELD_ERROR'));
        }

        return true;
    }

    /**
     * Saves user profile data
     *
     * @param   array    $data    entered user data
     * @param   boolean  $isNew   true if this is a new user
     * @param   boolean  $result  true if saving the user worked
     * @param   string   $error   error message
     *
     * @return  void
     *
     * @since   3.9.0
     */
    public function onUserAfterSave($data, $isNew, $result, $error): void
    {
        if (!$isNew || !$result) {
            return;
        }

        $userId = ArrayHelper::getValue($data, 'id', 0, 'int');

        $message = [
            'action'      => 'consent',
            'id'          => $userId,
            'title'       => $data['name'],
            'itemlink'    => 'index.php?option=com_users&task=user.edit&id=' . $userId,
            'userid'      => $userId,
            'username'    => $data['username'],
            'accountlink' => 'index.php?option=com_users&task=user.edit&id=' . $userId,
        ];

        /** @var ActionlogModel $model */
        $model = $this->getApplication()
            ->bootComponent('com_actionlogs')
            ->getMVCFactory()
            ->createModel('Actionlog', 'Administrator');

        $model->addLog([$message], 'PLG_USER_TERMS_LOGGING_CONSENT_TO_TERMS', 'plg_user_terms', $userId);
    }
}
