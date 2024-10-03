<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DatamanModel.php
 * @version 0.0.18.0 17th September 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Changelog\Changelog;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
//use Joomla\CMS\Layout\FileLayout;
//use DOMDocument;
//use ReflectionClass;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
//use CBOR\OtherObject\TrueObject;

class DatamanModel extends AdminModel {

    protected $daycat = 0;
    
//    public function getForm($data = [], $loadData = true) {
//        
//    }
    public function getForm($data = array(), $loadData = true) {
        $form = $this->loadForm('com_xbmusic.dataman', 'dataman',
            array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }
    
    /**
     * @name parseFilesMp3()
     * @desc takes either a folder path or a set of file pathnames and parses the files ID3 data
     * prepends the logging infomation to a log file with current date in /xbmusic-logs/
     * @param string|array $files - if a string it is assumed to be a folder 
     * @param int $cattype
     * @return boolean
     */
    public function parseFilesMp3($files, int $cattype) {
//        Factory::getApplication()->enqueueMessage(print_r($files,true));
        // if we are going to assign a day category to imported items we need to create it
        if ($cattype == "1") {
            $daycattitle = date('Y-m-d');
            //create category with import date
            $parentcat = XbmusicHelper::checkValueExists('import', '#__categories', 'alias', "`extension` = 'com_xbmusic'");
            if ($parentcat == false) {
                $catdata = array('title'=>'Import', 'alias'=>'import', 'description'=>'parent for import date categories used on importing items');
                $parentcat = XbmusicHelper::createCategory($catdata, true);
            }
            $this->daycat = XbmusicHelper::checkValueExists($daycattitle, '#__categories', 'alias', "`parent_id` = '".$parentcat."'");
            if ($this->daycat == false) {
                $catdata = array('title'=>$daycattitle, 'alias'=>$daycattitle, 'parent_id'=>$parentcat,'description'=>'items inported on '.date('D jS M Y'));
                $this->daycat = XbmusicHelper::createCategory($catdata, true);
            }
        } //endif cattype=1
        //start log
        $logmsg = 'Import Started '.date('H:i:s D jS M Y')."\n";
        
//        $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //XbmusicHelper::$musicBase;
        //are we doing a whole folder, or selected files?
        if (is_string($files)) {
            $folder = trim($files);
            //get files in folder to array
            $files = [];
            $dirit = new \DirectoryIterator(JPATH_ROOT.'/xbmusic/'.$folder);
            foreach ($dirit as $fileinfo) {
                if (strtolower($fileinfo->getExtension()) == 'mp3') {
                    $files[] = $folder.$fileinfo->getFilename();
                }
            }            
        } elseif (!is_array($files)) {
            $errmsg .= Text::_('Invalid files list');
            Factory::getApplication()->enqueueMessage($errmsg,'Error');
            $logmsg .= '[ERROR] '.$errmsg."\n ============================== \n\n";
            $this->writelog($logmsg);
            return false;
        }
        if (count($files)==0){
            $errmsg .= Xbtext::_('No mp3 files found in',2).$folder;
            Factory::getApplication()->enqueueMessage($errmsg,'Warning');
            $logmsg .= '[WARNING] '.$errmsg."\n ============================== \n\n";
            $this->writelog($logmsg);
            return false;
        }
        // set up counts for logging and start time
        $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0);
        $starttime = time(); 
        //do the files
        foreach ($files as $file) {
            $logmsg .= $this->parseID3($file, $cnts);            
        }
        //update the log file
        $logmsg .= '[SUM] '.$cnts['newtrk'].' new tracks, '.$cnts['duptrk'].' duplicates'."\n";
        $logmsg .= '[SUM] '.$cnts['newalb'].' new albums, '.$cnts['newart'].' new artists, '.$cnts['newsng'].' new songs, '."\n";
        $logmsg .= '[SUM] Elapsed time '.date('s', time()-$starttime).' seconds'."\n";
        $logmsg .= '======================================'."\n\n";
        $this->writelog($logmsg);
        return true;
    } //end parseFilesMp3()    
    
