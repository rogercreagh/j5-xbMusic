<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Station/HtmlView.php
 * @version 0.0.59.15 13th December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Station;

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
        $this->canDo = ContentHelper::getActions('com_xbmusic', 'station', $this->item->id);
        
        $this->params      = $this->get('State')->get('params');
        $this->azuracast = $this->params->get('azuracast','0');
        
//        $this->tagparentids = $this->params->get('stationtagparents',[]);
        
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
            Text::_('XBMUSIC_ADMIN_' . ($checkedOut ? 'VIEW_STATION_TITLE' : 'EDIT_STATION_TITLE')),
            'pencil-alt'
            );
        
        $itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);
        
        if (!$checkedOut && $itemEditable) {
            $toolbar->apply('station.apply');
            // $toolbar->save('station.save');
            //$toolbar->standardButton('stationapply', 'JTOOLBAR_APPLY', '')->listCheck(false)->icon('icon-apply')->onclick("showEl('azwaiter');Joomla.submitbutton('station.apply')") ;
            $toolbar->standardButton('stationsave', 'JTOOLBAR_SAVE', '')->listCheck(false)->icon('icon-save')->onclick("showEl('azwaiter');Joomla.submitbutton('station.save')") ;
            
        }        
        //$toolbar->cancel('station.cancel', 'JTOOLBAR_CLOSE');
        $toolbar->standardButton('stationcancel', 'JTOOLBAR_CLOSE', '')->listCheck(false)->icon('icon-cancel')->onclick("showEl('azwaiter');Joomla.submitbutton('station.cancel')") ;
        //$toolbar->divider();
        $dropdown = $toolbar->dropdownButton('views')
        ->text('XBMUSIC_OTHER_VIEWS')
        ->toggleSplit(false)
        ->icon('icon-ellipsis-h')
        ->buttonClass('btn btn-action')
        ->listCheck(false);
        $childBar = $dropdown->getChildToolbar();
        $childBar->standardButton('dashboardview', 'XB_DASHBOARD', 'dashboard.toDashboard')->listCheck(false)->icon('fas fa-info-circle') ;
        $childBar->standardButton('albumsview', 'XBMUSIC_ALBUMS', 'dashboard.toAlbums')->listCheck(false)->icon('fas fa-compact-disc') ;
        $childBar->standardButton('artistsview', 'XBMUSIC_ARTISTS', 'dashboard.toArtists')->listCheck(false)->icon('fas fa-users-line') ;
        //       $childBar->standardButton('playlistsview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        //        $childBar->standardButton('scheduleview', 'XBMUSIC_SCHEDULE', 'dashboard.toSchedule')->listCheck(false)->icon('fas fa-clock') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('trackview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('fas fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        if ( $this->azuracast) {
            $childBar->standardButton('azuracastview', 'XBMUSIC_AZURACAST', '')->listCheck(false)->icon('fas fa-broadcast-tower')->onclick("showEl('azwaiter');Joomla.submitbutton('dashboard.toAzuracast')") ;
        }
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->inlinehelp();
        $toolbar->help('Station: Edit',false,'https://crosborne.uk/xbmusic/doc#stationedit');
        
    }
}
