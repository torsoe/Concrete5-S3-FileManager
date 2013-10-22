<?php 

class FileVersion extends Concrete5_Model_FileVersion {





	public function refreshAttributes($firstRun = false) {
		if (defined('S3_ENABLED') && S3_ENABLED == true){
			return $this->S3_refreshAttributes($pointer, $filename, $fr);
		}else{
			return parent::refreshAttributes($pointer, $filename, $fr);
		}
	}


	public function S3_refreshAttributes($firstRun = false){	
		$fh = Loader::helper('file');
		$ext = $fh->getExtension($this->fvFilename);
		$ftl = FileTypeList::getType($ext);
		$db = Loader::db();


		$fileExist = $this->S3_fileExist($this->getPath());
		if(!$fileExist){
			return File::F_ERROR_FILE_NOT_FOUND;
		}
		$size = $fileExist->headers['size'];

		$title = ($firstRun) ? $this->getFilename() : $this->getTitle();

		$db->Execute('update FileVersions set fvExtension = ?, fvType = ?, fvTitle = ?, fvSize = ? where fID = ? and fvID = ?',
			array($ext, $ftl->getGenericType(), $title, $size, $this->getFileID(), $this->getFileVersionID())
		);
		if (is_object($ftl)) {
			if ($ftl->getCustomImporter() != false) {
				Loader::library('file/inspector');

				$db->Execute('update FileVersions set fvGenericType = ? where fID = ? and fvID = ?',
					array($ftl->getGenericType(), $this->getFileID(), $this->getFileVersionID())
				);

				// we have a custom library script that handles this stuff
				$cl = $ftl->getCustomInspector();
				$cl->inspect($this);

			}
		}
		$this->refreshThumbnails(false);
		$f = $this->getFile();
		$f->refreshCache();
		$f->reindex();
	}

	public function refreshThumbnails($refreshCache = true) {
		if (defined('S3_ENABLED') && S3_ENABLED == true){
			return $this->S3_refreshThumbnails($pointer, $filename, $fr);
		}else{
			return parent::refreshThumbnails($pointer, $filename, $fr);
		}
	}

	public function S3_refreshThumbnails($refreshCache = true){
		$db = Loader::db();
		$f = Loader::helper('concrete/file');
		for ($i = 1; $i <= $this->numThumbnailLevels; $i++) {
			$path = $f->getThumbnailSystemPath($this->fvPrefix, $this->fvFilename, $i);
			$hasThumbnail = 0;

			if($this->S3_fileExist($path)){
				$hasThumbnail = 1;
			}
				
			$db->Execute("update FileVersions set fvHasThumbnail" . $i . "= ? where fID = ? and fvID = ?", array($hasThumbnail, $this->fID, $this->fvID));
		}

		if ($refreshCache) {
			$fo = $this->getFile();
			$fo->refreshCache();
		}
	}

	public function S3_fileExist($path){
		$path = str_replace(S3_PATH.'/','',$path);//remove absolute uri
		$s3 = Loader::helper('s3');			
		$s3 = new S3Helper();
		$response = $s3->getObject(S3_BUCKET, $path);

		if($response == false)
			return false;
		else
			return $response;
	}
}