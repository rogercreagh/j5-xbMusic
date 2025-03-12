<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/joomla/form/field/subform/repeatable-table.php
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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form    $tmpl             The Empty form for template
 * @var   array   $forms            Array of JForm instances for render the rows
 * @var   bool    $multiple         The multiple state for the form field
 * @var   int     $min              Count of minimum repeating in multiple mode
 * @var   int     $max              Count of maximum repeating in multiple mode
 * @var   string  $name             Name of the input field.
 * @var   string  $fieldname        The field name
 * @var   string  $fieldId          The field ID
 * @var   string  $control          The forms control
 * @var   string  $label            The field label
 * @var   string  $description      The field description
 * @var   string  $class            Classes for the container
 * @var   array   $buttons          Array of the buttons that will be rendered
 * @var   bool    $groupByFieldset  Whether group the subform fields by it`s fieldset
 */
if ($multiple) {
    // Add script
    Factory::getApplication()
        ->getDocument()
        ->getWebAssetManager()
        ->useScript('webcomponent.field-subform');
}

$class = $class ? ' ' . $class : '';

// Build heading
$table_head = '';

if (!empty($groupByFieldset)) {
    if ($fieldset->description) {
        foreach ($tmpl->getFieldsets() as $k => $fieldset) {
            $table_head .= '<th scope="col">' . Text::_($fieldset->label);
            
            $table_head .= '<span class="icon-info-circle" aria-hidden="true" tabindex="0"></span><div role="tooltip" id="tip-th-' . $fieldId . '-' . $k . '">' . Text::_($fieldset->description) . '</div>';
            
            $table_head .= '</th>';
        }
    }

    $sublayout = 'section-byfieldsets';
} else {
    $cnt=0;
    foreach ($tmpl->getGroup('') as $field) {
        if (strip_tags($field->type) != 'Hidden') $cnt ++;
    }
    $wid = intdiv(92, $cnt);
    foreach ($tmpl->getGroup('') as $field) {
        //  Factory::getApplication()->enqueueMessage(print_r(strip_tags($field->type),true));
        $table_head .= '<th scope="col" ';
        if (strip_tags($field->type) == 'Hidden') {
            $table_head .= 'style="display:none;">';
        } else {
            $table_head .= 'style="width:'.$wid.'%">' . strip_tags($field->label);
        }        
        if ($field->description) {
            $table_head .= '<span class="icon-info-circle" aria-hidden="true" tabindex="0"></span><div role="tooltip" id="tip-' . $field->id . '">' . Text::_($field->description) . '</div>';
        }       
        $table_head .= '</th>';
    }

    $sublayout = 'section';

    // Label will not be shown for sections layout, so reset the margin left
    Factory::getApplication()
        ->getDocument()
        ->addStyleDeclaration('.subform-table-sublayout-section .controls { margin-left: 0px }');
}
?>

<div class="subform-repeatable-wrapper subform-table-layout subform-table-sublayout-<?php echo $sublayout; ?>">
    <joomla-field-subform class="subform-repeatable<?php echo $class; ?>" name="<?php echo $name; ?>"
        button-add=".group-add" button-remove=".group-remove" button-move="<?php echo empty($buttons['move']) ? '' : '.group-move' ?>"
        repeatable-element=".subform-repeatable-group"
        rows-container="tbody.subform-repeatable-container" minimum="<?php echo $min; ?>" maximum="<?php echo $max; ?>">
        <div class="table-responsive">
            <table class="table" id="subfieldList_<?php echo $fieldId; ?>">
                <caption class="visually-hidden">
                    <?php echo ''; ?>
                </caption>
                <thead>
                    <tr>
                        <?php echo $table_head; ?>
                        <?php if (!empty($buttons)) : ?>
                        <td style="width:8%;">
                            <?php if (!empty($buttons['add'])) : ?>
                                <div class="btn-group">
                                    <button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
                                        <span class="icon-plus" aria-hidden="true"></span>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="subform-repeatable-container">
                <?php
                foreach ($forms as $k => $form) :
                    echo $this->sublayout($sublayout, ['form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $k, 'buttons' => $buttons]);
                endforeach;
                ?>
                </tbody>
            </table>
        </div>
        <?php if ($multiple) : ?>
        <template class="subform-repeatable-template-section hidden">
            <?php echo trim($this->sublayout($sublayout, ['form' => $tmpl, 'basegroup' => $fieldname, 'group' => $fieldname . 'X', 'buttons' => $buttons])); ?>
        </template>
        <?php endif; ?>
    </joomla-field-subform>
</div>
