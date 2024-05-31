<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/TrackModel.php
 * @version 0.0.6.6 28th May 2024
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
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\String\PunycodeHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\TableInterface;
use Joomla\CMS\UCM\UCMType;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\ParameterType;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Symfony\Component\Validator\Constraints\IsNull;
use Crosborne\Component\Xbmusic\Administrator\Extension\XbmusicComponent;
use Joomla\CMS\Uri\Uri;

class TrackModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.track';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from tracks :';
        //	    $basePath = JPATH_ADMINISTRATOR.'/components/com_content';
        //	    require_once $basePath.'/models/track.php';
        //	    $trackmodel = new ContentModelArticle(array('table_path' => $basePath . '/tables'));
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
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.track');                
            }
            $id3data = json_decode($item->id3_data);
            $item->id3_tags = $id3data->id3tags;
            $item->audioinfo = $id3data->audioinfo;
            $item->fileinfo = $id3data->fileinfo;
            $item->imageinfo = $id3data->imageinfo;
            $item->image_type = $id3data->imageinfo->picturetype;
            $item->image_desc = $id3data->imageinfo->description;
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.track', 'track', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        $params = ComponentHelper::getParams('com_xbmusic');
        if ($params->get('use_xbmusic', 1)) {
            $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$params->get('xbmusic_subfolder','');
        } else {
            $basemusicfolder = (trim($params->get('music_path','')) != '') ? trim($params->get('music_path')) : JPATH_ROOT.'/xbmusic/';
        }
        $form->setFieldAttribute('pathname','directory',$basemusicfolder);
        $mf = $app->getSession()->get('musicfolder','');
        if ($mf !='') $form->setValue('pathname', null, $mf);
        $form->setFieldAttribute('filename','directory',$form->getValue('pathname'));
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.track.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->songlist = $this->getTrackSongList();
            $data->albumlist = $this->getTrackAlbumList();
            $data->artistlist = $this->getTrackArtistList();
//            $data->playlistlist = $this->getTrackPlayList();
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('track.id') == 0) && ($retview != '')) {
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
        $filter = InputFilter::getInstance();
        $params = ComponentHelper::getParams('com_xbmusic');
        $infomsg = '';
        $warnmsg = '';
        $isnewtrack = ($data['id'] == 0);
        //get the id3 data and use to set any track elements not set if id3 value available
        $filepathname = rtrim($data['pathname'],'/').'/'.$data['filename'];
        if ($isnewtrack) {  
            
            if (file_exists($filepathname)) {
                $filedata = XbmusicHelper::getFileId3($filepathname); 
                // get the artist name withoout "The " to use for sorting and in artwork filename
                if ($data['sortartist'] == '') {
                    if (isset($filedata['id3tags']['artist'])) {
                        $data['sortartist'] = $this->stripThe($filedata['id3tags']['artist']);
                    }
                }
                //get album title for use in creating and linking album
                $albumtitle = (isset($filedata['id3tags']['album'])) ? $filedata['id3tags']['album'] : '';
                //get album artist for use in image filename and creating album
                if (isset($filedata['id3tags']['band'])) {
                    $albumartist = $filedata['id3tags']['band'];
                } else {
                    $albumartist = (isset($filedata['id3tags']['artist'])) ? $filedata['id3tags']['artist'] : '';
                }
                // get artwork if not set and if available in ID3
                if (empty($data['artwork'])) {
                    if (isset($filedata['imageinfo']['data'])){
                        // filename for image will be "album-title-albumartist-name.ext"
                        // if track has no album listed but has image then "artist-name.ext" for all tracks by the artist
                        // path will finish with initial letter of title or "unknown"
                        $folder = ($albumtitle == '') ? 'NoAlbum' : 'albums/'.strtolower($albumtitle[0]);
                        $artpath = '/images/xbmusic/artwork/'.$folder.'/';                
                        if (!file_exists($artpath)) {
                            mkdir(JPATH_ROOT.$artpath,0775,true);                   
                        }
                        $artfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $albumtitle.' '.$data['sortartist'])).'.'.XbmusicHelper::imageMimeToExt($filedata['imageinfo']['image_mime']);
                        $artpathfile = JPATH_ROOT.$artpath.$artfilename;
                        $arturl = Uri::root().$artpath.$artfilename;
                        if (file_exists($artpathfile)) {
                            $data['artwork'] = $arturl;
                        } else {
                            if (file_put_contents($artpathfile, $filedata['imageinfo']['data'])) {
                                $data['artwork'] = $arturl;
                            }                       
                        }
                        unset($filedata['imageinfo']['data']);                
                    }
                    // set data['artwork'] to imagefile               
                } //endif empty artwork, if no artwork no action needed
                
 //               if ($data['id3_data'] == '') $data['id3_data'] = json_encode($filedata);
            } else {
                $app->enqueueMessage('Impossible Error : file '.$filepathname.' does not exist', 'Error');
                return false;
            } //endif file exists
        } //endif newtrack
