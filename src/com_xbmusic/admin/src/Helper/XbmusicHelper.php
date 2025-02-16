<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Helper/XbmusicHelper.php
 * @version 0.0.30.7 16th February 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Helper;

defined('_JEXEC') or die;

//require_once(JPATH_COMPONENT_ADMINISTRATOR.'/src/Helper/getid3/getid3.php');
require_once (JPATH_COMPONENT_ADMINISTRATOR. '/vendor/getID3/j5getID3.php');

use Joomla\CMS\Factory;
use Joomla\CMS\Access\Access;
//use Joomla\CMS\Application\ApplicationHelper;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Component\ComponentHelper;
//use Joomla\CMS\Filter\OutputFilter;
//use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
//use Joomla\Database;
//use Joomla\Database\DatabaseInterface;
use Joomla\Database\DatabaseQuery;
use Joomla\Filter\OutputFilter;
//use DOMDocument;
use DateTime;
use Exception;
//use Symfony\Component\Validator\Constraints\Existence;
//use Crosborne\Component\Xbmusic\Administrator\Helper\getid3\Getid3;
//use Joomla\CMS\Filter\InputFilter;

class XbmusicHelper extends ComponentHelper
{
	public static $extension = 'com_xbmusic';
	
	public static $musicBase = JPATH_ROOT.'/xbmusic/';
	
//	public static $linktypes = array('trackartist', 'tracksong');
//, 'artistalbum', 'songalbum', 'artistsong'

	public static function getActions($categoryid = 0) {
	    $user 	=Factory::getApplication()->getIdentity();
	    $result = new \stdClass();
	    if (empty($categoryid)) {
	        $assetName = 'com_xbmusic';
	        $level = 'component';
	    } else {
	        $assetName = 'com_xbmusic.category.'.(int) $categoryid;
	        $level = 'category';
	    }
	    $actions = Access::getActions('com_xbmusic', $level);
	    foreach ($actions as $action) {
	        $result->set($action->name, $user->authorise($action->name, $assetName));
	    }
	    return $result;
	}

