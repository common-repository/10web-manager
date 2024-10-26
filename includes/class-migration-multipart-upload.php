<?php
namespace Tenweb_Manager {

    include_once TENWEB_INCLUDES_DIR . '/class-api.php';
    include_once TENWEB_INCLUDES_DIR . '/class-amazon.php';
    include_once TENWEB_INCLUDES_DIR . '/class-amazon-multipart-upload.php';

    class MigrationMultiPartUpload extends Migration
    {
        /**
         * @var string $file
         */
        private $file;

        /**
         *  default is 5 mb
         *
         * @var integer $chunk_size
         */
        private $chunk_size;
        /**
         * @var integer $offset
         */
        private $offset = 0;
        /**
         * @var array $aws_credentials
         */
        private $aws_credentials = [];
        /**
         * @var AmazonMultiPartUpload $multipart_upload
         */
        private $multipart_upload;

        /**
         * MigrationMultiPartUpload constructor.
         *
         * @param $aws_credentials
         */
        public function __construct($aws_credentials)
        {
            parent::__construct();
            $this->aws_credentials = $aws_credentials;
            $this->chunk_size = $this->configs['TENWEB_MIGRATION_MULTIPART_UPLOAD_CHUNK_SIZE'] * 1024 * 1024;
        }

        /**
         * @param $file
         */
        public function initiate($file)
        {
            $this->offset = 0;
            $this->file = $file;
            
            $this->multipart_upload = new AmazonMultiPartUpload(new Amazon(
                $this->aws_credentials['credentials']['AccessKeyId'],
                $this->aws_credentials['credentials']['SecretAccessKey'],
                $this->aws_credentials['credentials']['SessionToken'],
                $this->aws_credentials['folder'] . '/' . basename($this->file),
                $this->aws_credentials['bucket'],
                $this->aws_credentials['region']
            ));

        }


        /**
         * @param        $file
         * @param        $aws_credentials
         * @param string $run_type
         *
         * @param bool   $initiate_upload
         *
         * @return bool
         */
        public static function run($file, $aws_credentials = null, $run_type = 'run', $initiate_upload = false)
        {
            Helper::store_migration_log('multipart_upload_run_type_' . current_time('timestamp'), 'Run type is ' . $run_type . '.');

            if ($run_type == 'run') {
                $upload_object = new self($aws_credentials);
            }

            if ($run_type == 'restart') {
                $upload_object = Migration::get_object_file_content();
            }

            if (isset($upload_object)) {

                if ($initiate_upload) {
                    $upload_object->initiate($file);
                }

                return $upload_object->upload($initiate_upload);
            }


            return false;
        }

        /**
         *
         * @param bool $initiate_upload
         *
         * @return bool
         */
        public function upload($initiate_upload = false)
        {
            $file_path = false;
            if ($initiate_upload) {
                if (!$this->multipart_upload->initiate()) {
                    Helper::store_migration_log('error_in_multipart_upload_initiate' . current_time('timestamp'), json_encode($this->multipart_upload->getErrors()));

                    return false;
                }
            }

            Helper::store_migration_log('start_multipart_upload_part part number: ' . $this->multipart_upload->part_number, 'Started multipart upload part. Upload id is: ' . $this->multipart_upload->upload_id);

            if ($this->uploadPart()) {
                $file_path = $this->multipart_upload->complete();
                if ($file_path === false) {
                    Helper::store_migration_log('error_in_multipart_upload_complete' . current_time('timestamp'), json_encode($this->multipart_upload->getErrors()));

                    return false;
                }

                Helper::store_migration_log('complete_multipart_upload' . current_time('timestamp'), 'Complete multipart upload part. Upload id is: ' . $this->multipart_upload->upload_id);

            }


            return $file_path;
        }

        /**
         * @return array|null
         */
        public static function getAmazonCredentials()
        {
            // get temp credentials
            $api = Api::get_instance();
            $aws_credentials = $api->get_amazon_tokens_for_migration(\TenwebServices::get_domain_id());
            if ($aws_credentials != null) {
                //can pass it as param, will change it
                return $aws_credentials;
            } else {
                Helper::store_migration_log('migration_archive_uploading_to_s3', 'Error while getting credentials.');
            }

            return null;
        }

        /**
         * @return bool
         */
        private function uploadPart()
        {
            $content = fopen($this->file, 'r');
            if (!$content) {
                Helper::store_migration_log('error_in_multipart_upload' . current_time('timestamp'), 'Empty file content. File does not exists or can not open it');

                return false;
            }

            $mime_type = mime_content_type($this->file);
            $file_size = filesize($this->file);
            fseek($content, $this->offset);

            while ($this->offset < $file_size) {
                $this->checkForRestart();

                $file_chunk = fread($content, $this->chunk_size);
                if (!$this->multipart_upload->uploadPart($file_chunk, $mime_type)) {
                    fclose($content);
                    Helper::store_migration_log('error_in_multipart_upload_part_upload' . current_time('timestamp'), json_encode($this->multipart_upload->getErrors()));

                    return false;
                }

                $this->offset += $this->chunk_size;
                fseek($content, $this->offset);
            }

            Helper::store_migration_log('end_multipart_upload_part part number: ' . $this->multipart_upload->part_number, 'End multipart upload part. Upload id is: ' . $this->multipart_upload->upload_id);

            return true;
        }

        private function checkForRestart()
        {
            $max_exec_time_server = ini_get('max_execution_time');
            $start = get_site_transient(TENWEB_PREFIX . "_migration_start_time");
            $script_exec_time = microtime(true) - $start;

            if ($script_exec_time >= ((int)$max_exec_time_server - $this->configs['TENWEB_MIGRATION_EXEC_TIME_OFFSET'])) {
                $this->writeObjectFile();
                $this->restart();
            }
        }

        private function writeObjectFile()
        {
            Helper::store_migration_log('start_write_object_file_multipart_upload_' . current_time('timestamp'), 'Starting write object file in multipart upload.');
            $this->wpdb = null;
            $content = serialize($this);
            file_put_contents($this->archive_dir . '/content_object.txt', $content);
            Helper::store_migration_log('end_write_object_file_multipart_upload_' . current_time('timestamp'), 'End write object file in multipart upload.');
        }

    }
}