//        $filedata = json_decode($data['id3_data'],true);
        if ($data['title'] == '') {
            if ($filedata['id3tags']['title'] != '') {
                $data['title'] = $filedata['id3tags']['title'];
            }
//        } elseif ($data['title'] != $filedata['id3tags']['title']) {
//            $warnmsg .= 'Track title does not match ID3 title<br />';
        }
        if ($data['rec_date'] == '') {
            if (isset($filedata['id3tags']['recording_time'])) {
                $data['rec_date'] = ($filedata['id3tags']['recording_time']); 
                //TODO check and format this
                $infomsg .= 'Check format of recording date<br />';
            }
        }
        if ($data['rel_date'] == '') {
            if (isset($filedata['id3tags']['year'])) {
                $data['rel_date'] = $filedata['id3tags']['year']; 
                //TODO check and format this
                $infomsg .= 'Check format of release date<br />';
            }
        }
        // track alias is the pathname without the basemusicfolder part
        if ($params->get('use_xbmusic', 1)) {
            $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //.$params->get('xbmusic_subfolder','');
        } else {
            $basemusicfolder = (trim($params->get('music_path','')) != '') ? trim($params->get('music_path')) : JPATH_ROOT.'/xbmusic/';
        }
        if (($isnewtrack) || ($data['alias']='')) {
            $data['alias'] = OutputFilter::stringURLSafe(str_replace($basemusicfolder, '' , $data['pathname']).'-'. $data['filename']);
            if ( XbmusicHelper::checkValueExists($data['alias'], '#__xbmusic_tracks', 'alias')) {
                $warnmsg .= 'Duplicate alias - this track appears to be already in the database';
                $app->enqueueMessage($warnmsg,'Error');
                if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
                return false;
            }
        }
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
               
