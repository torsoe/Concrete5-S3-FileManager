<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneHeaderWrapper(t('S3 File Manager Config'), false, false, false);?>

<div class="ccm-pane-body">
<?php
if(defined('S3_ENABLED')){
	if(defined('S3_BUCKET') && defined('S3_ACCESSKEY') && defined('S3_SECRETKEY')){
		$s3 = Loader::helper('s3');			
		$s3 = new S3Helper();
		if($s3->getBucket(S3_BUCKET)){
			echo t('Addon ist aktiv und connected erfolgreich zu S3.');
		}else{
			echo t('Addon ist nicht aktiv. Bitte überprüfe deine Angaben in der /config/site.php nochmal.');
		}
	}
}else{
?>
	<p><?php echo t('copy the following block into yout /config/site.php') ?></p>
	<code style="display:block; margin:20px 0">
	define('S3_ENABLED', true);<br>
	if(S3_ENABLED){<br>
	&nbsp;&nbsp;&nbsp;define('S3_BUCKET', '<span style="color:#0088CC">YOUR BUCKET NAME</span>');<br>
	&nbsp;&nbsp;&nbsp;define('S3_DIR', '<span style="color:#0088CC">YOUR SUBFOLDER</span>');<br>
	&nbsp;&nbsp;&nbsp;define('S3_ACCESSKEY', '<span style="color:#0088CC">YOUR ACCESSKEY</span>');<br>
	&nbsp;&nbsp;&nbsp;define('S3_SECRETKEY', '<span style="color:#0088CC">YOUR SECRETKEY</span>');<br>
	}<br>
	</code>
<?php
}
?>


<br><br><br>This Addon Use the "Amazon S3 PHP Class" from <a href="http://undesigned.org.za/2007/10/22/amazon-s3-php-class">Donovan Schonknecht</a>


</div>
<div class="ccm-pane-footer">
		
</div>


<?php echo Loader::helper('concrete/dashboard')->getDashboardPaneFooterWrapper(false);?>