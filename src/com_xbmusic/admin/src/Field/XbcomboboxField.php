<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbcomboboxField.php
 * @version 0.0.20.2 3rd February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc creates a form field type to select an album from list
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

//use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
//use Joomla\Database\DatabaseInterface;
//use \stdClass;

/**
 * @desc based on joomal combo form field
 * {@inheritDoc}
 * @see \Joomla\CMS\Form\Field\ComboField
 * 
 */
class XbcomboboxField extends ListField {
    
    protected $type = 'Xbcombobox';
    
    protected $layout = 'xbmusic.form.field.xbcombobox';

    protected function getInput()
    {
        if (empty($this->layout)) {
            throw new \UnexpectedValueException(\sprintf('%s has no layout assigned.', $this->name));
        }
        
        return $this->getRenderer($this->layout)->render($this->collectLayoutData());
    }
    
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();
        
        // Get the field options.
        $options = $this->getOptions();
        
        $extraData = [
            'options' => $options,
        ];
        
        return array_merge($data, $extraData);
    }
    
 }
