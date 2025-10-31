<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/CatlistModel.php
 * @version 0.0.18.8 8th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
//use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
//use Joomla\Utilities\ArrayHelper;
//use Joomla\CMS\Uri\Uri;
//use Joomla\CMS\Table\Table;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;

class CatlistModel extends ListModel {
    
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'a.id', 'title', 'a.title',
                'albumcnt','artistcnt','playlistcnt','songcnt','trackcnt',
                'published', 'parent', 'path'
            );            
        }        
        parent::__construct($config);
    }
    
    
    protected function getListQuery() {
        
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $user  = Factory::getApplication()->getIdentity();
        $query = $db->getQuery(true);
        $query->select('DISTINCT c.`id` AS id, c.`path` AS path, c.level AS level, c.`title` AS title, c.`description` AS description,
		 c.`note` AS note, c.`published` AS status,  c.`checked_out` AS checked_out, c.extension, c.created_user_id,
         c.`checked_out_time` AS checked_out_time, c.`lft`');
        $query->from('#__categories AS c');
        
        $query->select('(SELECT COUNT(*) FROM #__xbmusic_albums AS al WHERE al.catid = c.id ) AS albumcnt');
        $query->select('(SELECT COUNT(*) FROM #__xbmusic_artists AS ar WHERE ar.catid = c.id ) AS artistcnt');
        $query->select('(SELECT COUNT(*) FROM #__xbmusic_azplaylists AS pl WHERE pl.catid = c.id ) AS playlistcnt');
        $query->select('(SELECT COUNT(*) FROM #__xbmusic_songs AS sg WHERE sg.catid = c.id ) AS songcnt');
        $query->select('(SELECT COUNT(*) FROM #__xbmusic_tracks AS tr WHERE tr.catid = c.id ) AS trackcnt');
        
        $query->where('c.extension = '.$db->quote('com_xbmusic'));
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('published = ' . (int) $published);
        } else if ($published === '') {
            $query->where('(published IN (0, 1))');
        }
        
        //filter by cat branch
        $branch = (int)$this->getState('filter.branch');
        $path = XbcommonHelper::getItemValue('#__categories', 'path', $branch);
        if ($branch != '') {
            $query->where('c.path LIKE '.$db->quote($path.'%'));
        }
        
        // Filter by search in title/id (i:) /desc (d:)
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'i:') === 0) {
                $query->where($db->quoteName('c.id') . ' = ' . (int) substr($search, 2));
            } elseif ((stripos($search,'d:')===0) || (stripos($search,'d:')===0)) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim(substr($search,2)), true) . '%'));
                $query->where('(c.description LIKE ' . $search.')');
            } elseif (stripos($search,':')!= 1) {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(c.title LIKE ' . $search . ')');
            }
        }
        
        // Add the list ordering clause.
        $orderCol       = $this->state->get('list.ordering', 'path');
        $orderDirn      = $this->state->get('list.direction', 'ASC');
        $query->order('extension, '.$db->escape($orderCol.' '.$orderDirn));

        return $query;
    } //end getListQuery()
    
    public function getItems() {
        $items  = parent::getItems();
        //insert any pst query processing
        return $items;        
    } // end getItems()

}
