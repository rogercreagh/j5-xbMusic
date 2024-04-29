<?php
/*******
 * @package xbMusic
 * @filesource script.xbmusic.php
 * @version 0.0.4.1 28th April 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Version;
use Joomla\Filesystem\Path;
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
        $message = 'Uninstalling '.$this->extname.' component v.'.$parent->getManifest()->version.' '.$parent->getManifest()->creationDate;
        Factory::getApplication()->enqueueMessage($message,'Info');
    }
    
    function update($parent) {
    }
    
    function postflight($type, $parent) {
        $app = Factory::getApplication();
        $manifest = $parent->getManifest();
        $ext_mess = '<div style="position: relative; margin: 15px 15px 15px -15px; padding: 1rem; border:solid 1px #444; border-radius: 6px;">';
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
            $ext_mess .=  '<a href="index.php?option=com_config&view=component&component='.$this->extension.'" class="btn btn-small btn-info">'.$this->extname.' Options</a>';
            //$res = $this->createCssFromTmpl();
            // create default categories using category table if they haven't been recovered
            $cats = array(
                array("title"=>"Uncategorised","desc"=>"default fallback category for all xbMusic items"),
                array("title"=>"Albums","desc"=>"default parent category for xbMusic Albums"),
                array("title"=>"Artists","desc"=>"default parent category for xbMusic Artists"),
                array("title"=>"Playlists","desc"=>"default parent category for xbMusic Playlists"),
                array("title"=>"Songs","desc"=>"default parent category for xbMusic Songs"),
                array("title"=>"Tracks","desc"=>"default parent category for xbMaps Tracks")
            );
            $message .= $this->createCategories($cats);
            
            //create xbmusic image folder
            if (!file_exists(JPATH_ROOT.'/images/xbmusic')) {
                mkdir(JPATH_ROOT.'/images/xbmusic',0775);
                $message .= 'Music images folder created (/images/xbmusic/).<br />';
            } else{
                $message .= '"/images/xbmusic/" already exists.<br />';
            }
            //create /xbmusic folder
            if (!file_exists(JPATH_ROOT.'/xbmusic')) {
                mkdir(JPATH_ROOT.'/xbmusic',0775);
                $message .= 'Music folder created (/xbmusic/).<br />';
            } else{
                $message .= '"/xbmusic/" already exists.<br />';
            }
            
            
            Factory::getApplication()->enqueueMessage($message,'Info');
            
            
        }
        if (($type=='install') || ($type=='discover_install') || ($type == 'update')) {
            $ext_mess .= '<p>For help and information see <a href="https://crosborne.co.uk/'.$this->extslug.'/doc" target="_blank">www.crosborne.co.uk/'.$this->extslug.'/doc</a> ';
            $ext_mess .= 'or use Help button in <a href="index.php?option='.$this->extension.'" class="btn btn-small btn-info">'.$this->extname.' Dashboard</a></p>';
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
    public function createCategories(array $cats) {
        $message = 'Creating '.$this->extension.' categories. ';
        $db = Factory::getDBO();
        foreach ($cats as $cat) {
            if (key_exists('title',$cat)) {
                $query = $db->getQuery(true);
                //first check if category already exists
                $query->select('id')->from($db->quoteName('#__categories'))
                ->where($db->quoteName('title')." = ".$db->quote($cat['title']))
                ->where($db->quoteName('extension')." = ".$db->quote('com_xbmusic'));
                $db->setQuery($query);
                if ($db->loadResult()>0) {
                    $message .= '"'.$cat['title'].' already exists<br /> ';
                } else {
                    $category = Table::getInstance('Category');
                    $category->extension = $this->extension;
                    $category->title = $cat['title'];
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
                            $message .= $cat['title'].' id:'.$category->id.' created ok. ';
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
    
    
}
