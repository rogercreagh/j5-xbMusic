<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/joomla/form/field/subform/repeatable-table/section.php
 * @version 0.0.30.1 6th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @desc Override for Joomla subform layout to fix col widths problem
 ******/

/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form    $form       The form instance for render the section
 * @var   string  $basegroup  The base group name
 * @var   string  $group      Current group name
 * @var   array   $buttons    Array of the buttons that will be rendered
 */
?>

<tr class="subform-repeatable-group" data-base-name="<?php echo $basegroup; ?>" data-group="<?php echo $group; ?>">
    <?php foreach ($form->getGroup('') as $field) : ?>
        <td data-column="<?php echo strip_tags($field->label); ?>"
                <?php if (strip_tags($field->type) == 'Hidden') echo 'style="display:none;" '; ?>
        >
            <?php echo $field->renderField(['hiddenLabel' => true, 'hiddenDescription' => true]); ?>
        </td>
    <?php endforeach; ?>
    <?php if (!empty($buttons)) : ?>
    <td>
        <div class="btn-group">
            <?php if (!empty($buttons['add'])) : ?>
                <button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
                    <span class="icon-plus" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
            <?php if (!empty($buttons['remove'])) : ?>
                <button type="button" class="group-remove btn btn-sm btn-danger" aria-label="<?php echo Text::_('JGLOBAL_FIELD_REMOVE'); ?>">
                    <span class="icon-minus" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
            <?php if (!empty($buttons['move'])) : ?>
                <button type="button" class="group-move btn btn-sm btn-primary" aria-label="<?php echo Text::_('JGLOBAL_FIELD_MOVE'); ?>">
                    <span class="icon-arrows-alt" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
    </td>
    <?php endif; ?>
</tr>
