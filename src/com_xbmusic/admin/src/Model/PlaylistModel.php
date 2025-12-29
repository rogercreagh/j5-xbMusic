<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/PlaylistModel.php
 * @version 0.0.59.17 22nd December 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2025
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
use Joomla\Filesystem\File;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\AzApi;
use \SimpleXMLElement;
use Exception;
//use Webauthn\MetadataService\Event\NullEventDispatcher;

class PlaylistModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.playlist';
    
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.playlist.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing playlist.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.playlist.' . (int) $record->id);
        }
        
        // New playlist, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither playlist nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the playlists within the category so the new playlist is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.playlist');  
                if ($item->az_info) $item->az_info = json_decode($item->az_info);
                // TODO get station info as $item->station
                
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.playlist', 'playlist', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tags = $form->getValue('tags',null,'');
        $tagsarr = (is_array($tags)) ? $tags : explode(',',$tags);
        $parentids = $params->get('playlisttagparents',[]);
        if (!empty($parentids)) {
            $taghelp = new TagsHelper;
            $parr = $taghelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                $element = new SimpleXMLElement('<field name="'.$groupname.'" type="xbtags" label="'.ucfirst($parent).' Group" mode="nested" multiple="true" custom="allow" parent="'.$pid.'" class="xbtags" />');
                $form->setField($element, null, true, 'taggroups');
                if (!empty($tagsarr)){
                    $groupnametags = $taghelp->getTagTreeArray($pid);
                    //set tags that are in this group
                    $grouptags = array_intersect($groupnametags, $tagsarr);
                    $form->setValue($groupname,null,$grouptags);
                    //remove group tags from the main tags field
                    $tagsarr = array_diff($tagsarr, $groupnametags);
                }
            }
        } // endforeach parenttag
        $form->setValue('tags', null, $tagsarr);
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.playlist.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->tracklist = $this->loadPlaylistTracks();
            $data->schedulelist = $this->loadScheduleList($data->id);
            $data->scheduledcnt = count($data->schedulelist);
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('playlist.id') == 0) && ($retview != '')) {
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
        
    public function save($data) {
        $app    = Factory::getApplication();
        $input  = $app->getInput();
        $params = ComponentHelper::getParams('com_xbmusic');
        $filter = InputFilter::getInstance();
        $infomsg = '';
        $warnmsg = '';

        //alias defaults to title
        if ($data['alias'] == '') {
            $data['alias'] = OutputFilter::stringURLSafe($data['title']);
            $data['alias'] = XbcommonHelper::makeUniqueAlias($data['alias'], '#__xbmusic_azplaylists');
        }
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                //$newpid = $pid;
                if (!empty($data[$groupname])) {
                    //need to test for #new# in 'id' column and if found create a new tag and add its id to group
                    foreach ($data[$groupname] as &$value) {
                        if (strpos($value,'#new#') !== false) {
                            $newtag = XbcommonHelper::getCreateTagPath($value, $pid);
                            $value = $newtag['id'];
                        }
                    }
                    $data['tags'] = ($data['tags']) ?
                    array_unique(array_merge($data['tags'],$data[$groupname])) : $data[$groupname];
                }
            } //endforeach parenttag
        } // endif !empty parentids
        
        // ok ready to save the playlist data
        if (parent::save($data)) {
            if (isset($data['tracklist'])) {
                //remove dupes
                //not sequential or allowdupes=faslse
                if (($data['az_order'] != 'sequential') || ($data['allowdupes'] != 1)) {
                    $data['tracklist'] = $this->removeDupes($data['tracklist']);                    
                }
                $sid = $this->getState('playlist.id');
                $this->storePlaylistTracks($sid, $data['tracklist']);                
            }
            // save schedule
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
            
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    private function removeDupes($list) {
        if (empty($list)) return $list;
        $cnt1 = count($list);
        
       // $uniqueIds = array_unique(array_column($list, 'track_id'));
        $uniqueids = []; $filteredList = [];
        foreach ($list as $row) {
            if (in_array($row['track_id'], $uniqueids) === false) {
                $uniqueids[] = $row['track_id'];
                $filteredList[] = $row;
            }
        }
            
        // Re-index the array if needed
        //$filteredList = array_values($filteredList);
            
        // $filteredArray now contains only one row per unique 'track_id' value
        $cnt2 = count($filteredList);
        if ($cnt1>$cnt2) {
            Factory::getApplication()->enqueueMessage($cnt1-$cnt2.' duplicates removed '.$cnt2.' tracks remain','Warning');
        } else {
            Factory::getApplication()->enqueueMessage($cnt1.' tracks in list, no duplicates','Info');
        }
            
        return $filteredList;
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
        Factory::getApplication()->getSession()->set('playlistname', $data->title);
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
    
    function loadPlaylistTracks() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as track_id, ba.note AS note, ba.listorder AS ordering, ba.listorder AS oldorder');
        $query->from('#__xbmusic_trackplaylist AS ba');
        $query->innerjoin('#__xbmusic_tracks AS a ON ba.track_id = a.id');
        $query->where('ba.playlist_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC', 'a.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    function storePlaylistTracks($playlist_id, $trackList) {
        $res = false;
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('*')->from($db->quoteName('#__xbmusic_trackplaylist'));
        $query->where('playlist_id = '.$playlist_id);
        $db->setQuery($query);
        $oldlist = $db->loadAssocList();
        if (!is_array($oldlist)) $oldlist = [];
        //get existing tracklist to restore in case of failure
        //delete existing  list
        $query->clear();
        $query->delete($db->quoteName('#__xbmusic_trackplaylist'));
        $query->where('playlist_id = '.$playlist_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $n = 0;
        $query->clear();
        $query->insert($db->quoteName('#__xbmusic_trackplaylist'));
        $query->columns('playlist_id,track_id,note,listorder');
        foreach ($trackList as $trk) {
            if ($trk['track_id'] > 0) {
                $query->clear('values');
                $query->values('"'.$playlist_id.'","'.$trk['track_id'].'","'.$trk['note'].'","'.$n.'"');
                try {
                    $db->setQuery($query);
                    $res = $db->execute();                    
                } catch (\Exception $e) {
                    Factory::getApplication()->enqueueMessage('Error saving list '.$e->getCode().' '.$e->getMessage().
                        '<br />'.$query->dump(),'Error');
                }
                if ($res) $n ++;
            }
        }
        if (($n == 0) && (count($oldlist)>0)) {
            $query->clear();
            $query->insert($db->quoteName('#__xbmusic_trackplaylist'));
            $query->columns('playlist_id,track_id,note,listorder');
            $query->values($oldlist);
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                Factory::getApplication()->enqueueMessage('Error restoring old values '.$e->getCode().' '.$e->getMessage().
                    '<br />'.$query->dump(),'Error');
            }
            
        }
        return $n;
    }

    public function importTrklistAz($data) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $loglevel = $params['loglevel'];
        $msglevel = $params['msglevel'];
//        $loglevel = $this->loglevel; //$data['params']['loglevel'];
//        $msglevel = $this->msglevel; //$data['params']['loglevel'];
        $app = Factory::getApplication();
        if (!$this->save($data)) {
            $app->enqueueMessage('Save playlist data failed','Error');
            return false;
        }
        if (($data['az_plid'] > 0) && ($data['db_stid']>0)){
            $station = XbcommonHelper::getItem('#__xbmusic_azstations', $data['db_stid']);
//            $stmedia = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'mediapath', $data['db_stid']);
 //           $station = XbcommonHelper::getItem('#__xbmusic_azstations', $data['db_stid']);
            if (empty($station->mediapath)) {
                $errstr = 'Station media path not set. Unable to assign tracks to list. Please visit <a href="index.php?option=com_xbmusic&task=station.edit&id='.$data['db_stid'].'" >Edit Station</a> page and enter media path';
                if ($msglevel >1) $app->enqueueMessage($errstr,'Error');
                return false;
            }
            
            $m3ufpathname = $this->makeM3uFilename($data['alias'], $station->alias);
            
            //get az station id for use in the api call
//            $azstid = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'az_stid', $data['db_stid']);
            $api = new AzApi();
            $result = $api->getAzPlaylistM3u($station->az_stid, $data['az_plid'], $m3ufpathname);
            if ($result == true) {
 //               $stalias = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'alias', $data['db_stid']);
                $logfilename = 'az_import_'.$station->alias.'_'.$data['alias'].date('Y-m-d-Hi').'.log';
                $loglines = '';
                $loghead = '[IMPORT M3U]Importing M3U file from Azuracast for Playlist '.$data['title']."\n";
                if (file_exists($m3ufpathname)) {
                    if ($loglevel > 0) $loglines .= XBSUM.'Source File: '.$fname."\n";
                    $newtrks = $this->getM3uTracks($m3ufpathname, $data, $loglines);
                    if (empty($newtrks)) {
                        if ($msglevel > 2) $app->enqueueMessage('No new tracks found to import','Warning');
                        if ($loglevel > 2) $loglines .= 'No new tracks found to import'."\n";
                    } else {
                        $newtrks = $this->cleanTracklist($newtrks, $loglines, $data);
                        $res = $this->storePlaylistTracks($data['id'], $newtrks);
                        if ($res > 0) {
                            $msg = $res . ' files restored to track list';
                            if ($msglevel >0) $app->enqueueMessage($msg,'Success');
                            if ($loglevel > 0 ) $loglines .= XBSUM.$msg."\n";
                            //save lastsync to playlist
                            $db = Factory::getDbo();
                            $query = $db->getQuery(true)
                                ->update($db->qn('#__xbmusic_azplaylists'));
                            $query->set($db->qn('tracks_sync').' = CURRENT_TIMESTAMP');
                            $query->where($db->qn('id').' = '.$db->q($data[]));
                            try {
                                $db->setQuery($query);
                                $ret = $db->execute();
                            } catch (Exception $e) {
                                $msg = 'Error saving tracks_synce value for playlist<br/ >'.$e->getMessage().'<br />'.$query->dump();
                                if ($msglevel > 0) $app->enqueueMessage($msg,'Error');
                                if ($loglevel > 0) $loglines .= XBSUM.$msg."\n";
                            }
                        
                            
                        } else {
                            $msg = 'Error saving tracklist - check data.';
                            if ($msglevel > 0) $app->enqueueMessage($msg,'Error');
                            if ($loglevel > 0) $loglines .= XBSUM.$msg."\n";                            
                        }
                    }
                } else {
                    if ($msglevel >1) $app->enqueueMessage('Could not find m3u file '.$m3ufpathname,'Error');
                    $loglines .= XBERR.'Import file '.basename($m3ufpathname).' not found. '."\n";
                }
                XbmusicHelper::writelog($loghead.$loglines, $logfilename);
                return true;
            } else {
                if ($msglevel >1) $app->enqueueMessage('API error: '.print_r($result,true),'Error');
                return false;
            }
        } else {
            if ($msglevel >1) $app->enqueueMessage('Station or Playlist ID missing. St:'.$data['db_stid']. 'Pl:'.$data['az_plid'],'Error');   
            return false;
        }
    }
    
    public function loadTrklistM3u($data) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $loglevel = $params['loglevel'];
        $msglevel = $params['msglevel'];
//        $loglevel = $this->loglevel; //$data['params']['loglevel'];
//        $msglevel = $this->msglevel; //$data['params']['loglevel'];
        
        $app = Factory::getApplication();
        if (!$this->save($data)) {
            $app->enqueueMessage('Save playlist data failed','Error');
            return false;
        }
        $path = JPATH_ROOT. "/xbmusic-data/m3u/";
        $source = '';
        if ($data['loadsource'] == 1) {
            $file = $app->getInput()->files->get('jform')['upload_filem3u'];
            //get uploaded file
            $fname = File::makeSafe($file['name']);
            $src = $file['tmp_name'];
            $source = 'client upload '.$src;
            $dest = $path . $fname;
            try {
                $upok = File::upload($src, $dest);                
            } catch (\Exception $e) {
                if ($msglevel > 1) $app->enqueueMessage('UPLOAD FAILED: '.$source.'<br />'.$e->getMessage(), 'Error');
                return false;
            }
        } else {
            $source = '/xbmusic-data/m3u/';
            $fname = $data['local_filem3u'];
        }
        $loglines = '';
        $stalias = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'alias', $data['db_stid']);
        $logfilename = 'm3u_import_'.$stalias.'_'.$data['alias'].date('Y-m-d-Hi').'.log';
        $loghead = '[IMPORT M3U]Importing M3U file from '.$source.' for Playlist '.$data['title']."\n";
        if (file_exists($path.$fname)) {
            if ($loglevel > 0) $loglines .= XBSUM.'Source File: '.$fname."\n";
            $newtrks = $this->getM3uTracks($path.$fname, $data, $loglines);            
            if (empty($newtrks)) {
                if ($msglevel > 2) $app->enqueueMessage('No new tracks found to import','Warning');
                if ($loglevel > 2) $loglines .= 'No new tracks found to import'."\n";
            } else {
                $newtrks = $this->cleanTracklist($newtrks, $loglines, $data);
                $this->storePlaylistTracks($data['id'], $newtrks);                
                // returns fa
            }
        } else {
            $msg = 'Could not find m3u file ';
            if ($msglevel > 1) $app->enqueueMessage($msg.'<Code>xbmusic-data/m3u/'.$fname.'</code>','Error');
            if ($loglevel > 1) $loglines .= XBERR.$msg.'/xbmusic-data/m3u/'.$fname."\n";
        }
        if (($loglines != '') && ($loglevel > 0)) XbmusicHelper::writelog($loghead.$loglines, $logfilename);                
        return true;
    }
    
    /**
     * @name cleanTracklist()
     * @desc used by loadTrklistM3u() and loadTrklistAz() to merge imported list with existing and remove dupes as required
     * @param array $tracklst - the list being imported
     * @param array $data - the form data including existing tracklist
     * @return string - log messages
     */
    private function cleanTracklist(array $tracklst, string &$loglines, array $data) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $loglevel = $params['loglevel'];
        $msglevel = $params['msglevel'];
        
        $app = Factory::getApplication();
        $stname = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'title', $data['db_stid']);
        if ((!empty($data['tracklist'])) && ($data['params']['clearfirst'] == 0)) {
            //we have an existing tracklist and we are not clearing it
            $tracklst = array_merge($data['tracklist'],$tracklst);
            $msg = 'Imported tracks appended to '.count($data['tracklist']).' in existing list';
            if ($msglevel > 3) $app->enqueueMessage($msg, 'Info');
            if ($loglevel > 3) $loglines .= XBINFO.$msg."\n";
        } else {
            $msg='Existing list replaced with new list';
            if ($msglevel > 3) $app->enqueueMessage('$msg', 'Info');
            if ($loglevel > 3) $loglines .= XBINFO.$msg."\n";
        }
        //azuracast will only accept duplicates in a sequential list
        if (($data['az_order'] != 'sequential') || ($data['allowdupes'] != 1)) {
            $cnt1 = count($tracklst);
            $tracklst = $this->removeDupes($tracklst);
            if (count($tracklst) < $cnt1) {
                if ($msglevel > 2) $app->enqueueMessage('NB Duplicate tracks removed here, but changes not yet written back to '.$stname.'<br />Export playlist to update Azuracast', 'Warning');
                if ($loglevel > 2) $loglines .= ($cnt1-count($tracklst)).XBWARN.' duplicate tracks removed. Azuracast not updated yet'."\n";
            }
        }
        return $tracklst;
    }
    
    public function exportTrklistAz($data) {
        $app = Factory::getApplication();
        if (($data['az_order'] != 'sequential') || ($data['allowdupes'] != 1)) {
            $data['tracklist'] = $this->removeDupes($data['tracklist']);
            $this->storePlaylistTracks($data['id'], $data['tracklist']);
        }
        $filename = $this->saveTrkListM3u($data, false);
        if ($filename != '') {
            $api = new AzApi($data['db_stid']);
            if ( ($data['params']['clearremote'] == 0)) {
                $result = $api->clearAzPlaylistTracks($data['az_plid']);
                if ($result == true) {
                    $app->enqueueMessage('Existing tracklist cleared on Azuracast','Success');
                } else {
                    $app->enqueueMessage('API error: '.print_r($result,true),'Error');
                    return false;
                }
            }
            $result = $api->putAzPlaylistM3u($data['az_plid'],$filename);
            if ($result == true) {
                $app->enqueueMessage('M3u playlist upload okay','Success');
            } else {
                $app->enqueueMessage('API error: '.print_r($result,true),'Error');
                return false;
            }
        }                
        return true;
    }
    
    public function saveTrkListM3u($data, $dl = true) {
        $app = Factory::getApplication();
        $mediapath = JPATH_ROOT.'/xbmusic/';
        $stalias = '';
        if (($data['az_plid'] > 0) && ($data['db_stid']>0)){
            $station = XbcommonHelper::getItem('#__xbmusic_azstations', $data['db_stid']);
            if ($station) {                
                $mediapath .= $station->mediapath;
                $stalias = $station->alias."-";
            }
            if (empty($station->mediapath)) {
                $warnstr = 'Station media path not set, using JPATH_ROOT/xbmusic/ as the base folder';
                $app->enqueueMessage($warnstr,'Warning');
            }            
        } else {
            $warnstr = 'Station not set, using JPATH_ROOT/xbmusic/ as the base folder';
            $app->enqueueMessage($warnstr,'Warning');
        }
        $expfname = JPATH_ROOT."/xbmusic-data/m3u/".$stalias.$data['alias'].'-'.date('Y-m-d-Hi').".m3u";
        $n = 0;
        $parts = pathinfo($expfname);
        $tname = $parts['filename'];
        while (file_exists($parts['dirname'].'/'.$tname.'.'.$parts['extension'])) {
            $n ++;
            $tname = $parts['filename']."_";
            $tname .= ($n<10) ? '0' : '';
            $tname .= $n;
        }
        $expfname = $parts['dirname'].'/'.$tname.'.'.$parts['extension'];
        $explocalname = '/xbmusic-data/m3u/'.$tname.'.'.$parts['extension'];
        $f = fopen($expfname, 'w');
        foreach ($data['tracklist'] as $listrow) {
            $trackpath = XbcommonHelper::getItemValue('#__xbmusic_tracks','filepathname',$listrow['track_id']);
            $m3uline = str_replace($mediapath, '', $trackpath);
            if (!fwrite($f, $m3uline ."\n")) {
                $app->enqueueMessage('Error writing '.$m3uline.' to '.$explocalname,'Error');
                return false;           
            }
        }
        if (fclose($f)) {
            $app->enqueueMessage($explocalname.' saved ok','Success');
        } else {
            $app->enqueueMessage('Error closing '.$explocalname,'Error');
            return false;
        }
        //$download = $data['dl_file'];
        if ($dl && ($data['savedest'] == 1)) {
            if (file_exists($expfname)) {
                // Set headers to force download
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($expfname) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($expfname));
                readfile($expfname);
                exit;
            } else {
                //this would be pretty odd since we only just created it successfully!
                $app->enqueueMessage($explocalname.' file not found, could not download','Error');
                return false;
            }            
        }
        return $expfname;
    }
    

    /**
     * @name makeM3uFilename()
     * @desc assumes station and media path are set if required (imp/exp to azuracast) 
     * @param array $data - source for getting station name
     * @param string $action
     * @return string file full path name unique in xbmusic-data/m3u/ prefix_station_playlist_yy-mm-dd-hhmm[-cycleno]
     */
    private function makeM3uFilename(string $plalias, $stalias = '', $replace = false) {
        $m3ufname = JPATH_ROOT."/xbmusic-data/m3u/".$stalias."_".$plalias.".m3u";
        //'_'.date('Y-m-d-Hi').".m3u";
        if ($replace) {
            if (file_exists($m3ufname)) {
                unlink($m3ufname);
                clearstatcache(true,$m3ufname);
            }
        } else {          
            $n = 0;
            $parts = pathinfo($m3ufname);
            $tname = $parts['filename'];
            while (file_exists($parts['dirname'].'/'.$tname.'.'.$parts['extension'])) {
                $n ++;
                $tname = $parts['filename']."-";
                $tname .= ($n<10) ? '0' : '';
                $tname .= $n;
            }
            $m3ufname = $parts['dirname'].'/'.$tname.'.'.$parts['extension'];
        }
        return $m3ufname;
    }
 
    /**
     * @name getM3uTracks()
     * @desc parses a file and checks if each trackfile exist and then if it is in database
     * @desc If not in database optionally imports it.
     * @param string $m3ufile - the playlist file to parse
     * @param array $data - the playlist data
     * @param string $logfilename - 
     * @return boolean|string[][]|NULL[][]
     */
    public function getM3uTracks(string $m3ufile, array $data, &$loglines) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $loglevel = $params['loglevel'];
        $msglevel = $params['msglevel'];
