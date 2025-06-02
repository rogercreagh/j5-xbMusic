<!DOCTYPE html>
<html lang="en">

    <head>

        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="Wreckers Radio showcases and celebrates folk, roots, country, blues, acoustic and traditional music. We focus on music that is Cornish, Cornwall-based or has a connection with Cornwall. We also play music from the South West of England, the British Isles and around the world." />
        <meta name="author" content="Wreckers Radio" />
        <title>Wreckers Radio - Cornish, folk, roots, country, blues, acoustic and traditional music</title>

        <!-- Favicon-->
    <link rel="icon" type="image/svg+xml" href="/favicons/favicon.svg" />
    <link rel="shortcut icon" href="/favicons/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Wreckers Radio" />
    <link rel="manifest" href="/favicons/site.webmanifest" />

        <!-- Font Awesome icons (free version)-->
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <!-- Simple line icons-->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.5.5/css/simple-line-icons.min.css" rel="stylesheet" />
        <!-- Google fonts-->
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <!-- -->
        <link href="css/styles.css" rel="stylesheet" />
     <!--   -->

<!-- Player -->
<link href="audio6_html5.css" rel="stylesheet" type="text/css">
<link href="https://fonts.googleapis.com/css?family=PT+Sans:400,400i,700,700i" rel="stylesheet">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script> 
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="js/jquery.mousewheel.min.js" type="text/javascript"></script>
<script src="js/jquery.touchSwipe.min.js" type="text/javascript"></script>
<script src="js/audio6_html5.js" type="text/javascript"></script>
<script>
	jQuery(function() {
		setTimeout(function(){
				jQuery("#lbg_audio6_html5_shoutcast_1").audio6_html5({
					radio_stream:"https://radio.xbone.uk/listen/wreckers_radio/radio.mp3",
                    azuracast_api_nowplaying_url:"https://radio.xbone.uk/api/nowplaying/wreckers_radio",
                    			azuracast_get_image:true,
					radio_name:"Wreckers Radio",
					playerWidth:355,
					imageHeight:355,
					skin:"whiteControllers",
					responsive:true,
					grabLastFmPhoto:true,
					autoPlay:true,
					songTitleColor:"#000000",
					authorTitleColor:"#000000",
					lineSeparatorColor:"#ffffff",
					radioStationColor:"#000000",
					frameBehindTextColor:"#ffffff",
					frameBehindButtonsColor:"#ffffff",
					sticky:false,
					startMinified:false,
					showOnlyPlayButton:false,
					centerPlayer:true,
					playerBorderSize:0,
					playerBorderColor:"#000000",
					showFacebookBut:false,
					facebookAppID:"",
					facebookShareTitle:"",
					facebookShareDescription:"",
					showTwitterBut:false,
					showVolume:false,
					showRadioStation:false,
					showTitle:true,
					showHistoryBut:true,
					showHistory:true,
					showHistoryOnInit:true,
					translateReadingData:"reading data...",
					historyTranslate:"HISTORY - last three songs",
					historyTitleColor:"#858585",
					historyBgColor:"#ffffff",
					historyRecordBgColor:"transparent",
					historyRecordBottomBorderColor:"transparent",
					historyRecordSongColor:"#000000",
					historyRecordSongBottomBorderColor:"#cfcfcf",
					historyRecordAuthorColor:"#6d6d6d",
					numberOfThumbsPerScreen:3,
					historyPadding:16,
					historyRecordTitleLimit:25,
					historyRecordAuthorLimit:36,
					nowPlayingInterval:20,
					noImageAvailable:"noimageavailable.jpg",
					showListeners:true
				});
		}, 1000);
	});
</script>

    </head>

    <body id="page-top">
<script>
function myFunction() {
//alert('hello');
window.open ('now.php', 'newwindow', config='height=560,width=370,popup');}
</script>
<style>
.AudioShowHidePlaylist {width:130px !important;}
.AudioShowHidePlaylist:before {content:"show/hide history";}
</style>

        <!-- Listen -->
        <section class="content-section" id="listen">
            <div class="container px-4 px-lg-5 text-center">
                <div class="row gx-4 gx-lg-5 justify-content-center">
                    <div class="col-lg-10" style="width:355px;">
        <h4>Wreckers Radio</h4>
        <?php if ($_GET['b']) : ?>
        <div style="float:left; margin-bottom:10px;"><i>now playing</i></div>
       <div style="float:right; margin-bottom:10px;">
         	<span class="fa-solid fa-arrow-up-right-from-square" onclick="myFunction();" title="open in new window"></span>
         </div>
         <?php else : ?>
         	<div style="margin-bottom:10px;"><i>now playing</i></div>
       	<?php endif; ?>
<div class="clearfix"></div>
             <div class="audio6_html5">
             	<div id="lbg_audio6_html5_shoutcast_1"></div>
             </div>

                    </div>
                </div>
            </div>
        </section>

       <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
  <!--      <script src="js/scripts.js"></script> -->
    </body>
</html>
