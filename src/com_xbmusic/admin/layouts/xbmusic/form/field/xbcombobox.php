<?php
/*******
 * @package xbMusic
 * @filesource admin/layouts/joomla/form/field/combo.php
 * @version 0.0.20.2 2nd February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @desc Override for Joomla combo layout to make a better combo box
 ******/

defined('_JEXEC') or die;

extract($displayData);

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * Layout variables
 * -----------------
 * @var   string   $autocomplete    Autocomplete attribute for the field.
 * @var   boolean  $autofocus       Is autofocus enabled?
 * @var   string   $class           Classes for the input.
 * @var   string   $description     Description of the field.
 * @var   boolean  $disabled        Is this field disabled?
 * @var   string   $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $hint            Placeholder for the field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $label           Label of the field.
 * @var   string   $labelclass      Classes to apply to the label.
 * @var   boolean  $multiple        Does this field support multiple values?
 * @var   string   $name            Name of the input field.
 * @var   string   $onchange        Onchange attribute for the field.
 * @var   string   $onclick         Onclick attribute for the field.
 * @var   string   $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean  $readonly        Is this field read only?
 * @var   boolean  $repeat          Allows extensions to duplicate elements.
 * @var   boolean  $required        Is this field required?
 * @var   integer  $size            Size attribute of the input.
 * @var   boolean  $spellcheck      Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 * @var   array    $options         Options available for this field.
 * @var   string   $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var   array    $dataAttributes  Miscellaneous data attribute for eg, data-*.
 */

HTMLHelper::_('behavior.combobox');

$attr = '';

// Initialize some field attributes.
//$attr .= !empty($class) ? ' class="awesomplete form-control ' . $class . '"' : ' class="awesomplete form-control"';
$attr .= !empty($class) ? ' class="form-control ' . $class . '"' : ' class="form-control"';
$attr .= !empty($size) ? ' size="' . $size . '"' : '';
$attr .= !empty($readonly) ? ' readonly' : '';
$attr .= !empty($disabled) ? ' disabled' : '';
$attr .= !empty($required) ? ' required' : '';
$attr .= !empty($description) ? ' aria-describedby="' . ($id ?: $name) . '-desc"' : '';

// Initialize JavaScript field attributes.
$attr .= !empty($onchange) ? ' onchange="' . $onchange . '"' : '';

$opts = '';
foreach ($options as $option) {
    $opts .= '<option value="'.$option->text.'">';
}
?>
<input
    type="search"
    name="<?php echo $name; ?>"
    id="<?php echo $id; ?>"
    list="xbcombo"
    placeholder="<?php echo Text::_('XBMUSIC_COMBO_PLACEHOLDER'); ?>";
    value="<?php echo htmlspecialchars($value, ENT_COMPAT, 'UTF-8'); ?>"
    <?php echo $attr; ?>
> 
<datalist id="xbcombo">
	<?php echo $opts; ?>
</datalist>
