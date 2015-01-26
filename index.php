<?php

/*
	TODO
	- Move all the magic numbers from the code here to the top
	- Move some of these to a separate file, especially the username:password stuff!!
	- See more TODO entries in the code.
*/

$image_refresh_seconds = 15;
$camera_user_read = 'visitor:visitor';
$camera_user_oper = 'gcts:gcts';
$refresh_limit = 3;
$image_width = 640;
$image_height = 480;
$work_base = '/tmp/camera_';
$offline_image = 'webcam-offline.png';
$min_raw_image_data_len = 10240;

$upper_lumen_limit = 150; // if lum is above this we turn the IR LEDs off assuming they might be on!!

$http_cache_mins = 1;
//$cache_control_header = "Cache-Control: private, max-age=" . $http_cache_mins * 60 . ", must-revalidate, pre-check=" . $http_cache_mins * 60 ); // TODO: set better stuff?? What is pre-check?
$cache_control_header = "Cache-Control: private, no-store"; // TODO: set better stuff so this the http_cache_mins setting

/*$cam1_ipaddress = '59.167.222.122'; */
$cam1_ipaddress = 'robinacc.gcts'; /* Make sure this is resolved in /etc/hosts */
$cam2_ipaddress = $cam1_ipaddress; /* Make sure this is resolved in /etc/hosts */
$cam3_ipaddress = $cam1_ipaddress; /* Make sure this is resolved in /etc/hosts */
$cam1_port = ':10101';
$cam2_port = ':10102';
$cam3_port = ':10103';

$webcams = array(
	'CAMERA_1' => array(
		'published_name' => 'TechSpace entrance IR webcam',
		'cached_image'   => $work_base . '1.jpg',
		'ir_status_file'   => $work_base . '1_ir',
		'ir_attempt_file'   => $work_base . '1_attempts',
		'do_stuff_function'   => 'do_stuff',
		'snapshot_uri' => 'http://' . $camera_user_read . '@' . $cam1_ipaddress . $cam1_port . '/snapshot.cgi',
		'ir_on_uri'    => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/decoder_control.cgi?command=95',
		'ir_off_uri'   => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/decoder_control.cgi?command=94',
		'ir_on'     => '20',
		'b_hi_uri'  => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=1&value=96',
		'c_hi_uri'  => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=2&value=4',
		'b_med_uri' => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=1&value=80',
		'c_med_uri' => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=2&value=4',
		'b_low_uri' => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=1&value=64',
		'c_low_uri' => 'http://' . $camera_user_oper . '@' . $cam1_ipaddress . $cam1_port . '/camera_control.cgi?param=2&value=4',
	),
	'CAMERA_2' => array(
		'published_name' => 'TechSpace window IR camera',
		'cached_image'   => $work_base . '2.jpg',
		'ir_status_file'   => $work_base . '2_ir',
		'ir_attempt_file'   => $work_base . '2_attempts',
		'do_stuff_function'   => 'do_stuff',
		'snapshot_uri' => 'http://' . $camera_user_read . '@' . $cam2_ipaddress . $cam2_port . '/snapshot.cgi',
		'ir_on_uri'    => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/decoder_control.cgi?command=94',
		'ir_off_uri'   => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/decoder_control.cgi?command=95',
		'ir_on'     => '20',
		'b_hi_uri'  => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=1&value=128',
		'c_hi_uri'  => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=2&value=5',
		'b_med_uri' => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=1&value=112',
		'c_med_uri' => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=2&value=4',
		'b_low_uri' => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=1&value=96',
		'c_low_uri' => 'http://' . $camera_user_oper . '@' . $cam2_ipaddress . $cam2_port . '/camera_control.cgi?param=2&value=3',
	), 
);

date_default_timezone_set('Australia/Brisbane');

foreach($webcams as $webcam_id => $webcam){
	$webcams[$webcam_id]['id'] = $webcam_id;
}

if(isset($_REQUEST['get']) && isset($webcams[$_REQUEST['get']])){

	$webcam = $webcams[$_REQUEST['get']];

	if(is_file($webcam['cached_image'])
		&& filemtime($webcam['cached_image']) > time()-$image_refresh_seconds){
	}else{
		if(! is_file($webcam['cached_image'])){
			touch($webcam['cached_image']);
		}
		$opts = array(
			'http' =>
				array(
					'method'  => 'GET',
					'timeout' => 5
				)
		);
		$context  = stream_context_create($opts);
		$raw_image_data = file_get_contents($webcam['snapshot_uri'], false, $context, -1);
		if(strlen($raw_image_data)>$min_raw_image_data_len){
			file_put_contents($webcam['cached_image'],$raw_image_data);
		}else{
			copy($offline_image,$webcam['cached_image']);
		}
	}

	header($cache_control_header);
	header("Content-type: image/jpeg");
	// Cache-Control takes precedence...
	header("Expires: " . date(DATE_RFC822,strtotime("+" . $http_cache_mins . " minutes")));
	// Deprecated? header("Pragma: private");

	if(is_file($webcam['cached_image'])){
		if(isset($webcam['do_stuff_function']) && function_exists($webcam['do_stuff_function'])){
			call_user_func($webcam['do_stuff_function'],$webcam);
		}
		readfile($webcam['cached_image']);
	}else{
		readfile($offline_image);
	}
	exit;
}

