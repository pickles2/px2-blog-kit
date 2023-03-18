<?php
print (new \tomk79\pickles2\px2dthelper\main($px))->document_modules()->build_js();
?>
$(function(){
	var platform = (function(){
		var ua = window.navigator.userAgent;
		// console.log(ua);
		if( ua.indexOf( 'Mac OS X' ) >= 0 ){
			return 'mac';
		}else if( ua.indexOf( 'Windows' ) >= 0 ){
			return 'win';
		}else if( ua.indexOf( 'Linux' ) >= 0 ){
			return 'linux';
		}
		return 'unknown';
	})();
	// alert(platform);

	$('.platform--mac').hide();
	$('.platform--win').hide();
	$('.platform--linux').hide();
	$('.platform--unknown').hide();
	switch(platform){
		case "mac":
			$('.platform--mac').show();
			break;
		case "win":
			$('.platform--win').show();
			break;
		case "linux":
			$('.platform--linux').show();
			break;
		default:
			$('.platform--unknown').show();
			break;
	}
});
