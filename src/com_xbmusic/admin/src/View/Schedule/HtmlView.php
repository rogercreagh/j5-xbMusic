<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Schedule/HtmlView.php
 * @version 0.0.51.8 2nd May 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Schedule;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
// use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class HtmlView extends BaseHtmlView {
    
    protected $items;
    protected $station;
    protected $state;
    
    public $filterForm;
    
    public $activeFilters;
    
    public function display($tpl = null) {
        
        //$this->form = $this->filterForm;
        
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->azuracast = $params->get('azuracast',0);
        $this->onestation = 1; //dummy value to hide station button
        if ($this->azuracast ==1) {
            $this->xbstations = XbmusicHelper::getStations();
            if ($this->xbstations) {
                
                $this->items         = $this->get('Items');
                $this->state         = $this->get('State');
                $this->filterForm    = $this->get('FilterForm');
                $this->activeFilters = $this->get('ActiveFilters');
                
                // Check for errors.
                if (\count($errors = $this->get('Errors')) ) {
                    throw new GenericDataException(implode("\n", $errors), 500);
                }
                
                $this->onestation = XbmusicHelper::singleStationId();
                if ($this->onestation) {
                    $this->dbstid = $this->onestation;
                } else {
                    
                    $this->dbstid = (key_exists('dbstid', $this->activeFilters))
                        ? $this->activeFilters['dbstid'] : $this->state->get('filter.dbstid','');
                }
                if ($this->dbstid >0) {
                    $this->station = XbmusicHelper::getDbStation($this->dbstid);
                    $this->displayfmt = (key_exists('displayfmt', $this->activeFilters)) 
                        ? $this->activeFilters['displayfmt'] : $this->state->get('filter.displayfmt','1');
                    $this->numhours = (key_exists('numhours', $this->activeFilters)) 
                        ? $this->activeFilters['numhours'] : $this->state->get('filter.numhours','24');
                    $this->starttime = (key_exists('starttime', $this->activeFilters)) 
                        ? $this->activeFilters['starttime'] : $this->state->get('filter.starttime');
                    if ($this->starttime == '') $this->starttime = strtotime(date('H:i').':00');
                    $this->endtime = strtotime($this->starttime. ' + '. $this->numhours. ' hours');
                    $this->numdays =  (key_exists('numdays', $this->activeFilters)) 
                        ? $this->activeFilters['numdays'] : $this->state->get('filter.numdays',4) ; 
                }
            } //endif stations
        } //endif azuracast

        //we now need to reorgaise the items into and array of numdays and inside each arrays of the schedule items that are valid in time order
        
        
        $this->addToolbar();
        
        parent::display($tpl);
        
    }

    protected function addToolbar() {
        Factory::getApplication()->getInput()->set('hidemainmenu', false);
        $toolbar    = Toolbar::getInstance();
        ToolbarHelper::title(
            Text::_('XBMUSIC_ADMIN_VIEW_SCHEDULE'),
            'clock'
            );
        
        $canDo = ContentHelper::getActions('com_xbmusic');

        //ToolbarHelper::custom('schedule.setStation', 'fas fa-radio', '', 'XBMUSIC_SET_STATION', false) ;
        if (!$this->onestation) {
            $toolbar->popupButton('setstation', 'XBMUSIC_SET_STATION')
                ->icon('fas fa-radio')
                ->selector('collapseModal')
                ->listCheck(false);           
        }
    
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
        $childBar->standardButton('playlistview', 'XBMUSIC_PLAYLISTS', 'dashboard.toPlaylists')->listCheck(false)->icon('fas fa-headphones') ;
        $childBar->standardButton('songsview', 'XBMUSIC_SONGS', 'dashboard.toSongs')->listCheck(false)->icon('fas fa-music') ;
        $childBar->standardButton('catsview', 'XB_CATEGORIES', 'dashboard.toCats')->listCheck(false)->icon('fas fa-folder-tree') ;
        $childBar->standardButton('tagsview', 'XB_TAGS', 'dashboard.toTags')->listCheck(false)->icon('fas fa-tags') ;
        $childBar->standardButton('datamanview', 'XB_DATAMAN', 'dashboard.toDataman')->listCheck(false)->icon('icon-database') ;
        
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->inlinehelp();
        $toolbar->help('xbMusic:Schedule',false,'https://crosborne.uk/xbmusic/doc#tracks');
                
    }

}