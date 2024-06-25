<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Track/HtmlView.php
 * @version 0.0.4.0 25th April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Track;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    protected $item;
//    protected $state;
    protected $canDo;
    
    public function display($tpl = null) {
        
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
//        $this->state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_xbmusic', 'track', $this->item->id);
        
        $this->params      = $this->get('State')->get('params');
        
        if ($this->params->get('use_xbmusic', 1)) {
            $this->basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$this->params->get('xbmusic_subfolder','');
        } else {
            if (is_dir(trim($this->params->get('music_path','')))) {
                $this->basemusicfolder = trim($this->params->get('music_path'));
            } else {
                $this->basemusicfolder = JPATH_ROOT.'/';
            }
        }
        
//         $this->taggroups = $this->params->get('enable_taggroups',0);
//         if ($this->taggroups) {
//             $taggroup_ids = array();
//             $this->taggroup1_parent = $this->params->get('taggroup1_parent',0);
//             if ($this->taggroup1_parent) $taggroup_ids[] = $this->taggroup1_parent;
//             $this->taggroup2_parent = $this->params->get('taggroup2_parent',0);
//             if ($this->taggroup2_parent) $taggroup_ids[] = $this->taggroup2_parent;
//             $this->taggroup3_parent = $this->params->get('taggroup3_parent',0);
//             if ($this->taggroup3_parent) $taggroup_ids[] = $this->taggroup3_parent;
//             $this->taggroup4_parent = $this->params->get('taggroup4_parent',0);
//             if ($this->taggroup4_parent) $taggroup_ids[] = $this->taggroup4_parent;
            
//             $db = Factory::getDbo();
//             $query = $db->getQuery(true);
//             $query->select('id, title, description')->from($db->quoteName('#__tags'))
//             ->where('id IN ('.implode(',',$taggroup_ids).')');
//             $db->setQuery($query);
//             $this->taggroupinfo = $db->loadAssocList('id');
//         }
        
        // Check for errors.
        if (\count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }
        
        $this->addToolbar();
        
        parent::display($tpl);
    }
    
    protected function addToolbar() {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);
        $user       = $this->getCurrentUser();
        $userId     = $user->id;
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $toolbar    = Toolbar::getInstance();
        
        // Built the actions for new and existing records.
        $canDo = $this->canDo;
        
        ToolbarHelper::title(
            Text::_('XBMUSIC_ADMIN_' . ($checkedOut ? 'VIEW_TRACK_TITLE' : 'EDIT_TRACK_TITLE')),
            'pencil-alt'
            );
        
        $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);
        
        if (!$checkedOut && $itemEditable) {
            $toolbar->apply('track.apply');
            $toolbar->save('track.save');
        }
        
        $toolbar->cancel('track.cancel', 'JTOOLBAR_CLOSE');
        $toolbar->separatorButton();
        $toolbar->standardButton('loadid3','XBMUSIC_LOAD_ID3', 'track.loadid3')->icon('fas fa-file-arrow-down');
        $toolbar->standardButton('saveid3','XBMUSIC_SAVE_ID3', 'track.saveid3')->icon('fas fa-file-arrow-up');
        
        $toolbar->inlinehelp();
        $toolbar->help('Track: Edit',false,'https://crosborne.uk/xbmusic/doc#trackedit');
        
    }
}
