<?php
/*******
 * @package xbMusic
 * @filesource admin/src/View/Station/HtmlView.php
 * @version 0.0.54.1 17th June 2025
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
            $toolbar->save('station.save');
            
        }        
        $toolbar->cancel('station.cancel', 'JTOOLBAR_CLOSE');
        
        $toolbar->divider();
        $toolbar->inlinehelp();
        $toolbar->help('Station: Edit',false,'https://crosborne.uk/xbmusic/doc#stationedit');
        
    }
}
