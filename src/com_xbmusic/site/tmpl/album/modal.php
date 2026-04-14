<?php 
 /*******
 * @package xbMusic
 * @filesource site/tmpl/album/modal.php
 * @version 0.0.61.6 14th April 2026
 * @author Roger C-O
 * @copyright Copyright (c) Roger Creagh-Osborne, 2026
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
 ******/

defined('_JEXEC') or die;

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

$item = $this->item;
$iconarr = array('facebook'=>'<i class="fab fa-facebook"></i>',
    'twitter'=>'<i class="fab fa-twitter"></i>',
    'instagram'=>'<i class="fab fa-instagram"></i>',
    'bandcamp'=>'<i class="fab fa-bandcamp"></i>',
    'spotify'=>'<i class="fab fa-spotify"></i>',
    'youtube'=>'<i class="fab fa-youtube-square"></i>',
    'website'=>'<i class="fas fa-globe"></i>'
);
$numdiscarr = array('','','double album','3 disc set', '4 disc set', 'box set');

?>
<div class="xbcomponent xbpvitem">
	<table style="width:100%;">
		<tr>
			<td>
                <h3>
                	<i class='fas fa-compact-disc' ></i>
                	<?php echo $item->title; ?><br /><?php echo $item->albumartist; ?>
                </h3>
				<?php if ($item->subtitle != '') : ?>
			    	<?php echo $item->subtitle; ?><br />
			    <?php endif; ?>
                <?php echo $item->rel_date; ?>
                <?php if($item->num_discs > 1) {
                    if ($item->num_discs > 5 ) $item->num_discs = 5;
                    echo $numdiscarr[$item->num_discs];
                }?>
			    <br />
			    <?php if ($item->description != '') : 
			         echo Text::_('XB_DESCRIPTION'); ?>
			    	<div class="xbbox" style="width:100%;">
    					<?php echo $item->description; ?>
    				</div>
    			<?php endif; ?>
                <?php if (count($item->tracks) > 0) : ?>
                    <?php echo Text::_('Tracks and playlists'); ?>
                    <div class="xbbox">
                    	<ul class="xbnobullets">
            	        	<?php foreach ($item->tracks as $track) : ?>
                    	    	<li><?php echo $track['discno'].' '.$track['trackno'].' : '.$track['tracktitle']; 
                    	    	if (key_exists('playlists',$track)) : ?>
                    	    	    <?php foreach ($track['playlists'] as $plist ) : ?>
                    	    	    	<span class="xbbadge badge-yellow"><?php echo $plist['pltitle']; ?></span>
                    	    	    <?php endforeach; ?>
                    	    	<?php endif; ?>       	    	
                    	    	</li>
                    		<?php endforeach; ?>
                    		</ul>	
                    </div>
                <?php endif; ?>
				<table>
					<?php if ($item->catid > 0) : ?>
  						<tr><td>
  							<?php echo Text::_('XB_CATEGORY'); ?>
    					</td><td>
                    		<span class="xblabel label-cat" title="<?php echo $item->category_path;?>">
                    			<?php echo $item->category_title; ?>
                    		</span>	
						</td></tr>
					<?php endif; ?>
					<?php if ($item->tags) : ?>
						<tr><td>
							<?php echo Text::_('XB_TAGS'); ?>
						</td><td>
				    		<?php foreach ($item->tags as $t) : ?>
    							<span class="xblabel label-tag" title="<?php echo $t->path;?>">
    								<?php echo $t->title; ?>
    							</span><span style="width:15px;"></span>
    						<?php endforeach; ?>
						</td></tr>
        			<?php endif; ?>
          		</table>        
        	</td>
		<?php if ($item->imgurl != '') : ?>
			<td style="width:220px;">
				<?php  $src = trim($item->imgurl); ?>    	
    			<?php $tip = '<img src=\''.$src.'\'  />'; ?>
    			<img src="<?php echo $src; ?>" alt="<?php echo $item->title; ?>" 
    				class="hasPopover" title="" 
    				data-bs-content="<?php echo $tip; ?>" 				
    				style="width:200px;margin:0 0 20px 20px"  
        		/>
            </td>      
		<?php endif; ?>
		</tr>      
    </table>
    <?php if ($item->ext_links_cnt > 0) : ?>
          <?php echo Text::_('Internet Links'); ?>
		<div class="xbbox" style="width:100%;">
     		<table>
     			<?php foreach($item->ext_links as $link) : ?>
     				<tr>
                      <td>
     					<?php $key = trim(strtolower($link->link_text));
        			    if (key_exists($key, $iconarr)) : ?>
        			    	<span class="xbr12 xbpl10">
        			    		<a href="<?php echo $link->link_url; ?>" target="_blank" class="xbicomag"><?php echo $iconarr[$key]; ?></a>
        			        </span>
        			    	<?php echo $link->link_text; ?>
        			    <?php else : ?>
    			    		<a href="<?php echo $link->link_url; ?>" target="_blank">
    			        		<?php echo $link->link_text; ?>
    			        	</a>
        			    <?php endif; ?>
                        </td>
                      <td>
        			    <span class="xbnote xbpl20"><?php echo $link->link_desc; ?></span>
                        </td>
     				</tr>
     			<?php endforeach; ?>
     		</table>
     	</div>
    <?php endif; ?>
</div>
