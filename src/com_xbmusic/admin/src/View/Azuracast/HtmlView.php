<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Azuracast/HtmlView.php
 * @version 0.0.59.14 9th December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Azuracast;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
// use Joomla\CMS\Helper\TagsHelper;
// use Joomla\CMS\Installer\Installer;
// use Joomla\CMS\Layout\FileLayout;
// use Joomla\CMS\MVC\View\GenericDataException;
//use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbazuracastHelper;

class HtmlView extends BaseHtmlView {
    
    protected $form;
    
    public function display($tpl = null) {

        $this->user = Factory::getApplication()->getIdentity();
        $this->noazmess1 = '';
        $this->noazmess2 = '';
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->azuracast = $params->get('azuracast',0);
        if ($this->azuracast == 0) {
            $this->noazmess1 = Text::_('XBMUSIC_AZURACAST_NOT_ENABLED');
            $this->noazmess2 = Text::_('XBMUSIC_AZURACAST_NOT_ENABLED_ACTION');
        }
        $this->azurl = $params->get('az_url','');
        if ($this->azurl == '') {
            $this->noazmess1 = Text::_('Default Server URL not set');
            $this->noazmess2 = Text::_('Please set a valid Azuracast url in the Options');            
        } else {
            $userapikey = XbazuracastHelper::getSelectedApiKey($this->user->id);
            if ($userapikey) {
                $this->apiid = $userapikey->id;
                $this->azurl = $userapikey->az_url;
                $this->apikey = $userapikey->az_apikeyid.':'.$userapikey->az_apikeyval;
                $this->apicomment = $userapikey->az_apicomment;
            } else {
                $this->apiid = 0;
                $this->apikey = '';
                $this->apicomment = '';                
                $this->noazmess1 = Text::_('XBMUSIC_AZURACAST_NOAPI');
                $this->noazmess2 = Text::_('XBMUSIC_AZURACAST_NOAPI_ACTION');
            }
            $api = new AzApi();
            if ($api->getStatus() == true) {
                $this->item  = $this->get('Item');
                if (isset($this->item->server->storage_locations)) {
                    $this->indexedlocs = array_column($this->item->server->storage_locations,null,'id');
                }
                    
                $model = $this->getModel();
                
                $this->azme = $model->getAzMe($api);
                $this->xbstations = XbazuracastHelper::getStations();           
                if ($this->azme) {
                    $this->account = '<b>'.$this->azme->name. '</b> (<i>Roles:</i> ';
                    foreach ($this->azme->roles as $role) {
                        $this->account .= $role->name .', ';
                    }
                    $this->account = trim($this->account,' ,').') <i>API:</i> '.$this->apicomment.')';           
                    foreach ($this->xbstations as &$station) {
                        $quota = $api->azStationQuota($station['az_stid']);
                        $station['isadmin'] = (isset($quota->used)) ? true : false;
                    }
                } else {
                    $this->account = '<i>(XBMUSIC_NOT_LOGGED_IN)</i>';                
                    $this->noazmess1 = Text::_('XBMUSIC_AZURACAST_INVALID_API');
                    $this->noazmess2 = Text::sprintf('XBMUSIC_AZURACAST_INVALID_API_ACTION',$this->azurl);
                    foreach ($this->xbstations as &$station) {                   
                        $station['isadmin'] = false;
                    }
                }
                $this->azstations = $model->getAzStations($api);                   
            }
        }
        $this->form = $this->get('Form');
        $this->basemusicfolder = XbmusicHelper::$musicBase;
//        $this->log = XbmusicHelper::getLastImportLog();
//        $this->log = str_replace("\n", '<br />', $this->log);
//        $this->warnings = $this->get('Warnings');
//        $this->symlinks = $this->get('Symlinks');
        $this->addToolbar();
        
        return parent::display($tpl);
    
    }

    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_AZURACAST_TITLE'), 'fas fa-broadcast-tower');
       
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
//        $childBar->standardButton('playlistsview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
//        $childBar->standardButton('scheduleview', 'XBMUSIC_SCHEDULE', 'dashboard.toSchedule')->listCheck(false)->icon('fas fa-clock') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('trackview', 'XBMUSIC_TRACKS', 'dashboard.toTracks')->listCheck(false)->icon('fas fa-guitar') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('fas fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGLIST', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        
        
        $canDo = ContentHelper::getActions('com_xbmusic');
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
//        $toolbar->inlinehelp();
        $toolbar->help('Dataman:',false,'https://crosborne.uk/xbmusic/doc#dataman');
        
    }
    
    
}