    /**
     * @name parseID3()
     * @desc reads ID3 info from an mp3 file and if found creates track, album, artists and songs and saves the image file
     * @param string $filepathname - the file pathname from the JPATH_ROOT/xbmusic/ folder
     * @param array $cnts
     * @return string - the loging info to be appended to the log file
     */
    public function parseID3(string $filepathname, array &$cnts) {
        //we'll need the params for category allocation and handling genres and image files
        $params = ComponentHelper::getParams('com_xbmusic');
        //strat the logging for this file
        $logmsg = '[INFO] '.$filepathname."\n";
        $enditem .= " -------------------------- \n";
        $trackdata = []; //only one track per file
        $albumdata = []; //only one album per file
        $songdata = []; //only one song initially - if medley could be split later
        $artistdata = []; //can be mulitple artists
        $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //XbmusicHelper::$musicBase;
//         if (file_exists($basemusicfolder.$filepathname)==false) {
//             $logmsg .= '[ERROR] '.$basemusicfolder.$filepathname.' File not accessible'."\n";;
//             $logmsg .= $enditem;
//             return $logmsg;
//         }

// 1. make track alias, if it exists already then exit
        // track alias is the cleaned filepathname 
        $trackdata['alias'] = OutputFilter::stringURLSafe($filepathname); 
        // file must be unique
        if ( $tid = XbmusicHelper::checkValueExists($trackdata['alias'], '#__xbmusic_tracks', 'alias')) {
            $logmsg .= '[ERROR] Track already in database with track_id='.$tid."\n";
            $logmsg .= $enditem;
            $cnts['duptrk'] ++;
            return $logmsg;
        }
// 2. set track->pathname and track->filename, if filename exists then warning
        // path and filename are stored separately in the database
        $trackdata['pathname'] = rtrim(dirname($filepathname),'/').'/';
        $trackdata['filename'] = basename($filepathname);
        // check if same filename exists in a different folder - import it anyway as 
        if ( $fid = XbmusicHelper::checkValueExists($trackdata['filename'], '#__xbmusic_tracks', 'filename')) {  
            $fpath = XbmusicHelper::getItemValue('#__xbmusic_tracks', 'pathname', $fid);
            if ( $fpath == $trackdata['pathname'] ) {
                $logmsg .= '[WARNING] Track with same filename already in database in '.$fpath.'with track_id='.$fid."\n";
                $logmsg .= '[WARNING] Importing this one anyway, but check and delete one or other if necessary'."\n";               
                $cnts['duptrk'] ++;
            }
        }
        
//3. okay, now get the id3 data
        $id3data = XbmusicHelper::getFileId3($basemusicfolder.$filepathname);
        
// 4. set track->title, if not set then exit
        //title is required 
        if (!isset($id3data['title'])) { //could add any other required eleemts to the isset() function
            $logmsg .= '[ERROR] No title found in ID3 data. Cannot import'."\n";
            $logmsg .= $enditem;
            $cnts['duptrk'] ++;
            return $logmsg;          
        }
        $cnts['newtrk'] ++;
        $trackdata['title'] = $id3data['title'];
        
// 5. set artist[0] as track->sortartist and album->sortartisd and use for image name. set artistcnt
        // get the artist name without "The " to use for sorting tracks & albums by artist and in artwork filename
        if (isset($id3data['id3tags']['artist'])) {
            $artistarr = explode(' || ', $id3data['id3tags']['artist']);
            $trackdata['sortartist'] = XbmusicHelper::stripThe($artistarr[0]);
            if (count($artistarr) > 1) {
                $logmsg .= '[WARNING] Multiple artists in ID3 - first used as Album Artist if not separately specified, and as Track SortName. Check and adjust manually as required'."\n";
            }
        }
// 6. set album->title for use in image name (use track->title if no album), set album exists.
        if (isset($id3data['id3tags']['album'])) {
            $albumarr = explode(' || ', $id3data['id3tags']['album']);
            $albumdata['title'] = $albumarr[0];
            if (count($albumarr)>1) {
                $logmsg .= '[WARNING] Multiple album titles listed - first is used - a track can only belong to one album'."\n";
                $logmsg .= 'Album Titles: ';
                foreach ($albumarr as $value) {
                    $logmsg .= ' '.$value.',';
                }
                $logmsg = trim($logmsg,',')."/n";
            }
            if (isset($id3data['id3tags']['band'])) {
                $albumdata['albumartist'] = $id3data['id3tags']['band'];
                $albumdata['sortartist'] = XbmusicHelper::stripThe($id3data['id3tags']['band']);
            } else {
                $albumdata['albumartist'] = (isset($trackdata['sortartist'])) ? $trackdata['sortartist'] : null;
                $albumdata['sortartist'] = (isset($trackdata['sortartist'])) ? $trackdata['sortartist'] : null;
            }
        }
        
// 7. get image, resize as necessary and save as file `sort-artist_album-title.jpg`
        if (isset($id3data['imageinfo']['data'])){
            // filename for image will be "album-title_albumartist-name.ext"  in images/xbmusic/artwork/albums/[initial]/
            // if track has no album listed but has image then "track-title_artist-name.ext" and save in artwork/singles/
            // if no album or artist then just use "track-title.ext" in artwork/singles
            // path will finish with initial letter of title
            // folder for artwork will be "images/xbmusic/artwork/albums/[initial]/" or "images/xbmusic/artwork/singles/"
            $folder = (isset($albumdata['title'])) ? 'singles/' : 'albums/'.strtolower($albumdata['title'][0]).'/';
            $artpath = '/images/xbmusic/artwork/'.$folder;
            if (file_exists($artpath)==false) {
                mkdir(JPATH_ROOT.$artpath,0775,true);
            }
            // filename will be "album_title" or "track-title" with "_sort-artist" if set
            if (isset($albumdata['title'])) {
                $artfilename = $albumdata['title'];
            } else {
                $artfilename = $trackdata['title'];
            }
            if (isset($trackdata['sortartist'])) {
                $artfilename .= '_'.$trackdata['sortartist'];
            }
            $artfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $artfilename)).'.'.XbmusicHelper::imageMimeToExt($filedata['imageinfo']['image_mime']);
            $artpathfile = JPATH_ROOT.$artpath.$artfilename;
            $arturl = Uri::root().$artpath.$artfilename;
            if (file_exists($artpathfile)) {
                $trackdata['artwork'] = $arturl;
                $albumdata['artwork'] = $arturl;
            } else {
// 8. if image is bigger than limits then resize it before saving and save back to id3                
                if (file_put_contents($artpathfile, $id3data['imageinfo']['data'])) {
                    $trackdata['artwork'] = $arturl;
                    $albumdata['artwork'] = $arturl;
                }
            }
            // remove the image data from id3data as it causes problems in later steps
            unset($id3data['imageinfo']['data']);
        } //end ifset image data
        
