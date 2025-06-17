<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.53.1 12th June 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
//use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\InputFilter;
//use Joomla\Filter\OutputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
//use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use \SimpleXMLElement;

class TrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.track';
    
    protected $ilogmsg;
        
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
        'playlist' => 'batchPlaylist',
    );
    
    public function __construct($config = [], $factory = null, $form = null) {
        parent::__construct($config, $factory, $form);    
    } 
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from tracks :';
        foreach ($pks as $pk) {
            if ($this->user->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.track', $pk, false);
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
    
    protected function batchPlaylist($value, $pks, $contexts) {
        Factory::getApplication()->enqueueMessage('Playlist add '.$value.' to '.implode(',',$pks).' contexts '.implode(','.$contexts));
    }
    
    public function delete(&$pks) {
        //first need to delete links artists, songs
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        foreach ($pks as $pk) {
            $query->delete($db->qn('#__xbmusic_trackartist'));
            $query->where($db->qn('track_id').' = '.$db->q($pk));
            $db->setQuery($query);
            $db->execute();
            $query->clear('delete');
            $query->delete($db->qn('#__xbmusic_tracksong'));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
            $query->clear('delete');
            $query->delete($db->qn('#__xbmusic_trackplaylist'));
            $db->setQuery($query);
            $db->execute();
            $query->clear();
        }
        
        return parent::delete($pks);
    }
    
    public function loadId3() {
        $app  = Factory::getApplication();
        $app->setUserState('com_xbmusic.edit.track.id3data', null);
        $app->setUserState('com_xbmusic.edit.track.id3loaded', 0);
        $params = ComponentHelper::getParams('com_xbmusic');
        $loglevel = $params->get('loglevel',3);
        $newmsg = Text::_('XBMUSIC_NEW_ITEMS_CREATED').':<br />';
        $newdata = []; //only one track per file
        $ilogmsg = '[IMPORT] Import ID3 Started '.date('H:i:s D jS M Y')."\n";
        
        $data = $app->getInput()->get('jform',[],'array');
//         $app->enqueueMessage(print_r($data,true));
        if ($data['id']>0) {
            //this is an existing track we are reloading
            $ilogmsg .= '[RELOAD TRACK] '.str_replace(JPATH_ROOT.'/xbmusic/','',$data['filepathname'])."\n";            
        } else {
            $filepathname = JPATH_ROOT.'/xbmusic/'.$data['foldername'].$data['selectedfiles'];

// 1. check if filepathname already in database, if it exists already then exit
            if ( $tid = XbcommonHelper::checkValueExists($filepathname, '#__xbmusic_tracks', 'filepathname')) {
                $msg = Text::_('XBMUSIC_TRACK_IN_DB').Xbtext::_($tid,XBSP1 + XBDQ);
                $ilogmsg .= XBERR.$msg.XBENDITEM;
                $app->enqueueMessage(trim($msg),'Error');
                XbmusicHelper::writelog($ilogmsg);
                return false;
            }
            $data['filepathname'] = $filepathname; 
            $data['filename'] = $data['selectedfiles'];
            $ilogmsg = '[IMPORT TRACK] '.str_replace(JPATH_ROOT.'/xbmusic/','',$data['filepathname'])."\n";

// 2. set track->pathname and track->filename, if filename exists then warning
            // check if same filename exists in a different folder - import it anyway and warn
            $fpathinfo = pathinfo($filepathname);
            if ( $fid = XbcommonHelper::checkValueExists($fpathinfo['basename'], '#__xbmusic_tracks', 'filename')) {
                $fpath = XbcommonHelper::getItemValue('#__xbmusic_tracks', 'filepathname', $fid);
                $msg = Text::_('XBMUSIC_FILENAME_IN_DB').Xbtext::_($fpath,7).Text::_('XBMUSIC_WITH_TKID').Xbtext::_($fid,XBDQ + XBNL);
                $ilogmsg .= XBWARN.$msg;
                $msg2 = Xbtext::_('XBMUSIC_IMPORTING_ANYWAY',XBNL + XBTRL);
                $app->enqueueMessage(trim($msg).'<br />'.trim($msg2),'Warning');
                $ilogmsg .= XBWARN.$msg2;
            }            
        } //endif new file
        
//3. okay, now get the id3 data        
        $filedata = XbmusicHelper::getFileId3($data['filepathname']);
        if (!isset($filedata['id3tags']['title'])) { //could add any other required elements to the isset() function
            $msg = Xbtext::_('XBMUSIC_NO_ID3_TITLE',XBNL + XBTRL);
            $ilogmsg .= XBERR.$msg.XBENDITEM;
            $app->enqueueMessage(trim($msg),'Error');
            XbmusicHelper::writelog($ilogmsg);
            return false;
        }
        
        //4. get the basic trackdata from id3
        $id3data = XbmusicHelper::id3dataToItems($filedata['id3tags'],$ilogmsg);
        if (isset($id3data['trackdata'])) {

            $trackdata = $id3data['trackdata'];
            $trackdata['filepathname'] = $filepathname;
            $trackdata['filename'] = $fpathinfo['basename']; // TODO this is potentislly redundasnt
            $trackdata['foldername'] = $data['foldername'];
            $trackdata['selectedfiles'] = $data['selectedfiles'];
            // get genres list, catids are defined above in id3datatoitems()           
            $defcats = XbmusicHelper::getDefaultItemCats();
            
            
            $optimpcat = $params->get('impcat','0');
            $optcattag = ($optimpcat == 1) ? $params->get('genrecattag1',2) : $params->get('genrecattag',2);
            $genreids = (isset($id3data['genres'])) ? array_column($id3data['genres'],'id') : [];
            if ($optimpcat == 0) {
                if (($optcattag & 1) && (!empty($genreids))) { //cat or both cat&tag
                    //we will be creating genre categories under tracks since they only apply to tracks
                    $genrecatparent = ($params->get('rootcat_album')==0) ? $params->get('defcat_track',0) : $params->get('rootcat_album',0);
                    if ($genrecatparent=0) {
                        $genrecatparent = XbcommonHelper::getcreateCat(array('title'=>'MusicGenres'));
                    }
                    $defcats['track'] = XbcommonHelper::getCreateCat(array(
                        'title'=>$id3data['genres'][0]['title'],
                        'parent_id'=>$genrecatparent),true)>id;
                }            
            }
            
            $optalbsong = $params->get('genrealbsong',0);

            $trackdata['catid'] = $defcats['track'];
            if ($optcattag > 1) $trackdata['tags'] = $genreids;           
            //well be setting the album & song genre tags when we get there
            if (isset($filedata['imageinfo']['data'])){
                $imgdata = $filedata['imageinfo'];
                unset($filedata['imageinfo']['data']);
            }
            if (isset($filedata['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];
            
            $trackdata['id3tags'] = json_encode($filedata['id3tags']);
            $trackdata['fileinfo'] = json_encode($filedata['fileinfo']);
            $trackdata['audioinfo'] = json_encode($filedata['audioinfo']);
            
// get the song data and save in trackdata
            $trackdata['songdata'] = [];
            if (isset($id3data['songdata'])) {
                foreach ($id3data['songdata'] as &$song) {
                    $song['catid'] = $defcats['song'];
                    if ($optalbsong & 1) $song['tags'] = $genreids;       
                }
            }
            $trackdata['songdata'] = $id3data['songdata'];
            //songlinks will be generated on save and addedto artist album and track

// get artist details and save in trackdata            
            if (isset($id3data['artistdata'])) {
                foreach ($id3data['artistdata'] as &$artist) {
                    $artist['catid'] = $defcats['artist'];
                }
                $trackdata['artists'] = $id3data['artistdata'];//
            } 
            //artistlinks will be created on save and added to album and song
            
//6. Create image file if available
            if (($imgdata['data'])){
                $imgfilename = '/images/xbmusic/artwork/';
                if (isset($id3data['albumdata']['alias'])) {
                    $imgfilename .= 'albums/'.strtolower($id3data['albumdata']['alias'][0]).'/'.$id3data['albumdata']['alias'];
                } else {
                    $imgfilename .= 'singles/'.$trackdata['alias'];
                }
                if (isset($trackdata['sortartist'])) $imgfilename .= '_'.XbcommonHelper::makeAlias($trackdata['sortartist']);
                $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);
                unset($imgdata['data']);
                if ($imgurl !== false) {
                    $imgdata['imagetitle'] = $imgdata['picturetype'];
                    $imgdata['imagedesc'] = $imgdata['description'];
                    $trackdata['imgurl'] = $imgurl;
                    $trackdata['imageinfo'] = $imgdata; // json_encode on save
                } else {
                    unset ($imgdata);
                }
            } //end ifset image data
            // img url and info will need adding to album
            
// get album details
            if (isset($id3data['albumdata'])) {
                $trackdata['albumdata'] = $id3data['albumdata'];
                $trackdata['albumdata']['catid'] = $defcats['album'];
                if ($optalbsong > 1) $id3data['albumdata']['tags'] = $genreids;
                if (isset($imgdata)) $id3data['albumdata']['imageinfo'] = $imgdata;
                if ($imgurl != false) $id3data['albumdata']['imgurl'] = $imgurl;
                $trackdata['albumdata']['id'] = XbmusicHelper::getItemIdFromAlias('#__xbmusic_albums', $trackdata['albumdata']['alias']);
            }

            $trackdata['logmsg'] = $ilogmsg;
            $app->setUserState('com_xbmusic.edit.track.id3data', $trackdata); 
            $app->setUserState('com_xbmusic.edit.track.id3loaded', 1);
            
        } else {
            //no trackdata found in id3 - we've already reported this in id3dataToItems()
        }
        
        return true;
                            
    } //end loadId3()
    
    protected function canDelete($record) {
        if (empty($record->id) || ($record->status != -2)) {
            return false;
        }
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.track.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing track.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.track.' . (int) $record->id);
        }
        
        // New track, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither track nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the tracks within the category so the new track is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        $app  = Factory::getApplication();
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.track');
                if ($item->album_id > 0) $item->album = $this->getAlbum($item->album_id);               
                $item->artists = $this->getTrackArtistList($item->id);
                $item->songs = $this->getTrackSongList($item->id);
                if ($item->id3tags) $item->id3_tags = json_decode($item->id3tags);
                if ($item->audioinfo) $item->audioinfo = json_decode($item->audioinfo);
                if ($item->fileinfo) $item->fileinfo = json_decode($item->fileinfo);
                if ($item->imageinfo) $item->imageinfo = json_decode($item->imageinfo);
                $item->image_type = ($item->imageinfo) ? $item->imageinfo->picturetype : '';
                $item->image_desc = ($item->imageinfo) ? $item->imageinfo->description : '';   
                $item->albumimage = ($item->album_id > 0) ? $item->album['imgurl'] :'';
    
                if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
                    $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
                    if (!empty($id3data)) {                   
                        $app->enqueueMessage('New ID3 Data loaded but not yet saved','Warning');
                        //$item->fileinfo = json_decode($id3data['fileinfo']);
                        //$item->fileinfo = json_decode($id3data['fileinfo']);
                        $item->imageinfo = (object)$id3data['imageinfo'];
                        //$item->audioinfo = json_decode($id3data['audioinfo']);
                        //$item->id3tags = json_decode($id3data['id3tags']);
                        //$item->imgurl = $id3data['imgurl'];
                    }
                }
            }
        } else {
            //we have no item
            
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.track', 'track', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tags = $form->getValue('tags',null,'');
        $tagsarr = (is_array($tags)) ? $tags : explode(',',$tags);
        $parentids = $params->get('tracktagparents',[]);
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
        $data = $app->getUserState('com_xbmusic.edit.track.data', []);       
        if (empty($data)) {
            $data = $this->getItem();
            $data->songlist = $this->getTrackSongList($data->id);
            $data->artistlist = $this->getTrackArtistList($data->id);
//            $data->playlistlist = $this->getTrackPlayLists();
        }
        if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
            $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
            if (!empty($id3data)) {
                // overwrite these fields even if set
                $fields = array('title','alias','filepathname','filename','foldername',
                  'selectedfiles','catid','sortartist','rec_date','rel_date','duration',
                    'imgurl','discno','trackno'
                );
                $replaced = $this->setFields($fields,$data,$id3data);
                if (($data->id > 0) && (!empty($replaced))) {
                    $msg = 'Some data has been overwritten by new values from ID3 import. ';
                    $msg .= 'If previous values existed they are shown below the fields in red';
                    $msg .= '<br />Check restore any old values by copy/pasting before saving if necessary';
                    $msg .= '<br />Any new artists, songs or albums have not yet been created, but will be added on Save';
                    $app->enqueueMessage($msg,'Warning');
                    $app->setUserState('com_xbmusic.edit.track.replaced', $replaced);
                }

                //any new tags get added
                if (!empty($data->tags)) {
                    $data->tags .= ','.implode(',',$id3data['tags']);
                } elseif (key_exists('tags',$id3data)) {
                    $data->tags = $id3data['tags'];
                }
             }               
        }            
        return $data;
    }
    
    private function setFields(array $names, &$olddata, $newdata) {
        $replaced = [];
        foreach ($names as $name) {
            if (!empty($newdata[$name])) {
                if (empty($olddata->$name)) { 
                    $replaced[$name] = 'blank';
                    $olddata->$name = $newdata[$name];
                } elseif ($olddata->$name != $newdata[$name]) {
                    $replaced[$name] = $olddata->$name;
                    $olddata->$name = $newdata[$name];
                }
            }
        }
        return $replaced;
    }
       
    public function save($trackdata) {     
        
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        $urlhandling = $params->get('urlhandling',[]);
        $dotrackurl = (key_exists(0, $urlhandling)) ? true : false;
        $dosongurl = (key_exists(1, $urlhandling)) ? true : false;
        $doalbumurl = (key_exists(2, $urlhandling)) ? true : false;
        $doartisturl = (key_exists(3, $urlhandling)) ? true : false;
        $infomsg = '';
        $ilogmsg = '';
        if ($app->getUserState('com_xbmusic.edit.track.id3loaded', 0) == 1) {
            $id3data = $app->getUserState('com_xbmusic.edit.track.id3data', []);
            if (!empty($id3data)) {
                $loglevel = $params->get('loglevel',3);
                if ($loglevel>0) {
                    $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
                    $ilogmsg = $id3data['logmsg'];
                }
                if (!empty($id3data['songdata'])) {
                    foreach ($id3data['songdata'] as $song) {
                        $id = XbmusicHelper::createMusicItem($song, 'song');
                        if ($id > 0) {
                            $cnts['newsng'] ++;
                            $msg = Text::_('XBMUSIC_NEW_SONG_SAVED').' id:'.$id.Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                            if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                            $infomsg .= trim($msg).'<br />';
                        } elseif ($id == false) {
                            $msg = Text::_('XBMUSIC_PROBLEM_SAVING_SONG').Xbtext::_($song['title'],XBSP1 + XBDQ + XBNL);
                            if ($loglevel>0) $ilogmsg .= XBERR.$msg;
                            $app->enqueueMessage(trim($msg).'<br />','Error');
                        }
                        if ($id < 0) $id = $id * -1;
                        if ($id > 0) {
                            if ($dosongurl) {
                                foreach ($id3data['urls'] as $url) {
                                    XbmusicHelper::addExtLink($url, 'song', $id);
                                }                               
                            }
                            $trackdata['songlist'][] = array('song_id'=>$id,'role'=>'','note'=>'');;
                        }
                    }
                }
                if (!empty($id3data['artists'])) {
                    foreach ($id3data['artists'] as $artist) {
                        $id = XbmusicHelper::createMusicItem($artist, 'artist');
                        if ($id > 0) {
                            $cnts['newart'] ++;
                            $msg = Text::_('XBMUSIC_NEW_ARTIST_SAVED').' id:'.$id.Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                            if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                            $infomsg .= trim($msg).'<br />';
                        } elseif ($id == false) {
                            $msg = Text::_('XBMUSIC_PROBLEM_SAVING_ARTIST').Xbtext::_($artist['name'],XBSP1 + XBDQ + XBNL);
                            if ($loglevel>0) $ilogmsg .= XBERR.$msg;
                            $app->enqueueMessage(trim($msg).'<br />','Error');
                        }
                        if ($id < 0) $id = $id * -1;
                        if ($id > 0) {
                            $trackdata['artistlist'][] = array('artist_id'=>$id,'role'=>'','note'=>'');
//                             if (isset($artist['url'])) {
//                                 XbmusicHelper::addExtLink($artist['url'], 'artist', $id);
//                             }
                            if ($doartisturl) {
                                foreach ($id3data['urls'] as $url) {
                                    XbmusicHelper::addExtLink($url, 'artist', $id);
                                }
                            }
                        }
                    }
                }
                if (!empty($id3data['albumdata'])) {
                    $id = XbmusicHelper::createMusicItem($id3data['albumdata'], 'album');
                    if ($id > 0) {
                        $cnts['newalb'] ++;
                        $msg = Text::_('XBMUSIC_NEW_ALBUM_SAVED').' id:'.$id.Xbtext::_($id3data['albumdata']['title'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel==4) $ilogmsg .= XBINFO.$msg;
                        $infomsg .= trim($msg).'<br />';
                    } elseif ($id == false) {
                        $msg = Text::_('XBMUSIC_PROBLEM_SAVING_ALBUM').Xbtext::_($id3data['albumdata']['title'],XBSP1 + XBDQ + XBNL);
                        if ($loglevel>0) $ilogmsg .= XBERR.$msg;
                        $app->enqueueMessage(trim($msg).'<br />','Error');
                    }
                    if ($id < 0) $id = $id * -1;
                    if ($id > 0) {
                        if ($doalbumurl) {
                            foreach ($id3data['urls'] as $url) {
                                XbmusicHelper::addExtLink($url, 'album', $id);
                            }
                        }
//                         foreach ($id3data['albumdata']['urls'] as $url) {
//                             XbmusicHelper::addExtLink($url, 'album', $id);
//                         }
                        $trackdata['album_id'] = $id;
                    }
                } else {
                    //its a single
                    $msg = Xbtext::_('XBMUSIC_NO_ALBUM_FOUND',XBNL + XBTRL);
                    if ($loglevel>2) $ilogmsg .= XBWARN.$msg;
                    $app->enqueueMessage(trim($msg).'<br />','Warning');
                }
                $trackdata['fileinfo'] = $id3data['fileinfo'];
                $trackdata['imageinfo'] = json_encode($id3data['imageinfo']);
                $trackdata['audioinfo'] = $id3data['audioinfo'];
                $trackdata['id3tags'] = $id3data['id3tags'];
            }
        } //endif id3data
        $filter = InputFilter::getInstance();
        if (isset($trackdata['created_by_alias'])) {
            $trackdata['created_by_alias'] = $filter->clean($trackdata['created_by_alias'], 'TRIM');
        }
        $userid = $this->getCurrentUser()->id;
        if (!isset($trackdata['created_by'])) $trackdata['created_by'] = $userid;
        if ((!isset($trackdata['modified_by'])) && ($trackdata['id'] > 0)) $trackdata['modified_by'] = $userid;
        
        //merge any tag groups back into tags
        $parentids = $params->get('tracktagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
           
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                //$newpid = $pid;
                if (!empty($trackdata[$groupname])) {
                    //need to test for #new# in 'id' column and if found create a new tag and add its id to group
                    foreach ($trackdata[$groupname] as &$value) {
                        if (strpos($value,'#new#') !== false) {
                            $newtag = XbcommonHelper::getCreateTagPath($value, $pid);
                            $value = $newtag['id'];
                        }
                    }
                    $trackdata['tags'] = ($trackdata['tags']) ? 
                        array_unique(array_merge($trackdata['tags'],$trackdata[$groupname])) : $trackdata[$groupname];
                }
            } //endforeach parenttag
        } // endif !empty parentids
        
        //NB save2copy will include any reloaded id3 data in the copy not the original
        $input  = $app->getInput();
        if ($input->get('task') == 'save2copy') {
            $origTable = clone $this->getTable();
            $origTable->load($input->getInt('id'));
            
            if ($trackdata['title'] == $origTable->title) {
                list($title, $alias) = $this->generateNewTitle($trackdata['catid'], $trackdata['alias'], $trackdata['title']);
                $trackdata['title'] = $title;
                $trackdata['alias'] = $alias;
            } else {
                if ($trackdata['alias'] == $origTable->alias) {
                    $trackdata['alias'] = '';
                }
            }
            //need to copy links
            // standard Joomla practice is to set the new copy record as unpublished
            $trackdata['status'] = 0;
        }
               
        // if new track check if track alias already exists and if it does append [basename(imagename)]
        if (($trackdata['id'] == 0 ) && XbcommonHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
            $append = '';
            if (key_exists('sortartist',$trackdata)) $append = $trackdata['sortartist'];
            if ($trackdata['album_id'] > 0) $append .= ' '.$id3data['albumdata']['alias'];
            if ($append != '') $append = ' ['.$append.']';
            $trackdata['alias'] = XbcommonHelper::makeUniqueAlias($trackdata['alias'].$append, '#__xbmusic_tracks');
            //$msg .= ' - '.Text::sprintf('Trying save with alias %s',$trackdata['alias']);
        }
        if (parent::save($trackdata)) {
            $app->setUserState('com_xbmusic.edit.track.id3data', null);
            $app->setUserState('com_xbmusic.edit.track.id3loaded', 0);
            $tid = $this->getState('track.id');
            // if a new track we need to create and link any songs and artists and add genre to song and artist per options,
            if ($dotrackurl) {
                foreach ($id3data['urls'] as $url) {
                    XbmusicHelper::addExtLink($url, 'track', $tid);
                }
            }
//             foreach ($id3data['trackdata']['urls'] as $url) {
//                 XbmusicHelper::addExtLink($url, 'track', $tid);
//             }
            $trackdata['songlist'] = XbcommonHelper::uniqueNestedArray($trackdata['songlist'], 'song_id');
            $this->storeTrackSongs($tid, $trackdata['songlist']);
            
            $trackdata['artistlist'] = XbcommonHelper::uniqueNestedArray($trackdata['artistlist'], 'artist_id');
            $this->storeTrackArtists($tid, $trackdata['artistlist']);
            //$this->storeTrackPlaylists($tid, $trackdata['playlists']);
            if (!empty($ilogmsg)) XbmusicHelper::writelog($ilogmsg);
            if (!empty($infomsg)) $app->enqueueMessage($infomsg);
            return true;
        }
        return false;
    }
    
    private function getAlbum($albumid) {
        $album = [];
        if ($albumid >0) {
            $db = $this->getDatabase();
            $query = $db->getQuery(true);
            $query->select('id, title, sortartist, rel_date, imgurl');
            $query->from('#__xbmusic_albums');
            $query->where($db->qn('id').' = '.$db->q($albumid));
            $db->setQuery($query);
            $album = $db->loadAssoc();
        }
        return $album;
    }

    private function getTrackArtistList($track_id) {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.artist_id AS artist_id, b.name AS name, a.role AS role, a.note AS note, a.listorder AS listorder');
        $query->from('#__xbmusic_trackartist AS a');
        $query->innerjoin('#__xbmusic_artists AS b ON a.artist_id = b.id');
        $query->where('a.track_id = '.$db->q($track_id));
        $query->order('a.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    private function storeTrackArtists($track_id, $artistlist) {
        //delete existing role list
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_trackartist'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        $query->clear();
        $query->insert($db->quoteName('#__xbmusic_trackartist'));
        $query->columns('artist_id,track_id,role, note,listorder');
        foreach ($artistlist as $artist) {
            if ($artist['artist_id'] > 0) {
                $listorder ++;
                $query->values('"'.$artist['artist_id'].'","'.$track_id.'","'.$artist['role'].'","'.$artist['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
                $query->clear('values');                
            }
        }
    }
    
    private function getTrackSongList($track_id) {
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.title AS title, b.song_id as song_id, b.role AS role, b.note AS note, b.listorder AS listorder');
        $query->from('#__xbmusic_tracksong AS b');
        $query->innerjoin('#__xbmusic_songs AS a ON b.song_id = a.id');
        $query->where('b.track_id = '.$db->q($track_id));
        $query->order('b.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
      
    private function storeTrackSongs($track_id, $songlist) {
        //delete existing role list
        $db = $this->getDatabase();
        //$db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_tracksong'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        $query->clear();
        $query->insert($db->quoteName('#__xbmusic_tracksong'));
        $query->columns('song_id,track_id,role,note,listorder');
        foreach ($songlist as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query->values('"'.$song['song_id'].'","'.$track_id.'","'.$song['role'].'","'.$song['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
                $query->clear('values');
            }
        }
    }
    
}