//         if ((int) $data['catid'] > 0)
//         {
//             $data['catid'] = CategoriesHelper::validateCategoryId($data['catid'], 'com_xbmusic');
//         }
        
        //merge groups back into tags
        /*
         if ($data['taggroup1']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup1'])) : $data['taggroup1'];
        }
        if ($data['taggroup2']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup2'])) : $data['taggroup2'];
        }
        if ($data['taggroup3']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup3'])) : $data['taggroup3'];
        }
        if ($data['taggroup4']) {
            $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data['taggroup4'])) : $data['taggroup4'];
        }
         */            
        if (parent::save($data)) {
            $tid = $this->getState('track.id');
            if ($isnewtrack) {
                // do the same for artist - 
                //check id3 song title against song table and add if missing (alias created from title)
                if ($filedata['id3tags']['title'] != '') {
                    $songtitle = $filedata['id3tags']['title'];
                    $songcomp = (isset($filedata['id3tags']['composer'])) ? $filedata['id3tags']['composer'] : '';
                    $songid = $this->getCreateSong($songtitle,$tid, $songcomp);
                    $db = $this->getDbo();
                    // what if two songs with same title
                    if (empty(XbmusicHelper::getItems('#__xbmusic_songtrack', 'song_id', $songid, 'track_id = '.$db->q($tid)))) {
                        $n = count($data['songlist']);
                        $data['songlist']['songlist'.$n] = array('song_id' => $songid, 'note' =>'auto added with new track '.$tid);
                        $infomsg .= Text::sprintf('Song %s added to track',$songtitle );
                    } else {
                        $infomsg .= Text::sprintf('Song %s already linked to track',$songtitle );
                    }
                } else {
                    $infomsg .= Text::_('No ID3 song title available for track');
                }
                // do the same for album
                if ($albumtitle != '') {
                    $albumid = $this->getCreateAlbum($albumtitle,$tid, $albumartist, $data['rel_date'], $data['artwork'] );
                    $db = $this->getDbo();
                    // what if two songs with same title
                    if (empty(XbmusicHelper::getItems('#__xbmusic_albumtrack', 'album_id', $albumid, 'track_id = '.$db->q($tid)))) {
                        $n = count($data['albumlist']);
                        $trackno = (isset($filedata['id3tags']['track_number'])) ? $filedata['id3tags']['track_number'] : 0;
                        $discno = (isset($filedata['id3tags']['part_of_a_set'])) ? (int) $filedata['id3tags']['part_of_a_set'] : '0';
                        $data['albumlist']['albumlist'.$n] = array('album_id' => $albumid, 'note' =>'auto added with new track '.$tid, 'trackno' => $trackno);
                        $infomsg .= Text::sprintf('Album %s added to track',$albumtitle);
                    } else {
                        $infomsg .= Text::sprintf('Album %s already linked to track',$albumtitle);
                    }
                } else {
                    $infomsg .= Text::_('No ID3 album info available for track');
                }
                //check album against album table and add if missing (alias created from artist-album)
                //check artist against artist table and add if missing (alias from name)
                //check album-artist against artist table and add if missing
            } else {
                // $warnmsg .= 'if ID3 data in file has changed the changes will not be reflected here or in linked items';
            }
            $this->storeTrackSongs($tid, $data['songlist']);
            $this->storeTrackAlbums($tid, $data['albumlist']);
            
            
            
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');
            return true;
        }
        $app->enqueueMessage('Error saving track data','Error');
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
    public function importID3data(&$data) {
        
    }
    
    protected function preprocessForm(Form $form, $data, $group = 'content') {
//         if ($this->canCreateCategory()) {
//             $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
//             // Add a prefix for categories created on the fly.
//             $form->setFieldAttribute('catid', 'customPrefix', '#new#');
//         }
        
        parent::preprocessForm($form, $data, $group);
    }
    
    public function getTrackAlbumList() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as album_id, ba.discno AS discno, ba.trackno AS trackno, ba.listorder AS oldorder');
        $query->from('#__xbmusic_albumtrack AS ba');
        $query->innerjoin('#__xbmusic_albums AS a ON ba.album_id = a.id');
        $query->where('ba.track_id = '.(int) $this->getItem()->id);
        $query->order('a.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    public function getTrackArtistList() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as artist_id, ba.role AS role, ba.note AS note');
        $query->from('#__xbmusic_artisttrack AS ba');
        $query->innerjoin('#__xbmusic_artists AS a ON ba.artist_id = a.id');
        $query->where('ba.artist_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    public function getTrackSongList() {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('a.id as song_id, ba.note AS note');
        $query->from('#__xbmusic_songtrack AS ba');
        $query->innerjoin('#__xbmusic_songs AS a ON ba.song_id = a.id');
        $query->where('ba.track_id = '.(int) $this->getItem()->id);
        $query->order('ba.listorder ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    
    function storeTrackSongs($track_id, $songlist) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_songtrack'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        foreach ($songlist as $song) {
            if ($song['song_id'] > 0) {
                $listorder ++;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_songtrack'));
                $query->columns('song_id,track_id,note,listorder');
                $query->values('"'.$song['song_id'].'","'.$track_id.'","'.$trk['note'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
            } else {
                // Factory::getApplication()->enqueueMessage('<pre>'.print_r($pers,true).'</pre>');
                //create person
                //add filmperson with new id
            }
        }
    }
    
    function storeTrackAlbums($track_id, $albumlist) {
        //delete existing role list
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__xbmusic_albumtrack'));
        $query->where('track_id = '.$db->q($track_id));
        $db->setQuery($query);
        $db->execute();
        //restore the new list
        $listorder=0;
        foreach ($albumlist as $album) {
            if ($album['album_id'] > 0) {
                $listorder = ($album['oldorder']>0) ? $album['oldorder'] : 0;
                $query = $db->getQuery(true);
                $query->insert($db->quoteName('#__xbmusic_albumtrack'));
                $query->columns('album_id,track_id,discno, trackno,listorder');
                $query->values('"'.$album['album_id'].'","'.$track_id.'","'.$album['discno'].'","'.$album['trackno'].'","'.$listorder.'"');
                //try
                $db->setQuery($query);
                $db->execute();
            } else {
                // Factory::getApplication()->enqueueMessage('<pre>'.print_r($pers,true).'</pre>');
                //create person
                //add filmperson with new id
            }
        }
    }
    
    
    
    public function getCreateSong($title, $tid, $composer = '') {
        //check if two songs have same title but different alias?
        $newalias = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $title));
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_songs')->where('alias = '.$db->q($newalias));
        $db->setQuery($query);
        $id = $db->loadResult();
        if (empty($id)) {
            //create new song
            $params = ComponentHelper::getParams('com_xbmusic');
            //get song default category
            $catid = $params->get('defcat_song',XbmusicHelper::getCatByAlias('uncategorised'));
            $createmod = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_songs');
            $query->columns('title, alias, composer, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$title.'","'.$newalias.'","'.$composer.'","'.$catid.'","1","1","'.$createmod.'","'.$createmod.'","'.$createbyalias.'"');
//            $query->values($db->quote($title, $newalias, $catid, $createmod, $createmod, $createbyalias));
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
            }
            $id = $db->insertid();
 //           Factory::getApplication()->enqueueMessage(count(idarr).' '.Text::_('Songs with this title exist - please link manually','Warning'));
 //           return false;
        }
        return $id;
    }
    
    public function getCreateAlbum($title, $tid, $artist, $reldate, $artwork) {
        //what if artist releases two albums with same title?
        $sortartist = $this->stripThe($artist);
        $newalias = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $title).' '.$sortartist);
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('id')->from('#__xbmusic_albums')->where('alias = '.$db->q($newalias));
        $db->setQuery($query);
        $id = $db->loadResult();
        if (empty($id)) {
            //create new song
            $params = ComponentHelper::getParams('com_xbmusic');
            //get song default category
            $catid = $params->get('defcat_album');
            if ($catid == '') $catid = XbmusicHelper::getCatByAlias('uncategorised');
            $createmod = Factory::getDate()->toSql();
            $createbyalias = 'created from ID3';
            $query->clear();
            $query->insert('#__xbmusic_albums');
            $query->columns('title, alias, albumartist, sortartist, rel_date, artwork, catid, status, access, created, modified, created_by_alias');
            $query->values('"'.$title.'","'.$newalias.'","'.$artist.'","'.$sortartist.'","'.$reldate.'","'.$artwork.'","'.$catid.'","1","1","'.$createmod.'","'.$createmod.'","'.$createbyalias.'"');
            //            $query->values($db->quote($title, $newalias, $catid, $createmod, $createmod, $createbyalias));
            $db->setQuery($query);
            try {
                $db->execute();
            } catch (\Exception $e) {
                $dberr = $e->getMessage();
                Factory::getApplication()->enqueueMessage($dberr.'<br />Query: '.$query->dump(), 'error');
            }
            $id = $db->insertid();
            //           Factory::getApplication()->enqueueMessage(count(idarr).' '.Text::_('Songs with this title exist - please link manually','Warning'));
            //           return false;
        }
        return $id;
    }
    
    public function stripThe($name) {
        if (substr(strtolower($name), 0, 4) == 'the ') {
            $name = substr($name,4);
        }
        return $name;
    }
    
    private function canCreateCategory() {
        return $this->getCurrentUser()->authorise('core.create', 'com_content');
    }
    
    /*** not needed?
     public function validate($form, $data, $group = null) {
     if (!$this->getCurrentUser()->authorise('core.admin', 'com_xbmusic')) {
     if (isset($data['rules'])) {
     unset($data['rules']);
     }
     }
     
     return parent::validate($form, $data, $group);
     }
     ****/
    
    /*
     protected function batchMove($value, $pks, $contexts) {
     
     if (empty($this->batchSet))
     {
     // Set some needed variables.
     $this->user = $this->getCurrentUser();
     $this->table = $this->getTable();
     $this->tableClassName = \get_class($this->table);
     $this->contentType = new UcmType();
     $this->type = $this->contentType->getTypeByTable($this->tableClassName);
     }
     
     $categoryId = (int) $value;
     
     if (!$this->checkCategoryId($categoryId)) {
     return false;
     }
     
     PluginHelper::importPlugin('system');
     
     // Parent exists so we proceed
     foreach ($pks as $pk) {
     if (!$this->user->authorise('core.edit', $contexts[$pk])) {
     $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_EDIT'));
     
     return false;
     }
     
     // Check that the row actually exists
     if (!$this->table->load($pk)) {
     if ($error = $this->table->getError()) {
     // Fatal error
     $this->setError($error);
     
     return false;
     }
     // Not fatal error
     $this->setError(Text::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));
     continue;
     }
     
     $fields = FieldsHelper::getFields('com_xbmusic.track', $this->table, true);
     $fieldsData = array();
     
     if (!empty($fields)) {
     $fieldsData['com_fields'] = array();
     
     foreach ($fields as $field) {
     $fieldsData['com_fields'][$field->name] = $field->rawvalue;
     }
     }
     
     // Set the new category ID
     $this->table->catid = $categoryId;
     
     //             // We don't want to modify tags - so remove the associated tags helper
     //             if ($this->table instanceof TaggableTableInterface) {
     //                 $this->table->clearTagsHelper();
     //             }
     
     // Check the row.
     if (!$this->table->check()) {
     $this->setError($this->table->getError());
     
     return false;
     }
     
     if (!empty($this->type))
     {
     $this->createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);
     }
     
     // Store the row.
     if (!$this->table->store()) {
     $this->setError($this->table->getError());
     
     return false;
     }
     
     // Run event for moved track
     Factory::getApplication()->triggerEvent('onContentAfterSave', ['com_xbmusic.track', &$this->table, false, $fieldsData]);
     }
     
     // Clean the cache
     $this->cleanCache();
     
     return true;
     }
     
     */
    
}

