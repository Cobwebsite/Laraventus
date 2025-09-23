<?php

namespace Aventus\Laraventus\Models;

use Aventus\Laraventus\Tools\Console;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * 
 */
abstract class AventusFile implements CastsAttributes
{
    /**
     * Get from the db
     */
    public function get($model, $key, $value, $attributes)
    {
        // maybe check if $model->$key is nullable to return null instead of AventusFile
        if (is_string($value)) {
            $result = new static($model);
            $result->uri = $value;
            return $result;
        }
        return null;
    }

    /**
     * Set into the db
     */
    public function set($model, $key, $value, $attributes)
    {
        if ($value instanceof AventusFile) {
            $value->before_save($model, $key);
            return $value->uri;
        } else if (is_array($value) && array_key_exists("uri", $value)) {
            $result = new static($model);
            $result->uri = $value["uri"] ?? "";
            if (array_key_exists("upload", $value)) {
                $result->upload = $value["upload"];
            }
            $result->before_save($model, $key);
            return $result->uri;
        }
        return null;
    }


    public string $uri = "";
    public ?UploadedFile $upload = null;

    protected function before_save($model, $key)
    {
        if ($this->upload != null) {
            $file = $this->upload;
            $fs = $this->define_filesystem();
            $base_directory = $this->get_save_directory($model);
            $filename = $this->get_file_name($file);
            $path = $fs->putFileAs($base_directory, $file, $filename);
            $this->uri = $this->get_uri($path);

            // $mimeType = $file->getMimeType();
            // if ($mimeType !== 'image/svg+xml') {
            //     $image = ImageManager::gd()->read($file->getContent());
            //     $image->resizeDown(width: 2000);
            //     $saveName = str_replace($file->getClientOriginalExtension(), "webp", $saveName);
            //     $image->save($base_directory . '/' . $saveName, ['format' => 'webp']);
            // }
        }
    }

    protected function define_filesystem(): Filesystem
    {
        return Storage::disk('public');
    }
    protected function get_uri(string $path): string
    {
        return Storage::url($path);
    }
    protected abstract function get_save_directory($model): string;
    protected function get_file_name(UploadedFile $upload): string
    {
        $random_str = Str::uuid()->toString();
        return $random_str . '.' . $upload->getClientOriginalExtension();
    }
}