function do_stuff($webcam){
	/* TODO: This entire logic is faulty: eg cached images get multiple timestamps. 
	   The cached image should get timestamped once, ie on first fetch of a new image from the camera.
	   The brightness logic needs checking too.
	   */
	if(is_file($webcam['cached_image'])){

		$ir_status_file = $webcam['ir_status_file'];
		$ir_attempt_file = $webcam['ir_attempt_file'];
		if(!is_file($ir_status_file))touch($ir_status_file);
		if(!is_file($ir_attempt_file))touch($ir_attempt_file);
		// is our IR on or off?
		$ir_status = (int)file_get_contents($ir_status_file); // 1 for on, 0 or nothing for off.
		$ir_on_attempt_count = (int)file_get_contents($ir_attempt_file); // counter, instead of sessions.

		// check it's luminosity
		$lum = get_avg_luminance($webcam['cached_image']);

		$im = imagecreatefromjpeg($webcam['cached_image']);

		if($lum < $webcam['ir_on']){
			// we don't bother turning the LED off, this happens in a separate cron job that
			// simply checks our timestamp on the IR lock file and turns it off after
			// 5 minutes (or so) of inactivity.

			if(!$ir_on_attempt_count){
				$ir_on_attempt_count = 0;
			}

			if(!$ir_status && $ir_on_attempt_count<2){

				$ir_on_attempt_count ++;
				// we THINK ir is off (it may be on from the other admin panel, oh well)
				// so we try to turn it on.
				file_put_contents($ir_status_file,1);
				$opts = array(
					'http' =>
					array(
						'method'  => 'GET',
						'timeout' => 3
					)
				);
				$context  = stream_context_create($opts);
				file_get_contents($webcam['b_hi_uri'], false, $context, -1);
				file_get_contents($webcam['c_hi_uri'], false, $context, -1);
				file_get_contents($webcam['ir_on_uri'], false, $context, -1);

				$string = "Turning night vision (IR) on...";
				$font = 4;
				$width = imagefontwidth($font) * strlen($string) ;
				$height = imagefontheight($font) ;
				$x = imagesx($im) - $width ;
				$y = imagesy($im) - $height;
				$textColor = imagecolorallocate ($im, 255, 255, 255);
				imagestring ($im, $font, $x, $y,  $string, $textColor);


			}else if (!$ir_status && $ir_on_attempt_count<3){

				$ir_on_attempt_count++;

				// ir is on but the image is still too dark.
				$opts = array(
					'http' =>
					array(
						'method'  => 'GET',
						'timeout' => 3
					)
				);
				$context  = stream_context_create($opts);
				file_get_contents($webcam['ir_off_uri'], false, $context, -1);

				$string = "Turning night vision (IR) off..";
				$font = 4;
				$width = imagefontwidth($font) * strlen($string) ;
				$height = imagefontheight($font) ;
				$x = imagesx($im) - $width ;
				$y = imagesy($im) - $height;
				$textColor = imagecolorallocate ($im, 255, 255, 255);
				imagestring ($im, $font, $x, $y,  $string, $textColor);

			}else{
				$ir_on_attempt_count =0;//reset
				// the IR is on but the image is still too dark.
				// increase the brightness a bit and we leave it at that.

				file_put_contents($ir_status_file,0);

				$opts = array(
					'http' =>
					array(
						'method'  => 'GET',
						'timeout' => 3
					)
				);
				$context  = stream_context_create($opts);
				file_get_contents($webcam['b_hi_uri'], false, $context, -1);

				// todo! increase it again. if the image is too dark and the IR is already on.
				$string = "Increasing brightness...";
				$font = 4;
				$width = imagefontwidth($font) * strlen($string) ;
				$height = imagefontheight($font) ;
				$x = imagesx($im) - $width ;
				$y = imagesy($im) - $height;
				$textColor = imagecolorallocate ($im, 255, 255, 255);
				imagestring ($im, $font, $x, $y,  $string, $textColor);
			}
			file_put_contents($ir_attempt_file,$ir_on_attempt_count);
		}else if($lum > $upper_lumen_limit){

			if($ir_status){
				// image is too bright! turn the IR off.
				// only turn it off if the IR was set a few minutes ago.
				if(filemtime($ir_status_file) < time()-60){
					file_put_contents($ir_status_file,0); // set it off.
					$opts = array(
						'http' =>
						array(
							'method'  => 'GET',
							'timeout' => 3
						)
					);
					$context  = stream_context_create($opts);
					file_get_contents($webcam['b_low_uri'], false, $context, -1);
					file_get_contents($webcam['c_low_uri'], false, $context, -1);
					file_get_contents($webcam['ir_off_uri'], false, $context, -1);

					$string = "Turning night vision (IR) off...";
					$font = 4;
					$width = imagefontwidth($font) * strlen($string) ;
					$height = imagefontheight($font) ;
					$x = imagesx($im) - $width ;
					$y = imagesy($im) - $height;
					$textColor = imagecolorallocate ($im, 10, 10, 10);
					imagestring ($im, $font, $x, $y,  $string, $textColor);
				}
			}else{
				// IR is off, but still too bright.
				// drop the brightness a bit.
				$opts = array(
					'http' =>
					array(
						'method'  => 'GET',
						'timeout' => 3
					)
				);
				$context  = stream_context_create($opts);
				file_get_contents($webcam['b_low_uri'], false, $context, -1);
				file_get_contents($webcam['c_low_uri'], false, $context, -1);

				// todo! increase it again. if the image is too dark and the IR is already on.
				$string = "Decreasing brightness...";
				$font = 4;
				$width = imagefontwidth($font) * strlen($string) ;
				$height = imagefontheight($font) ;
				$x = imagesx($im) - $width ;
				$y = imagesy($im) - $height;
				$textColor = imagecolorallocate ($im, 10, 10, 10);
				imagestring ($im, $font, $x, $y,  $string, $textColor);
			}
		}else{
			// normal image
		}

		// add the date to this image too.
		// $string = "Hackspace IRcam - ".date('d/m/Y H:i:s') .'  (LUM:'.(int)$lum.')';
		$string = $webcam['published_name'] . ' - ' . date('d/m/Y H:i:s') . '  (LUM:'. ( int)$lum. ' )';
		$font = 1;
		$height = imagefontheight($font) ;
		$x = 2;
		$y = 2;//imagesy($im) - $height;
		$textColor = imagecolorallocate ($im, 255, 192, 255);
		imagestring ($im, $font, $x, $y,  $string, $textColor);
		$textColor = imagecolorallocate ($im, 0, 64, 0);
		imagestring ($im, $font, $x, $y+$height+2,  $string, $textColor);

		imagejpeg($im,$webcam['cached_image'],100); 
	}
}

