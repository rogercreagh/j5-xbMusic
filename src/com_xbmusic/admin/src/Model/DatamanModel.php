<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/DatamanModel.php
 * @version 0.0.18.8 13th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
//use Joomla\CMS\Filter\OutputFilter;
use Joomla\Filter\OutputFilter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Uri\Uri;
use DirectoryIterator;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbmusicHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;
use \SimpleXMLElement;
//use CBOR\OtherObject\TrueObject;
//use Joomla\CMS\Application\ApplicationHelper;
//use Joomla\CMS\Changelog\Changelog;
//use Joomla\CMS\MVC\Model\ListModel;
//use Joomla\CMS\Toolbar\Toolbar;
//use Joomla\CMS\Toolbar\ToolbarHelper;
//use Joomla\CMS\Layout\FileLayout;
//use DOMDocument;
//use ReflectionClass;

const INFO = '[INFO] ';
const WARN = '[WARNING] ';

class DatamanModel extends AdminModel {

    protected $trackcatid = 0;
    protected $albumcatid = 0;
    protected $artistcatid = 0;
    protected $songcatid = 0;
    protected $usedaycat = 0;
    //when creating alias with strURLsafe() punction, apostrophes and quotes get converted into hyphens - we don't want that
    protected $unwantedaliaschars = array("?","!",",",";",".","'","\"");
    
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
    public function parseFilesMp3($files, $usedaycat) {
        //if ($usedaycat == '') $usedaycat = $params->get('usedaycat','0');
        $params = ComponentHelper::getParams('com_xbmusic');
        $this->usedaycat = ($usedaycat === '') ? $params->get('impcat','0') : $usedaycat;
        //start log
        $loghead = '[IMPORT] Import ID3 Started '.date('H:i:s D jS M Y')."\n";
        $logmsg = '';
        
        //are we doing a whole folder, or selected files?
        if (is_string($files)) {
            $folder = trim($files);
            //get files in folder to array
            $files = [];
            $dirit = new \DirectoryIterator(JPATH_ROOT.'/xbmusic/'.$folder);
            foreach ($dirit as $fileinfo) {
                if (strtolower($fileinfo->getExtension()) == 'mp3') {
                    $files[] = trim($folder.$fileinfo->getFilename());
                }
            }            
        } elseif (!is_array($files)) {
            $errmsg .= Text::_('Invalid files list');
            Factory::getApplication()->enqueueMessage($errmsg,'Error');
            $logmsg .= $loghead.'[ERROR] '.$errmsg."\n ============================== \n\n";
            $this->writelog($logmsg);
            return false;
        }
        if (count($files)==0){
            $errmsg .= Xbtext::_('No mp3 files found in',2).$folder;
            Factory::getApplication()->enqueueMessage($errmsg,'Warning');
            $logmsg .= $loghead.'[WARNING] '.$errmsg."\n ============================== \n\n";
            $this->writelog($logmsg);
            return false;
        }
        // set up counts for logging and start time
        $cnts = array('newtrk'=>0,'duptrk'=>0,'newalb'=>0,'newart'=>0,'newsng'=>0,'errtrk'=>0);
        $starttime = time();
        //default categories for albums, artists and songs
        $uncatid = XbcommonHelper::getCatByAlias('uncategorised');
        $this->albumcatid = $params->get('defcat_album',$uncatid);
        $this->artistcatid = $params->get('defcat_artist',$uncatid);
        $this->songcatid = $params->get('defcat_song',$uncatid);
        $this->trackcatid = $params->get('defcat_track',$uncatid);
        //track category may be overriden by genre (tracks-genres-genre) on per item basis
        if ($this->usedaycat == 1) {
            //we are going to change the defaults to a day category under \imports 
            $daycatid = 0;
            $daycattitle = date('Y-m-d');
            $dcparent = XbcommonHelper::checkValueExists('imports', '#__categories', 'alias', "`extension` = 'com_xbmusic'");
            if ($dcparent === false) {
                $catdata = array('title'=>'Imports', 'alias'=>'imports', 'description'=>'parent for import date categories used when importing items from MP3');
                $dcparent = XbcommonHelper::createCategory($catdata, true);
            }
            $daycatid = XbcommonHelper::checkValueExists($daycattitle, '#__categories', 'alias', "`extension` = 'com_xbmusic'");
            if ($this->trackcatid === false) {
                $catdata = array('title'=>$daycattitle, 'alias'=>$daycattitle, 'parent_id'=>$parentcat,'description'=>'items inported on '.date('D jS M Y'));
                $daycatid = XbcommonHelper::createCategory($catdata, true)->id;
            }
            if ($daycatid > 0) {
                $this->albumcatid = $daycatid;
                $this->artistcatid = $daycatid;
                $this->songcatid = $daycatid;
                $this->trackcatid = $daycatid;
            }
        } //endif cattype=1
        
        // ok we're going to iterate through the files
        $basemusicfolder = JPATH_ROOT.'/xbmusic/'; //XbmusicHelper::$musicBase;
        foreach ($files as $file) {
            $logmsg .= $this->parseID3($basemusicfolder.$file, $cnts);            
        }
        //update the log file with counts at the top
        $loghead .= '[SUM] '.$cnts['newtrk'].' new tracks, '.$cnts['duptrk'].' duplicates'."\n";
        $loghead .= '[SUM] '.$cnts['newalb'].' new albums, '.$cnts['newart'].' new artists, '.$cnts['newsng'].' new songs, '."\n";
        $loghead .= '[SUM] Elapsed time '.date('s', time()-$starttime).' seconds'."\n";
        $loghead .= " -------------------------- \n";
        $logmsg = $loghead.$logmsg;
        $logmsg .= '======================================'."\n\n";
        $this->writelog($logmsg);
        return true;
    } //end parseFilesMp3()    
    
