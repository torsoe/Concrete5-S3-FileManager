<?php 

class File extends Concrete5_Model_File {

	public function delete() {
		if (defined('S3_ENABLED') && S3_ENABLED == true){
			return $this->S3_delete();
		}else{
			return parent::delete();
		}
	}

	public function S3_delete() {
		// first, we remove all files from the drive
		$db = Loader::db();
		$pathbase = false;
		$r = $db->GetAll('select fvFilename, fvPrefix from FileVersions where fID = ?', array($this->fID));
		$h = Loader::helper('concrete/file');
		Loader::model('file_storage_location');
		if ($this->getStorageLocationID() > 0) {
			$fsl = FileStorageLocation::getByID($this->getStorageLocationID());
			$pathbase = $fsl->getDirectory();
		}
		foreach($r as $val) {
			
			// Now, we make sure this file isn't referenced by something else. If it is we don't delete the file from the drive
			$cnt = $db->GetOne('select count(*) as total from FileVersions where fID <> ? and fvFilename = ? and fvPrefix = ?', array(
				$this->fID,
				$val['fvFilename'],
				$val['fvPrefix']
			));
			if ($cnt == 0) {
				if ($pathbase != false) {
					$path = $h->mapSystemPath($val['fvPrefix'], $val['fvFilename'], false, $pathbase);
				} else {
					$path = $h->mapSystemPath($val['fvPrefix'], $val['fvFilename'], false);
				}
				$t1 = $h->getThumbnailSystemPath($val['fvPrefix'], $val['fvFilename'], 1);
				$t2 = $h->getThumbnailSystemPath($val['fvPrefix'], $val['fvFilename'], 2);
				$t3 = $h->getThumbnailSystemPath($val['fvPrefix'], $val['fvFilename'], 3);
			

				$path = str_replace(S3_PATH.'/','',$path);//remove absolute uri
				$t1 = str_replace(S3_PATH.'/','',$t1);//remove absolute uri
				$t2 = str_replace(S3_PATH.'/','',$t2);//remove absolute uri
				$t3 = str_replace(S3_PATH.'/','',$t3);//remove absolute uri

				$s3 = Loader::helper('s3');			
				$s3 = new S3Helper();

				$s3->deleteObject(S3_BUCKET,$path);
				$s3->deleteObject(S3_BUCKET,$t1);
				$s3->deleteObject(S3_BUCKET,$t2);
				$s3->deleteObject(S3_BUCKET,$t3);
			}
		}
		
		// now from the DB
		$db->Execute("delete from Files where fID = ?", array($this->fID));
		$db->Execute("delete from FileVersions where fID = ?", array($this->fID));
		$db->Execute("delete from FileAttributeValues where fID = ?", array($this->fID));
		$db->Execute("delete from FileSetFiles where fID = ?", array($this->fID));
		$db->Execute("delete from FileVersionLog where fID = ?", array($this->fID));
		$db->Execute("delete from FileSearchIndexAttributes where fID = ?", array($this->fID));
		$db->Execute("delete from DownloadStatistics where fID = ?", array($this->fID));
		$db->Execute("delete from FilePermissionAssignments where fID = ?", array($this->fID));		
	}
}