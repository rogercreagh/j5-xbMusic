<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Dataman/HtmlView.php
 * @version 0.0.51.8 5th May 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Dataman;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
// use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
// use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
// use Joomla\CMS\Helper\TagsHelper;
//use Joomla\CMS\Layout\FileLayout;
//use Joomla\CMS\Toolbar\ToolbarFactoryInterface;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    
    public function display($tpl = null) {

        $params = ComponentHelper::getParams('com_xbmusic');
        $this->azuracast = $params->get('azuracast',0);
        $this->xbstations = XbmusicHelper::getStations();
        if ($this->azuracast ==1) {
            $this->azstations = $this->get('AzStations');
             $this->apiname = $params->get('az_apiname','');
             $this->azurl = $params->get('az_url','');
        } else {
            $this->azstations = '';
        }
        $this->form = $this->get('Form');
        $this->basemusicfolder = XbmusicHelper::$musicBase;
        $this->log = XbmusicHelper::getLastImportLog();
        $this->log = str_replace("\n", '<br />', $this->log);
        $this->warnings = $this->get('Warnings');
        $this->symlinks = $this->get('Symlinks');
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_DATAMAN_TITLE'), 'database');
       
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
        $childBar->standardButton('playlistsview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('scheduleview', 'XBMUSIC_SCHEDULE', 'dashboard.toSchedule')->listCheck(false)->icon('fas fa-clock') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('trackview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('fas fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        $toolbar->inlinehelp();
        $toolbar->help('Dataman:',false,'https://crosborne.uk/xbmusic/doc#dataman');
        
    }
    
    
}