<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Model/AlbumModel.php
 * @version 0.0.18.8 8th November 2024
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2024
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\InputFilter;
//use Joomla\CMS\Form\Form;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
//use Joomla\CMS\Plugin\PluginHelper;
//use Joomla\CMS\String\PunycodeHelper;
//use Joomla\CMS\Table\Table;
//use Joomla\CMS\Table\TableInterface;
//use Joomla\CMS\UCM\UCMType;
//use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
//use Joomla\Database\ParameterType;
use Joomla\Filter\OutputFilter;
use Joomla\Registry\Registry;
//use Joomla\Component\Categories\Administrator\Helper\CategoriesHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \SimpleXMLElement;
//use Symfony\Component\Validator\Constraints\IsNull;

class AlbumModel extends AdminModel {
  
    public $typeAlias = 'com_xbmusic.album';
    
    protected $xbmusic_batch_commands = array(
        'untag' => 'batchUntag',
    );
    
    public function batch($commands, $pks, $contexts) {
        $this->batch_commands = array_merge($this->batch_commands, $this->xbmusic_batch_commands);
        return parent::batch($commands, $pks, $contexts);
    } 
    
    protected function batchUntag($value, $pks, $contexts) {
        $taghelper = new TagsHelper();
        $message = 'tag:'.$value.' removed from albums :';
        foreach ($pks as $pk) {
            if ($this->user->authorise('core.edit', $contexts[$pk])) {
                $existing = $taghelper->getItemTags('com_xbmusic.album', $pk, false);
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
        
        return $this->getCurrentUser()->authorise('core.delete', 'com_xbmusic.album.' . (int) $record->id);
    }
    
    protected function canEditState($record) {
        $user = $this->getCurrentUser();
        
        // Check for existing album.
        if (!empty($record->id)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.album.' . (int) $record->id);
        }
        
        // New album, so check against the category.
        if (!empty($record->catid)) {
            return $user->authorise('core.edit.state', 'com_xbmusic.category.' . (int) $record->catid);
        }
        
        // Default to component settings if neither album nor category known.
        return parent::canEditState($record);
    }
    
    protected function prepareTable($table) {
        
        // Reorder the albums within the category so the new album is first
        if (empty($table->id)) {
            $table->reorder('catid = ' . (int) $table->catid . ' AND status >= 0');
        }
    }
    
    public function getItem($pk = null) {
        if ($item = parent::getItem($pk)) {
            if (!empty($item->id)) {
                $tagsHelper = new TagsHelper();
                $item->tags = $tagsHelper->getTagIds($item->id, 'com_xbmusic.album');                
            }
        }        
        return $item;
    }
    
    public function getForm($data = [], $loadData = true) {
        $app  = Factory::getApplication();
        $params = ComponentHelper::getParams('com_xbmusic');
        
        // Get the form.
        $form = $this->loadForm('com_xbmusic.album', 'album', ['control' => 'jform', 'load_data' => $loadData]);
        
        if (empty($form)) {
            return false;
        }
        
        //dynamically add fields for any taggroups defined in options and add the tags for them
        $tagsarr = explode(',',$form->getValue('tags',null,''));
        $parentids = $params->get('albumtagparents',[]);
        if (!empty($parentids)) {
            $taghelp = new TagsHelper;
            $parr = $taghelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                $element = new SimpleXMLElement('<field name="'.$groupname.'" type="xbtags" label="'.ucfirst($parent).' Group" mode="nested" multiple="true" custom="deny" parent="'.$pid.'" class="xbtags" />');
                $form->setField($element, null, true, 'taggroups');
                if (!empty($tagsarr)){
                    $groupnametags = $taghelp->getTagTreeArray($pid);
                    $grouptags = array_intersect($groupnametags, $tagsarr);
                    $form->setValue($groupname,null,$grouptags);
                }
            }
        } // endforeach parenttag
        
        return $form;
    }
    
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $app  = Factory::getApplication();
        $data = $app->getUserState('com_xbmusic.edit.album.data', []);
        
        if (empty($data)) {
            $data = $this->getItem();
            $data->tracklist = $this->getAlbumTrackList($data->id);
            
            $retview = $app->input->get('retview','');
            // Pre-select some filters (Status, Category, Language, Access) in edit form if those have been selected in Article Manager: Articles
            if (($this->getState('album.id') == 0) && ($retview != '')) {
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
                $data['title'] = $title;
                $data['alias'] = $alias;
            } else {
                if ($data['alias'] == $origTable->alias) {
                    $data['alias'] = '';
                }
            }
            //need to add albumlinks
            $this->storeAlbumTracks($origTable->id, $data['tracklist']);
            // standard Joomla practice is to set the new copy record as unpublished
            $data['status'] = 0;
        }
        
       
        //alias is the title so we'll set and check it every time
        $newalias = OutputFilter::stringURLSafe($data['title']);
        if (($data['id'] == 0) && XbcommonHelper::checkValueExists($newalias, '#__xbmusic_albums', 'alias')) {
            $warnmsg .= 'Duplicate alias - this album title is already in the database';
            $app->enqueueMessage($warnmsg,'Error');
            return false;
        }
        $data['alias'] = $newalias;        
        
        if (isset($data['created_by_alias'])) {
            $data['created_by_alias'] = $filter->clean($data['created_by_alias'], 'TRIM');
        }
        
        //merge any tag groups back into tags
        $parentids = $params->get('albumtagparents',[]);
        if (!empty($parentids)) {
            $thelp = new TagsHelper;
            $parr = $thelp->getTags($parentids);
            foreach ($parr as $pid=>$parent) {
                $groupname = $parent.'_tags';
                if (!empty($data[$groupname])) {
                    $data['tags'] = ($data['tags']) ? array_unique(array_merge($data['tags'],$data[$groupname])) : $data[$groupname];
                }
            }
        } // endforeach parenttag
        
        // ok ready to save the album data
        if (parent::save($data)) {
            $sid = $this->getState('album.id');
            $this->storeAlbumTracks($sid, $data['tracklist']);
            // Check possible workflow
            if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');            
            return true;
        }
        if ($infomsg != '') $app->enqueueMessage($infomsg, 'Information');
        if ($warnmsg != '') $app->enqueueMessage($warnmsg, 'Warning');        
        return false;
    }
    
//     protected function preprocessForm(Form $form, $data, $group = 'content') {
//         Factory::getApplication()->getSession()->set('albumtitle', $data->title);
//         if ($this->canCreateCategory()) {
//             $form->setFieldAttribute('catid', 'allowAdd', 'true');
            
//             // Add a prefix for categories created on the fly.
//             $form->setFieldAttribute('catid', 'customPrefix', '#new#');
//         }
        
//         parent::preprocessForm($form, $data, $group);
//     }
    
//     public function saveorder($idArray = null, $lft_array = null)
//     {
//         // Get an instance of the table object.
//         $table = $this->getTable();
        
//         if (!$table->saveorder($idArray, $lft_array))
//         {
//             $this->setError($table->getError());
            
//             return false;
//         }
        
//         return true;
//     }
    
//     private function canCreateCategory() {
//         return $this->getCurrentUser()->authorise('core.create', 'com_content');
//     }
    
    public function getAlbumTrackList($album_id) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->select('t.id AS track_id, t.discno AS disc_no, t.trackno AS track_no');
        $query->from('#__xbmusic_tracks AS t');
        $query->where('t.album_id = '.$db->q($album_id));
        $query->order('t.discno, t.trackno, t.title ASC');
        $db->setQuery($query);
        return $db->loadAssocList();
    }
    
    function storeAlbumTracks($album_id, $trackList) {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);
        //restore the new list
        foreach ($trackList as $trk) {
            if ($trk['track_id'] > 0) {
                $query->clear();
                $query->update('#__xbmusic_tracks')
                    ->set('album_id = '.$db->q($album_id))
                    ->set('trackno = '.$db->q($trk['track_no']))
                    ->set('discno = '.$db->q($trk['disc_no']));
                $query->where('id = '.$db->q($trk['track_id']));
                //try
                $db->setQuery($query);
                $db->execute();
            }
        }
    }

    
}

