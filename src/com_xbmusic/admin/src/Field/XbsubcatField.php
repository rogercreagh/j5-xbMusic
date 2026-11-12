<?php
/*******
 * @package xbMusic
 * @filesource admin/src/Field/XbsubcatField.php
 * @version 0.1.0.2 20th October 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 * @desc create a form field type to select a category allowing both a parent and a number of levels to be specified.
 * based on code from joomla3|4-/libraires/legacy|src/Form/Field/category|CategoryField.php
 ******/

namespace Crosborne\Component\Xbmusic\Administrator\Field;

defined('JPATH_BASE') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use \stdClass;

/**
 * @name Xbsubcatfield
 * @desc makes category list of subcategories for given itemtype
 * @elements: 
 *      extension (string) - default com_xbmusic, 
 *      published (string) - only published - default true, 
 *      itemtype (string) - required the item type. NB singular eg 'track' not 'tracks', 
 *      showuncat (string) - show the uncategorized category in list - default false, 
 *           (string) - show import date categories children of 'imports' - default false
 * @author rogerco
 *
 */
class XbsubcatField extends ListField {
    
    public function getOptions() {
        
        $params = ComponentHelper::getParams('com_xbmusic');
        $options = array();
        $extension = $this->element['extension'] ? (string) $this->element['extension'] : 'com_xbmusic';
        $published = $this->element['published'] ? (string) $this->element['published'] : 'true';
        $showuncat  = $this->element['showuncat'] ? (string) $this->element['showuncat'] : 'false';
        $showdatecats  = $this->element['showdatecats'] ? (string) $this->element['showdatecats'] : 'false';
        $uncat = XbcommonHelper::getCatByAlias('uncategorised', $extension);
        $datecats = [];
        $rootid = 0;
        if (!empty($this->element['itemtype'])) {
            $itemtype = (string) $this->element['itemtype'];
            $rootid = $params->get('rootcat_'.$itemtype);
            $incroot= true;
            $defcat = XbcommonHelper::getCat($params->get('defcat_'.$itemtype));
        } else {
            $incroot = false;
            $defcat = $uncat;
        }
        $defopt = array(array('value'=>$defcat->id, 'text'=>$defcat->title.'(default)'));
        $db = Factory::getDbo();
        $query  = $db->getQuery(true);
        if ($rootid>0) {
            $query->select('*')->from('#__categories')->where('id='.$rootid);
            $db->setQuery($query);
            $rootcat=$db->loadObject();
        }
        $start = $incroot ? '>=' : '>';
        $query->clear();
        $query->select('id AS value, title AS text, level')->from('#__categories')
        ->where('extension = '.$db->quote($extension));
        if ($rootid>0) {
            $query->where(' lft'.$start.$rootcat->lft.' AND rgt <='.$rootcat->rgt);
        }
        if ($published == 'true') {
            $query->where('published = 1');
        }
        $query->order('lft');
        $db->setQuery($query);
        $options = $db->loadObjectList();
        foreach ($options as &$item) {
            $adj = $incroot ? 0 : 1;
            $startlevel = $rootid>0 ? $rootcat->level + $adj :1;
            if ($item->level>0) {
//                $item->text = str_repeat('- ', $item->level - $startlevel)
                $item->text = str_repeat("\u{2003}",$item->level - $startlevel)."\u{2514}\u{2500} ".$item->text;;
            }
        }
        if ($showdatecats) {
            // also show import date categories if they exist
            $importparent = XbcommonHelper::getCatByAlias('imports',$extension);
            if ($importparent) {
                $query->clear();
                $query->select('id AS value, title AS text')->from('#__categories')
                    ->where('extension = '.$db->quote($extension));
                $query->where('parent_id = '.$importparent->id);
                $query->order('title DESC');  
                $db->setQuery($query);
                $datecats = $db->loadObjectList();
            }                    
        }
        $dateopts = [];
        if (!empty($datecats)) {
            $dateopts[] = array('value'=>'disable', 'text'=>"\u{2500}\u{2003}".'import dates'."\u{2003}\u{2500}");
            foreach ($datecats as &$dopt) {
                $dopt->text = "\u{2003}"."\u{2514}\u{2500} ".$dopt->text;
                $dateopts[] = $dopt;
            }
        }
            
        if ($showuncat) {           
            $options[] = array('value'=>$uncat->id, 'text'=>$uncat->title);
        }
        // Merge options with import cats and anything set in the XML definition.
        $options = array_merge(parent::getOptions(),$defopt, $options, $dateopts);
        return $options;
    }
    
}