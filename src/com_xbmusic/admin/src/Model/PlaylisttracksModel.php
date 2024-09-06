<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/PlaylisttracksModel.php
 * @version 0.0.13.0 31st August 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;


use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Table\Table;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;

class PlaylisttracksModel extends ListModel {
    
    protected $id;
    
    public function __construct($config = array())
    {
        if (empty($config['filter_fields']))
        {
            $config['filter_fields'] = array(
                'id', 'pt.id',
                'track_id', 'pt.track_id',
                'track_title', 't.title',
                'artist', 't.sortartist',
                'album_title', 'b.title',
                'ordering', 'pt.listorder',
                'category_id', 'level'
            );
            
        }
        
        parent::__construct($config);
    }
    
    protected function populateState($ordering = 'ordering', $direction = 'desc')
    {
        $app = Factory::getApplication();
        
        $this->id = $app->input->get('id',0);
        $this->setState('id',$this->id);
        //       $this->setState('filter.category_id', $categoryId);
        
        // Adjust the context to support modal layouts.
        if ($layout = $app->input->get('layout'))
        {
            $this->context .= '.' . $layout;
        }
        
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);
                                
        $formSubmited = $app->input->post->get('form_submited');
        if ($formSubmited)
        {
//            $artlist = $app->input->post->get('artlist');
//            $this->setState('filter.artlist', $artlist);
            
//            $categoryId = $app->input->post->get('category_id');
//            $this->setState('filter.category_id', $categoryId);
            
//            $scfilt = $app->input->post->get('scfilt');
//            $this->setState('filter.artlist', $scfilt);
        }
        
        // List state information.
        parent::populateState($ordering, $direction);
        
    }
    
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . serialize($this->getState('filter.access'));
        $id .= ':' . $this->getState('filter.status');
        
        return parent::getStoreId($id);
    }
    
    protected function getListQuery() {
        
        // Create a new query object.
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $app = Factory::getApplication();
        $user  = $app->getIdentity();
        /*
         SELECT pt.id,`track_id`,`listorder`,t.title AS `track_title`, t.sortartist AS `artist`, c.title AS `album_title`  FROM `j5_xbmusic_playlisttrack` AS pt
INNER JOIN `j5_xbmusic_tracks` AS t ON t.id = pt.track_id
LEFT JOIN `j5_xbmusic_albums` AS c ON c.id = t.album_id
WHERE pt.playlist_id = 1
         */
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'pt.id AS id, pt.track_id, pt.listorder AS ordering, '.
                't.title AS track_title, t.sortartist AS artist, b.title AS album_title, 1 AS catid, '.
                't.checked_out AS checked_out, t.checked_out_time AS checked_out_time, '.
                't.status AS status, t.access AS access, t.created AS created, t.created_by AS created_by, t.created_by_alias AS created_by_alias, t.modified AS modified, t.note AS note, 0 AS category_id'
                )
            );
        $query->from('#__xbmusic_playlisttrack AS pt');
        $query->join('INNER', $db->qn('#__xbmusic_tracks'). ' AS t ON t.id = pt.track_id');
        $query->join('LEFT', $db->qn('#__xbmusic_albums'). ' AS b ON b.id = t.album_id');
        $query->where('pt.playlist_id = '.$this->id); 
        
        //search in track title
        
        //search in sort artist
        
        //search in album title
        
        //filter by track title
        
        //filter by sort artist
        
        //filter by album title
        
        // Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'ordering');
		$orderDirn = $this->state->get('list.direction', 'ASC');
		
		
		$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
		
		return $query;
    } //end getlistquery
    
    public function getItems() {
        $items  = parent::getItems();

        return $items;
        
    } // end getItems

    public function saveorder($pks = [], $order = null){
        Factory::getApplication()->enqueueMessage('in saveorder id='.$this->id);
    }
    
    
}
