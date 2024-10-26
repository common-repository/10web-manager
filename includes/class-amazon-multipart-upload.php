<?php
namespace Tenweb_Manager {

    class AmazonMultiPartUpload
    {
        private $request_data;

        public $upload_id;
        public $part_number = 1;
        private $ETag = '';
        private $parts = [];
        private $errors = [];
        private $timeout = 50;

        /**
         * AmazonMultiPartUpload constructor.
         *
         * @param Amazon $request_data
         */
        public function __construct(Amazon $request_data)
        {
            $this->request_data = $request_data;
        }

        /**
         * @return bool
         */
        public function initiate()
        {
            $this->request_data->setMethod('POST');
            $this->request_data->setQueryString(array('uploads' => ''));
            $request_data = $this->request_data->getRequestData();

            $response = wp_remote_request($request_data['url'], array(
                'timeout' => $this->timeout,
                'method'  => 'POST',
                'headers' => $request_data['headers'],
            ));

            $body = $this->parseResponseBody($response);
            if ($body === false) {
                return false;
            }
            $this->upload_id = (string)$body->UploadId;

            return true;
        }

        /**
         * @param $file_chunk
         * @param $mime_type
         *
         * @return bool
         */
        public function uploadPart($file_chunk, $mime_type)
        {
            $this->request_data->setMethod('PUT');
            $this->request_data->setQueryString(array('partNumber' => $this->part_number, 'uploadId' => $this->upload_id));
            $this->request_data->setPayload($file_chunk);
            $this->request_data->setExtraHeaders(array('content-type' => $mime_type));

            $request_data = $this->request_data->getRequestData();

            $response = wp_remote_request($request_data['url'], array(
                'timeout' => $this->timeout,
                'method'  => 'PUT',
                'headers' => $request_data['headers'],
                'body'    => $file_chunk
            ));

            $ETag = $this->getETagFromResponseHeader($response);
            if ($ETag === false) {
                return false;
            }

            $this->ETag = $ETag;
            $this->addParts();
            $this->part_number += 1;

            return true;
        }

        /**
         * @return bool|string
         */
        public function complete()
        {
            $xml = $this->getCompleteXml();
            $this->request_data->setMethod('POST');
            $this->request_data->setQueryString(array('uploadId' => $this->upload_id));
            $this->request_data->setPayload($xml);
            $this->request_data->setExtraHeaders(array('content-type' => 'application/xml'));

            $request_data = $this->request_data->getRequestData();

            $response = wp_remote_request($request_data['url'], array(
                'timeout' => $this->timeout,
                'method'  => 'POST',
                'headers' => $request_data['headers'],
                'body'    => $xml
            ));

            $body = $this->parseResponseBody($response);
            if ($body === false) {
                return false;
            }

            return (string)$body->Location;
        }

        /**
         * @return array
         */
        public function getErrors()
        {
            return $this->errors;
        }

        /**
         * Collect multipart upload tags
         */
        private function addParts()
        {
            $this->parts[] = [
                'ETag'       => $this->ETag,
                'PartNumber' => $this->part_number,
            ];
        }

        /**
         * @return string
         */
        private function getCompleteXml()
        {
            $xml = '<CompleteMultipartUpload>';

            foreach ($this->parts as $part) {
                $xml .= '<Part>';
                $xml .= '<PartNumber>' . $part['PartNumber'] . '</PartNumber>';
                $xml .= '<ETag>' . $part['ETag'] . '</ETag>';
                $xml .= '</Part>';
            }

            $xml .= '</CompleteMultipartUpload>';

            return $xml;
        }

        /**
         * @param $response
         *
         * @return bool|\SimpleXMLElement
         */
        private function parseResponse($response)
        {
            if (is_wp_error($response)) {
                $this->errors['api_error'] = $response->get_error_message();

                return false;
            }
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code != 200) {
                $this->errors['api_error_content'] = $response;

                return false;
            }

            return true;
        }

        /**
         * @param        $response
         *
         *
         * @return bool|array
         */
        private function getETagFromResponseHeader($response)
        {
            if (!$this->parseResponse($response)) {
                return false;
            }

            return rtrim(ltrim(wp_remote_retrieve_header($response, 'ETag'), '"'), '"');
        }

        /**
         * @param $response
         *
         * @return bool|\SimpleXMLElement
         */
        private function parseResponseBody($response)
        {
            if (!$this->parseResponse($response)) {
                return false;
            }

            return simplexml_load_string($response['body']);
        }
    }
}