	/**
	 * @name getFileId3()
	 * @desc this uses j5getID3.php to interface with the getID3 component.
	 * It does some preliminary processing of the raw id3 data to produce an array of 4 arrays
	 *  - audioinfo contains the data relating to the audio file itself (sample and bitrates etc)
	 *  - fileinfo contains details about the file (mim type, size etc)
	 *  - imageinfo contains info about embedded images including the image data blobs
	 *  - id3tags contains the actual id3 descriptors
	 * @param string $filename
	 * @return array(audioinfo, fileinfo, id3tags, imageinfo)
	 */
	public static function getFileId3(string $filename) {
//	    require_once (JPATH_COMPONENT_ADMINISTRATOR. '/vendor/getID3/j5getID3.php');
	    $ThisFileInfo = getIdData($filename);
	    $result = array();	 
	    $result['audioinfo'] = array();
	    $result['imageinfo'] = array();
	    $result['id3tags'] = array();
	    $result['fileinfo'] = array();
	    $result['fileinfo']['playtime_string'] = (isset($ThisFileInfo['playtime_string'])) ? $ThisFileInfo['playtime_string'] : '';
	    $result['fileinfo']['mime_type'] = (isset($ThisFileInfo['mime_type'])) ? $ThisFileInfo['mime_type'] : '';
	    $result['fileinfo']['filesize'] = (isset($ThisFileInfo['filesize'])) ? $ThisFileInfo['filesize'] : '';
	    $result['fileinfo']['fileformat'] = (isset($ThisFileInfo['fileformat'])) ? $ThisFileInfo['fileformat'] : '';
	    $result['audioinfo']['bitrate'] = (isset($ThisFileInfo['bitrate'])) ? $ThisFileInfo['bitrate'] : '';
	    $result['audioinfo']['channels'] = (isset($ThisFileInfo['audio']['channels'])) ? $ThisFileInfo['audio']['channels'] : '';
	    $result['audioinfo']['channelmode'] = (isset($ThisFileInfo['audio']['channelmode'])) ? $ThisFileInfo['audio']['channelmode'] : '';
	    $result['audioinfo']['sample_rate'] = (isset($ThisFileInfo['audio']['sample_rate'])) ? $ThisFileInfo['audio']['sample_rate'] : '';
	    $result['audioinfo']['bitrate_mode'] = (isset($ThisFileInfo['audio']['bitrate_mode'])) ? $ThisFileInfo['audio']['bitrate_mode'] : '';
	    $result['audioinfo']['compression_ratio'] = (isset($ThisFileInfo['audio']['compression_ratio'])) ? $ThisFileInfo['audio']['compression_ratio'] : '';
	    $result['audioinfo']['encoder_options'] = (isset($ThisFileInfo['audio']['encoder_options'])) ? $ThisFileInfo['audio']['encoder_options'] : '';
	    $result['audioinfo']['encoder'] = (isset($ThisFileInfo['audio']['encoder'])) ? $ThisFileInfo['audio']['encoder'] : '';
	    $result['audioinfo']['playtime_seconds'] = (isset($ThisFileInfo['playtime_seconds'])) ? $ThisFileInfo['playtime_seconds'] : '';
	    if(isset($ThisFileInfo['comments']['picture'][0])){
//            $image='data:'.$ThisFileInfo['comments']['picture'][0]['image_mime'].';charset=utf-8;base64,'.base64_encode($OldThisFileInfo['comments']['picture'][0]['data']);
//            $image = $ThisFileInfo['comments']['picture'][0]['data'];
//    	    unset($ThisFileInfo['comments']['picture'][0]['data']);
    	    $result['imageinfo'] = $ThisFileInfo['comments']['picture'][0]; //we're only getting the first image
    	    unset($ThisFileInfo['comments']['picture']);
	    }
	    if (isset($ThisFileInfo['comments']['music_cd_identifier'])) { //this can contain binary chars and screws things up
	        unset($ThisFileInfo['comments']['music_cd_identifier']);
	    }
	    if (isset($result['imageinfo']['description'])) { //fix for an album with odd encoding on picture description
	        $desc = $result['imageinfo']['description'];
	        $res = htmlentities($desc, ENT_QUOTES | ENT_IGNORE, 'UTF-8');
	        $res =  preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $res);
	        $result['imageinfo']['description'] = $res;
	    }
        $id3tags = array();
	    foreach ($ThisFileInfo['comments'] as $key => $valuearr) {
	        // artist, album, genre and maybe others have been seen with mulitple entries
	        // concat them with ' || ' which will look ok if printed as string but allows to explode to array to handle values separately
            $id3tags[$key] = implode(' || ', $valuearr);
	    }
	    $result['id3tags'] = $id3tags;
	    return $result;
	} // end getFileID3()
	    
	/**
	 * @name id3dataToItems()
	 * @desc processes the id3tags only from getFileId3() to produce separate arrays of data available for items
	 * track, album, songs, artists, genres
	 * @param array $id3data - array returned by getFileID3() or from json id3data in track item
	 * @param string $flogdata
	 * @return array | false
	 */
	public static function id3dataToItems(array $id3data, string &$ilogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $splitsongs = $params->get('splitsongs',0);
	    $nobrackets = $params->get('nobrackets',0);
	    $loglevel = $params->get('loglevel',3);
	    $items = array();
	    $trackdata = array(); //only one track
	    $albumdata = array(); //only one album title allowed, if alternates present reported in log
	    $songdata = array(); //only track may be split into separate songs.
	    $artistdata = array(); //could be more than one artist imploded with ' || '
	    $genres = array(); //will will create any genres we find and return them as array of id=>title
//	    $images = array(); //we will create any images found and return as an array of data
	    if (isset($id3data['title'])) { 
	        $trackdata['title'] = $id3data['title'];
	    } else { //no title found
	        $msg = Xbtext::_('No track title found in ID3 data. Cannot import',XBNL);
	        $ilogmsg .= '[ERROR] '.$msg;
	        Factory::getApplication()->enqueueMessage(trim($msg),'Error');
	        return false;
	    }
	    // trackdata['alias'] is title with suffix to make it unique in case of two tracks with same title
	    // we are not making it unique at this stage - do it on save
	    $trackdata['alias'] = XbcommonHelper::makeAlias($trackdata['title']);
	    if (isset($id3data['track_number'])) $trackdata['track_number'] = $id3data['track_number'];
	    if (isset($id3data['part_of_a_set'])) $trackdata['part_of_a_set'] = $id3data['part_of_a_set'];
//	    if (isset($id3data['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$id3data['audioinfo']['playtime_seconds'];
	    // dates in id3 can be any format and may include y m and D or not
	    $datematch = '/(^(\d{4})$)|(^(\d{4})-{1}[0-1][1-9]$)|(^(\d{4})-{1}[0-1][1-9]-{1}[0-3][1-9]$)/';
	    if (isset($id3data['recording_time'])) {
	        if (preg_match($datematch,$id3data['recording_time'])==1) {
	            $trackdata['rec_date'] = ($id3data['recording_time']);
	        } else {
	            $ilogmsg .= '[WARNING] Recording date '.$id3data['recording_time'].' wrong format. Enter manually for track'."\n";
	        }
	    }
	    if (isset($id3data['year'])) {
	        $year = trim(explode('||', $id3data['year'])[0]);
	        if (preg_match($datematch,$year)==1) {
	            $trackdata['rel_date'] = $year;
	        } else {
	            $ilogmsg .= '[WARNING] Release date '.$id3data['year'].' wrong format. Enter manually for track and album'."\n";
	        }
	    } else {
	        $ilogmsg .= '[WARNING] No release date found. Enter manually for track and album'."\n";
	    }
	    
	    if (isset($id3data['track_number'])) $trackdata['trackno'] = $id3data['track_number'];
	    if (isset($id3data['totaltracks'])) $trackdata['disctracks'] = $id3data['totaltracks'];
	    
	    //get artist info
	    /** id3data artist is a string possibly containing more than one name separated by ' || '
	     *  This section will generate separate arrays for each name containing the name alias and id
	     *  If the alias already exists in the xbmusic_artists table then the full data will be loaded 
	     *  and the id will be a positive integer. 
	     *  If it is a new artist then the id will be zero and the sortname will be added.
	     */
	    if (isset($id3data['artist'])) {
	        $artiststr = $id3data['artist'];
	        $artcnt = substr_count($artiststr,' || ') + 1;
	        if ($artcnt > 1) {
	            if ($loglevel==4) $ilogmsg .= '[INFO] '.$artiststr.' '.$artcnt.' artist entries found in ID3.'."\n";
	        }
	        //the first artist in the list will become the track sortartist and album artist 
	        $origartist = substr($artiststr, 0, strpos($artiststr.' ||', ' ||')+1);
	        $trackdata['sortartist'] = XbcommonHelper::stripThe($origartist);
	        //now break any listed artists into separate if the have & or and or with or feat.
            $artistnames = explode(' || ', $artiststr);
            $artistname = $artistnames[0];
            //only take the first name - ideally check to see if first is same as second with The
//            foreach ($artistnames as $artistname) {
	            $artistname = trim($artistname);	        
                 $splits = array(" & "," and "," with "," featuring","feat.");
                 $splitcnt = 0;
                 $fnd = str_replace($splits," || ", $artistname, $splitcnt);
                 if ($splitcnt > 0) {
                     $msg = Xbtext::_($artistname,XBSP2 + XBDQ).Xbtext::_('may possibly be more than one artist. Will save as one, use save-copy on artist edit to split',XBDQ + XBNL);
                     $ilogmsg .='[WARN] .'.$msg;
                     Factory::getApplication()->enqueueMessage($msg,'Warning');
                }           
	            $artist = array('id'=>0, 'name'=>$artistname, 
	                'alias'=>XbcommonHelper::makeAlias($artistname),
	                'sortname'=>XbcommonHelper::stripThe($artistname)
	            );
	            $artistdata[] = $artist;
//	        }
	    } //endif set artist
	    
	    //get album info
	    if (isset($id3data['album'])) {
	        $albumstr = $id3data['album'];
	        $albcnt = substr_count($albumstr,'||') + 1;
	        if ($albcnt > 1) {
	            $ilogmsg .= XBWARN.Xbtext::_('more than one album name in ID3, only first will be used',XBNL);
	            $ilogmsg .= XBWARN.'<ul><li>'.str_replace(' || ','</li><li>'.$albumstr).'</li></ul>'."\n";
	            $albumstr = trim(explode('||', $albumstr)[0]);
	        }
	        
	        $albumdata['title'] = $albumstr;
	        $albumalias = $albumstr; 
	        if (isset($id3data['band'])) {
	            $albumdata['albumartist'] = $id3data['band'];
	            $albumdata['sortartist'] = XbcommonHelper::stripThe($id3data['band']);
	        } else {
	            if (isset($id3data['artist'])) {
	                $albumdata['albumartist'] = $origartist;
	                $albumdata['sortartist']= XbcommonHelper::stripThe($origartist);
	            }
	        }
	        if (isset($albumdata['sortartist'])) $albumalias.= '-'.$albumdata['sortartist'];
	        $albumdata['alias'] = XbcommonHelper::makeAlias($albumalias);
//	        $aid = XbcommonHelper::checkValueExists($albumdata['alias'], '#__xbmusic_albums', 'alias');
//	        if ($aid>0) {
//	            $albumdata['id'] = $aid;
//	        } else {
	            $albumdata['id'] = 0;
//	        }
            $albumdata['rel_date'] = $trackdata['rel_date'];
            if (isset($id3data['part_of_a_set'])) {
                $trackdata['discno'] = $id3data['part_of_a_set'];
                $setstr = explode('/',$id3data['part_of_a_set']);
                if (count($setstr)==2) {
                    $albumdata['num_discs'] = (int)$setstr[1];
                } else {
                    $ilogmsg .= '[WARNING] failed to parse `num_discs` from "'.$id3data['part_of_a_set'].'"\n';
                }
            }
            $albumdata['compilation'] = (isset($trackdata['part_of_a_compilation'])) ? '1' : '0';   
	    } //end albuminfo
	    
	    
	    //get song info - we assume song has the same title as the track
	    // we will warn if it could be a medley, but will treat as single song
        $title = trim($trackdata['title']);

        $songtitle = $title;
        // we may be going to discard anything in brackets at the end of the title
        $rcnt = 0;
        $newtitle = preg_replace('/\(.*?\)|\[.*?\]/','',$songtitle,4,$rcnt);
        if ($newtitle && ($rcnt > 0)) {
            if ($nobrackets == 1) {
                $songtitle = $newtitle;
                $songtitle = trim($songtitle,', ');
                $msg = Xbtext::_($songtitle,XBSP2 + XBDQ).Xbtext::_('Bracketed text in track title removed to make song title. Check and restore if necessary',XBNL);
                $ilogmsg .= XBWARN.$msg;
                Factory::getApplication()->enqueueMessage(trim($msg),'Warning');	            
            } else {
                $msg = Xbtext::_($songtitle,XBSP2 + XBDQ).Xbtext::_('Bracketed text has NOT been removed in song title. Check and remove if necessary',XBNL);
                $ilogmsg .= XBINFO.$msg;
                Factory::getApplication()->enqueueMessage(trim($msg),'Info');                
            }
             
        }
        // do we have the word medley?
        // now we may be going to split title into several songs
        $splits = array(",","/","->",">");
        $splitcnt = 0;
        $songtitles = str_replace($splits," || ", $songtitle, $splitcnt);
        $songtitles = explode(" || ", $songtitles);
        if ($splitcnt > 0) {
            if ($splitsongs == 1) {
                $msg = Xbtext::_($songtitle,XBSP2 + XBDQ).Text::sprintf('has been split into %s songs',$splitcnt + 1)."\n";
                $ilogmsg .=XBWARN.$msg;
                Factory::getApplication()->enqueueMessage(trim($msg),'Info');               
            } else {
                $msg = Xbtext::_($songtitle,XBSP2 + XBDQ).Xbtext::_('may possibly be a medley. Saved as one song, use save-copy on song edit to split',XBNL);
                $ilogmsg .=XBWARN.$msg;
                Factory::getApplication()->enqueueMessage(trim($msg),'Warning');               
            }
        } else {
            if (stripos($songtitle, 'medley') && ($splitsongs == 1)) {
                $msg = Xbtext::_($songtitle,XBSP2 + XBDQ).Text::_('contains the word Medley but has not been split.')."\n";
                $ilogmsg .=XBINFO.$msg;
                Factory::getApplication()->enqueueMessage(trim($msg),'Info');            
            }           
        }
        foreach ($songtitles as $songtitle) {
            $songdata[] = array('id'=>0, 'title' => $songtitle, 'alias'=>XbcommonHelper::makeAlias($songtitle));
   	    }        
	    
	    //get genres
	    if (isset($id3data['genre'])) {
	        $genres = self::createGenres($id3data['genre'], $ilogmsg);
	    }
	    
	    //images are handled separately
	    
	    if (!empty($trackdata)) $items['trackdata'] = $trackdata;
	    if (!empty($albumdata)) $items['albumdata'] = $albumdata;
	    if (!empty($songdata)) $items['songdata'] = $songdata;
	    if (!empty($artistdata)) $items['artistdata'] = $artistdata;
	    if (!empty($genres)) $items['genres'] = $genres;
	    return $items;
	    
	} // end id3dataToItems()
	
	public static function getItemIdFromAlias(string $table, string $alias){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select($db->qn('id'))->from($db->qn($table))->where('alias = '.$db->q($alias));
        $db->setQuery($query);
        return $db->loadResult();
	}
	
	public static function getGroupMembers($gid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('a.id AS member_id, a.name AS membername, gm.role, gm.since, gm.until, gm.note');
	    $query->join('LEFT','#__xbmusic_artists AS a ON a.id = gm.member_id');
	    $query->from('#__xbmusic_artistgroup AS gm');
	    $query->where('gm.group_id = '.$db->q($gid));
	    $query->order('gm.listorder ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getMemberGroups($aid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::class);
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('a.id AS group_id, a.name AS groupname, gm.role, gm.since, gm.until, gm.note');
	    $query->join('LEFT','#__xbmusic_artists AS a ON a.id = gm.group_id');
	    $query->from('#__xbmusic_artistgroup AS gm');
	    $query->where('gm.member_id = '.$db->q($aid));
	    $query->order('gm.listorder ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getArtistSingles($aid) {
//	    $db = Factory::getContainer()->get(DatabaseInterface::class);
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('t.id AS trackid, t.title AS tracktitle, t.imgurl, t.rel_date');
	    $query->join('LEFT','#__xbmusic_trackartist AS at ON at.track_id = t.id');
	    $query->from('#__xbmusic_tracks AS t');
	    $query->where('t.album_id = 0 AND at.artist_id = '.$aid);
	    $query->order('t.rel_date, t.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	/**
	 * @name createMusicItem()
	 * @desc Creates an xbMusic item with supplied data. Returns id or -id if alias exists. 
	 * Status, access, created & modified dates will be default values if missing. 
	 * Created_by will be set to user, alias will be created from title if missing (not if no title)
	 * Uses item model::save() to create item, so all valid data elements will be created, others will default.
	 * @param array $data
	 * @param string $table
	 * @return int|false - new item positive id, existing item negative id, or false on failure
	 */
	public static function createMusicItem(array $data, string $itemtype) {
	    $app = Factory::getApplication();
	    if (strpos(' track song playlist artist album ', $itemtype) == false) {
	        $app->enqueueMessage('Invalid itemtype to create','Error');
	        return false;
	    }
	    $id = XbcommonHelper::checkValueExists($data['alias'], '#__xbmusic_'.$itemtype.'s', 'alias');
	    if ($id !== false) return $id * -1;
	    if ($data['id'] == 0) unset($data['id']);
	    $itemid = false;
	    $sqldate = Factory::getDate()->toSql();
	    $user 	= $app->getIdentity();
	    if (!key_exists('status', $data))  $data['status'] = 1;
	    if (!key_exists('access', $data))  $data['access'] = 1;
	    if (!key_exists('created', $data))  $data['created'] = $sqldate;
	    if (!key_exists('modified', $data))  $data['modified'] = $sqldate;
	    if (!key_exists('created_by', $data))  $data['created_by'] = $user->id;
	    if ((!key_exists('alias', $data)) && (key_exists('title', $data))) 
	        $data['alias'] = XbcommonHelper::makeUniqueAlias($data['title'],'#__xbmusic_'.$itemtype.'s');
	    
//	    $itemmodel = $this->getMVCFactory()->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
	    $itemmodel = $app->bootComponent('com_xbmusic')->getMVCFactory()
	       ->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
	    if ($itemmodel->save($data) == false) {
	        $app->enqueueMessage('createMusicItem().'.$itemtype.' '.$itemmodel->getError(), 'Error');
	        return false;
	    }
        $itemid = $itemmodel->getState($itemtype.'.id');
	    return $itemid;
//	    }
//	    return false;
	}  // end createMusicItem()
	
	/**
	 * @name createImageFile()
	 * @desc takes image data from ID3 and a desired pathfilename from Joomla root and creates if not exists
	 * @param array $imgdata
	 * @param string $imgfilename
	 * @param string $flogmsg
	 * @return string|boolean - img url if exists or created or false on failure
	 */
	public static function createImageFile(array &$imgdata, string $imgfilename, string &$flogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $loglevel = $params->get('loglevel',3);
	    $imgpath = pathinfo($imgfilename, PATHINFO_DIRNAME);
	    $folder = str_replace('/images/xbmusic/artwork/','',$imgpath);
	    $imgpath = JPATH_ROOT.$imgpath;
	    //create the folder if it doesn't exist (eg new initial)
	    if (file_exists($imgpath)==false) {
	        if (mkdir($imgpath,0775,true)) {
	            if ($loglevel==4) $flogmsg .= XBINFO.Text::_('artwork folder created',2).Xbtext::_($folder,XBSP1 + XBDQ + XBNL);
	        } else  {
	            $flogmsg .= XBERR.Text::_('failed to create artwork folder').Xbtext::_($imgpath,XBSP1 + XBDQ + XBNL);
	           return false;
	        }
	    }
	    $imgext = XbcommonHelper::imageMimeToExt($imgdata['image_mime']);
	    $imgfilename = $imgfilename.'.'.$imgext;
	    $xbfilename = $folder.'/'.pathinfo($imgfilename, PATHINFO_BASENAME);
	    $imgpathfile = JPATH_ROOT.$imgfilename;
	    $imgurl = Uri::root().ltrim($imgfilename,'/');
	    $imgok = false;
	    if (file_exists($imgpathfile)) {
	        $imgok = true;
	        if ($loglevel==4) $flogmsg .= '[INFO] '.Text::sprintf('Artwork file "%s" already exists',$xbfilename)."\n";
	    } else {
	        $params = ComponentHelper::getParams('com_xbmusic');
	        $maxpx = $params->get('imagesize',500);
	        if ($imgdata['image_height'] > $maxpx) {
	            //need to resize image
	            $image = imagecreatefromstring($imgdata['data']);
	            $newimage = imagescale($image, $maxpx);
	            switch ($imgext) {
	                case 'jpg':
	                    $imgok = imagejpeg($newimage, $imgpathfile);
	                    break;
	                case 'png':
	                    $imgok = imagepng($newimage, $imgpathfile);
	                    break;
	                case 'gif':
	                    $imgok = imagegif($newimage, $imgpathfile);
	                    break;
	                default:
	                    $imgok = false;
	                    break;
	            }
	        } else {
	            $imgok = file_put_contents($imgpathfile, $imgdata['data']);
	        }
	    } //endif artfile !exists
	    if ($imgok) {
	        unset($imgdata['data']);
	        $imgdata['imgurl'] = $imgurl;
	        $imgdata = array_merge($imgdata, self::getImageInfo($imgdata));
	        if ($loglevel==4) $flogmsg .= XBINFO.Text::_('image created').Xbtext::_($xbfilename,XBSP1 + XBDQ + XBNL);
	        return $imgurl;
	    }
	    $flogmsg .= XBERR.Text::_('failed to create image').Xbtext::_($imgpathfile,XBSP1 + XBDQ + XBNL);
	    
	    return false;
	} //end createImageFile()
	
	public static function getImageInfo(array $imgdata) {
	    $file = trim(str_replace(Uri::root(), JPATH_ROOT.'/',$imgdata['imgurl']));
	    if (file_exists($file)){
	        $imgdata['folder'] = dirname(str_replace(Uri::root(),'',$imgurl));
	        $imgdata['basename'] = basename($file);
	        $bytes = filesize($file);
	        $lbl = Array('bytes','kB','MB','GB');
	        $factor = floor((strlen($bytes) - 1) / 3);
	        $imgdata['filesize'] = sprintf("%.2f", $bytes / pow(1024, $factor)) . @$lbl[$factor];
	        $imgdata['filedate'] = date("d M Y at H:i",filemtime($file));
	        $imagesize = getimagesize($file);
	        $imgdata['filemime'] = $imagesize['mime'];
	        $imgdata['filewidth'] = $imagesize[0];
	        $imgdata['fileht'] = $imagesize[1];
	        $imgdata['imagetitle'] = $imgdata['picturetype'];
	        $imgdata['imagedesc'] = $imgdata['description'];
	    }
	    return $imgdata;
	}
	
	/**
	 * @name normaliseGenrename()
	 * @desc applies any normalisation to names for genres specified in component options
	 * @param string $genrename
	 * @param string $ilogmsg
	 * @return string
	 */
	public static function normaliseGenrename(string $genrename, string &$ilogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $opthyphen = $params->get('genrehyphen',1);
	    $optspaces = $params->get('genrespaces',1);
	    $optcase = $params->get('genrecase',1);
	    $cnt = 0;
	    if ($opthyphen == 1) {
	        $genrename = str_replace('/','-',$genrename, $cnt);
	    } elseif ($opthyphen==2) {
	        $genrename = str_replace('-','/',$genrename, $cnt);
	    }
	    if ($optspaces == 1)  {
	        $genrename = str_replace(' ','-',$genrename, $cnt);
	    } elseif ($optspaces == 2) {
	        $genrename = str_replace(' ','/',$genrename, $cnt);
	    }
	    if ($cnt > 0) $ilogmsg .= $genrename.' normalized'."\n";
	    if ($optcase > 0) $genrename = strtolower($genrename);
	    if ($optcase == 1) $genrename = ucfirst($genrename);
	    return $genrename;	    
	} // end normaliseGenrename()
	
	/**
	 * @name createGenres()
	 * @desc takes a string of genre names separated by || and returns them as array of assoc array of data for each
	 * also splits names on commas
	 * @param string $genrenames - multiple names separated by ' || '
	 * @param string $ilogmsg
	 * @return array genres data including 'isnew' if created
	 */
	public static function createGenres(string $genrenames, string &$ilogmsg) {
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $optspaces = $params->get('genrespaces',1);
	    //split names including commas 
	    $genrenames = str_replace(',', '||', $genrenames);
	    // if required split names with spaces into two or more genres "Folk Rock" -> "Folk" and "Rock"
	    if ($optspaces == 3) $genrenames = str_replace(array(' || ', ' '), '||', $genrenames);	        
	    $genres = array();
	    $genrenames = explode('||', $genrenames);
        //get the parent tag for genre tags
        $parentgenre = XbcommonHelper::getCreateTag(array('title'=>'MusicGenres'));
	    foreach ($genrenames as &$genre) {
	        $genre = trim($genre,'-');
	        $genre = self::normaliseGenrename(trim($genre), $ilogmsg);
	        //get or create the genre tag id and title
	        $newtag = XbcommonHelper::getCreateTag(array('title'=>$genre, 'parent_id'=>$parentgenre['id'],
	                       'created_by_alias'=>'xbMusicHelper::createGenres()'));
	        if ($newtag) $genres[] = $newtag;
	    } //end foreach genre
	    return $genres;
	} //end createGenres()
	
	public static function getTrackArtists($tid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('a.id AS artistid, a.name AS artistname, a.alias AS alias, ta.role AS role, ta.listorder');
	    $query->from('#__xbmusic_artists AS a');
	    $query->join('LEFT','#__xbmusic_trackartist AS ta ON ta.artist_id = a.id');
	    $query->where('ta.track_id = '.$db->q($tid));
	    $query->order('ta.listorder ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getTrackSongs($tid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('s.id AS songid, s.title AS songtitle, s.alias AS songalias, ts.role AS songrole, ts.listorder AS songorder');
	    $query->from('#__xbmusic_songs AS s');
	    $query->join('LEFT','#__xbmusic_tracksong AS ts ON ts.song_id = s.id');
	    $query->where('ts.track_id = '.$db->q($tid));
	    $query->order('ts.listorder ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	

	public static function getAlbumTracks($aid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('t.id AS trackid, t.title AS tracktitle, t.filepathname, t.sortartist, t.discno, t.trackno');
	    $query->from('#__xbmusic_tracks AS t');
	    $query->where('t.album_id = '.$db->q($aid));
	    $query->order('t.discno, t.trackno, t.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getAlbumArtists($albumid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('DISTINCT a.name AS artistname, a.id AS artistid, ta.role AS artistrole');
	    $query->from('#__xbmusic_tracks AS t');
	    $query->join('LEFT','#__xbmusic_trackartist AS ta ON ta.track_id = t.id');
	    $query->leftjoin('#__xbmusic_artists AS a ON a.id = ta.artist_id');
	    $query->where('t.album_id = '.$db->q($albumid).' AND a.name <> \'\'');
	    //$query->group('a.name');
	    $query->order('a.sortname ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	
	public static function getAlbumSongs($albumid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('s.title AS songtitle, s.id AS songid, s.alias AS songalias, ts.role AS songrole, ts.note AS songnote, ts.listorder AS songorder');
	    $query->from('#__xbmusic_tracks AS t');
	    $query->leftjoin('#__xbmusic_tracksong AS ts ON ts.track_id = t.id');
	    $query->leftjoin('#__xbmusic_songs AS s ON s.id = ts.song_id');
	    $query->where('t.album_id = '.$db->q($albumid));
	    $query->order('s.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getSongArtists($songid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('a.id AS artistid, a.name AS artistname, a.imgurl, a.sortname AS artistsort');
	    $query->from('#__xbmusic_artists AS a');
	    $query->join('LEFT','#__xbmusic_trackartist AS ta ON ta.artist_id = a.id');
	    $query->join('LEFT','#__xbmusic_tracksong AS ts ON ts.track_id = ta.track_id');
	    $query->where('ts.song_id = '.$db->q($songid));
	    $query->group('a.id');
	    $query->order('a.sortname ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();	    
	}
	
	public static function getSongAlbums($songid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('DISTINCT a.id AS albumid, a.title AS albumtitle, a.rel_date, a.imgurl');
	    $query->from('#__xbmusic_albums AS a');
	    $query->join('LEFT','#__xbmusic_tracks AS t ON t.album_id = a.id');
	    $query->join('LEFT','#__xbmusic_tracksong AS ts ON ts.track_id = t.id');
	    $query->where('ts.song_id = '.$db->q($songid).' AND t.album_id > 0');
	    $query->group('a.id');
	    $query->order('a.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();	    
	}
	
	public static function getArtistSongs($artistid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('s.id AS songid, s.title AS songtitle, s.composer AS composer');
	    $query->from('#__xbmusic_songs AS s');
	    $query->join('LEFT','#__xbmusic_tracksong AS ts ON ts.song_id = s.id');
	    $query->join('LEFT','#__xbmusic_trackartist AS ta ON ta.track_id = ts.track_id');
	    $query->where('ta.artist_id = '.$db->q($artistid));
	    $query->group('s.id');
	    $query->order('s.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getArtistAlbums($artistid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('DISTINCT a.id AS albumid, a.title AS albumtitle, a.rel_date, a.imgurl');
	    $query->from('#__xbmusic_albums AS a');
	    $query->join('LEFT','#__xbmusic_tracks AS t ON t.album_id = a.id');
	    $query->join('LEFT','#__xbmusic_trackartist AS ta ON ta.track_id = t.id');
	    $query->where('ta.artist_id = '.$db->q($artistid).' AND t.album_id > 0');
	    $query->order('a.title ASC');
	    $db->setQuery($query);
	    return $db->loadAssocList();
	}
	
	public static function getTagItemCnts($id) {
	    $res = array('albumcnt'=>0, 'artistcnt'=>0, 'playlistcnt'=>0, 'songcnt'=>0, 'trackcnt'=>0, 'total'=>0);
	    $db = Factory::getDbo();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['albumcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['artistcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['playlistcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['songcnt'] = $db->loadResult();
	    $db->setQuery('SELECT COUNT(*) FROM #__contentitem_tag_map AS al WHERE al.type_alias='.$db->quote('com_xbmusic.album').' AND al.tag_id = '.$db->q($id));
	    $res['trackcnt'] = $db->loadResult();
	    $tot = array_sum($res);
	    $res['total'] = $tot;
	    return $res;
	    
	} // end getTagItemCnts()

	public static function getLastImportLog() {
	    $file = null;
	    foreach(new \DirectoryIterator(JPATH_ROOT.'/xbmusic-logs') as $item) {
	        if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
	            $file = clone $item;
	        }
	    }
	    if (!is_null($file)) {
	        $logtxt = file_get_contents($file->getPathname());
	        return $logtxt;
	    }
	    return '';
	}
	
	public static function writelog(string $logstr, $filename = '') {
	    if ($filename == '') {
	        $filename = 'import_'.date('Y-m-d').'.log';
	    }
	    $logstr .= self::readlog($filename);
	    $pathname = JPATH_ROOT.'/xbmusic-logs/'.$filename;
	    $f = fopen($pathname, 'w');
	    fwrite($f, $logstr);
	    fclose($f);
	}
	
	public static function readlog(string $filename, $filter ='') {
	    $pathname = JPATH_ROOT.'/xbmusic-logs/'.$filename;
	    if (file_exists($pathname)) {
    	    if ($filter == '') return file_get_contents($pathname);
            $logstr = '';
	        $flags = explode(',',$filter);
    	    if ($lines = file($pathname)) {
	           foreach ($lines as $line) {
	                foreach ($flags as $flag) {
	                    if (str_starts_with($line, $flag)) $logstr .= $line;
	                }
	           }
    	    }
	    } else {
	        $logstr = '';
	    }
	    return $logstr;
	}

	/**
	 * @name getItemdefCats()
	 * @desc returns object with properties for default categories as set in global options
	 * @return \stdClass object with default cat ids for track, artists, album and songs
	 */
	public static function getItemDefCats() {
	    //default categories for albums, artists and songs
	    $defcats = new \stdClass();
	    $uncatid = XbcommonHelper::getCatByAlias('uncategorised');
	    $params = ComponentHelper::getParams('com_xbmusic');
	    $usedaycat = $params->get('impcat','0');
	    $defcats->albumcatid = $params->get('defcat_album',$uncatid);
	    $defcats->artistcatid = $params->get('defcat_artist',$uncatid);
	    $defcats->songcatid = $params->get('defcat_song',$uncatid);
	    $defcats->trackcatid = $params->get('defcat_track',$uncatid);
	    //track category may be overriden by genre (tracks-genres-genre) on per item basis
	    if ($usedaycat == 1) {
	        //we are going to change the defaults to a day category under \imports
	        $daycatid = 0;
	        $daycattitle = date('Y-m-d');
	        $dcparent = XbcommonHelper::checkValueExists('imports', '#__categories', 'alias', "`extension` = 'com_xbmusic'");
	        if ($dcparent === false) {
	            $catdata = array('title'=>'Imports', 'alias'=>'imports', 'description'=>'parent for import date categories used when importing items from MP3');
	            $dcparent = XbcommonHelper::createCategory($catdata, true);
	        }
	        $parentcat = XbcommonHelper::getCatByAlias('imports');
	        $parentid = ($parentcat>0) ? $parentcat->id : 1;
	        $catdata = array('title'=>$daycattitle, 'alias'=>$daycattitle, 'parent_id'=>$parentid,'description'=>'items inported on '.date('D jS M Y'));
	        //            }
	        $daycatid = XbcommonHelper::checkValueExists($daycattitle, '#__categories', 'alias', "`extension` = 'com_xbmusic'");
	        if  ($daycatid==false) $daycatid = XbcommonHelper::createCategory($catdata, true)->id;
	        if ($daycatid > 0) {
	            $defcats->albumcatid = $daycatid;
	            $defcats->artistcatid = $daycatid;
	            $defcats->songcatid = $daycatid;
	            $defcats->trackcatid = $daycatid;
	        }
	    } //endif cattype=1
	    return $defcats;	    
	}
	
	public static function getAlbumImgInfo($albumid) {
	    //$db = Factory::getContainer()->get(DatabaseInterface::cl
	    $db = Factory::getDbo();
	    $query = $db->getQuery(true);
	    $query->select('DISTINCT a.imgurl, a.imginfo');
	    $query->from('#__xbmusic_albums AS a');
	    $db->setQuery($query);
	    return $db->loadAssoc();	    
	}
	
	public static function getID3image($trackfilename,$imgfilename) {
	    $ThisFileInfo = getIdData($trackfilename);
	    $imgdata = $ThisFileInfo['comments']['picture'][0]; //we're only getting the first image
	    unset($ThisFileInfo['comments']['picture']);
	    if (isset($imgdata['description'])) { //fix for an album with odd encoding on picture description
	        $desc = $imgdata['description'];
	        $res = htmlentities($desc, ENT_QUOTES | ENT_IGNORE, 'UTF-8');
	        $res =  preg_replace('/[\x00-\x1F\x7F-\x9F]/u', '', $res);
	        $imgdata['description'] = $res;
	    }
	    if (($imgdata['data'])){
	        $log ='';
	        $imgurl = self::createImageFile($imgdata, $imgdata, $log);
	        if ($imgurl !== false) {
	            $imgdata['imagetitle'] = $imgdata['picturetype'];
	            $imgdata['imagedesc'] = $imgdata['description'];
	        }
	    }
	    return $imgdata;
	}
	
	public static function setID3image($imgfilename, $trackfilename) {
	    
	}
	
} //end xbmusicHelper