    /**
     * @name parseID3()
     * @desc reads ID3 info from an mp3 file and if found creates track, album, artists and songs and saves the image file
     * @param string $filepathname - the file pathname from the JPATH_ROOT/xbmusic/ folder. It must exist if from file selector
     * @param array $cnts - updated with the counts of items porcessed
     * @return string - the loging info to be appended to the log file
     */
    public function parseID3(string $filepathname, array &$cnts) {
        //we'll need the params for category allocation and handling genres and image files
        $params = ComponentHelper::getParams('com_xbmusic');
//        $uncatid = XbmusicHelper::getCatByAlias('uncategorised');
        //strat the logging for this file
        $ilogmsg = INFO.str_replace(JPATH_ROOT,'',$filepathname)."\n";
        $enditem = " -------------------------- \n";
        $trackdata = []; //only one track per file
//        $albumdata = []; //only one album per file
//        $songdata = []; //only one song initially - if medley could be split later
//        $songlist = []; //will contain ids of songs to be linked to track
        $songids = [];
//        $artistdata = []; //can be mulitple artists, will attempt to split
//        $artistlist = []; //will contain artist ids to be linked to track and album
        $artistids = [];
        
        $fpathinfo = pathinfo($filepathname);

// 1. check if filepathname already in database, if it exists already then exit
        if ( $tid = XbcommonHelper::checkValueExists($filepathname, '#__xbmusic_tracks', 'filepathname')) {
            $ilogmsg .= '[ERROR] Track already in database with track_id='.$tid."\n";
            $ilogmsg .= $enditem;
            $cnts['duptrk'] ++;
            return $ilogmsg;
        }
// 2. set track->pathname and track->filename, if filename exists then warning
               // path and filename are stored separately in the database
//        $trackdata['pathname'] = str_replace(JPATH_ROOT.'/xbmusic/','',$fpathinfo['dirname']);
        // check if same filename exists in a different folder - import it anyway and warn 
        if ( $fid = XbcommonHelper::checkValueExists($fpathinfo['basename'], '#__xbmusic_tracks', 'filename')) {  
            $fpath = XbcommonHelper::getItemValue('#__xbmusic_tracks', 'filepathname', $fid);
            $ilogmsg .= '[WARNING] Filename already in database at '.$fpath.'with track_id='.$fid."\n";
            $ilogmsg .= '[WARNING] Importing this one anyway, but check and delete one or other if necessary'."\n";               
//                $cnts['duptrk'] ++;
        }
        
//3. okay, now get the id3 data
        $filedata = XbmusicHelper::getFileId3(($filepathname));
        
        if (!isset($filedata['id3tags']['title'])) { //could add any other required elements to the isset() function
            $ilogmsg .= '[ERROR] No title found in ID3 data. Cannot import'."\n";
            $ilogmsg .= $enditem;
            $cnts['errtrk'] ++;
            return $ilogmsg;          
        }

//4. get the basic trackdata from id3
        $id3data = XbmusicHelper::id3dataToItems($filedata['id3tags'],$ilogmsg);
        if (isset($id3data['trackdata'])) {
            $trackdata = $id3data['trackdata'];
            $trackdata['filepathname'] = $filepathname;
            $trackdata['filename'] = $fpathinfo['basename'];
            // get genres list, catids are defined above in parseFilesMp3()
            $optalbsong = $params->get('genrealbsong',0);
            $optcattag = $params->get('genrecattag',2);
            if (isset($id3data['genres'])) {
                $genreids = array_column($id3data['genres'],'id');
                if (($this->usedaycat == 0) && ($optcattag & 1)) {
                    //first check if we already have a cat for the genre
                    $thisgid = XbcommonHelper::getCatByAlias($id3data['genres'][0]['alias'])->id;
                    if (is_null($thisgid)) {
                        //get Genre in Tracks category
                        $gcat = XbcommonHelper::getCatByAlias('genres');
                        if ($gcat->id > 0) {
                            $gid = $gcat->id;
                        } else {
                            //we need to create the Tracks/Genres category
                            $tcat = XbcommonHelper::getCatByAlias('tracks');
                            $gpid = ($tcat->id > 0) ? $tcat->id : 1; //if the tracks category has been deleted fallback to root
                            $gid = XbcommonHelper::getCreateCat(array('title'=>'Genres', 'alias'=>'genres', 'parent_id'=>$gpid),true)->id;
                        }
                        $thisgid = XbcommonHelper::getCreateCat(array('title'=>$id3data['genres'][0]['title'], 'alias'=>$id3data['genres'][0]['alias'], 'parent_id'=>$gid),true)->id;
                    }
                    $trackdata['catid'] = $thisgid;
                    
                } else {
                    $trackdata['catid'] = $this->trackcatid;
                }
            } else {
                $trackdata['catid'] = $this->trackcatid;
            }
            
            if (isset($filedata['audioinfo']['playtime_seconds'])) $trackdata['duration'] = (int)$filedata['audioinfo']['playtime_seconds'];
            
            if (isset($id3data['genres'])) $trackdata['tags'] = $genreids;
            
            if (isset($filedata['imageinfo']['data'])){
                $imgdata = $filedata['imageinfo'];
                unset($filedata['imageinfo']['data']);
            }
            //save json encoded filedata with track for reference
            $trackdata['imageinfo'] = json_encode($filedata['imageinfo']);
            $trackdata['id3tags'] = json_encode($filedata['id3tags']);
            $trackdata['fileinfo'] = json_encode($filedata['fileinfo']);
            $trackdata['audioinfo'] = json_encode($filedata['audioinfo']);

//4. get the song(s) creating as necessary and make list of song ids to link to track, artist and album
            if (isset($id3data['songdata'])) {
                //create songs
                $songlinks = array();
                foreach ($id3data['songdata'] as $song) {
                    if ($song['id']==0) {
                        $song['catid'] = $this->songcatid;
                        if ($optalbsong & 1) $song['tags'] = $genreids;
                        $song['id'] = XbmusicHelper::createMusicItem($song, 'song');  
                        if ($song['id']) {
                            $cnts['newsng'] ++;
                            $ilogmsg .= INFO.Xbtext::_('new song saved',2).$song['id'].': '.Xbtext::_($song['title'],12);
                        }
                    } else {
                        if ($optalbsong & 1) $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.song', $song['id'], $genreids);
                        $ilogmsg .= INFO.$gadd.Xbtext::_('genres added to song',3).$song['id'].': '.Xbtext::_($song['title'],12);
                        
                    }
                    if ($song['id']>0) {
                        $song['role'] = '';
                        $song['note'] = '';
                        $songlinks[] = $song; //will be linked to album and track once we have ids
                    }
                }
            }
            
//5. get the artist(s) and create as necessary. make list of artists to link to track, album and song
            if (isset($id3data['artistdata'])) {
                foreach ($id3data['artistdata'] as $artist) {
                    if ($artist['id']==0) {
                        $artist['catid'] = $this->artistcatid;
                        $artist['id'] = XbmusicHelper::createMusicItem($artist, 'artist');
                        if ($artist['id']) {
                            $cnts['newart'] ++;
                            $ilogmsg .= INFO.Xbtext::_('new artist saved',2).Xbtext::_($artist['name'],12);
                        } else {
                            $ilogmsg .= WARN.Xbtext::_('problem saving artist',2).Xbtext::_($artist['name'],12);
                        }
                    }
                    if ($artist['id']>0) {
                        $artistlinks[] = array('id'=>$artist['id'], 'role'=>'', 'note'=>''); 
                        $ilogmsg .= INFO.XbmusicHelper::addItemLinks($artist['id'],$songlinks,'artist','artistsong')."\n";
                    }                   
                    //will be linked to album and track once we have ids for them
                }
                //link artists to songs
            }
            
//6. Create image file if available
            if (($imgdata['data'])){
                // filename will be "albums/X/album-alias_sortartist.ext" or "singles/track-alias_sortartist.ext"
                // saved in "images/xbmusic/artwork/albums/[initial letter of album title]/"
                // if track has no album then filename "track-title_sortartist.ext" and save in artwork/singles/
                $imgfilename = '/images/xbmusic/artwork/';
                if (isset($id3data['albumdata']['alias'])) {
                    $imgfilename .= 'albums/'.strtolower($id3data['albumdata']['alias'][0]).'/'.$id3data['albumdata']['alias'];
//                    if (isset($id3data['albumdata']['sortartist'])) {
//                        $imgfilename .= '_'.$id3data['albumdata']['sortartist'];
//                    }
                } else {
                    $imgfilename .= 'singles/'.$trackdata['alias'];
                    if (isset($trackdata['sortartist'])) $imgfilename .= '_'.$trackdata['sortartist'];
                }
                $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg)."\n";
                if ($imgurl != false) {
                    $trackdata['imgfile'] = $imgurl;
                } else {
                    //$ilogmsg .= Text::_('[WARN] failed to create image file').' '.$imgfilename."\n";
                }
            } //end ifset image data

//7. get album data and create album if necessary
            if (isset($id3data['albumdata'])) {
                $albumdata = $id3data['albumdata'];
                if ($albumdata['id'] == 0) {
                    $albumdata['catid'] = $this->albumcatid;
                    if ($optalbsong > 1) $albumdata['tags'] = $genreids;
                    if ($imgurl != false) $albumdata['imgfile'] = $imgurl;
                    $albumdata['id'] = XbmusicHelper::createMusicItem($albumdata, 'album');
                    if ($albumdata['id']>0) {
                        $cnts['newalb'] ++;                       
                        $ilogmsg .= INFO.Xbtext::_('new album saved',2).Xbtext::_($albumdata['title'],8);
                    } else {
                        $existing = XbcommonHelper::getItem('#__xbmusic_albums', $albumdata['alias'], 'alias','',true);
                        if ($existing) {
                            $existing['tags'] = array_unique(array_merge($existing['tags'],$genreids));
                            $albumdata = array_merge($existing, $albumdata);
                            $ilogmsg .= INFO.Xbtext::_('linking to existing album',2).Xbtext::_($albumdata['title'],8);             
                        }
                    }                    
                } else {
                    if ((is_array($genreids)) && ($optalbsong > 1)) $gadd = XbcommonHelper::addTagsToItem('com_xbmusic.album', $albumdata['id'], $genreids);
                    $ilogmsg .= INFO.$gadd.Xbtext::_('genres added to album',3).$albumdata['id'].': '.Xbtext::_($albumdata['title'],12);
                }
                if ($albumdata['id']>0) {
                    $trackdata['album_id'] = $albumdata['id'];
                    //link artists to album
                    $ilogmsg .= INFO.XbmusicHelper::addItemLinks($albumdata['id'],$songlinks, 'album','songalbum')."\n";
                    //link songs to album
                    $ilogmsg .= INFO.XbmusicHelper::addItemLinks($albumdata['id'],$artistlinks, 'album','artistalbum')."\n";
                }
            }
                         
            $trackid = XbmusicHelper::createMusicItem($trackdata, 'track');
            if ($trackid>0) {
                //link artists to track
                $ilogmsg .= INFO.XbmusicHelper::addItemLinks($trackid, $songlinks, 'track', 'songtrack')."\n";
                //links songs to track
                $ilogmsg .= INFO.XbmusicHelper::addItemLinks($trackid, $artistlinks, 'track','artisttrack')."\n";
                $cnts['newtrk'] ++;
                $ilogmsg .= INFO.Text::_('new track saved').' '.$trackdata['id'].': '.Xbtext::_($trackdata['title'],13);
            }
             
        } //end if iset id3data[trackdata]
        return $ilogmsg;
    } //end parseID3()
        
        
