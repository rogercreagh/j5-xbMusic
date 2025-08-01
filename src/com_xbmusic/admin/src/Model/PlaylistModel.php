<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/PlaylistModel.php
 * @version 0.0.57.1 26ths
 *  July 2025
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
//use Webauthn\MetadataService\Event\NullEventDispatcher;

class PlaylistModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.playlist';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from playlists :';
        foreach ($pks as $pk) {
            if ($this->getCurrentUser()->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.playlist', $pk, false);
                $oldtags = array_column($existing,'tag_id');
                $newtags = array();
                for ($i = 0; $i<count($oldtags); $i++) {
                    if ($oldtags[$i] != $value) {
                        $newtags[] = $oldtags[$i];
                    }
                }
                $params = array( 'id' => $pk, 'tags' => $newtags );
                
                if($this->save($params)){
                    $message .= ' '.$pk;
                }
            } else {
                $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
                return false;
            }
            Factory::getApplication()->enqueueMessage($message);
        }
        return true;
    }
    
    public function delete(&$pks) {
        //first need to delete links to albums, artists, tracks
        //this should now be down by sql cascade
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
            $query->delete($db->qn('#__xbmusic_trackplaylist'));
            $query->where($db->qn('playlist_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
        }
        return parent::delete($pks);
    }
    
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
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
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

        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));
            
            if ($data['title'] == $origTable->title) {
                list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);
                $data['title'] = title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }
            //need to add tracklinks
            $this->storePlaylistTracks($origTable->id, $data['tracklist']);
            // standard Joomla practice is to set the new copy record as unpublished
            $data['status'] = 0;
        }
        
       
        //alias defaults to title
        if ($data['alias'] == '') {
            $data['alias'] = OutputFilter::stringURLSafe($data['title']);
            $data['alias'] = XbcommonHelper::makeUniqueAlias($data['alias'], '#__xbmusic_playlists');
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
                $sid = $this->getState('playlist.id');
                $this->storePlaylistTracks($sid, $data['tracklist']);                
            }
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
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
        //delete existing  list
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_trackplaylist'));
        $query->where('playlist_id = '.$playlist_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $n = 1;
        foreach ($trackList as $trk) {
            if ($trk['track_id'] > 0) {
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_trackplaylist'));
                $query->columns('playlist_id,track_id,note,listorder');
                $query->values('"'.$playlist_id.'","'.$trk['track_id'].'","'.$trk['note'].'","'.$n.'"');
                //try
                $db->setQuery($query);
                $db->execute();
                $n ++;
            }
        }
    }

    public function importTrklistAz($data) {
        $app = Factory::getApplication();
        if (($data['az_id'] > 0) && ($data['az_dbstid']>0)){
            $stmedia = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'mediapath', $data['az_dbstid']);
            if (empty($stmedia)) {
                $errstr = 'Station media path not set. Unable to assign tracks to list. Please visit <a href="index.php?option=com_xbmusic&task=station.edit&id='.$data['az_dbstid'].'" >Edit Station</a> page and enter media path';
                $app->enqueueMessage($errstr,'Error');
                return false;
            }
            $api = new AzApi($data['az_dbstid']);
            $result = $api->getAzPlaylistM3u($data['az_id']);
            if ($result == true) {
                $stalias = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'alias', $data['az_dbstid']);
                $logfilename = 'playlistm3u_import_'.date('Y-m-d-Hi').'.log';
                $loghead = '[IMPORT M3U]Importing M3U file from Azuracast for Playlist '.$data['title']."\n";
 //               XbmusicHelper::writelog(XBENDLOG, $logfilename);
                $tmpfile = JPATH_ROOT."/xbmusic-data/m3u/".date('Y-m-d-Hi').".m3u";
                //$tmpfile = JPATH_ROOT."/xbmusic-data/today".".m3u";
                if (file_exists($tmpfile)) {
                    $newtrks = $this->getM3uTracks($tmpfile, $data, $logfilename);
                    if (!empty($newtrks)) {
                        rename($tmpfile, JPATH_ROOT."/xbmusic-data/m3u/".$stalias.'-'.$data['alias'].'_'.date('Y-m-d').".m3u");
                        //need to merge newtrks with existing
                        if (!empty($data['tracklist'])) $newtrks = array_merge($data['tracklist'],$newtrks);
                        $this->storePlaylistTracks($data['id'], $newtrks);
                        
                    }
                } else {
                    $app->enqueueMessage('Could not find m3u file '.$tmpfile,'Error');
                }
                XbmusicHelper::writelog($loghead, $logfilename);
            } else {
                $app->enqueueMessage('API error: '.print_r($result,true),'Error');
            }
        } else {
            $app->enqueueMessage('Station or Playlist ID invalid. St:'.$data['az_dbstid']. 'Pl:'.$data['az_id'],'Warning');           
        }
    }
    
    public function loadTrklistM3u($data) {
        $app = Factory::getApplication();
        $path = JPATH_ROOT. "/xbmusic-data/m3u/";
        $source = '';
        if ($data['local_remote'] == 0) {
            $source = 'client upload';
            //get uploaded file
            $file = $app->getInput()->files->get('jform')['upload_filem3u'];
            $fname = File::makeSafe($file['name']);
            $src = $file['tmp_name'];
            $dest = $path . $fname;
            File::upload($src, $dest);
        } else {
            $source = '/xbmusic-data/m3u/';
            $fname = $data['local_filem3u'];
        }
        $app->enqueueMessage('fname '.$fname);
        $logfilename = 'playlistm3u_import_'.date('Y-m-d-Hi').'.log';
        $loghead = '[IMPORT M3U]Importing M3U file from '.$source.' for Playlist '.$data['title']."\n";
        $loghead .= XBINFO.'File: '.$fname."\n";
        if (file_exists($path.$fname)) {
            $newtrks = $this->getM3uTracks($path.$fname, $data, $logfilename);
            if (!empty($newtrks)) {
//                rename($fname, JPATH_ROOT."/xbmusic-data/m3u/".$stalias.'-'.$data['alias'].'_'.date('Y-m-d').".m3u");
                //need to merge newtrks with existing
                if (!empty($data['tracklist'])) $newtrks = array_merge($data['tracklist'],$newtrks);
                $this->storePlaylistTracks($data['id'], $newtrks);
                
            }
        } else {
            $app->enqueueMessage('Could not find m3u file '.$path.$fname,'Error');
        }
        XbmusicHelper::writelog($loghead, $logfilename);                
        return true;
    }
    
    public function exportTrklistAz($data) {
        $app = Factory::getApplication();
        $filename = $this->saveTrkListM3u($data, false);
        if ($filename != '') {
            $api = new AzApi($data['az_dbstid']);
            $result = $api->putAzPlaylistM3u($data['az_id'],$filename);
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
        if (($data['az_id'] > 0) && ($data['az_dbstid']>0)){
            $station = XbcommonHelper::getItem('#__xbmusic_azstations', $data['az_dbstid']);
            If ($station) {
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
        $expfname = JPATH_ROOT."/xbmusic-data/m3u/".$stalias.$data['alias'].date('Y-m-d').".m3u";
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
        if ($dl && $data['dl_file']) {
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
    
    public function getM3uTracks(string $m3ufile, array $data, string $logfilename) {
        $app = Factory::getApplication();
        $msgstr = ''; $warnstr = ''; $errstr = '';
        $logstr = '';
//        $logfilename = 'playlistm3u_import_'.date('Y-m-d').'.log';
        $filelist = [];
        $ignoremissing = $data['ignoremissing']; //data['ignoremissing']
        $dupesok = ($data['az_order'] == 1                                                                          ) ? $data['allowdupes'] : 0;
        $createtracks = $data['createtrks'];
        $stmedia = XbcommonHelper::getItemValue('#__xbmusic_azstations', 'mediapath', $data['az_dbstid']);
        $mediapath = JPATH_ROOT.'/xbmusic/'.$stmedia;
        if ($lines = file($m3ufile)) {
            $msg = 'Missing file ';
            foreach ($lines as $line) {
                if (file_exists($mediapath.trim($line))) {
                    $filelist[] = trim($line);
                } elseif ($ignoremissing) {
                    $warnstr .= $msg.$mediapath.trim($line).'<br />';
                    $logstr .= XBWARN.$msg.$mediapath.$line;
                } else {
                    $errstr .= $msg.$mediapath.trim($line).'<br />';
                    $logstr .= XBERR.$msg.$line;
                }
            }
        } else {
            $errstr = 'Could not open m3u file for reading '.basename($m3ufile);
            $app->enqueueMessage($errstr,'Error');
            XbmusicHelper::writelog($errstr.XBENDLOG, $logfilename);	            
            return false;
        }
        if ($errstr != '') {
            $msg = 'Import aborted due to missing files:';
            $app->enqueueMessage($msg.$errstr,'Error');
            XbmusicHelper::writelog(XBERR.$msg."\n".$logstr.XBENDLOG,$logfilename);
            return false;              
        }
//             if ($ignoremissing) {
//                 $app->enqueueMessage($msgstr,'Warning');                
//             } else {
//             }
        if (count($filelist)==0) {
            $warnstr .= 'No valid files found to add to playlist';
            $app->enqueueMessage($warnstr,'Warning');
            XbmusicHelper::writelog(XBWARN.$warnstr."\n".$logstr.XBENDLOG,$logfilename);
            return false;
        }
        $msgstr .= count($filelist).' valid files found in m3u file: ';
        $logstr = XBINFO.$msgstr."\n";
        $cnt= 0;
        $newtrks = [];
        foreach ($filelist as $file) {  
            //first we'll check if the track needs importing
            
            $trk = XbcommonHelper::getItem('#__xbmusic_tracks',$mediapath.$file,'filepathname');
            if (is_null($trk)) {
                if ($createtracks) {
                    $file = array($stmedia.$file);
                    $dmmodel = $this->getMVCFactory()->createModel('Dataman'); 
                    $dmmodel->parseFilesMp3($file, '');
                    $trk = XbcommonHelper::getItem('#__xbmusic_tracks',$mediapath.$file,'filepathname');
                } else {
                    $warnstr .= 'Ignoring '.$file.' not in database.<br />';
                    $logstr .= XBWARN.'Ignoring '.$file.' not in database.'."\n";                    
                }
            }
            if (!is_null($trk)) {
                //check if dupes allowed
                $ids = (empty($data['tracklist'])) ? [] : array_column($data['tracklist'],'track_id');
                if (($dupesok) || (array_search($trk->id, $ids)===false)) {
                    $newtrks[] = array('track_id'=>$trk->id, 'note'=>'from M3U', 'oldorder'=>'0');                        
                    $cnt ++;
                }
            }
        }
        $msg = $cnt.' files to add to playlist';
        $msgstr .= $msg.'<br />';
        $logstr .= XBINFO.$msg."\n";
        //write to log
        XbmusicHelper::writelog($logstr.XBENDLOG, $logfilename);
        $app->enqueueMessage($msgstr,'Info');
        if ($warnstr != '') $app->enqueueMessage($warnstr,'Warning');
        return $newtrks;        
    }
    
    public function loadPlaylist($dbdata) {
        //get api with dbstid
        $api = new AzApi($dbdata['azstation']);
        //get azplaylist
        $azpldata = $api->azPlaylist($dbdata['azplaylist']);
        if (isset($azpldata->code)) {
            Factory::getApplication()->enqueueMessage('loadPlaylist Azuracast API Error: code '.$azpldata->code.' - '.$azpldata->type.
                '<br />'.$azpldata->formatted_message,'Warning');
            return false;
        }
        if ($dbdata['id'] > 0) {
            $dbdata['modified_by'] = $this->getCurrentUser()->id;
            $dbdata['modified'] = Factory::getDate()->toSql();
        } else {
            $dbdata['created_by'] = $this->getCurrentUser()->id;
            $dbdata['created_by_alias'] = 'import from Azuracast API';
        }
        if ($dbdata['title'] == '') $dbdata['title'] = $azpldata->name;
        $dbdata['alias'] = $azpldata->short_name.'-'.$dbdata['azstation'].'-'.$azpldata->id;
        $dbdata['alias'] = XbcommonHelper::makeUniqueAlias($dbdata['alias'], '#__xbmusic_playlists');
        $dbdata['az_id'] = $azpldata->id;
        $dbdata['az_name'] = $azpldata->name;
        $dbdata['az_dbstid'] = $dbdata['azstation'];
        
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
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_IMPORT_OK').$id,'Success');
            return $id;
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('XBMUSIC_PLAYLIST_SAVE_FAIL'),'Error');
        }
        return false;
    }
    
    public function reloadPlaylist($dbdata) {
        $api = new AzApi($dbdata['az_dbstid']);
        $azpldata = $api->azPlaylist($dbdata['az_id']);
        if (isset($azpldata->code)) {
            Factory::getApplication()->enqueueMessage('reloadPlaylist Azuracast API Error: code '.$azpldata->code.
                ' - '.$azpldata->type.'<br />'.$azpldata->formatted_message,'Warning');
            return false;
        }
        $dbdata['modified_by'] = $this->getCurrentUser()->id;
        $dbdata['modified'] = Factory::getDate()->toSql();
        if ($dbdata['alias'] == '') $dbdata['alias'] = $azpldata->short_name.'-'.$dbdata['azstation'].'-'.$azpldata->id;
//         $dbdata['az_id'] = $azpldata->id;
        $dbdata['az_name'] = $azpldata->name;
//         $dbdata['az_dbstid'] = $dbdata['azstation'];
        
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
        //$dayarr = array('M','Tu','W','Th','F','Sa','Su');
        foreach ($scheduleitems as $schd) {
//            $daystr ='';
//            foreach ($schd->days as $day) {
//                $daystr .= $dayarr[$day-1].',';
//            }
//            $daysstr = trim($daystr,', ');
//            if ($daysstr == '') $daysstr = implode(', ',$dayarr);
            $azdays = $schd->az_days;
            $azloop = ($schd->loop_once == true) ? 1 : 0;
            $dostart = !empty($schd->start_date);
            $doend = !empty($schd->end_date);
            $query->clear();
            $query->insert($db->qn('#__xbmusic_azschedules'));
            $columns = 'dbplid, az_id, az_starttime, az_endtime,';
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
        
        /*************** ADD BACK SCHEDULE ITEMS ****************/
        
        $jsondata = json_encode($data);
        $api = new AzApi($dbdata['az_dbstid']);
        $putres = $api->putAzPlaylist($dbdata['az_id'], $jsondata);
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
        $dbdata['az_id'] = 0;
        $dbdata['az_dbstid'] = 0;
        
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
        $query->select('sh.id AS id, sh.az_id AS az_id, sh.dbplid AS dbplid, az_starttime, az_endtime, az_startdate, az_enddate, az_days, az_loop');
        $query->from('#__xbmusic_azschedules AS sh');
        $query->innerjoin('#__xbmusic_playlists AS a ON sh.dbplid= a.id');
        $query->where('sh.dbplid = '.$playlist_id);
        $query->order('az_startdate ASC', 'az_starttime ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    function storeScheduleList($playlist_id, $scheduleList) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_azschedules'));
        $query->where('playlist_id = '.$playlist_id);
        $db->setQuery($query);
        $db->execute();
        //restore the new list
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_azschedules'));
                $query->columns('id, dbplid, az_id, az_starttime, az_endtime, az_startdate, az_enddate, az_days, az_loop, status, created, created_by, created_by_alias, note');
                $query->values('');
        foreach ($scheduleList as $schd) {
            $query->clear('values');
            
//            if ($trk['track_id'] > 0) {
//                $query->values('"'.$playlist_id.'","'.$trk['track_id'].'","'.$trk['note'].'","'.$n.'"');
                //try
//                $db->setQuery($query);
//                $db->execute();
//                $n ++;
//            }
        }
    }
    
}

