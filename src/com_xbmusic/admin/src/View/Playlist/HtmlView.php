<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Playlist/HtmlView.php
 * @version 0.0.59.15 13th December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
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
use Crosborne\Component\Xbmusic\Administrator\Helper\XbazuracastHelper;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    protected $item;
//    protected $state;
    protected $canDo;
    
    public function display($tpl = null) {
        
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
//        $this->state = $this->get('State');
        if (isset($this->item->az_info)) {
            $this->azschedule = $this->item->az_info->schedule_items;
            unset($this->item->az_info->schedule_items);
            $this->azlinks = $this->item->az_info->links;
            unset($this->item->az_info->links);
            $this->azpodcasts = $this->item->az_info->podcasts;
            unset($this->item->az_info->podcasts);
            $this->azbackend = $this->item->az_info->backend_options;
            unset($this->item->az_info->backend_options);
        }
        
        if ($this->item->az_plid > 0) {
            if ($this->item->modified > $this->item->created) {
                $this->azchanged = false;
            } else {
                $this->azchanged = true;               
            }
            
            $cntr = 0; 
            switch ($this->item->az_type) {
                case '1':
                    if ($this->item->az_info->type == 'default') $this->azchanged = false;
                    break;
                case '2':
                    if ($this->item->az_info->type == 'once_per_x_songs') $this->azchanged = false;
                    $cntr = $this->item->az_info->play_per_songs;
                    break;
                case '3':
                    if ($this->item->az_info->type == 'once_per_x_minutes') $this->azchanged = false;
                    $cntr = $this->item->az_info->play_per_minutes;
                    break;
                case '4':
                    if ($this->item->az_info->type == 'once_per_hour') $this->azchanged = false;
                    $cntr = $this->item->az_info->play_per_hour_minute;
                    break;
                case '-1':
                    if ($this->item->az_info->type == 'custom')  $this->azchanged = false;
                    break;                
                default:
                    if ($this->item->az_info->type == '') $this->azchanged = false;
                    break;
            }
            if ($this->azchanged == false) {
                if ($cntr != $this->item->az_cntper)  $this->azchanged = true;
            }
            if ($this->azchanged == false) {
                if ($this->item->az_name != $this->item->az_info->name) $this->azchanged = true;
            }
            if ($this->azchanged == false) {
                if ($this->item->az_jingle != $this->item->az_info->is_jingle) $this->azchanged = true;
            }
            if ($this->azchanged == false) {
                if ($this->item->az_weight != $this->item->az_info->weight) $this->azchanged = true;
            }
            if ($this->azchanged == false) {
                if ($this->item->az_order != $this->item->az_info->order) $this->azchanged = true;
            }
            
            $this->frmtlength = $this->item->az_info->total_length;
            if ($this->frmtlength > 86399) {
                $this->frmtlength = gmdate("z \d\a\y\s H \h\o\u\\r\s i \m\i\\n\s s \s\\e\c\s", $this->frmtlength);
            } elseif ($this->frmtlength > 3599) {
                $this->frmtlength = gmdate("H \h\o\u\\r\s i \m\i\\n\s s \s\\e\c\s", $this->frmtlength);
            } elseif ($this->frmtlength > 59) {
                $this->frmtlength = gmdate("i \m\i\\n\s s \s\\e\c\s", $this->frmtlength);
            } else {
                $this->frmtlength .= ' seconds';
            }
        } //endif item->az_type
        $this->canDo = ContentHelper::getActions('com_xbmusic', 'playlist', $this->item->id);
        
        $this->params      = $this->get('State')->get('params');
                
        $this->tagparentids = $this->params->get('playlisttagparents',[]);
        $this->azuracast = $this->params->get('azuracast','0');
        $this->az_apiname = $this->params->get('az_apiname','not set');
        $this->az_url = $this->params->get('az_url','not set');
        $dispvals = array('Nothing', 'Summary only','Summary and Errors','Summary, Errors &amp; Warnings','All information');
        $this->logging = $dispvals[$this->params->get('loglevel','0')];
        $this->messaging = $dispvals[$this->params->get('msglevel','0')];
        
        $this->stncnt = count(XbazuracastHelper::getStations());
        if ($this->item->db_stid > 0) {
            $this->station = XbazuracastHelper::getDbStation($this->item->db_stid);
        }
        if ($this->item->id > 0) $this->tracks = XbmusicHelper::getPlaylistTrackTitles($this->item->id);
        $this->aztrkcnt = ($this->item->id > 0) ? $this->item->az_info->num_songs : 0;
        $this->dbtrkcnt = ($this->item->id > 0) ? count($this->tracks) : 0;
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
            $toolbar->save('playlist.save');
            
//             $saveGroup = $toolbar->dropdownButton('save-group');
//             if ($isNew) {
//                 $toolbar->save('playlist.save');
//             } else {
//                 $saveGroup->configure(
//                     function (Toolbar $childBar) use ($canDo, $isNew) {
//                         $childBar->save('playlist.save');
//                         if (!$isNew && $canDo->get('core.create')) {
//                             $childBar->save2copy('playlist.save2copy');
//                         }
//                         if ($canDo->get('core.create')) {
//                             $childBar->save2new('playlist.save2new');
//                         }
//                     }
//                     );
//             }
        }
        
        $toolbar->cancel('playlist.cancel', 'JTOOLBAR_CLOSE');
        $toolbar->divider();
        $toolbar->standardButton('scheduleview', 'XBMUSIC_SCHEDULE', 'dashboard.toSchedule')->listCheck(false)->icon('fas fa-clock') ;
        $toolbar->standardButton('playlisttracksview', 'Tracks List', 'dashboard.toPlaylisttracks')->icon('fas fa-headphones') ;
 
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
            $stations = XbazuracastHelper::getStations();
            $childBar->standardButton('azuracastview', 'XBMUSIC_AZURACAST_STATIONS', '')
            ->listCheck(false)->icon('fas fa-broadcast-tower')
            ->onclick("showEl('azwaiter',Joomla.JText._('XBMUSIC_WAITING_SERVER'));
                Joomla.submitbutton('dashboard.toAzuracast')") ;
            foreach ($stations AS $station) {
                $childBar->linkButton('stationview'.$station['id'],'<span class="xbpl20">'.$station['title'].'</span>', '')
                ->url('index.php?option=com_xbmusic&task=station.edit&id='.$station['id'])
                ->listCheck(false)->icon('fas fa-radio');
            }
        }
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->inlinehelp();
        $toolbar->help('Playlist: Edit',false,'https://crosborne.uk/xbmusic/doc#playlistedit');
        
    }
}
