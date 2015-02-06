<?php

namespace rmatil\cms\Handler;

use rmatil\cms\Entities\File;
use rmatil\cms\Exceptions\DocFormatNotFoundException;
use rmatil\cms\Exceptions\DocTypeNotFoundException;
use rmatil\cms\Exceptions\FileNotSavedException;
use rmatil\cms\Handler\ThumbnailHandler;

class FileHandler {

    /**
     * allowed file extensions
     * 
     * @var array
     */
    private $allowedFileExtensions = array();

    private $localPathToMediaDir;

    private $httpPathToMediaDir;

    /**
     * Init file handler
     * 
     * @param string $localPathToMediaDir Local path to media dir
     */
    public function __construct($httpPathToMediaDir, $localPathToMediaDir) {
        $this->allowedFileExtensions['image']       = array('jpg', 'jpeg', 'png', 'gif', 'tiff', 'svg');
        $this->allowedFileExtensions['documents']   = array('doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'xls', 'xlsx', 'pages', 'keynote', 'numbers', 'pdf', 'odt', 'txt', 'zip');
        $this->allowedFileExtensions['audio']       = array('mp3', 'm4a', 'aac', 'ogg', 'wav');
        $this->allowedFileExtensions['video']       = array('mp4', 'm4v', 'mkv', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2');
        $this->httpPathToMediaDir                   = $httpPathToMediaDir;
        $this->localPathToMediaDir                  = $localPathToMediaDir;
    }

    public function saveUploadedFile(File &$fileObject) {
        // file: name of form input field for file
        // tmp_name: The temporary filename of the file in which the uploaded file was stored on the server.
        if (!empty($_FILES['file']['error'])) {
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_FORM_SIZE:
                    throw new FileNotSavedException('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
                    break;

                case UPLOAD_ERR_PARTIAL:
                    throw new FileNotSavedException('The uploaded file was only partially uploaded');
                    break;

                case UPLOAD_ERR_NO_FILE:
                    throw new FileNotSavedException('No file was uploaded');
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new FileNotSavedException('Missing a temporary folder');
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    throw new FileNotSavedException('Failed to write file to disk');
                    break;

                case UPLOAD_ERR_EXTENSION:
                    throw new FileNotSavedException('A PHP extension stopped the file upload');
                    break;
            }
        }

        if ($_FILES['file']['size'] >= $this->getUploadMaxFileSize()) {
            throw new FileNotSavedException(sprintf('Your uploaded file exceeds the maximum allowed file size of %s', $this->getUploadMaxFileSize()));
        }
        $fileObject->setSize($_FILES['file']['size']);

        $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($fileExtension === null) {
            // no file extension provided
            throw new FileNotSavedException(sprintf('Your uploaded file must have a file extension'));
        }
        $fileObject->setExtension(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        if (!$this->formatHasType('image', $fileExtension) &&
            !$this->formatHasType('documents', $fileExtension) &&
            !$this->formatHasType('audio', $fileExtension) &&
            !$this->formatHasType('video', $fileExtension)) {
            throw new FileNotSavedException(sprintf('Your uploaded file does not have a valid file extension'));
        }

        // strip whitespaces & german umlauts from filename
        $fileName   = explode(sprintf('.%s', $fileExtension), $_FILES['file']['name']);
        
        if ($fileName === false || // file name was an empty string
            (is_array($fileName) && empty($fileName)) || // in case a negative limit is used
            (is_array($fileName) && $fileName[0] === $_FILES['file']['name']) // delimiter was not found 
           ) {
            throw new FileNotSavedException('Could not replace whitespaces in filename');
        }

        $fileName                = $this->replaceWhitespacesFromString($fileName[0]);
        $_FILES['file']['name']  = $fileName;
        $fileObject->setName($fileName);

        if (file_exists(sprintf('%s/%s.%s', $this->localPathToMediaDir, $_FILES['file']['name'], $fileExtension))) {
            throw new FileNotSavedException('A file with the same name already exists');
        }

        // all checks passed -> move file to media dir
        // echo"<pre>";var_dump($_FILES);exit();
        $code = move_uploaded_file($_FILES['file']['tmp_name'], sprintf('%s/%s.%s', $this->localPathToMediaDir, $_FILES['file']['name'], $fileExtension));
        if ($code === false) {
            // not a valid uploaded file or valid, but cannot be moved
            throw new FileNotSavedException('An unknown error occured');
        }
        $fileObject->setLink(sprintf('%s/%s.%s', $this->httpPathToMediaDir, $_FILES['file']['name'], $fileExtension));
        $fileObject->setLocalPath(sprintf('%s/%s.%s', $this->localPathToMediaDir, $_FILES['file']['name'], $fileExtension));

        // create thumbnail with width of 40px
        ThumbnailHandler::createThumbnail($fileObject, $this->httpPathToMediaDir, $this->localPathToMediaDir, $_FILES['file']['name'], $fileExtension, 40, null);
    }

    public function deleteFileOnDisk($fileName, $extension) {
        $path = sprintf('%s/%s.%s', $this->localPathToMediaDir, $fileName, $extension);
        
        $ret = true;
        if (file_exists($path)) {
            $ret = @unlink($path);
        }

        // TODO: delete thumbnail

        if (!$ret) {
            throw new \Exception(sprintf('Failed to delete file %s.%s with path %s', $fileName, $extension, $path));
        }
    }

    /**
     * Adds a given file extension to a given document type. Type must be one
     * of image, documents, audio, video
     * 
     * @param string $type   The document type
     * @param string $format The file extension to add
     *
     * @throws \rmatil\cms\Exceptions\DocTypeNotFoundException If the provided document type is not found
     */
    public function addFormatForType($type, $format) {
        $types = array('image', 'documents', 'audio', 'video');
        if (!in_array($type, $types)) {
            throw new DocTypeNotFoundException(sprintf('Type %s not found', $type));
        }

        if ($this->formatHasType($type, $format)) {
            // format is already contained
            return;
        }

        $this->allowedFileExtensions[$type][] = $format;
    }

    /**
     * Removes a given file extension from a document type. 
     * Type must be one of image, documents, audio, video.
     * 
     * @param  string $type   The document type
     * @param  string $format The format, i.e. the file extension, to remove from the type
     *
     * @throws \rmatil\cms\Exceptions\DocTypeNotFoundException If the provided document type is not found
     * @throws \rmatil\cms\Exceptions\DocFormatNotFoundException If the provided file extension is not found
     */
    public function removeFormatFromType($type, $format) {
        $types = array('image', 'documents', 'audio', 'video');
        if (!in_array($type, $types)) {
            throw new DocTypeNotFoundException(sprintf('Type %s not found', $type));
        }

        $formatIndex = array_search($format, $this->allowedFileExtensions[$type]);

        if ($formatIndex === false) {
            throw new DocFormatNotFoundException(sprintf('Format %s for Type %s not found', $format, $type));
        }

        // remove format
        unset($this->allowedFileExtensions[$type][$formatIndex]);
        // reset array keys
        $this->allowedFileExtensions[$type] = array_values($this->allowedFileExtensions[$type]);
    }

    /**
     * Checks whether the given document type contains
     * the given format as an allowed extension
     * 
     * @param  string $type   The document type
     * @param  string $format The format to check
     * @return boolean        True, if the format is contained as allowed format in the document type, otherwise false
     */
    public function formatHasType($type, $format) {
        $types = array('image', 'documents', 'audio', 'video');
        if (!in_array($type, $types)) {
            throw new DocTypeNotFoundException(sprintf('Type %s not found', $type));
        }

        return in_array($format, $this->allowedFileExtensions[$type]);
    }

    /**
     * Gets the allowed file extensions.
     *
     * @return array
     */
    public function getAllowedFileExtensions() {
        return $this->allowedFileExtensions;
    }

    /**
     * Sets the allowed file extensions.
     *
     * @param array $allowedFileExtensions the allowed file extensions
     */
    public function setAllowedFileExtensions(array $allowedFileExtensions) {
        $this->allowedFileExtensions = $allowedFileExtensions;
    }

    /**
     * Returns the maximum value of post_max_size and upload_max_filesize
     * as set in php.ini
     * @return string The max file size on which the upload will not fail
     */
    private function getUploadMaxFileSize() {
        return min($this->getFileSizeInBytes(ini_get('post_max_size')), 
                   $this->getFileSizeInBytes(ini_get('upload_max_filesize')));
    }

    /**
     * Converts the php.ini notation for number (like 2M) to 
     * an integer representation.
     * 
     * @param  string $size String to transform
     * @return integer      An integer representation in bytes
     */
    private function getFileSizeInBytes($size)  {  
        $l = substr($size, -1);  
        $ret = substr($size, 0, -1);  
        switch(strtoupper($l)){  
        case 'P':  
            $ret *= 1024;  
        case 'T':  
            $ret *= 1024;  
        case 'G':  
            $ret *= 1024;  
        case 'M':  
            $ret *= 1024;  
        case 'K':  
            $ret *= 1024;  
            break;  
        }  
        return $ret;  
    }

    /**
     * Returns a given filesize in a human readable format.
     * 
     * @param  String $bytes    Number of bytes to convert
     * @return String           Filesize with KB, MB, ...
     */
    private function getFileSizeHuman($bytes) {
        $output = $bytes." B"; 

        if ($bytes>=1024) {
            // output in kb with one decimal place
            $kb     = sprintf("%01.1f",$bytes/1024);
            $output = "$kb KB";
        }

        // bigger than 100kb
        if ($bytes>=100*1024) {
            $kb     = round($bytes/1024);
            $output = "$kb KB";
        }

        // bigger than 1024 KB
        if ($bytes>=1024*1024) {
            $mb     = sprintf("%01.1f",$bytes/1048576);
            $output = "$mb MB";
        }

        // bigger than 1024 MB
        if ($bytes>=1024*1024*1024) {
            $gb     = sprintf("%01.1f",$bytes/1073741824);
            $output = "$gb GB";
        }
    
        return $output;
     }


    /**
    * Replaces whitespaces with a dash and removes 
    * german umlauts. Additionally converts the 
    * string to lowercase.
    * 
    * @param string $string The string to apply this functionality on.
    * 
    * @return string The edited string
    */
    private function replaceWhitespacesFromString($string) {
        // lower case everything
        $string    = strtolower($string);
        // clean up multiple dashes or whitespaces
        $string    = preg_replace("/[\s-]+/", " ", $string);
        // convert dots to dashes
        $string    = preg_replace("/\./", "-", $string);
        // convert whitespaces and underscore to dash
        $string    = preg_replace("/[\s_]/", "-", $string);
        // removes german umlauts
        $string    = preg_replace("/[\x7f-\xff]/", "", $string);

        return $string;
     }
}