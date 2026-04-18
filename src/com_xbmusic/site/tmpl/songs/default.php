<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/Artists/default.php
 * @version 0.0.62.1 18th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Crosborne\Component\Xbmusic\Site\Helper\RouteHelper as XbmusicHelperRoute;
use Crosborne\Component\Xbmusic\Administrator\Helper\XbcommonHelper;
use Crosborne\Component\Xbmusic\Administrator\Helper\Xbtext;

HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('bootstrap.popover', '.hasPopover', ['trigger'=>'hover']);

$wa = $this->document->getWebAssetManager();
$wa->useScript('joomla.dialog')
    ->useScript('multiselect')
    ->useScript('xbmusic.xbgeneral');


$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$iconarr = array('facebook'=>'<i class="fab fa-facebook"></i>',
                'twitter'=>'<i class="fab fa-twitter"></i>',
                'instagram'=>'<i class="fab fa-instagram"></i>',
                'bandcamp'=>'<i class="fab fa-bandcamp"></i>',
                'spotify'=>'<i class="fab fa-spotify"></i>',
                'youtube'=>'<i class="fab fa-youtube-square"></i>',
                'website'=>'<i class="fas fa-globe"></i>'
            );

$root = Uri::root();
$document = Factory::getApplication()->getDocument();
$document->addScriptOptions('com_xbmusic.uri', array("root" => $root));

?>
<script type="module" src="<?php echo Uri::root(); ?>/media/com_xbmusic/js/xbdialog.js"></script>

<div id="xbcomponent" >

<h3><i class='fas fa-music' ></i> <?php echo Text::_('XBMUSIC_SONG_INDEX'); ?></h3>

<?php if ($this->showplay) : ?>
    <div class="pull-right">
    	<?php if ($this->playtime>0) : ?>
    		<p class="xbnote xbr09 xbmb5 xbtr"><?php echo Text::sprintf('play restricted to %s seconds', $this->playtime); ?></p>
    	<?php endif; ?>
    	<audio id="player" controls style="height:30px;border-radius:12px;"
    		src="<?php echo Uri::root(true).'/xbmusic/'; ?>">
    			<i>Your browser does not support the audio tag.</i>
    	</audio>        		
        <div id="playertitle" class="pull-left" style="margin:5px 20px 0 0;"></div>
    </div>
    <div class="clearfix"></div>      
<?php endif; ?>

<form action="<?php echo Route::_('index.php?option=com_xbmusic&view=songs'); ?>" method="post" name="adminForm" id="adminForm">

  	<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
  	<?php  echo $this->indexfield->renderField(); ?>
	<div class="pull-right pagination xbm0">
		<?php  echo $this->pagination->getPagesLinks(); ?>
	</div>        
	<div class="clearfix"></div>      
	<div class="pull-right pagination xbm0 xbpr20 xbpt5">
		<?php  echo $this->pagination->getResultsCounter(); ?>
	</div>
	<div class="clearfix"></div>      
              
	<?php if (empty($this->items)) : ?>
		<div class="alert alert-no-items">
			<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
	<?php else : ?>
		<div class="pull-left">
    		<p class="xbmb5">              
                <?php echo Xbtext::_('XB_SORTED_BY',XBSP2 + XBTRL).$listOrder.' '.$listDirn ; ?>
    		</p>
		</div>
		<div class="pull-left" style="width:60%">
      		<p class="xbtr xbnote xbmb5"><?php echo Text::_('XB_AUTOCLOSE_DROPS'); ?><input  type="checkbox" id="autoclose" name="autoclose" value="yes" checked="true" style="margin:0 5px;" />
      		</p>
      	</div>
    	<div class="clearfix"></div>      