//        $loglevel = $this->loglevel; //$data['params']['loglevel'];
//        $msglevel = $this->msglevel; //$data['params']['loglevel'];
        clearstatcache(true,$m3ufile);
        $app = Factory::getApplication();
        $msgstr = ''; $warnstr = ''; $errstr = '';
//        $logstr = '';
//        $logfilename = 'playlistm3u_import_'.date('Y-m-d').'.log';
        //check file size <?
        $filelist = [];
        $missingfiles = [];
//        $createfiles = [];
        $newtrks = [];
//        $trks2create = [];
        $ignoremissing = $data['params']['ignoremissing']; //data['ignoremissing']
        $createtracks = $data['params']['createtrks'];
        $delafter = $data['delete_m3u'];
        $stmedia = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'mediapath', $data['db_stid']);
        $mediapath = JPATH_ROOT.'/xbmusic/'.$stmedia;
        if ($lines = file($m3ufile)) {
            if (count($lines) >400) {
                $app->enqueueMessage('M3U file contains over 400 lines. Importing the whole list will take some time and may create memory overflow or other data loss issues. The file will saved as multiple files each containing no more than 400 lines with a suffix added to the name. Please import each file separately from the data folder.','Warning');
                //process file here - chunk the array as save each as separate file
                $chunks = array_chunk($lines, 300);
                foreach ($chunks as $key=>$value) {
                    $parts = pathinfo($m3ufile);
                    $chunkfile = $parts['dirname'].'/'.$parts['filename'].'_'.($key+1).'.'.$parts['extension'];
                    if (file_exists($chunkfile)) {
                        unlink($chunkfile);
                        clearstatcache(true,$chunkfile);
                    }
                    file_put_contents($chunkfile, implode(PHP_EOL, $value));
                }
                $app->enqueueMessage(count($chunks).' '.'chunk files <code>'.$m3ufile.'_N</code> have been saved in'. 
                    '<code>/xbmusic-data/m3u/</code>','Success');
                return false;
            } else {
                $msg = 'Missing files ';
                foreach ($lines as $line) {
                    if (file_exists($mediapath.trim($line))) {
                        $filelist[] = trim($line);
                    } elseif ($ignoremissing) {
                        $warnstr .= $msg.$mediapath.trim($line).'<br />';
                        $missingfiles[] = trim($line);
                        if ($loglevel > 2) $loglines .= XBWARN.$msg.$mediapath.$line."\n";
                    } else {
                        $missingfiles[] = trim($line);
                        $errstr .= $msg.$mediapath.trim($line).'<br />';
                        if ($loglevel > 1) $loglines .= XBERR.$msg.$line."\n";
                    }
                }
            }
        } else {
            $errstr = 'Could not open m3u file for reading ';
            if ($msglevel > 1) $app->enqueueMessage($errstr.'<code>'.basename($m3ufile).'</code>','Error');
            if ($loglevel > 1) $loglines .= XBERR.$errstr.basename($m3ufile)."\n";
            return false;
        }
        if (!empty($missingfiles)) {
            $f = fopen(JPATH_ROOT.'/xbmusic-data/m3u/missing_files.m3u', 'a');
            fwrite($f, implode("\n", $missingfiles));
            fclose($f);
            if ($loglevel > 0) $loglines .= XBSUM.'Missing files list appended to /xbmusic-data/m3u/missing_files.m3u'."\n";
            $warnstr .= 'Missing files list appended to <code>/xbmusic-data/m3u/missing_files.m3u</code><br />';
        }
        if ($errstr != '') {
            $msg = 'Import aborted due to missing file(s):';
            if ($msglevel > 1) $app->enqueueMessage($errstr.'<br />'.$msg,'Error');
            if ($loglevel > 1) $loglines .= XBERR.$msg."\n";
            if ($delafter == 2) umlink($m3ufile);
            return false;              
        }
        if (count($filelist)==0) {
            $warnstr .= 'No valid files found to add to playlist';
            if ($msglevel > 2) $app->enqueueMessage($warnstr,'Warning');
            if ($loglevel > 2) $loglines .= XBWARN.$warnstr."\n";
            if ($delafter == 2) umlink($m3ufile);
            return false;
        }
        $msgstr .= count($filelist).' valid files found in m3u file: ';
        if ($loglevel > 0) $loglines = XBINFO.$msgstr."\n";
        $cnt= 0;
        $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
        $dmmodel = $this->getMVCFactory()->createModel('Dataman');
        
        foreach ($filelist as $file) {  
            // we'll check if the track needs importing           
            $trk = XbcommonHelper::getItem('#__xbmusic_tracks',$mediapath.$file,'filepathname');
            if (is_null($trk)) {
                if ($createtracks) {
                    //add station media path to file pathname from m3u
                    //$trks2create[] = $stmedia.$file;
                    $loglines .= $dmmodel->parseID3(JPATH_ROOT.'/xbmusic/'.$stmedia.$file, $cnts);
                    $trk = XbcommonHelper::getItem('#__xbmusic_tracks',$mediapath.$file,'filepathname');
                } else {
                    $warnstr .= 'Ignoring '.$file.' not in database.<br />';
                    if ($loglevel > 2) $loglines .= XBWARN.'Ignoring '.$file.' not in database.'."\n";                    
                }
            }
            if (!is_null($trk)) {
                $newtrks[] = array('track_id'=>$trk->id, 'note'=>'from M3U', 'oldorder'=>'0');                        
                $cnt ++;
            }
        }
        Factory::getApplication()->enqueueMessage('<pre>'.print_r($cnts,true).'</pre>');