/////////////from getForm()
//         $params = ComponentHelper::getParams('com_xbmusic');
//         if ($this->params->get('use_xbmusic', 1)) {
//             $this->basemusicfolder = JPATH_ROOT.'xbmusic/'.$this->params->get('xbmusic_subfolder','');
//         } else {
//             $this->basemusicfolder = ($this->params->get('music_path','') != '') ? $this->params->get('music_path') : JPATH_ROOT.'/xbmusic/';
//         }
//         $form->setFieldAttribute('pathname','directory',$this->basemusicfolder,'general');


/*
 // Object uses for checking edit state permission of track
 $record = new \stdClass();
 
 $trackIdFromInput = $app->getInput()->getInt('id', 0);
 
 $id = (int) $this->getState('track.id', $trackIdFromInput);
 
 $record->id = $id;
 
 // For new tracks we load the potential state + associations
 if ($id == 0 && $formField = $form->getField('catid')) {
 $assignedCatids = $data['catid'] ?? $form->getValue('catid');
 
 $assignedCatids = \is_array($assignedCatids)
 ? (int) reset($assignedCatids)
 : (int) $assignedCatids;
 
 // Try to get the category from the category field
 if (empty($assignedCatids)) {
 $assignedCatids = $formField->getAttribute('default', null);
 
 if (!$assignedCatids) {
 // Choose the first category available
 $catOptions = $formField->options;
 
 if ($catOptions && !empty($catOptions[0]->value)) {
 $assignedCatids = (int) $catOptions[0]->value;
 }
 }
 }
 
 // Activate the reload of the form when category is changed
 $form->setFieldAttribute('catid', 'refresh-enabled', true);
 $form->setFieldAttribute('catid', 'refresh-cat-id', $assignedCatids);
 $form->setFieldAttribute('catid', 'refresh-section', 'track');
 
 // Store ID of the category uses for edit state permission check
 $record->catid = $assignedCatids;
 } else {
 // Get the category which the track is being added to
 if (!empty($data['catid'])) {
 $catId = (int) $data['catid'];
 } else {
 $catIds  = $form->getValue('catid');
 
 $catId = \is_array($catIds)
 ? (int) reset($catIds)
 : (int) $catIds;
 
 if (!$catId) {
 $catId = (int) $form->getFieldAttribute('catid', 'default', 0);
 }
 }
 
 $record->catid = $catId;
 }
 
 // Modify the form based on Edit State access controls.
 if (!$this->canEditState($record)) {
 // Disable fields for display.
 $form->setFieldAttribute('featured', 'disabled', 'true');
 //            $form->setFieldAttribute('featured_up', 'disabled', 'true');
 //            $form->setFieldAttribute('featured_down', 'disabled', 'true');
 $form->setFieldAttribute('ordering', 'disabled', 'true');
 $form->setFieldAttribute('publish_up', 'disabled', 'true');
 $form->setFieldAttribute('publish_down', 'disabled', 'true');
 $form->setFieldAttribute('state', 'disabled', 'true');
 
 // Disable fields while saving.
 // The controller has already verified this is an track you can edit.
 $form->setFieldAttribute('featured', 'filter', 'unset');
 //            $form->setFieldAttribute('featured_up', 'filter', 'unset');
 //            $form->setFieldAttribute('featured_down', 'filter', 'unset');
 $form->setFieldAttribute('ordering', 'filter', 'unset');
 $form->setFieldAttribute('publish_up', 'filter', 'unset');
 $form->setFieldAttribute('publish_down', 'filter', 'unset');
 $form->setFieldAttribute('state', 'filter', 'unset');
 }
 
 // Don't allow to change the created_by user if not allowed to access com_users.
 if (!$this->getCurrentUser()->authorise('core.manage', 'com_users')) {
 $form->setFieldAttribute('created_by', 'filter', 'unset');
 }
 
 */

