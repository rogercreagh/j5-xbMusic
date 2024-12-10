<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Track/HtmlView.php
 * @version 0.0.19.2 10th December 2024
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
// use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    protected $item;
//    protected $state;
    protected $canDo;
    
    public function display($tpl = null) {
        $app  = Factory::getApplication();
        
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        
        $this->id3loaded = $app->getUserState('com_xbmusic.edit.track.id3loaded', 0);
        $this->replaced = $app->getUserState('com_xbmusic.edit.track.replaced',[]);
        if ($this->id3loaded) {
            $this->id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
            $app->setUserState('com_xbmusic.edit.track.replaced', null);
            if (empty($this->id3data)) $this->id3loaded = 0;
        }
//        $this->state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_xbmusic', 'track', $this->item->id);
        
        $this->params      = $this->get('State')->get('params');

        $this->tagparentids = $this->params->get('tracktagparents',[]);
        $this->basemusicfolder = XbmusicHelper::$musicBase;
        
//        if ($this->params->get('use_xbmusic', 1)) {
//            $this->basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$this->params->get('xbmusic_subfolder','');
//        } else {
//            if (is_dir(trim($this->params->get('music_path','')))) {
//                $this->basemusicfolder = trim($this->params->get('music_path'));
//            } else {
//                $this->basemusicfolder = JPATH_ROOT.'/';
//            }
//        }
        
//       if (($this->item->id>0) && !file_exists($this->item->pathname.$this->item->filename)) {
        if (($this->item->id>0) && !file_exists($this->item->filepathname)) {
                Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_ERROR_NO_MUSIC_FILE'), 'Error');
        }
        
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
        $isNew      = (($this->item->id == 0) && ($this->id3loaded));
        $attrib = ($isNew) ? array('disabled'=>'true') : [];
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $toolbar    = Toolbar::getInstance();
        
        // Built the actions for new and existing records.
        $canDo = $this->canDo;
        
        ToolbarHelper::title(
            Text::_('XBMUSIC_ADMIN_' . ($checkedOut ? 'VIEW_TRACK_TITLE' : 'EDIT_TRACK_TITLE')),
            'pencil-alt'
            );
        
        $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);
        
        $loadlbl = ($isNew) ? 'Load ID3' : 'Reload ID3' ;
        $toolbar->standardButton('loadid3',$loadlbl, 'track.loadid3')->icon('fas fa-file-arrow-down');
        if (!$checkedOut && $itemEditable) {
            $toolbar->apply('track.apply')->attributes($attrib);
            $toolbar->save('track.save')->attributes($attrib);
        }
        
        $toolbar->cancel('track.cancel', 'JTOOLBAR_CLOSE');
        if (!$isNew) {
            //TODO implement save back to ID3
//            $toolbar->standardButton('saveid3','XBMUSIC_SAVE_ID3', 'track.saveid3')
//                ->icon('fas fa-file-arrow-up')->attributes(array('disabled'=>'true'));            
        }
        
        $toolbar->inlinehelp();
        $toolbar->help('Track: Edit',false,'https://crosborne.uk/xbmusic/doc#trackedit');
        
    }
}