//         $creatcnt = count($trks2create);
//         if ($creatcnt > 0) {
//             $dmmodel = $this->getMVCFactory()->createModel('Dataman');
//             if ($creatcnt > 50) {
//                 $msg = $creatcnt . ' missing tracks need adding to database. Only creating first 50. Remainder in file <code>MissingTracks_'.$m3ufile.'</code> in the <code>xbmusic-data/m3u</code> folder. Re-import this file to create and ass the missing ones.';
//             }
//             $trks2create = array_chunk($trks2create, 50);
//             foreach ($trks2create as $chunk) {    
//                 //parseFilesMp3 takes a single file or folder name or an array of filenames but no more than 50 at a time
//                 $cnt += $dmmodel->parseFilesMp3($chunk);                                
//             }
//         }
        if ($delafter == 2) unlink($m3ufile);
        if (($cnts['errtrk' == 0]) && ($delafter == 1)) unlink($m3ufile);
        $msg = $cnt.' files to add to playlist';
        $msgstr .= $msg.'<br />';
        if ($loglevel > 0) $loglines .= XBINFO.$msg."\n";
        if ($msglevel > 3) $app->enqueueMessage($msgstr,'Info');
        if (($msglevel > 2) && ($warnstr != '')) $app->enqueueMessage($warnstr,'Warning');
        return $newtrks;        
    }

    
    public function reloadPlaylist($dbdata) {
        $api = new AzApi($dbdata['db_stid']);
        $azpldata = $api->azPlaylist($dbdata['az_plid']);
        if (isset($azpldata->code)) {
            Factory::getApplication()->enqueueMessage('reloadPlaylist Azuracast API Error: code '.$azpldata->code.
                ' - '.$azpldata->type.'<br />'.$azpldata->formatted_message,'Warning');
            return false;
        }
        $dbdata['modified_by'] = $this->getCurrentUser()->id;
        $dbdata['modified'] = Factory::getDate()->toSql();
        if ($dbdata['alias'] == '') $dbdata['alias'] = $azpldata->short_name.'-'.$dbdata['azstation'].'-'.$azpldata->id;
//         $dbdata['az_plid'] = $azpldata->id;
        $dbdata['az_name'] = $azpldata->name;
//         $dbdata['db_stid'] = $dbdata['azstation'];
        
        $dbdata['az_info'] = json_encode($azpldata);
        
        $dbdata['az_cntper'] = 0;
        $type = $azpldata->type;
        switch ($type) {
            case 'default':
                $dbdata['az_type'] = 1;
                break;
            case 'once_per_x_songs':
                $dbdata['az_type'] = 2;
                $dbdata['az_cntper'] = $azpldata->play_per_songs;
                break;
            case 'once_per_x_minutes':
                $dbdata['az_type'] = 3;
                $dbdata['az_cntper'] = $azpldata->play_per_minutes;
                break;
            case 'once_per_hour':
                $dbdata['az_type'] = 4;
                $dbdata['az_cntper'] = $azpldata->play_per_hour_minute;
                break;
            case 'custom':
                $dbdata['az_type'] = -1;
                break;
                
            default:
                $dbdata['az_type'] = 0;
                break;
        }
        $dbdata['az_order'] = $azpldata->order;
        $dbdata['az_jingle'] = ($azpldata->is_jingle == 'true') ? 1 : 0;
        $dbdata['az_weight'] = $azpldata->weight;
        $dbdata['scheduledcnt'] = count($azpldata->schedule_items);
        if ($azpldata->is_enabled == false) $dbdata['status'] = 0;
        
        $ans = $this->save($dbdata);
        
        if ($ans) {
            //also need to remove existing schedule items for this playlist and generate new ones
            $id = $this->getState('playlist.id');
            $this->deleteSchedules($id);
            $this->createSchedules($id, $azpldata);
            Factory::getApplication()->enqueueMessage(Text::sprintf('XBMUSIC_PLAYLIST_RELOAD_OK',$dbdata['az_name']),'Success');
        return $id;
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_SAVE_FAIL'),'Error');
        }
        return false;
    }
    
    private function deleteSchedules(int $dbplid){
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_azschedules'));
        $query->where('dbplid = '.$dbplid);
        try {
            $db->setQuery($query);
            $res = $db->execute();            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getCode().' '.$e->getMessage().'<br />'. $query>dump(),'Error');
            return $e;
        }
        return $res;
    }
    
    private function createSchedules(int $dbplid, $azpldata) {
        $scheduleitems = $azpldata->schedule_items;
        $status = ($azpldata->is_enabled == true) ? 1 : 0;
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $res = true;
        $cnt = 0;
        foreach ($scheduleitems as $schd) {
            $azdays = implode(',',$schd->days);
            $azloop = ($schd->loop_once == true) ? 1 : 0;
            $dostart = !empty($schd->start_date);
            $doend = !empty($schd->end_date);
            $query->clear();
            $query->insert($db->qn('#__xbmusic_azschedules'));
            $columns = 'dbplid, az_shid, az_starttime, az_endtime,';
            if ($dostart) $columns .= 'az_startdate,';
            if ($doend) $columns .= 'az_enddate,';
            $columns .= 'az_days, az_loop, status, created, created_by, created_by_alias, note';
            $query->columns($columns);
            $values = $db->q($dbplid).','.$db->q($schd->id).','.
                $db->q(date("H:i:s", strtotime($schd->start_time))).','.
                $db->q(date("H:i:s", strtotime($schd->end_time))).',';
            if ($dostart) $values .= $db->q($schd->start_date).',';
            if ($doend) $values .= $db->q($schd->end_date).',';
            $values .= $db->q($azdays).','.
                $db->q($azloop).','.
                $db->q($status).','.
                $db->q(Factory::getDate()->toSql()).','.
                $db->q(Factory::getApplication()->getIdentity()->id).','.
                $db->q('import from Azuracast API').','.
                $db->q('');
                $query->values($values); //(implode(',',$values));
                $db->setQuery($query);
                $res = $db->execute();
                if ($res == false) {
                    Factory::getApplication()->enqueueMessage('Problem saving schedule item '. $schd->id, 'Warning');
                } else {
                    $cnt ++;
                }
            }
            if ($res == true)   Factory::getApplication()->enqueueMessage($cnt.' schedule items saved', 'Success');
            return $res;
        }    
    
    public function putPlaylist($dbdata) {
        $data = array();
        $data['name'] = $dbdata['az_name'];
        $data['play_per_songs'] = '0';
        $data['play_per_minutes'] = '0';
        $data['play_per_hour_minute'] = '0';
        
        switch ($dbdata['az_type']) {
            case 1:
                $data['type'] = 'default';
                break;
            case 2:
                $data['type'] = 'once_per_x_songs';
                $data['play_per_songs'] = $dbdata['az_cntper'];
                break;
            case 3:
                $data['type'] = 'once_per_x_minutes';
                $data['play_per_minutes'] = $dbdata['az_cntper'];
                break;
            case 4:
                $data['type'] = 'once_per_hour';
                $data['play_per_hour_minute'] = $dbdata['az_cntper'];
                break;
            case -1:
                $data['type'] = 'custom';            
                break;
            
            default:
                $data['type'] = 'default';                
            break;
        }
        $data['order'] = $dbdata['az_order'];
        $data['is_jingle'] = $dbdata['az_jingle'];
        $data['weight'] = $dbdata['az_weight'];
        
        /*************** ADD BACK SCHEDULE ITEMS HERE****************/
        
        $jsondata = json_encode($data);
        $api = new AzApi($dbdata['db_stid']);
        $putres = $api->putAzPlaylist($dbdata['az_plid'], $jsondata);
        if (isset($putres->code)) {
            Factory::getApplication()->enqueueMessage('putPlaylist Azuracast API Error: code '.$putres->code.' - '.$putres->type.
                '<br />'.$putres->formatted_message,'Warning');
            return false;
        }
        $ans = $this->reloadPlaylist($dbdata);
        return $ans;
    }
    
    public function unlinkPlaylist($dbdata) {
        
        $dbdata['modified_by'] = $this->getCurrentUser()->id;
        $dbdata['modified'] = Factory::getDate()->toSql();
        $dbdata['az_name'] = null;
        $dbdata['az_info'] = null;
        $dbdata['az_cntper'] = null;
        $dbdata['az_type'] = null;
        $dbdata['az_order'] = null;
        $dbdata['az_jingle'] = null;
        $dbdata['az_weight'] = null;
        $dbdata['az_plid'] = 0;
        $dbdata['db_stid'] = 0;
        
        $ans = $this->save($dbdata);
        if ($ans) {
            //delete playlist schdules
            $this->deleteSchedules($dbdata['id']);
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_UNLINK_OK'),'Success');
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_SAVE_FAIL'),'Error');
        }
        return $ans;
    }

    public function loadScheduleList($playlist_id = 0) {
        if ($playlist_id <1) $playlist_id = 0;
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('sh.id AS id, sh.az_shid AS az_shid, sh.dbplid AS dbplid, az_starttime, az_endtime, az_startdate, az_enddate, az_days, az_loop');
        $query->from('#__xbmusic_azschedules AS sh');
        $query->innerjoin('#__xbmusic_azplaylists AS a ON sh.dbplid= a.id');
        $query->where('sh.dbplid = '.$playlist_id);
        $query->order('az_startdate ASC', 'az_starttime ASC');
        $db->setQuery($query);
        $schedulelist =  $db->loadAssocList();
        return $schedulelist;
    }
    
    function storeScheduleList($playlist_id, $scheduleList) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_azschedules'));
        $query->where('playlist_id = '.$playlist_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $showpublic = '1';
        $status = '1';
        $created = Factory::getDate()->toSql();
        $created_by = Factory::getApplication()->getIdentity()->id;
        
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_azschedules'));
                $query->columns('dbplid, az_shid, az_starttime, az_endtime, az_startdate, az_enddate, az_days, az_loop, showpublic, status, created, created_by');
                $query->values('');
        foreach ($scheduleList as $schd) {
            $query->clear('values');
   
        }
    }
    
}