/*
// 10. Create image file
        if (isset($filedata['imageinfo']['data'])){
            
            // if album is set image filename will be "album-title_sortartist.ext"
            // saved in "images/xbmusic/artwork/albums/[initial letter of album title]/"
            // if track has no album then filename "track-title_sortartist.ext" and save in artwork/singles/
            $imgfilename = '/images/xbmusic/artwork/';
            if (isset($albumdata['alias'])) {
                $imgfilename .= 'albums/'.strtolower($albumdata['alias'][0]).'/'.$albumdata['alias'];
            } else {
                $imgfilename .= 'singles/'.$trackdata['alias'];
            }
            if (isset($trackdata['sortartist'])) $imgfilename .= '_'.$trackdata['sortartist'];
            $imgurl = XbmusicHelper::createImageFile($imgdata, $imgfilename, $ilogmsg);
            if ($imgurl != false) {
                $trackdata['imgfile'] = $imgurl;
                $ilogmsg .= Text::_('[INFO] image file created').' '.str_replace(Uri::root(),'',$imgurl)."\n";
            } else {
                $ilogmsg .= Text::_('[WARN] failed to create image file').' '.$imgurl."\n";
            }
        } //end ifset image data
 */       

    private function createGenres($genrenames, &$ilogmsg) {
        $params = ComponentHelper::getParams('com_xbmusic');
        $opthyphen = $params->get('genrehyphen',1);
        $optspaces = $params->get('genrespaces',1);
        $optcase = $params->get('genrecase',1);
        $genres = [];
        $genrenames = explode(' || ', $genrenames);
        //split name with spaces into two or more genres "Folk Rock" -> "Folk" | "Rock"
        if ($optspaces == 3) {
            $tempnames = array();
            foreach ($tempnames as $genre) {
                $multi = explode(' ',$genre);
                $newgenrenames = array_merge($tempnames, $multi);
                if (count($multi) > 1) {
                    $ilogmsg .= '"'.$genre.Text::sprintf("split into %s genres.\n",count($multi),2);
                }
            }
            $genrenames = $newgenrenames;
        }
        foreach ($genrenames as &$genre) {
            $cnt = 0;
            if ($opthyphen == 1) {
                $genre = str_replace('/','-',$genre, $cnt);
            } elseif ($opthyphen==2) {
                $genre = str_replace('-','/',$genre, $cnt);
            }
            if ($optspaces == 1)  {
                $genre = str_replace(' ','-',$genre, $cnt);
            } elseif ($optspaces == 2) {
                $genre = str_replace(' ','/',$genre, $cnt);
            }
            if ($cnt > 0) $ilogmsg .= $genre.' normalized'."\n";
            $genre = strtolower($genre);
            if ($optcase == 2) {
                $genre = ucfirst($genre);
            }
            //get the parent tag for genre tags
            $tpid = XbcommonHelper::getCreateTag(array('title'=>'Genres'));
            //get or create the genre tag id and title
            $newtag = XbcommonHelper::getCreateTag(array('title'=>$genre, 'parent_id'=>$tpid), true);
            if ($newtag) $genres[] = $newtag;
        } //end foreach genre
        return $genres;
    }
    
    private function createImageFile($filedata, string $imgfilename, string &$flogmsg) {
        $albumtitle = '';
        $imgpath = '/images/xbmusic/artwork/singles/';
        if (isset($filedata['id3tags']['album'])) {
            $albumarr = explode(' || ', $filedata['id3tags']['album']);
            $albumtitle = $albumarr[0];
            if ($albumtitle != '') {
                $imgpath = '/images/xbmusic/artwork/albums/'.strtolower($albumtitle[0]).'/';
            }
        }
        //create the folder if it doesn't exist (eg new initial)
        if (file_exists($imgpath)==false) {
            mkdir(JPATH_ROOT.$imgpath,0775,true);
            $flogmsg .= Text::_('[INFO] folder created').' '.$imgpath."\n";
        }
        $imgext = XbcommonHelper::imageMimeToExt($filedata['imageinfo']['image_mime']);
        $imgfilename = OutputFilter::stringURLSafe(str_replace(' & ',' and ', $imgfilename)).'.'.$imgext;
        $imgpathfile = JPATH_ROOT.$imgpath.$imgfilename;
        $imgurl = Uri::root().$imgpath.$imgfilename;
        $imgok = false;
        if (file_exists($imgpathfile)) {
            $imgok = true;
            $flogmsg .= Text::sprintf('[INFO] Artwork file %s already exists',$imgfilename)."\n";
        } else {
            $params = ComponentHelper::getParams('com_xbmusic');
            $maxpx = $params->get('imagesize',500);
            if ($filedata['imageinfo']['image_height'] > $maxpx) {
                //need to resize image
                $image = imagecreatefromstring($filedata['imageinfo']['data']);
                $newimage = imagescale($image, $maxpx);
                switch ($imgext) {
                    case 'jpg':
                        $imgok = imagejpeg($newimage, $imgpathfile);
                        break;
                    case 'png':
                        $imgok = imagejpeg($newimage, $imgpathfile);
                        break;
                    case 'gif':
                        $imgok = imagejpeg($newimage, $imgpathfile);
                        break;
                    default:
                        $imgok = false;
                        break;
                }
            } else {
                $imgok = file_put_contents($imgpathfile, $filedata['imageinfo']['data']);
            }
        } //endif artfile !exists
        if ($imgok) return $imgurl;
        
        return false;
    }
    
    public function getLastImportLog() {
        $file = null;
        foreach(new DirectoryIterator(JPATH_ROOT.'/xbmusic-logs') as $item) {
            if ($item->isFile() && (empty($file) || $item->getMTime() > $file->getMTime())) {
                $file = clone $item;
 //               $pathname = ;
            }
        }
        if (!is_null($file)) {
//            if (file_exists($file->getPathname)) { //do we need this? surely it exists
                $logtxt = file_get_contents($file->getPathname());
                return $logtxt;
//            }
        }
        return '';
        
//        return file_get_contents($file->getPathname);
    }
    
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
        $pathname = JPATH_ROOT.'/xbmusic-logs/'.$filename;
        return file_get_contents($pathname);
        $logstr = '';
        if ((file_exists($pathname)) && (filesize($pathname) > 0)) {
            $f = fopen($pathname,'r');
            if ($f) {
                $logstr = fread($f, filesize($pathname));
                fclose($f);
            } else {
                Factory::getApplication()->enqueueMessage('readLog() Could not open file <code>/xbxbmusic-logs/'.$filename.'</code> - is it locked?', 'Warning');
            }
        }
        return $logstr;
    }
    
    /**
     * @name createMusicItem()
     * @desc Creates an xbMusic item with supplied data. status, access, created & modified dates will be default values if missing, , Created_by will be set to user, alias will be created from title/name if missing
     * @param array $data
     * @param string $table
     * @param boolean $silent
     * @return \stdClass
     */
    public function createMusicItem(array $data, string $itemtype, $silent = false) {
        if (strpos(' track song playlist artist album ', $itemtype) == false) {
            if (!$silent) Factory::getApplication()->enqueueMessage('Invalid itemtype to create','Error');
            return false;
        }
        $app = Factory::getApplication();
        $itemid = false;
        $createmoddate = Factory::getDate()->toSql();
        $user 	=Factory::getApplication()->getIdentity();
        if (!key_exists('status', $data))  $data['status'] = 1;
        if (!key_exists('access', $data))  $data['access'] = 1;
        if (!key_exists('created', $data))  $data['created'] = $createmoddate;
        if (!key_exists('modified', $data))  $data['modified'] = $createmoddate;
        if (!key_exists('created_by', $data))  $data['created_by'] = $user;
        
        $itemmodel = $this->getMVCFactory()->createModel(ucfirst($itemtype), 'Administrator', ['ignore_request' => true]);
        // Factory::getApplication()->bootComponent('com_categories')
        //->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
        if (!$itemmodel->save($data)) {
            if (!$silent) Factory::getApplication()->enqueueMessage('createMusicItem() '.$itemtype.' '.$itemmodel->getError(), 'Error');
            return false;
        }
        $itemid = $itemmodel->getState($itemtype.'.id');
        return $itemid;
    }
    
    
}
