<?php


namespace App\Helpers;


use Exception;
use File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class FileHelper
{
    /* Upload file function */
    public static function uploadFile($file, $path, $wmax = null, $hmax = null): array
    {
        try {
            $id = auth('sanctum')->id() ?? "0001";
            $ext = strtolower(preg_replace("#.+\.([a-z]+)$#i", "$1", $file->getClientOriginalName()));
            $fileName = $id . '-' . now()->unix() . '.' . $ext;
            $file->storeAs('public/images/' . $path, $fileName);
//            $img = self::resizeMedium($file);
//            $folder = storage_path('app/public/images/' . $path.'/');
//            if (!File::exists($folder)) {
//                File::makeDirectory($folder, 0775, true, true);
//            }
//            $img->save($folder.$fileName);

//            $source = Storage::path('/public/images/'.$path . '/' . $fileName);
//            Image::make($file)->fit($wmax, $hmax)->save($source);
            return ['status' => true, 'code' => ResponseError::NO_ERROR, 'data' => $path.'/'.$fileName];
        } catch (Exception $e) {
            return ['status' => false, 'code' => ResponseError::ERROR_400, 'message' => $e->getMessage()];
        }
    }

    /* Download file function */
    public static function downloadFile($path, $name){
        $path = Storage::disk('public')->path($path);
        return response()->download($path, $name);
    }

    /* Delete file function */
    public static function deleteFile($path){
        return Storage::disk('public')->delete('images/' . $path);
    }

    /* Обрезка картинки под стандарты системы */
    public static function resize($target, $dest, $wmax, $hmax, $ext){
        list($w_orig, $h_orig) = getimagesize($target);
        $ratio = $w_orig / $h_orig;

        if (($wmax / $hmax) > $ratio){
            $wmax = $hmax * $ratio;
        } else {
            $hmax = $wmax / $ratio;
        }
        $img = "";
        switch ($ext){
            case ("gif"):
                $img = imagecreatefromgif($target);
                break;
            case ("png"):
                $img = imagecreatefrompng($target);
                break;
            default:
                $img = imagecreatefromjpeg($target);
        }

        $newImg = imagecreatetruecolor($wmax, $hmax);
        if ($ext == "png"){
            imagesavealpha($newImg, true);
            $transPng = imagecolorallocatealpha($newImg, 0,0,0,127);
            imagefill($newImg, 0,0, $transPng);
        }
        imagecopyresampled($newImg, $img, 0,0,0,0, $wmax, $hmax, $w_orig, $h_orig);
        switch ($ext){
            case("gif"):
                imagegif($newImg, $dest);
                break;
            case("png"):
                imagepng($newImg, $dest);
                break;
            default:
                imagejpeg($newImg,$dest);
        }
        imagedestroy($newImg);
    }

    public static function resizeMedium($file): \Intervention\Image\Image
    {
        $img = Image::make($file->getRealPath());
        $img->orientate();
        $img->resize(500, 500, function ($constraint) {
            $constraint->aspectRatio();
        });
        return $img;
    }

}