//////////////////////////////////////////// from loadFromData()
//need to extract any genre tags and poke them into $data->genre
//ie filter tag list (comma sep string) by parent
// get genre_parent and if set
//
/*             $tagsHelper = new TagsHelper;
 $params = ComponentHelper::getParams('com_xbarticleman');
 $taggroup1_parent = $params->get('taggroup1_parent','');
 if ($taggroup1_parent && !(empty($data->tags))) {
 $taggroup1_tags = $tagsHelper->getTagTreeArray($taggroup1_parent);
 $data->taggroup1 = array_intersect($taggroup1_tags, explode(',', $data->tags->tags));
 }
 $taggroup2_parent = $params->get('taggroup2_parent','');
 if ($taggroup2_parent && !(empty($data->tags))) {
 $taggroup2_tags = $tagsHelper->getTagTreeArray($taggroup2_parent);
 $data->taggroup2 = array_intersect($taggroup2_tags, explode(',', $data->tags->tags));
 }
 $taggroup3_parent = $params->get('taggroup3_parent','');
 if ($taggroup3_parent && !(empty($data->tags))) {
 $taggroup3_tags = $tagsHelper->getTagTreeArray($taggroup3_parent);
 $data->taggroup3 = array_intersect($taggroup3_tags, explode(',', $data->tags->tags));
 }
 $taggroup4_parent = $params->get('taggroup4_parent','');
 if ($taggroup4_parent && !(empty($data->tags))) {
 $taggroup4_tags = $tagsHelper->getTagTreeArray($taggroup4_parent);
 $data->taggroup4 = array_intersect($taggroup4_tags, explode(',', $data->tags->tags));
 }
 */
//allow content plugins to preprocess
//$this->preprocessData('com_xbmusic.track', $data);


