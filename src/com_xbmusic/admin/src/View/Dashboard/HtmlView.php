<?php 
/*******
 * @package xbMusic
 * @filesource admin/src/View/Dashboard/HtmlView.php
 * @version 0.0.2.1 1st April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
//use Joomla\CMS\Layout\FileLayout;
//use Joomla\CMS\Toolbar\ToolbarFactoryInterface;

class HtmlView extends BaseHtmlView {
    
    public function display($tpl = null) {
        $app = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
//        $this->artcnts = $this->get('ArticleCnts');
        
        $changelog = $this->get('Changelog');
        
        $this->xmldata = Installer::parseXMLInstallFile(JPATH_COMPONENT_ADMINISTRATOR . '/xbmusic.xml');
        $this->client = $this->get('Client');
        $this->albumcnts = $this->get('AlbumCnts');
        $this->artistcnts = $this->get('ArtistCnts');
        $this->playlistcnts = $this->get('PlaylistCnts');
        $this->songcnts = $this->get('SongCnts');
        $this->trackcnts = $this->get('TrackCnts');
        
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new GenericDataException(implode("\n", $errors), 500);
        }    
        $this->updateable = false;
         // format the changelog
         $this->changelog = '<div style="margin:10px 0;">';
         if ((!is_array($changelog)) || (!$changelog)) {
             $this->changelog .= Text::_('XB_CHANGELOG_NOT_FOUND');
         } else {
             if (array_key_exists('element',$changelog['changelog'])) {
                 //we have only one entry, need to demote it a level
                 $changelog = array('changelog'=>array($changelog['changelog']));
             }
             foreach ($changelog['changelog'] as $i=>$log) {
                 $this->titleok = false;
                 $this->changelog .= '<div class="xbmt10 ';
                 $iscurrent = (version_compare($log['version'], $this->xmldata['version']));
                 $this->changelog .= ($iscurrent === 0) ? 'xbbgltgreen' : '';
                 if ($iscurrent === 1) {
                    $this->changelog .=  'xbbgltred';
                    $this->updatable = true;
                 }
                 $this->changelog .= ' " style="padding:5px 20px;">';
                 $this->changelog .= '<b>Version '.$log['version'].'</b> ';
                 if (key_exists('date',$log)) $this->changelog .= '&nbsp;&nbsp;<i>Updated</i>:&nbsp;'.$log['date'];
                 if (key_exists('title',$log)) {
                     $this->changelog .= '<h3>'.$log['title'].'</h3>';
                     $this->titleok = true;
                 }
                 $this->changelog .= '</div>';
                 $this->colours = array('security'=>'bg-danger', 'fix'=>'bg-dark','language'=>'bg-primary','addition'=>'bg-success',
                    'change'=>'bg-warning text-dark','remove'=>'bg-secondary','note'=>'bg-info'
                 );
                 foreach ($log as $key=>$items) {
                     if (is_array($items)) {
                         $this->changelog .= $this->itemstr($items, $key);
                     }
                 }
             }
         }
         $this->changelog .= '</div>';
        
        $this->addToolbar();
        
        return parent::display($tpl);
    }
    
    private function itemstr($items, $tag) {
        if (empty($items)) return '';
        $ans =  '<div class="changelog"><div class="changelog__item"><div class="changelog__tag">';
        $ans .=  '<span class="badge ';
        if (key_exists($tag, $this->colours)) {
            $ans .=  $this->colours[$tag];
            $ans .=  '">'. Text::_('XB_CHANGELOG_'.$tag) .'</span>';
        } else {
            $ans .= 'badge-ltblue">'.$tag.'</span>';
        }
        $ans .=  '</div>';
        $ans .=  '<div class="changelog__list"><ul>';
        if (is_array($items['item'])) {
            foreach ($items['item'] as $item) {
                if ((!$this->titleok) || !(str_starts_with($item, '<h3>'))) {
                    $ans .= '<li>'.$item.'</li>';
                }
            }
        } else {
            $ans .= $items['item'];
        }
        $ans .=  '</li></ul>';
        $ans .=  '</div></div></div>';
        return $ans;
    }
    
    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');        
        //$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar($name);
        
        ToolbarHelper::title(Text::_('XBMUSIC_ADMIN_DASHBOARD_TITLE'), 'fas fa-info-circle');
        
        $canDo = ContentHelper::getActions('com_xbmusic');           
        if ($canDo->get('core.admin')) {
            //$toolbar->preferences('com_xbmusic');
            ToolbarHelper::preferences('com_xbmusic');
        }
        
        $toolbar->help('Articles:Images',false,'https://crosborne.uk/xbmusic/doc#dashboard');
        
    }
        
}
