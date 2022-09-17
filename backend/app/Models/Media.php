<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;

class Media extends Model
{
    const UPLOADS_PATH = 'uploads/'; // default uploads path
    const AVATAR_PATH = 'uploads/avatars/'; // default avatars image path
    
    protected $table = 'media';
    protected $fillable = [
        'path', 'type', 'name'
    ];
    
    /**
     * Rules for uploading a new file
     *
     * @return type
     */
    public function rules()
    {
        $rules = [
            'file' => 'required'
        ];
        
        return $rules;
    }
    
    /**
     * add the full site path to path field automaticly
     *
     * @param type $value
     * @return type
     */
    public function getPathAttribute($value)
    {
        if ($value == '') {
            return null;
        }
        
        return $this->getFileBy($value);
    }
    
    /**
     * Upload a base64 file
     *
     * @param type $file
     * @param type $path
     * @return array
     */
    public function uploadBase64($file, $path)
    {
        $mimeType = $this->getBase64Type($file);
        $fileName = uniqid() . $this->getExtension($mimeType);
        $name = $path . $fileName;
    
        if (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'])) {
            $fileArray = explode('base64,', $file);
            if (!isset($fileArray[1])) {
                throw new \Exception("Missing file {$fileName}");
            }
    
            file_put_contents($name, base64_decode($fileArray[1]));
        }
        elseif (in_array($mimeType, ['application/pdf', 'application/vnd.ms-excel'])) {
            file_put_contents($name, base64_decode($file));
        } else {
            return false;
        }
    
        return [
            'path' => $path,
            'filename' => $fileName
        ];
    }
    
    /**
     * Upload a file using request
     *
     * @param type $file
     * @param type $path
     * @return array
     */
    public function uploadFile(File $file, $path)
    {
        // new file filename
        $newFilename = self::getUniqueName($file->getClientOriginalExtension(), $file->getFilename());
        
        // save the uploaded file
        $file->move($path, $newFilename);
        
        return [
            'path' => $path,
            'filename' => $newFilename
        ];
    }
    
    /**
     * Upload a file
     *
     * @param type $request
     * @param type $property
     * @param type $path
     * @return boolean|\App\Models\Media
     */
    public function upload($request, $property = 'file', $path = self::UPLOADS_PATH)
    {
        $fileInfo = null;
        if (!\File::isDirectory($path)) {
            \File::makeDirectory($path, $mode = 0777, true, true);
        }
        if ($request->hasFile($property) && $request->file($property)->isValid()) {
            $fileInfo = $this->uploadFile($request->file($property), $path);
        } else {
            $fileInfo = $this->uploadBase64($request->input($property), $path);
        }
        if ($fileInfo) {
            // new file name and path
            $this->path = $fileInfo['path'].$fileInfo['filename'];
            $this->name = $fileInfo['filename'];
            $this->type = 'image/png';
//            $this->type = Image::make($this->path)->mime();
    
            $this->save();
            
            return $this;
        }
        
        return false;
    }
    
    /**
     * Upload Media files to private local storage
     * @param Request $request
     * @param $fieldName
     * @param string $folder
     * @return $this|bool
     * @throws \Exception
     */
    public function uploadToStorage(Request $request, $fieldName, $folder = self::UPLOADS_PATH) {
        if (!\Storage::disk('local')->exists($folder)) {
            if (!\Storage::disk('local')->makeDirectory($folder)) {
                throw new \Exception('Was not possible to create folder: '. $folder);
            }
        }
        
        $path = \Storage::disk('local')->path($folder);
        
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {
            $fileInfo = $this->uploadFile($request->file($fieldName), $path);
        } else {
            $fileInfo = $this->uploadBase64($request->get($fieldName), $path);
        }
        
        if ($fileInfo) {
            // new file name and path
            $this->path = $folder.$fileInfo['filename'];
            $this->type = Image::make($fileInfo['path'].$fileInfo['filename'])->mime();
            $this->save();
            
            return $this;
        }
        
        return false;
    }
    
    /**
     * Create a thumbnail of a file
     *
     * @param string $newFileName
     * @param int $quality
     * @return string
     */
    public function createThumbnail($newFileName = null, $quality = 40)
    {
        if (is_null($newFileName)) {
            $currentName = explode('/', $this->path);
            $newFileName = self::UPLOADS_PATH . 'thumbnail_' . end($currentName);
        }
        
        $img = Image::make($this->path);
        
        $img->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
        });
        
        $img->save($newFileName, $quality);
        
        return $img;
    }
    
    /**
     * Create a blur of the provided image
     *
     * @param string $newFileName
     * @param int $blur
     * @param int $quality
     * @return string
     */
    public function createBlur($newFileName = null, $blur = 10, $quality = 20)
    {
        if (is_null($newFileName)) {
            $currentName = explode('/', $this->path);
            $newFileName = self::UPLOADS_PATH . 'blur_' . end($currentName);
        }
        
        $img = Image::make($this->path);
        
        // apply stronger blur
        $img->blur($blur);
        
        $img->save($newFileName, $quality);
        
        return $img;
    }
    
    /**
     *
     *
     * @param type $extension
     * @param type $name
     * @return string
     */
    protected static function getUniqueName($extension, $name = 'upload')
    {
        return $name . '-' . date('d-m-Y-his') . '.' . $extension;
    }
    
    /**
     * get the file mime type
     *
     * @param type $str
     * @return type
     */
    protected function getBase64Type($str)
    {
        // $str should start with 'data:' (= 5 characters long!)
        return substr($str, 5, strpos($str, ';') - 5);
    }
    
    /**
     * find the file extention from its mime type
     *
     * @param type $mimeType
     * @return string
     */
    protected function getExtension($mimeType)
    {
        $mimeTypes = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        
        $type = array_search($mimeType, $mimeTypes);
        
        if ($type) {
            return '.' . $type;
        }
        
        return '.txt';
    }
    
    
    public function getFileBy($filePath) {
        if (\File::exists($filePath)) {
            return asset($filePath);
        }
        elseif (\Storage::disk('local')->exists($filePath)) {
            return \Storage::disk('local')->path($filePath);
        } else {
            return asset('images/user.png');
        }
        
        return false;
    }
    
}
