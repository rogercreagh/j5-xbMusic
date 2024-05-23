<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbfolderlistField.php
 * @version 0.0.4.2 29th April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc display tree of folders from given parent 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\FolderlistField;
use Joomla\CMS\Form\FormField;

FormHelper::loadFieldClass('folderlist');

/**
 * @name GpxFolderList
 * @desc extends FolderList to set directory to the parameter base_gpx_folder and allow selection of directory itself as well as subfolders
 * @author rogerco
 *
 */
class XbfolderlistField extends FolderlistField {
    
//     public function __construct($form = null) {
//         parent::__construct($form);
        
//     }
        
    public function getOptions() {
 
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($params->get('use_xbmusic', 1)) {
            $basemusicfolder = JPATH_ROOT.'/xbmusic/'.$params->get('xbmusic_subfolder','');
        } else { 
            $musicpath = trim($params->get('music_path',''));
            if (is_dir($musicpath)) {
                $basemusicfolder = $musicpath;
            } else {
                $basemusicfolder = '/';
            }
        }
        
//        $def_folder = trim($params->get('base_gpx_folder','xbmaps-tracks'),'/');

        $this->element['directory'] = $basemusicfolder;
        $def = new \stdClass;
        $def->text = basename($basemusicfolder);
        $def->value = $basemusicfolder;
        $default = array($def);
        $options = parent::getOptions();
        foreach ($options as $opt) {
            $len = strlen(substr($opt->text, 0,strrpos($opt->text,'/')));
            $opt->text = str_repeat('&nbsp;',$len).' └─ '.basename($opt->text);
            $opt->value = rtrim($basemusicfolder,'/').'/'.$opt->value;
        }       
        $options = array_merge($default, $options );
        return $options;
    }
}