<div class="table-responsive">
  <table class="table table-striped">
  <thead>
    <tr>
 		<th scope="col">
			<?php echo HTMLHelper::_('searchtools.sort', 'XB_TITLE', 'a.title', $listDirn, $listOrder); ?>
			<i><?php echo HTMLHelper::_('searchtools.sort', 'XBMUSIC_SORTNAME', 'idxtitle', $listDirn, $listOrder); ?></i>
		</th>
		<?php if($this->showlinks) : ?>
			<th class="hidden-tab-phone"> </th>
		<?php endif; ?>
		<?php if($this->showimg) : ?>
			<th class="hidden-tab-phone" style="width:75px;"> </th>
			<th class="hidden-tab-phone" style="width:75px;"> </th>
		<?php endif; ?>
		<th style="width:500px;"><?php echo Text::_('XBMUSIC_ARTIST_VERSIONS'); ?></th>
		<?php if ($this->showcat || $this->showtags) : ?>
			<th class="hidden-tab-phone">
				<?php if ($this->showcat) echo Text::_('XB_CATEGORY');
    			      if ($this->showcat && $this->showtags) echo  ' &amp; ';
    			      if ($this->showtags) echo Text::_('XB_TAGS'); 
    			?>		
			</th>
		<?php endif; ?>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($this->items as $id => $item) :
	?>
	<tr>
		<td>
			<span class="xbbold"><?php echo $item->title; ?></span>
			<br />
			<span class="xbnit xbpl20 xbr085">
		    	<?php if ($item->idxtitle != strtolower($item->title)) echo $item->idxtitle; ?> 
			</span><br />
		</td>
		
		<?php if($this->showlinks) : ?>
    		<td class="hidden-tab-phone">
        		<?php if (!empty($item->ext_links)) : ?>
        			<?php foreach ($item->ext_links as $linkobj) : ?>
        			    <span class="xbr12">
        			    	<a href="<?php echo $linkobj->link_url; ?>" target="_blank" 
            			    class="hasPopover" title="" 
        						data-bs-content="<?php echo ($linkobj->link_desc!='') ? $linkobj->link_desc : $linkobj->link_text; ?>">
        					<?php $key = trim(strtolower($linkobj->link_text));
            			    if (key_exists($key, $iconarr)) { 
            			        echo '<span class="xbicomag">'.$iconarr[$key].'</span>';
            			    } else {
            			        echo '<span class="xbr10">'.$linkobj->link_text.'</span>';
            			    } ?>
            			    </a>
    					</span>
        			<?php endforeach; ?>
        		<?php endif; ?>
    		</td>
		<?php endif; ?>
		
		<?php if($this->showimg) : ?>
    		<td class="hidden-tab-phone">
    			<?php if (count($item->tracks) > 0) : ?>
        			<?php foreach ($item->tracks as $track) : ?>
        				<div class="pull-left" style="height:67px;width:67px;">
        			    	<?php if(($this->showimg) && ($track['artistimg']!='')) :?>
            					<?php  $src = trim($track['artistimg']);
            					  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
            						$tip = '<img src=\''.$src.'\' />';
            					?>
            					<img src="<?php echo $src; ?>" alt="<?php echo $track['artistname']; ?>" 
            						style="width:60px;margin: 2px;"
            						class="hasPopover" title="" 
            						data-bs-content="<?php echo $tip; ?>" />
            					<?php endif; ?>
            				<?php endif; ?>
            			</div>
        				<div class="clearfix"></div>  		
        			<?php endforeach; ?>
        		<?php endif; ?>    				
    		</td>
		<?php endif; ?>
		
		<?php if($this->showimg) : ?>
    		<td class="hidden-tab-phone">
    			<?php if (count($item->tracks) > 0) : ?>
        			<?php foreach ($item->tracks as $track) : ?>
        				<div class="pull-left" style="height:67px;width:67px;">
        			    	<?php if(($this->showimg) && ($track['trackimg']!='')) :?>
            					<?php  $src = trim($track['trackimg']);
            					  if ((!$src=='') && (file_exists(JPATH_ROOT.'/'.str_replace(Uri::root(),'',$src)))) :
            						$tip = '<img src=\''.$src.'\' />';
            					?>
            					<img src="<?php echo $src; ?>" alt="<?php echo $track['albumtitle']; ?>" 
            						style="width:60px;margin: 2px;"
            						class="hasPopover" title="" 
            						data-bs-content="<?php echo $tip; ?>" />
            					<?php endif; ?>
            				<?php endif; ?>
            			</div>
        				<div class="clearfix"></div>  		
        			<?php endforeach; ?>
        		<?php endif; ?>    				
    		</td>
		<?php endif; ?>
		
		<td style="width:500px;">
    		<?php if (count($item->tracks) > 0) : ?>
    			<?php foreach ($item->tracks as $track) : ?>
        			<div class="pull-left xbmr10" style="height:67px;">
    			    	<?php if ($this->showplay) : ?>
                            <span class="icon-play xbpl10 xbgreen xbpl10" 
                            	onclick='playAudio("<?php echo rawurlencode($track['artistname'].' : '.$track["tracktitle"]); ?>", 
                            	"<?php echo rawurlencode(str_replace(JPATH_ROOT,'',$track["trackfile"])); ?>",
                            	"<?php echo $this->playtime; ?>");'>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="pull-left xbmr20" style="width:400px;">
    			    	<?php echo $track['artistname']; ?>
						<?php if ($track['trackid']) : ?>
		                	<span class="xbpl10 xbpr10">
		                		[<?php if($track['reldate']) echo '('.$track['reldate'].')'; ?>
		                	]</span> 
    						<br />
		                	<?php if ($track['albumid']) : ?>
        		                <?php echo $track['albumtitle']; ?> 
                    			<?php $pvtit = "'".Text::_('XBMUSIC_ALBUM_DETAILS')."'"; ?>
                                <span class="xbpveye" 
                                	onclick="pvItem(<?php echo $pvtit; ?>,'album','<?php echo $track['albumid']; ?>');">
                                	<span class="icon-eye xbpl10"></span>
                                </span>
            				<?php endif; ?>
            			<?php endif; ?>    						
    				</div>
		    		<div class="clearfix"></div>  		
    			<?php endforeach; ?>    				
    		<?php else : ?>
    			<p class="xbnit"><?php echo Text::_('XBMUSIC_NO_VERSIONS_FOUND'); ?></p>
    		<?php endif; ?>  
    	</td>
    		
		<?php if (($this->showcat) && ($this->showtags)) : ?>
    		<td class="hidden-tab-phone">
    			<?php if (($this->showcat) && ($item->catid > 0)) : ?>
    				<p>
    					<span class="xblabel label-cat" >
    						<?php echo $item->category_title; ?>
    					</span>							
    				</p>						
    			<?php endif; ?>
    			<?php if ($this->showtags) : ?>
        			<ul class="inline">
        			<?php foreach ($item->tags as $t) : ?>
        				<li><span class="xblabel label-tag">
        					<?php echo $t->title; ?></span>
        				</li>												
        			<?php endforeach; ?>
    			</ul>						   
    			<?php endif; ?> 											    			
		</td>
		<?php endif; ?>
		
	</tr>

	<?php endforeach; ?>
	</tbody>
  </table>
</div>

		<?php echo $this->pagination->getListFooter(); ?>
	<?php endif; ?>
	<input type="hidden" name="task" value="">
	<input type="hidden" name="boxchecked" value="0">
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
    <script language="JavaScript" type="text/javascript"
      src="<?php echo Uri::root(); ?>media/com_xbmusic/js/closedetails.js" >
    </script>

</div>
