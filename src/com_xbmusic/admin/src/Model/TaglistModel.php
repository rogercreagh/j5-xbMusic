<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TaglistModel.php
 * @version 0.0.52.6 4th June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
// use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
// use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

class TaglistModel extends ListModel {
    
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'title', 'path',
                't.id', 't.title', 't.path',
                'albumcnt','artistcnt','playlistcnt','songcnt','trackcnt',
                't.published', 'status', 'parent', 't.parent_id'
            );            
        }        
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 't.path', $direction = 'asc')
    {
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
        
        $published = $this->getUserStateFromRequest($this->context . '.filter.status', 'filter_status', '');
        $this->setState('filter.published', $published);
        
        $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');
        $this->setState('filter.level', $level);
        
        // List state information.
        parent::populateState($ordering, $direction);
    }
    
    protected function getListQuery() {
        
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = Factory::getApplication()->getIdentity();
        $query = $db->getQuery(true);
        $query->select('DISTINCT t.`id` AS id, t.`path` AS path, t.level AS level, t.`title` AS title, t.`description` AS description,
		 t.`note` AS note, t.`published` AS status,  t.`checked_out` AS checked_out,
         t.`checked_out_time` AS checked_out_time, t.`lft`,t.`parent_id` AS parent');
        $query->from('#__tags AS t');
//        $query->join('LEFT','#__contentitem_tag_map AS m ON m.tag_id = t.id');
//        $query->where('m.type_alias LIKE ('.$db->q('com_xbmusic%').')');
        
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.tag_id = t.id AND al.type_alias='.$db->quote('com_xbmusic.album').') AS albumcnt');
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS ar WHERE ar.tag_id = t.id AND ar.type_alias='.$db->quote('com_xbmusic.artist').') AS artistcnt');
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS pl WHERE pl.tag_id = t.id AND pl.type_alias='.$db->quote('com_xbmusic.playlist').') AS playlistcnt');
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS sg WHERE sg.tag_id = t.id AND sg.type_alias='.$db->quote('com_xbmusic.song').') AS songcnt');
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS tr WHERE tr.tag_id = t.id AND tr.type_alias='.$db->quote('com_xbmusic.track').') AS trackcnt');
        $query->select('(SELECT COUNT(*) FROM #__contentitem_tag_map AS toth WHERE toth.tag_id = t.id AND toth.type_alias NOT LIKE '.$db->quote('com_xbmusic%').') AS othcnt');
                
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
        ->join('LEFT', '#__users AS uc ON uc.id=t.checked_out');
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('t.published = ' . (int) $published);
        } else if ($published === '') {
            $query->where('(t.published IN (0, 1))');
        }
        
        //filter by tag branch
        $branch = (int)$this->getState('filter.branch');
        $path = XbcommonHelper::getItemValue('#__tags', 'path', $branch);
        if ($branch != 0) {
            $query->where('t.path LIKE '.$db->quote($path.'%'));
        }
        
        // Filter by search in title/id (i:) /desc (d:)
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'i:') === 0) {
                $query->where($db->quoteName('t.id') . ' = ' . (int) substr($search, 2));
            } elseif ((stripos($search,'d:')===0) || (stripos($search,'d:')===0)) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search,2)), true) . '%'));
                $query->where('(t.description LIKE ' . $search.')');
            } elseif (stripos($search,':')!= 1) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(t.title LIKE ' . $search . ')');
            }
        }
        
        // Add the list ordering clause.
        $orderCol       = $this->state->get('list.ordering', 'path');
        $orderDirn      = $this->state->get('list.direction', 'ASC');
        $query->order($db->escape($orderCol.' '.$orderDirn));

        return $query;
    } //end getListQuery()
    
    public function getItems() {
        $items  = parent::getItems();
        //insert any pst query processing
        return $items;        
    } // end getItems()

}
