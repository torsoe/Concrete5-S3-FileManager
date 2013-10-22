<?php
defined('C5_EXECUTE') or die("Access Denied.");
class SiteImageHelper extends  Concrete5_Helper_Image {

	public function create($originalPath, $newPath, $width, $height, $crop = false) {
		//start S3 tour if s3 is enabled
		if (defined('S3_ENABLED') && S3_ENABLED == true){
			return $this->S3_create($originalPath, $newPath, $width, $height, $crop);
		}else{
			return parent::create($originalPath, $newPath, $width, $height, $crop);
		}
	}

	public function S3_create($originalPath, $newPath, $width, $height, $crop = false) {
		// first, we grab the original image. We shouldn't ever get to this function unless the image is valid
		$imageSize = @getimagesize($originalPath);

		$oWidth = $imageSize[0];
		$oHeight = $imageSize[1];
		$finalWidth = 0; //For cropping, this is really "scale to width before chopping extra height"
		$finalHeight = 0; //For cropping, this is really "scale to height before chopping extra width"
		$do_crop_x = false;
		$do_crop_y = false;
		$crop_src_x = 0;
		$crop_src_y = 0;

		// first, if what we're uploading is actually smaller than width and height, we do nothing
		if ($oWidth < $width && $oHeight < $height) {
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
			$width = $oWidth;
			$height = $oHeight;
		} else if ($crop && ($height >= $oHeight && $width <= $oWidth)) {
			//crop to width only -- don't scale anything
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
			$height = $oHeight;
			$do_crop_x = true;
		} else if ($crop && ($width >= $oWidth && $height <= $oHeight)) {
			//crop to height only -- don't scale anything
			$finalHeight = $oHeight;
			$finalWidth = $oWidth;
			$width = $oWidth;
			$do_crop_y = true;
		} else {
			// otherwise, we do some complicated stuff
			// first, we divide original width and height by new width and height, and find which difference is greater
			$wDiff = $oWidth / $width;
			$hDiff = ($height != 0 ? $oHeight / $height : 0);
			
			if (!$crop && ($wDiff > $hDiff)) {
				//no cropping, just resize down based on target width
				$finalWidth = $width;
				$finalHeight = ($wDiff != 0 ? $oHeight / $wDiff : 0);
			} else if (!$crop) {
				//no cropping, just resize down based on target height
				$finalWidth = ($hDiff != 0 ? $oWidth / $hDiff : 0);
				$finalHeight = $height;
			} else if ($crop && ($wDiff > $hDiff)) {
				//resize down to target height, THEN crop off extra width
				$finalWidth = ($hDiff != 0 ? $oWidth / $hDiff : 0);
				$finalHeight = $height;
				$do_crop_x = true;
			} else if ($crop) {
				//resize down to target width, THEN crop off extra height
				$finalWidth = $width;
				$finalHeight = ($wDiff != 0 ? $oHeight / $wDiff : 0);
				$do_crop_y = true;
			}
		}
		
		//Calculate cropping to center image
		if ($do_crop_x) {
			/*
			//Get half the difference between scaled width and target width,
			// and crop by starting the copy that many pixels over from the left side of the source (scaled) image.
			$nudge = ($width / 10); //I have *no* idea why the width isn't centering exactly -- this seems to fix it though.
			$crop_src_x = ($finalWidth / 2.00) - ($width / 2.00) + $nudge;
			*/
			$crop_src_x = round(($oWidth - ($width * $oHeight / $height)) * 0.5);
		}
		if ($do_crop_y) {
			/*
			//Calculate cropping...
			//Get half the difference between scaled height and target height,
			// and crop by starting the copy that many pixels down from the top of the source (scaled) image.
			$crop_src_y = ($finalHeight / 2.00) - ($height / 2.00);
			*/
			$crop_src_y = round(($oHeight - ($height * $oWidth / $width)) * 0.5);
		}
		
		//create "canvas" to put new resized and/or cropped image into
		if ($crop) {
			$image = @imageCreateTrueColor($width, $height);
		} else {
			$image = @imageCreateTrueColor($finalWidth, $finalHeight);
		}
		
		$im = false;		
		switch($imageSize[2]) {
			case IMAGETYPE_GIF:
				$im = @imageCreateFromGIF($originalPath);
				break;
			case IMAGETYPE_JPEG:
				$im = @imageCreateFromJPEG($originalPath);
				break;
			case IMAGETYPE_PNG:
				$im = @imageCreateFromPNG($originalPath);
				break;
		}


		
		if ($im) {
			// Better transparency - thanks for the ideas and some code from mediumexposure.com
			if (($imageSize[2] == IMAGETYPE_GIF) || ($imageSize[2] == IMAGETYPE_PNG)) {
				$trnprt_indx = imagecolortransparent($im);
				
				// If we have a specific transparent color
				if ($trnprt_indx >= 0 && $trnprt_indx < imagecolorstotal($im)) {
			
					// Get the original image's transparent color's RGB values
					$trnprt_color = imagecolorsforindex($im, $trnprt_indx);
					
					// Allocate the same color in the new image resource
					$trnprt_indx = imagecolorallocate($image, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
					
					// Completely fill the background of the new image with allocated color.
					imagefill($image, 0, 0, $trnprt_indx);
					
					// Set the background color for new image to transparent
					imagecolortransparent($image, $trnprt_indx);
					
				
				} else if ($imageSize[2] == IMAGETYPE_PNG) {
				
					// Turn off transparency blending (temporarily)
					imagealphablending($image, false);
					
					// Create a new transparent color for image
					$color = imagecolorallocatealpha($image, 0, 0, 0, 127);
					
					// Completely fill the background of the new image with allocated color.
					imagefill($image, 0, 0, $color);
					
					// Restore transparency blending
					imagesavealpha($image, true);
			
				}
			}
			

			$res = @imageCopyResampled($image, $im, 0, 0, $crop_src_x, $crop_src_y, $finalWidth, $finalHeight, $oWidth, $oHeight);


			if($res){
				switch($imageSize[2]) {
					case IMAGETYPE_GIF:
						ob_start();
						imageGIF($image, null);
						$image_contents = ob_get_clean();
						break;
					case IMAGETYPE_JPEG:
						ob_start();
						imageJPEG($image, null, $this->jpegCompression);
						$image_contents = ob_get_clean();
						break;
					case IMAGETYPE_PNG:
						ob_start();
						imagePNG($image, null);
						$image_contents = ob_get_clean();
						break;
				}		

				$s3 = Loader::helper('s3');			
				$s3 = new S3Helper();
				$newPath = str_replace(S3_PATH.'/','',$newPath);//remove absolute uri
				$r = $s3->putObject($image_contents, S3_BUCKET, $newPath, S3Helper::ACL_PUBLIC_READ,array('Content-Type'=>'image/jpg'),array('Content-Type'=>'image/jpg'));
			
				return $r;
			}
		}
	}

	
	
}