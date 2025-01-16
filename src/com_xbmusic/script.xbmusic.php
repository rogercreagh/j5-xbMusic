<?php
/*******
 * @package xbMusic
 * @filesource script.xbmusic.php
 * @version 0.0.19.5 10th January 2025
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\CMS\Uri\Uri;

class Com_xbmusicInstallerScript extends InstallerScript
{
    protected $jminver = '4.0';
    protected $jmaxver = '6.0';
    protected $extension = 'com_xbmusic';
    protected $extname = 'xbMusic';
    protected $extslug = 'xbmusic';
    protected $ver = 'v1.2.3.4';
    protected $date = '32nd January 2024';
    protected $oldver = 'v1.2.3.4';
    protected $olddate = '32nd January 2024';
    
    function preflight($type, $parent) {
        $jversion = new Version();
        $jverthis = $jversion->getShortVersion();
        if ((version_compare($jverthis, $this->jminver,'lt')) || (version_compare($jverthis, $this->jmaxver, 'ge'))) {
            throw new RuntimeException($this->extname.' requires Joomla version greater than '.$this->jminver. ' and less than '.$this->jmaxver.'. You have '.$jverthis);
        }
        // if we are updating then get the old version and date from component xml before it gets overwritten.
        if ($type=='update') {
            $componentXML = Installer::parseXMLInstallFile(Path::clean(JPATH_ADMINISTRATOR . '/components/'.$this->extension.'/'.$this->extslug.'.xml'));
            $this->oldver = $componentXML['version'];
            $this->olddate = $componentXML['creationDate'];
            //            Factory::getApplication()->enqueueMessage('Updating '.$this->extname.' from '.$this->oldver.' '.$this->olddate.' to '.$parent->getManifest()->version);
        }
    }
    
    function install($parent) {
    }
  
    
    function uninstall($parent) {
        $app = Factory::getApplication();
        $message = 'Uninstalling '.$this->extname.' component v.'.$parent->getManifest()->version.' '.$parent->getManifest()->creationDate.'<br />';
        $params = ComponentHelper::getParams('com_xbmusic');
        $savedata = $params->get('savedata',0);
        if ($savedata == 0) {  //we are deleting data and images          
            if ($this->uninstallData()) {
                $message .= ' ...xbMusic data tables deleted';
            }
            if ($params->get('savefiles',1)==0) { //also deleting music
                //need to first unlink any symlinked external folders or folder::delete fails
                $folders = glob(JPATH_ROOT . '/xbmusic/*' , GLOB_ONLYDIR);
                if (!empty($folders)) {
                    foreach ($folders AS $folder) {
                        if (is_link($folder)) {
                            unlink($folder) ;
                        }
                    }
                }                
                if (Folder::delete(JPATH_ROOT.'/xbmusic')){
                    $message .= ' .../xbmusic folder and files deleted. NB SymLink targets have not been deleted';
                } else {
                    $err = 'Problem deleting /xbmusic - please check';
                    $app->enqueueMessage($err,'Error');
                }
                $dest='/images/xbmusic';
                if (Folder::exists(JPATH_ROOT.$dest)) {
                    if (Folder::delete(JPATH_ROOT.$dest)){
                        $message .= ' ...images/xbmusic folder deleted';
                    } else {
                        $err = 'Problem deleting xbMusic images folder "/images/music" - please check in Media manager';
                        $app->enqueueMessage($err,'Error');
                    }
                }
                $dest='/xbmusic-logs';
                if (Folder::exists(JPATH_ROOT.$dest)) {
                    if (Folder::delete(JPATH_ROOT.$dest)){
                        $message .= ' .../xbmusic-logs folder deleted';
                    } else {
                        $err = 'Problem deleting xbMusic Logs folder "/xbmusic-logs" - please check';
                        $app->enqueueMessage($err,'Error');
                    }
                }
            } else {
                $message .= '<br /><b>/xbMusic files (images, music and logs) have NOT been deleted.';
            }
            
        } else {
            $message .= ' xbMusic data tables and files have <b>NOT</b> been deleted. CATEGORIES may be recovered on re-install, but TAG links will be lost although tags themselves have not been deleted.';
            $db = Factory::getDbo();
            $query = $db->getQuery(true);
//             //check if !xbmusic! params cat still exists - if it does update
//             $query->select('id')->from($db->qn('#__categories'))
//                 ->where($db->qn('extension').'='.$db->q('!com_xbmusic!').' AND '. $db->qn('title').'='.$db->q('params'));
//             $db->setQuery($query);
//             if ($db->loadResult()>0) {
//                 //update
//                 $query->clear();
//                 $query->update('#__categories')
//                 ->set('description='.$db->q(json_encode($params)))
//                 ->where($db->qn('extension').'='.$db->q('!com_xbmusic!').' AND '. $db->qn('title').'='.$db->q('params')
//                 );
//                 $db->setQuery($query);
//                 $db->execute();
//             } else {
//                 //create
//                 //$query->clear();
                $mess = $this->createCategories(array(array('title'=>'params','desc'=>json_encode($params))));
                $app->enqueueMessage($mess,'warning');                
//             }
//             $query->clear();
            $query->update('#__categories')
                ->set('extension='.$db->q('Xcom_xbmusicX'))
                ->where('extension='.$db->q('com_xbmusic'));
            $db->setQuery($query);
            $db->execute();
            $cnt = $db->getAffectedRows();
            
            if ($cnt>0) {
                $message .= $cnt.' xbMusic categories.extension renamed as "<b>X</b>com_xbmusic<b>X</b>". They will be recovered on reinstall with original ids.';
            }
        }
        $app->enqueueMessage($message,'Info');
    }
    
    function update($parent) {
    }
    
    function postflight($type, $parent) {
        $app = Factory::getApplication();
        $manifest = $parent->getManifest();
        $ext_mess = '<div style="position: relative; margin: 15px 15px 15px -15px; padding: 1rem; border:solid 1px #444; border-radius: 6px;">';
        $message = '';
        if ($type == 'update') {
            //set message so that at least something is displayed if com_installed update bug not fixed
            $app->enqueueMessage('Updated '.$this->extname.' component from '.$this->oldver.' to v'.$parent->getManifest()->version.' Please see <a href="index.php?option=com_xbmusic">Dashboard</a> for more info.');
            
            $ext_mess .= '<p><b>'.$this->extname.'</b> component has been updated from '.$this->oldver.' of '.$this->olddate;
            $ext_mess .= ' to v<b>'.$manifest->version.'</b> dated '.$manifest->creationDate.'</p>';
        }
        if (($type=='install') || ($type=='discover_install')) {
            $ext_mess .= '<h3>'.$this->extname.' component installed</h3>';
            $ext_mess .= '<p>version '.$manifest->version.' dated '.$manifest->creationDate.'</p>';
            $ext_mess .= '<p><b>Important</b> Before starting review &amp; set the component options&nbsp;&nbsp;';
            $ext_mess .=  '<a href="index.php?option=com_config&view=component&component='.$this->extension.'" class="btn btn-small btn-info" style="color:#fff;">'.$this->extname.' Options</a>';
            $db = Factory::getDbo(); 
            $query = $db->getQuery();
            // Recover categories if they exist assigned to extension !com_xbmusic!
            $query->clear();
            $query->update('#__categories')
                ->set('extension='.$db->q('com_xbmusic'))
                ->where('extension='.$db->q('Xcom_xbmusicX'));
            $db->setQuery($query);
            try {
                $db->execute();
                $cnt = $db->getAffectedRows();
            } catch (Exception $e) {
                $app->enqueueMessage($e->getMessage(),'Error');
            }
            $message .= $cnt.' existing xbMusic categories restored. ';
            //if we've got a params category then restore params and delete the category
            $query->clear();
            $paramwhere = $db->qn('extension').'='.$db->q('com_xbmusic').' AND '. $db->qn('title').'='.$db->q('params');
            $query->select('description')->from($db->qn('#__categories'));
            $query->where($paramwhere);
            $db->setQuery($query);
            $recparams = $db->loadResult();
            if ($recparams != '') {
                $query->clear();
                $query->update('#__extensions');
                $query->set('params='.$db->q($recparams))
                    ->where('name='.$db->q('com_xbmusic'));
                $db->setQuery($query);
                try {
                    $db->execute();
                    $cnt = $db->getAffectedRows();
                } catch (Exception $e) {
                    $app->enqueueMessage($e->getMessage(),'Error');
                }
                $query->clear();
                $query->delete('#__categories')->where($paramwhere);
                $db->setQuery($query);
                try {
                    $db->execute();
                    $cnt = $db->getAffectedRows();
                } catch (Exception $e) {
                    $app->enqueueMessage($e->getMessage(),'Error');
                }
                if ($cnt>0) $message.= 'com_xbmusic options recovered. Check values before proceeding.';
            }
            // create default categories using category table if they haven't been recovered
            $cats = array(
                array("title"=>"Uncategorised","desc"=>"default fallback category for all xbMusic items"),
                array("title"=>"Albums","desc"=>"default parent category for xbMusic Albums"),
                array("title"=>"Artists","desc"=>"default parent category for xbMusic Artists"),
                array("title"=>"Playlists","desc"=>"default parent category for xbMusic Playlists"),
                array("title"=>"Songs","desc"=>"default parent category for xbMusic Songs"),
                array("title"=>"Imports","desc"=>"parent category for imported date subcats"),
                array("title"=>"Tracks","desc"=>"default parent category for xbMusic Tracks")
            );
            $message .= $this->createCategories($cats).'<br />';
            
            $message .= $this->createTag('MusicGenres').'<br />';
            
            //create a top level tag to be parent for id3genre tags
//            $this->createTag(array('title'=>'Id3Genres', 'parent_id'=>1, 'published'=>1, 'description'=>'Parent tag for ID3 genres. Do not remove, genres will be added automatically from track files.'));
            
            //create xbmusic image folder. Check in case left after previous uninstall
            $imgroot = JPATH_ROOT.'/images/xbmusic/';
            if (!file_exists($imgroot.'artwork/albums/')) mkdir($imgroot.'artwork/albums/',0775,true);
            if (!file_exists($imgroot.'artwork/singles/')) mkdir($imgroot.'artwork/singles/',0775,true);
            if (!file_exists($imgroot.'artists/')) mkdir($imgroot.'artists/',0775,true);
            $message .= 'Music image folders created in <code>/images/xbmusic/</code>.<br />';
            //create /xbmusic folder
            if (!file_exists(JPATH_ROOT.'/xbmusic')) {
                mkdir(JPATH_ROOT.'/xbmusic',0775);
                $message .= 'Music files folder <code>/xbmusic/</code> created.<br />';
            } else{
                $message .= 'Music files folder <code>/xbmusic/</code> already exists.<br />';
            }
            if (!file_exists(JPATH_ROOT.'/xbmusic-logs')) {
                mkdir(JPATH_ROOT.'/xbmusic-logs',0775);
                $message .= 'Log files folder <code>/xbmusic-logs/</code> created.<br />';
            } else{
                $message .= 'Log files folder <code>/xbmusic-logs/</code> already exists.<br />';
            }
            
            $message .= '<br />Checking indicies... ';
            $this->createAliasIndex(array('album','artist','playlist','song','track'));
            
            Factory::getApplication()->enqueueMessage($message,'Info');
        }
        if (($type=='install') || ($type=='discover_install') || ($type == 'update')) {
            $ext_mess .= '<p>For help and information see <a href="https://crosborne.co.uk/'.$this->extslug.'/doc" target="_blank" style="font-weight:bold; color:black;">www.crosborne.co.uk/'.$this->extslug.'/doc</a> ';
            $ext_mess .= 'or use Help button in <a href="index.php?option='.$this->extension.'" class="btn btn-small btn-info" style="color:#fff;">'.$this->extname.' Dashboard</a></p>';
            $ext_mess .= '</div>';
            echo $ext_mess;
        }
        return true;
    }
    
    /**
     * @name createCategories()
     * @param array $cats
     * @return string message
     */
    public function createCategories(array $cats, $ext = '') {
        if ($ext == '') $ext = 'com_xbmusic';
        $message = 'Creating '.$ext.' categories. ';
        $db = Factory::getDBO();
        foreach ($cats as $cat) {
            if (key_exists('title',$cat)) {
                $query = $db->getQuery(true);
                //first check if category already exists
                $query->select('id')->from($db->quoteName('#__categories'))
                ->where($db->quoteName('title')." = ".$db->quote($cat['title']))
                ->where($db->quoteName('extension')." = ".$db->quote($ext));
                $db->setQuery($query);
                if ($db->loadResult()>0) {
                    $message .= '"'.$cat['title'].' already exists<br /> ';
                } else {
                    $category = Table::getInstance('Category');
                    $category->extension = $ext;
                    $category->title = $cat['title'];
                    $category->alias = $cat['title'];
                    $category->description = $cat['desc'];
                    $category->published = 1;
                    $category->access = 1;
                    $category->params = '{"category_layout":"","image":"","image_alt":""}';
                    $category->metadata = '{"page_title":"","author":"","robots":""}';
                    $category->language = '*';
                    // Set the location in the tree
                    $category->setLocation(1, 'last-child');
                    // Check to make sure our data is valid
                    if ($category->check()) {
                        if ($category->store(true)) {
                            // Build the path for our category
                            $category->rebuildPath($category->id);
                            $message .= $cat['title'].' :'.$category->id.', ';
                        } else {
                            throw new Exception(500, $category->getError());
                            //return '';
                        }
                    } else {
                        throw new Exception(500, $category->getError());
                        //return '';
                    }                  
                }
            }
        }
        return $message;
    }
    
    /**
     * @name createTag()
     * @desc function to create a tag with title, parent_tag_id, status, and optional description
     * @param assoc array $tagdata
     * @return boolean|int new (or existing) tagid of false in case of error
     */
    public function createTag($title) {
        $db = Factory::getDbo();
        //$db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);
        $query->select('id')->from($db->quoteName('#__tags'))
        ->where('LOWER('.$db->quoteName('title').')='.$db->quote(strtolower($title)));
        $db->setQuery($query);
        $res = $db->loadResult();
        if ($res > 0) {
            return 'Tag '.$title.' already exists';
        }
        // doesnt already exist so set defaults for status & parent
        $tagdata = array('title'=>$title, 'published'=>1, 'parent_id'=>1, 'language'=>'*', 'description'=>'Parent tag for genres. Do not remove, new genres may be added automatically from track files.');
        // Create new tag.
        $tagModel = Factory::getApplication()->bootComponent('com_tags')
            ->getMVCFactory()->createModel('Tag', 'Administrator', ['ignore_request' => true]);
         if (!$tagModel->save($tagdata)) {
                return '<span style="color:red;">'.$tagModel->getError().'</span>';
            } else {
                $tagid = $tagModel->getState('tag.id');
                return 'New tag '.$title.' created';
            }
            return '';
        }
        
        protected function uninstallData() {
            $message = '';
            $db = Factory::getDBO();
            $db->setQuery('DROP TABLE IF EXISTS   
              `#__xbmusic_albums`,
              `#__xbmusic_artists`,
              `#__xbmusic_playlists`,
              `#__xbmusic_songs`,
              `#__xbmusic_tracks`,
              `#__xbmusic_artisttrack`,
              `#__xbmusic_artistsong`,
              `#__xbmusic_medleytrack`,
              `#__xbmusic_playlisttrack`,
              `#__xbmusic_songtrack`,
              `#__xbmusic_artistgroup`,
              `#__xbmusic_artistalbum`,
              `#__xbmusic_songalbum`
            ');
            $res = $db->execute();
            if ($res === false) {
                $message = 'Error deleting xbMusic tables, please check manually';
                Factory::getApplication()->enqueueMessage($message,'Error');
                return false;
            }
            return true;
        }
        
        protected function createAliasIndex(array $types) {
            $db = Factory::getDbo();
//            $prefix = Factory::getApplication()->get('dbprefix');
//            $querystr = 'ALTER TABLE '.$prefix.'xbfilms ADD INDEX filmaliasindex (alias)';
            $errmsg = '';
            foreach ($types as $type) {
                $querystr = 'ALTER TABLE `#__xbmusic_'.$type.'s` ADD INDEX `'.$type.'aliasindex` (`alias`)';
                try {
                    $db->setQuery($querystr);
                    $db->execute();
                } catch (Exception $e) {
                    if($e->getCode() == 1061) {
                        $errmsg = $type.' alias index already exists. ';
                        Factory::getApplication()->enqueueMessage($errmsg, 'Warning');
                    } else {
                        $errmsg .= '[ERROR creating '.$type.'.aliasindex: '.$e->getCode().' '.$e->getMessage().']<br />';
                    }
                }          
            }
            if ($errmsg !='') Factory::getApplication()->enqueueMessage($errmsg, 'Error');
       }
        
        
/*         
        Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_tags/tables');
        $app = Factory::getApplication();
        //check if alias already exists (dupe id)
        $alias = $tagdata['title'];
        $alias = ApplicationHelper::stringURLSafe($alias);
        if (trim(str_replace('-', '', $alias)) == '') {
            $alias = Factory::getDate()->format('Y-m-d-H-i-s');
        }
        $db = Factory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id')->from($db->quoteName('#__tags'))->where($db->quoteName('alias').' = '.$db->quote($alias));
        $db->setQuery($query);
        $id = $db->loadResult();
        if ($id>0) {
            $app->enqueueMessage('Tag with alias '.$alias.' already exists with id '.$id, 'Warning');
            return $id;
        }
        
        $table = Table::getInstance('Tag', 'TagsTable', array());
        // Bind data
        if (!$table->bind($tagdata)) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        // Check the data.
        if (!$table->check()) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        // set the parent details
        $table->setLocation($tagdata['parent_id'], 'last-child'); //no error reporting from this one
        // Store the data.
        if (!$table->store()){
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        if (!$table->rebuildPath($table->id)) {
            $app->enqueueMessage($table->getError(),'Error');
            return false;
        }
        $app->enqueueMessage('New tag '.$tagdata['title'].' created with id '.$table->id);
        return $table->id;
    }
    
 */
}