// 9. recording and release dates for track and album
        // dates in id3 can be any format and may include y m and D or not
        $datematch = '/(^(\d{4})$)|(^(\d{4})-{1}[0-1][1-9]$)|(^(\d{4})-{1}[0-1][1-9]-{1}[0-3][1-9]$)/';
        if (isset($id3data['id3tags']['recording_time'])) {
            if (preg_match($datematch,$id3data['id3tags']['recording_time'])==1) {
                $trackdata['rec_date'] = ($id3data['id3tags']['recording_time']);
            } else {
                $logmsg .= '[WARNING] Recording date '.$id3data['id3tags']['recording_time'].' wrong format. Enter manually for track'."\n";
            }
        }
        if (isset($id3data['id3tags']['year'])) {
            if (preg_match($datematch,$id3data['id3tags']['year'])==1) {
                $trackdata['rel_date'] = $id3data['id3tags']['year'];
                $albumdata['rel_date'] = (isset(albumdata['title'])) ? $id3data['id3tags']['year'] : null;
            } else {
                $logmsg .= '[WARNING] Release date '.$id3data['id3tags']['year'].' wrong format. Enter manually for track and album'."\n";
            }
        } else {
            $logmsg .= '[WARNING] No release date found. Enter manually for track and album'."\n";
        }
        
