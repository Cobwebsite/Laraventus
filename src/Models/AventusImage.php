<?php

namespace Aventus\Laraventus\Models;

use Exception;
use Intervention\Image\ImageManager;

/**
 * 
 */
abstract class AventusImage extends AventusFile
{
    protected function before_save($model, $key)
    {
        if ($this->upload != null) {
            $file = $this->upload;
            $fs = $this->define_filesystem();
            $base_directory = $this->get_save_directory($model);
            $filename = $this->get_file_name($file);

            $maxSize = $this->max_size();
            $extension = $this->force_extension();
            if($extension === true) {
                throw new Exception("Impossible to have a true as return value of force_extension");
            }
            $sameExtension = $extension == false || $extension == $this->upload->getClientOriginalExtension();
            if ($maxSize["width"] == null && $maxSize["height"] == null && $sameExtension) {
                $path = $fs->putFileAs($base_directory, $file, $filename);
            } else {
                $mimeType = $file->getMimeType();
                $image = ImageManager::gd()->read($file->getContent());
                $encoded = "";
                if ($mimeType !== 'image/svg+xml' && ($maxSize["width"] != null || $maxSize["height"] != null)) {
                    $image->resizeDown($maxSize["width"], $maxSize["height"]);
                    $encoded = $image->encodeByExtension()->toString();
                }
                if (!$sameExtension) {
                    $finalExtension = $extension == false ? $this->upload->getClientOriginalExtension() : $extension;
                    if (str_starts_with($finalExtension, ".")) {
                        $finalExtension = substr($finalExtension, 1);
                        $encoded = $image->encodeByExtension($finalExtension)->toString();
                        $filename = str_replace("." . $file->getClientOriginalExtension(), $finalExtension, $filename);
                    }
                }

                if($encoded == "") {
                    $encoded = $image->encodeByExtension()->toString();
                }
                $path = $fs->putFileAs($base_directory, $encoded, $filename);
            }
            $this->uri = $this->get_uri($path);
        }
    }
    /**
     * Define max size for your image.
     * If the value of width or height is null, the size will be auto adapted
     * If the value of width an height is null, the size will be the same as uploaded
     */
    protected function max_size()
    {
        return ["width" => null, "height" => null];
    }
    /**
     * Return the file extension you need for example cast all images to webp
     */
    protected function force_extension(): bool|string
    {
        return false;
    }
}