function get_avg_luminance($filename, $num_samples=10) {
	$img = imagecreatefromjpeg($filename);

	$width = imagesx($img);
	$height = imagesy($img);

	$x_step = intval($width/$num_samples);
	$y_step = intval($height/$num_samples);

	$total_lum = 0;

	$sample_no = 1;

	for ($x=0; $x<$width; $x+=$x_step) {
		for ($y=0; $y<$height; $y+=$y_step) {

			$rgb = imagecolorat($img, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;

			// choose a simple luminance formula from here
			// http://stackoverflow.com/questions/596216/formula-to-determine-brightness-of-rgb-color
			$lum = ($r+$r+$b+$g+$g+$g)/6;
			$total_lum += $lum;
			$sample_no++;
		}
	}
	$avg_lum  = $total_lum/$sample_no;
	return $avg_lum;
}
?>
<html>
<head>
	<title>Gold Coast TechSpace WebCams</title>
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
	<style type="text/css">
	.CamSpace {
		display:inline-block;
		border:1px solid #CCC;
		margin:3px;
		padding:2px;
	}
	.SmallText {
		font-size:85%
	}
	</style>
</head>
<body><center>

	<?php foreach($webcams as $webcam_id => $webcam_data){ ?>

	<div class="CamSpace">
		<img src="webcam-loading.png" rel="<?php echo $webcam_id;?>" id="webcam_<?php echo $webcam_id;?>" width="<?php echo $image_width;?>" height="<?php echo $image_height;?>" class="image_refresh"><br/>
		<span class="SmallText">Camera: <?php echo $webcam_data['published_name'];?>
		</span>
	</div>
	<?php } ?>

	<p class = "SmallText" id="RefreshSeconds"></p>

<script>
var sec = 0; // start loading images straight away.
var x = 0;
var loading_images = 0;
var rand_postfix = 1;

function countdown_and_refresh_camera_images(){
	if(loading_images>0){
		// wait for images to finish loading.
		$('#refreshin').html("0 (pending..)");
		setTimeout(countdown_and_refresh_camera_images,500);
	}else{
		$('#refreshin').html(sec);
		sec = sec - 1;

		if (sec > -1) {
			document.getElementById("RefreshSeconds").innerHTML = "Refreshing in: " + (sec+1) + " seconds";
			}
		else {
			document.getElementById("RefreshSeconds").innerHTML = "Loading...";
			}
		if(sec <= -1){
			rand_postfix++;
			loading_images = $('.image_refresh').length; // this is how many images we're loading...
			var d = new Date();
			$('.image_refresh').each(function(){
				with({t:this}){
					var image = document.createElement('img');
					image.onload = function() {
						loading_images--;
						$(t).attr('src','?get='+$(t).attr('rel')+'&r=<?php echo time();?>_'+rand_postfix);
					};
					image.src = '?get='+$(t).attr('rel')+'&r=<?php echo time();?>_'+rand_postfix;
				}
				});
			sec = <?php echo $image_refresh_seconds;?>;
			}

		setTimeout(countdown_and_refresh_camera_images,1000); // 1 second countdown and refresh at 0
	}

}

countdown_and_refresh_camera_images();

</script>

</center></body>
</html>
