<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Playlist/HtmlView.php
 * @version 0.0.42.2 12th March 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Playlist;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    protected $item;
//    protected $state;
    protected $canDo;
    
    public function display($tpl = null) {
        
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
//        $this->state = $this->get('State');
        $this->canDo = ContentHelper::getActions('com_xbmusic', 'playlist', $this->item->id);
        
        $this->params      = $this->get('State')->get('params');
                
        $this->tagparentids = $this->params->get('playlisttagparents',[]);
        $this->azuracast = $this->params->get('azuracast','0');
        $this->az_apiname = $this->params->get('az_apiname','not set');
        $this->az_url = $this->params->get('az_url','not set');
        $this->stncnt = count(XbmusicHelper::getStations());
        if ($this->item->az_dbstid > 0) {
            $this->station = XbmusicHelper::getDbStation($this->item->az_dbstid);
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
        $isNew      = ($this->item->id == 0);
        $checkedOut = !(\is_null($this->item->checked_out) || $this->item->checked_out == $userId);
        $toolbar    = Toolbar::getInstance();
        
        // Built the actions for new and existing records.
        $canDo = $this->canDo;
        
        ToolbarHelper::title(
            Text::_('XBMUSIC_ADMIN_' . ($checkedOut ? 'VIEW_PLAYLIST_TITLE' : 'EDIT_PLAYLIST_TITLE')),
            'pencil-alt'
            );
        
        $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);
        
        if (!$checkedOut && $itemEditable) {
            $toolbar->apply('playlist.apply');
            
            $saveGroup = $toolbar->dropdownButton('save-group');
            if ($isNew) {
                $toolbar->save('playlist.save');
            } else {
                $saveGroup->configure(
                    function (Toolbar $childBar) use ($canDo, $isNew) {
                        $childBar->save('playlist.save');
                        if (!$isNew && $canDo->get('core.create')) {
                            $childBar->save2copy('playlist.save2copy');
                        }
                        if ($canDo->get('core.create')) {
                            $childBar->save2new('playlist.save2new');
                        }
                    }
                    );
            }
        }
        
        $toolbar->cancel('playlist.cancel', 'JTOOLBAR_CLOSE');
        $toolbar->divider();
        $toolbar->inlinehelp();
        $toolbar->help('Playlist: Edit',false,'https://crosborne.uk/xbmusic/doc#playlistedit');
        
    }
}
