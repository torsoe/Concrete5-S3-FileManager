<?php 

defined('C5_EXECUTE') or die("Access Denied.");

class FileImporter extends Concrete5_Library_FileImporter {



	public function import($pointer, $filename = false, $fr = false) {
		//start S3 tour if s3 is enabled
		if (defined('S3_ENABLED') && S3_ENABLED == true){
			return $this->S3_Import($pointer, $filename, $fr);
		}else{
			return parent::import($pointer, $filename, $fr);
		}
	}



	protected function S3_Import($pointer, $filename = false, $fr = false){
		if ($filename == false) {
			// determine filename from $pointer
			$filename = basename($pointer);
		}
		
		$fh = Loader::helper('validation/file');
		$fi = Loader::helper('file');
		$filename = $fi->sanitize($filename);
		
		// test if file is valid, else return FileImporter::E_FILE_INVALID
		if (!$fh->file($pointer)) {
			return FileImporter::E_FILE_INVALID;
		}
		
		if (!$fh->extension($filename)) {
			return FileImporter::E_FILE_INVALID_EXTENSION;
		}

		
		$prefix = $this->generatePrefix();
		
		// do save in the FileVersions table
		
		// move file to correct area in the filesystem based on prefix
		$response = $this->S3_storeFile($prefix, $pointer, $filename, $fr);
		if (!$response) {
			return FileImporter::E_FILE_UNABLE_TO_STORE;
		}
		
		if (!($fr instanceof File)) {
			// we have to create a new file object for this file version
			$fv = File::add($filename, $prefix);
			$fv->refreshAttributes(true);
			$fr = $fv->getFile();
		} else {
			// We get a new version to modify
			$fv = $fr->getVersionToModify(true);
			$fv->updateFile($filename, $prefix);
			$fv->refreshAttributes();
		}

		$fr->refreshCache();
		return $fv;
	}


	protected function S3_storeFile($prefix, $pointer, $filename, $fr = false){
		$fi = Loader::helper('concrete/file');
		$path = str_replace(S3_PATH.'/','',$fi->mapSystemPath($prefix, $filename));

		$s3 = Loader::helper('s3');			
		$s3 = new S3Helper();
		$r = $s3->putObject(S3Helper::inputFile($pointer), S3_BUCKET, $path, S3Helper::ACL_PUBLIC_READ,array('Content-Type'=>'image/jpg'),array('Content-Type'=>'image/jpg'));

		return $r;
	}

}