// 10. Genres
        $genrenames = '';
        $opt = $params->get('genrecattag',0);
        if (isset($id3data['id3tags']['genre'])) {
            $genrenames = explode(' || ', $id3data['id3tags']['genre']);
            $genretags = [];
            $find = array('.',',','/'); //replace these with hyphens in genre title and make lower case
            foreach ($genrenames as $value) {
                $value = strtolower(str_replace($find,'-',$genre));
            }
            if(($opt == 2) || ($opt == 3)) {
                $ptag = XbmusicHelper::getCreateTag(array('title'=>'Genres'));
                foreach ($genrenames as $genre) {
                    $genre = str_replace($find,'-',$genre);
                    $tid = XbmusicHelper::checkValueExists($genre, '#__tags', 'title');
                    if ($tid == 0) {
                        $newtag = XbmusicHelper::getCreateTag(array('title'=>$genre, 'parent_id'=>$ptag->id, 'note'=>Text::_('XBMUSIC_ID3GENRES_TAG_NOTE')),true);
                        if ($newtag->id) {
                            $tid = $newtag->id;
                        }
                    } else{
                        $infomsg .= Text::sprintf('Tag "%s" assigned to track', $genre).'<br />';
                    } //endif tag already exists
                    //add tag to item
                    $trackdata['tags'][] = $tid;
                    //need to also assign to album and song as per settings once they have been created after track has been saved
                    //do we have song and album ids? $data['album_id']
                    $genretags[$tid] = $genre;
                } //endif opt=2|3
            } //end foreach genre
            $trackdata['genres'] = $genretags;
            //
        } // endif id3 genre is set
        
        
        
// 11. Category

        
        
//===============================================        
//         $trackdata[''] = $id3data[''];
        // ok lets proceed, get the categories to apply
        //first we'll extract the genre info as may need it 
        if ($this->daycat > 0) {
            $trackdata['catid'] = $this->daycat;
        } else {
            if ((($opt == 1) || ($opt == 3)) && (is_array($genrenames))) {
                $cid = XbmusicHelper::getCatByAlias(ApplicationHelper::stringURLSafe($genrenames[0]))->id;
                if ($cid > 0) {
                    $trackdata['catid'] = $cid;                   
                } else {
                    $trackdata['catid'] = $params->get('defcat_track');                   
                }
            } else {
                $trackdata['catid'] = $params->get('defcat_track'); 
            }
        }
        //create track (need to add album id and sortartist once we have them
        
        //build album data
        
        //create album
        
        //build song data
        //create song
        
        //build artist(s) data
        //create artists
        
        //save image
        
        //save links
        
// get artist details and check if exists

    
//         if (isset($id3data['id3tags']['artist'])) {
//             $artistarr = explode(' || ', $id3data['id3tags']['artist']);
//             $data['sortartist'] = $this->stripThe($artistarr[0]);
//             if (count($artistarr) > 1) {
//                 $warnmsg .= Text::_('More than one artist listed - only first used as Main Performer (sortname &amp; album artist). Check and adjust sortname manually if required').'<br />';
//             }
//         }
        
//         getCreate album
//         getCreate song
//         create track incl link track-album id
//         link artist-album
//         link artist-song
//         link artist-track
//         link song-track

        $logmsg .= $enditem;
        return $logmsg;
    } //end parseID3()
    
    public function writelog(string $logstr, $filename = '') {
        if ($filename == '') {
            $filename = 'import_'.date('Y-m-d').'.log';
        }
        $logstr .= $this->readlog($filename);
        $pathname = JPATH_ROOT.'/xbmusic-logs/'.$filename;
        $f = fopen($pathname, 'w');
        fwrite($f, $logstr);
        fclose($f);
    }
    
    public function readlog(string $filename) {
        $logstr = '';
        $pathname = JPATH_ROOT.'/xbmusic-logs/'.$filename;
        if ((file_exists($pathname)) && (filesize($pathname) > 0)) {
            $f = fopen($pathname,'r');
            if ($f) {
                $logstr = fread($f, filesize($pathname));
                fclose($f);
            } else {
                Factory::getApplication()->enqueueMessage('Could not open file <code>/xbxbmusic-logs/'.$filename.'</code> - is it locked?', 'Warning');
            }
        }
        return $logstr;
    }
}
