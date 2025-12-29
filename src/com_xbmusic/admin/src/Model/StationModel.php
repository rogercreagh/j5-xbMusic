<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/StationModel.php
 * @version 0.0.54.1 16th June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
// use Joomla\CMS\Plugin\PluginHelper;
// use Joomla\CMS\String\PunycodeHelper;
// use Joomla\CMS\Table\Table;
// use Joomla\CMS\Table\TableInterface;
// use Joomla\CMS\UCM\UCMType;
// use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
// use Joomla\Database\ParameterType;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
// use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \SimpleXMLElement;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
// use Symfony\Component\Validator\Constraints\IsNull;

class StationModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.station';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
//        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
//         $taghelper = new TagsHelper();
//         $message = 'tag:'.$value.' removed from stations :';
//         foreach ($pks as $pk) {
//             if ($this->user->authorise('core.edit', $contexts[$pk])) {
//                 $existing = $taghelper->getItemTags('com_xbmusic.station', $pk, false);
//                 $oldtags = array_column($existing,'tag_id');
//                 $newtags = array();
//                 for ($i = 0; $i<count($oldtags); $i++) {
//                     if ($oldtags[$i] != $value) {
//                         $newtags[] = $oldtags[$i];
//                     }
//                 }
//                 $params = array( 'id' => $pk, 'tags' => $newtags );
                
