<?php
defined('C5_EXECUTE') or die(_("Access Denied."));
class S3FileManagerPackage extends Package {
     protected $pkgHandle = 's3_file_manager';
     protected $appVersionRequired = '5.6.2';
     protected $pkgVersion = 'experimental';

     public function getPackageDescription() {
        return t("Stores Files on Amazons S3");
     }

     public function getPackageName() {
        return t("S3 File Manager");
     }
     
     public function install() {
        $pkg = parent::install();
     

        Loader::model('single_page');
        // install pages
        SinglePage::add('/dashboard/files/s3', $pkg);

        //copy files who cant overridden with package




     }
     

     public function on_start(){
        define('S3_PATH', 'https://'.S3_BUCKET.'.s3.amazonaws.com');
        define('S3_DIR_FILES_UPLOADED_THUMBNAILS', '/thumbnails');
        define('S3_DIR_FILES_UPLOADED_THUMBNAILS_LEVEL2', '/thumbnails/level2');
        define('S3_DIR_FILES_UPLOADED_THUMBNAILS_LEVEL3','/thumbnails/level3');


        $objEnv = Environment::get();
        $objEnv->overrideCoreByPackage('helpers/concrete/file.php', $this);
        $objEnv->overrideCoreByPackage('helpers/image.php', $this);
        $objEnv->overrideCoreByPackage('helpers/s3.php', $this);
        $objEnv->overrideCoreByPackage('libraries/file/importer.php', $this);
        $objEnv->overrideCoreByPackage('models/file.php', $this);
        $objEnv->overrideCoreByPackage('models/file_version.php', $this);
        $objEnv->overrideCoreByPackage('tools/files/bulk_properties.php', $this);
        $objEnv->overrideCoreByPackage('tools/files/properties.php', $this);
        $objEnv->overrideCoreByPackage('tools/files/get_data.php', $this);
     }


}
?>