//                 if($this->save($params)){
//                     $message .= ' '.$pk;
//                 }
//             } else {
//                 $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
//                 return false;
//             }
//             Factory::getApplication()->enqueueMessage($message);
//         }
        return true;
    }
 
    public function delete(&$pks) {
        //first need to delete links to albums, artists, tracks
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
//             $query->delete($db->qn('#__xbmusic_trackstation'));
//             $query->where($db->qn('station_id').' = '.$db->q($pk));
//             $db->setQuery($query);
//             $db->execute();
//             $query->clear();
        }           
        return parent::delete($pks);
    }
       
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.station.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing station.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.station.' . (int) $record->id);
        }
        
        // New station, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither station nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the stations within the category so the new station is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $item->playlists = $this->getDbPlaylists($item->id);
                $item->dateorder = Factory::getApplication()->input->get('dateorder',1);
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.station');                
//                 $item->tracks = $this->getStationTrackList($item->id);
//                 $item->artists = XbmusicHelper::getStationArtists($item->id);
//                 $item->albums = XbmusicHelper::getStationAlbums($item->id);
            }
        } 
        
        return $item;
    }
    
    public function getDbPlaylists($stdbid) {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $app = Factory::getApplication();
        $user  = $app->getIdentity();
        $status = '';
        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'DISTINCT a.id, a.title, a.alias, a.description, '
                .'a.az_plid, a.az_name, a.az_type,'
                .'a.scheduledcnt, publicschd,'
                .'a.checked_out, a.checked_out_time, a.catid, '
                .'a.status, a.access, a.created, a.created_by, a.created_by_alias, '
                .'a.modified, a.modified_by, a.ordering, a.lastsync, '
                .'a.note'
                )
            );
        $query->select('(SELECT COUNT(DISTINCT(tk.id)) FROM #__xbmusic_trackplaylist AS tk WHERE tk.playlist_id = a.id) AS trkcnt');
        $query->from('#__xbmusic_azplaylists AS a');
        
        $query->where($db->qn('db_stid').' = '.$db->q($stdbid));
                
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor')
        ->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
        
        // Join over the categories.
        $query->select('c.title AS category_title, c.created_user_id AS category_uid, c.level AS category_level'.
            ',c.path AS category_path')
            ->join('LEFT', '#__categories AS c ON c.id = a.catid');
            
            // Join over the parent categories.
            $query->select('parent.title AS parent_category_title, parent.id AS parent_category_id,
						parent.created_user_id AS parent_category_uid, parent.level AS parent_category_level');
			
			$query->join('LEFT', '#__categories AS parent ON parent.id = c.parent_id');
												
			if (is_numeric($status)) {
			    $query->where('a.status = ' . (int) $status);
			} elseif ($status === '') {
			    $query->where('(a.status = 0 OR a.status = 1)');
			}
						
			// Add the list ordering clause.
			$orderCol  = 'a.title'; //todo order by sched start
			$orderDirn = 'ASC';
									
			$query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
			$db->setQuery($query);
			$playlists = $db->loadObjectList();
			$tagsHelper = new TagsHelper;
			foreach ($playlists as &$pl) {
			    
			    $pl->tags = $tagsHelper->getItemTags('com_xbmusic.playlist' , $pl->id);
			    
			    $pl->tracks = $this->getPlaylistTracks($pl->id);
			    $pl->schedules = $this->getPlaylistSchedule($pl->id);
			} //end foreach
			return $playlists;			
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.station', 'station', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
//         $tags = $form->getValue('tags',null,'');
//         $tagsarr = (is_array($tags)) ? $tags : explode(',',$tags);
//         $parentids = $params->get('stationtagparents',[]);
//         if (!empty($parentids)) {
//             $taghelp = new TagsHelper;
//             $parr = $taghelp->getTags($parentids);
//             foreach ($parr as $pid=>$parent) {
//                 $groupname = $parent.'_tags';
//                 $element = new SimpleXMLElement('<field name="'.$groupname.'" type="xbtags" label="'.ucfirst($parent).' Group" mode="nested" multiple="true" custom="allow" parent="'.$pid.'" class="xbtags" />');
//                 $form->setField($element, null, true, 'taggroups');
//                 if (!empty($tagsarr)){
//                     $groupnametags = $taghelp->getTagTreeArray($pid);
//                     //set tags that are in this group
//                     $grouptags = array_intersect($groupnametags, $tagsarr);
//                     $form->setValue($groupname,null,$grouptags);
//                     //remove group tags from the main tags field
//                     $tagsarr = array_diff($tagsarr, $groupnametags);
//                 }
//             }
//         } // endforeach parenttag
//         $form->setValue('tags', null, $tagsarr);
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.station.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
//             $data->tracklist = $this->getStationTrackList($data->id);
//             $data->artists = XbmusicHelper::getStationArtists($data->id);
//             $data->albums = XbmusicHelper::getStationAlbums($data->id);

//            $data->dateorder = $app->input->get('dateorder',1);

            
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('station.id') == 0) && ($retview != '')) {
                $filters = (array) $app->getUserState('com_xbmusic.'.$retview.'.filter');
                $data->set(
                    'status',
                    $app->getInput()->getInt(
                            'status',
                        ((isset($filters['status']) && $filters['status'] !== '') ? $filters['status'] : null)
                        )
                    );
                    $data->set('catid', $app->getInput()->getInt('catid', (!empty($filters['category_id']) ? $filters['category_id'] : null)));

                     if ($app->isClient('administrator')) {
                         $data->set('language', $app->getInput()->getString('language', (!empty($filters['language']) ? $filters['language'] : null)));
                     }
    
                    $data->set(
                        'access',
                        $app->getInput()->getInt('access', (!empty($filters['access']) ? $filters['access'] : $app->get('access')))
                        );
                }
            }
            
            // If there are params fieldsets in the form it will fail with a registry object
            if (isset($data->params) && $data->params instanceof Registry) {
                $data->params = $data->params->toArray();
            }
            
            return $data;
        }
        
        public function setDateOrder($post) {
//            Factory::getApplication()->enqueueMessage('setdo '.$do);
            
            $params = json_decode(XbcommonHelper::getItemValue('#__xbmusic_azstations', 'params', $post['id'])  );
            if (is_null($params)) $params = new \stdClass();
            $params->dateorder = $post['params']['dateorder'];
            
            $res = XbcommonHelper::setItemValue('#__xbmusic_azstations', 'params', $post['id'], json_encode($params));
            return $res;
        }
        
        
        public function save($data) {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';

//         if ($input->get('task') == 'save2copy') {
//             $origTable = clone $this->getTable();
//             $origTable->load($input->getInt('id'));
            
//             if ($data['title'] == $origTable->title) {
//                 list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
//                 $data['title'] = $title;
//                 $data['alias'] = $alias;
//             } else {
//                 if ($data['alias'] == $origTable->alias) {
//                     $data['alias'] = '';
//                 }
//             }
//             //need to copy links
//             $this->storeStationTracks($origTable->id, $data['stationlist']);
//             //            $data->tracklist = $this->getStationTrackList($data->id);
//             // standard Joomla practice is to set the new copy record as unpublished
//             $data['status'] = 0;
//         }
        
       
        //alias is the title so we'll set and check it every time
        $newalias = OutputFilter::stringURLSafe($data['title']);
        if (($data['id'] == 0) && XbcommonHelper::checkValueExists($newalias, '#__xbmusic_stations', 'alias')) {
            $warnmsg .= 'Duplicate alias - this station title is already in the database';
            $app->enqueueMessage($warnmsg,'Error');
            return false;
        }
        $data['alias'] = $newalias;        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
        
//         //merge any tag groups back into tags
//         $parentids = $params->get('tracktagparents',[]);
//         if (!empty($parentids)) {
//             $thelp = new TagsHelper;
//             $parr = $thelp->getTags($parentids);
            
//             foreach ($parr as $pid=>$parent) {
//                 $groupname = $parent.'_tags';
//                 //$newpid = $pid;
//                 if (!empty($data[$groupname])) {
//                     //need to test for #new# in 'id' column and if found create a new tag and add its id to group
//                     foreach ($data[$groupname] as &$value) {
//                         if (strpos($value,'#new#') !== false) {
//                             $newtag = XbcommonHelper::getCreateTagPath($value, $pid);
//                             $value = $newtag['id'];
//                         }
//                     }
//                     $data['tags'] = ($data['tags']) ?
//                     array_unique(array_merge($data['tags'],$data[$groupname])) : $data[$groupname];
//                 }
//             } //endforeach parenttag
//         } // endif !empty parentids
        
        // ok ready to save the station data
        if (parent::save($data)) {
            $sid = $this->getState('station.id');
//            $this->storeStationTracks($sid, $data['tracklist']);
//            $this->storeStationAlbums($sid, $data['albumlist']);
//            $this->storeStationArtists($sid, $data['artistlist']);
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
        Factory::getApplication()->getSession()->set('stationtitle', $data->title);
        if ($this->canCreateCategory()) {
            $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
            // Add a prefix for categories created on the fly.
            $form->setFieldAttribute('catid', 'customPrefix', '#new#');
        }
        
        parent::preprocessForm($form, $data, $group);
    }
    
    public function saveorder($idArray = null, $lft_array = null)
    {
        // Get an instance of the table object.
        $table = $this->getTable();
        
        if (!$table->saveorder($idArray, $lft_array))
        {
            $this->setError($table->getError());
            
            return false;
        }
        
        return true;
    }
    
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
    
    public function getPlaylistTracks($pid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('t.id AS trackid, t.title AS tracktitle, t.sortartist AS artistname, pt.listorder AS ordering');
        $query->join('LEFT','#__xbmusic_trackplaylist AS pt ON pt.track_id = t.id');
        $query->from('#__xbmusic_tracks AS t');
        $query->where('pt.playlist_id = '.$pid);
        $query->order('pt.listorder, t.title ASC');
        $db->setQuery($query);
        $res = $db->loadAssocList();
        return $res;
    }
    
    public function getPlaylistSchedule($pid) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('s.id AS schdbid, s.az_starttime, s.az_endtime, s.az_startdate, s.az_enddate, s.az_days, s.az_loop');
        $query->from('#__xbmusic_azschedules AS s');
        $query->where('s.dbplid = '.$pid);
        $query->order('s.az_startdate, s.az_starttime ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    private function getStationTrackList($stationid) {
//         $db = $this->getDbo();
//         $query = $db->getQuery(true);
//         $query->select('a.track_id as track_id, b.title AS title, b.rel_date AS rel_date, a.role AS role, a.note AS note, a.listorder AS listorder');
//         $query->from('#__xbmusic_trackstation AS a');
//         $query->innerjoin('#__xbmusic_tracks AS b ON a.track_id = b.id');
//         $query->where('a.station_id = '.$db->q($stationid));
//         $query->order('b.title ASC');
//         $db->setQuery($query);
//         return $db->loadAssocList();
    }
    
    private function storeStationTracks($station_id, $trackList) {
//         //delete existing list
//         $db = $this->getDbo();
//         $query = $db->getQuery(true);
//         $query->delete($db->quoteName('#__xbmusic_trackstation'));
//         $query->where('station_id = '.$db->q($station_id));
//         $db->setQuery($query);
//         $db->execute();
//         //restore the new list
//         foreach ($trackList as $trk) {
//             if ($trk['track_id'] > 0) {
//                 if (!key_exists('listorder', $trk)) $trk['listorder'] = 0;
//                 $query->clear();
//                 $query->insert($db->quoteName('#__xbmusic_trackstation'));
//                 $query->columns('station_id,track_id,role,note,listorder');
//                 $query->values('"'.$station_id.'","'.$trk['track_id'].'","'.$trk['role'].'","'.$trk['note'].'","'.$trk['listorder'].'"');
//                 //try
//                 $db->setQuery($query);
//                 $db->execute();
//             }
//         }
    }
    
}

