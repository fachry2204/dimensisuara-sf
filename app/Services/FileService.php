<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

use App\Models\File;
use App\Models\Track;

class FileService
{
    public static function chunkStore($chunk, $index, $filename)
    {
        // Define folder name
        $filedir = 'chunk_' . $filename;

        // Check for global temp folder
        $directory  = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        if (!FileFacade::exists($directory)) {
            FileFacade::makeDirectory($directory, 0755, true);
        }

        // Check for chunk folder
        $chunk_directory = $directory . DIRECTORY_SEPARATOR . $filedir;
        if (!FileFacade::exists($chunk_directory)) {
            FileFacade::makeDirectory($chunk_directory, 0755, true);
        }

        // Store chunk
        FileFacade::put($chunk_directory . DIRECTORY_SEPARATOR . "chunk_{$index}", file_get_contents($chunk));
    }

    public static function chunkMerge($filename, $total_chunks_original, $hash_original)
    {
        // Define directories
        $directory          = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        $chunk_directory    = $directory . DIRECTORY_SEPARATOR . 'chunk_' . $filename;
        $final_path         = $directory . DIRECTORY_SEPARATOR . $filename;

        try {
            // Get all chunks
            $chunks         = glob($chunk_directory . DIRECTORY_SEPARATOR . 'chunk_*');
            $total_chunks    = count($chunks);
            if($total_chunks != $total_chunks_original) {
                throw new \Exception("Total chunks does not match: {$total_chunks} != {$total_chunks_original}");
            }
    
            // Ensure correct order
            natsort($chunks); 
        
            // Create final empty file
            FileFacade::put($final_path, '');
    
            foreach ($chunks as $chunk) {
                // Merge chunks
                FileFacade::append($final_path, FileFacade::get($chunk));
    
                // Delete chunk after merging
                FileFacade::delete($chunk);
            }
            // Remove temporary chunks directory
            FileFacade::deleteDirectory($chunk_directory);
    
            // Compare hash
            $hash = hash_file('sha256', $final_path);
            if($hash != $hash_original) {
                throw new \Exception("Hash does not match");
            }
    
            return $final_path;
            
        } 
        catch (\Throwable $th) {

            // Remove temporary directory
            if(FileFacade::exists($chunk_directory)) {
                FileFacade::deleteDirectory($chunk_directory);
            }
    
            // Delete final file
            if(FileFacade::exists($final_path)) {
                FileFacade::delete($final_path);
            }

            throw $th;
        }
    }

    /**
     * Accept:
     * - File
     * - Contract
     * - Report
     * - Transaction
     * - Cover
     * - Thumbnail
     * - Track
     */
    public static function store($newfile, $owner, $profile, $type = null, $dir = 'files')
    {
        $file = DB::transaction(function () use ($newfile, $owner, $profile, $type, $dir ) {
            
            // Define variables
            $path = $name = $mime = $ext = $size = null;

            // Check for global file folder
            $directory = storage_path('app' . DIRECTORY_SEPARATOR . $dir);
            if (!FileFacade::exists($directory)) {
                FileFacade::makeDirectory($directory, 0755, true);
            }

            // Define filename
            $filename = bin2hex(random_bytes(16));

            // Get metadata
            if($newfile instanceof UploadedFile) {
                $path   = $newfile->getRealPath();
                $name   = $newfile->getClientOriginalName();
                $mime   = $newfile->getClientMimeType();
                $ext    = $newfile->getClientOriginalExtension();
                $size   = $newfile->getSize();

                if($owner instanceof Track) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['wav', 'flac'])) {
                        $ext = 'wav';
                    }
                }
            }
            else if($type == 'signature') {
                validateSignature($newfile);
                $name   = 'Signature';
                $mime   = 'image/jpeg';
                $ext    = 'jpeg';
                $size   = strlen($newfile);
            }
            
            else {
                throw new \Exception("Invalid file");
            }

            // Delete existing record with same owner_id, owner_type, and type
            File::where('owner_id', $owner->id)
                ->where('owner_type', get_class($owner))
                ->where('type', $type)
                ->delete();
            
            // Create file record
            $file = new File;
            $file->disk             = 'local';
            $file->identifier       = $dir . DIRECTORY_SEPARATOR . $filename . '.' . $ext;
            $file->original_name    = $name;
            $file->mime_type        = $mime;
            $file->size             = $size;
            $file->type             = $type;
            $file->uploaded_at      = now();

            // Assign file to owner
            $file->owner()->associate($owner);
            $file->profile()->associate($profile);
            $file->save();
            
            // Process image as JPEG
            if (strpos($mime, 'image/') === 0 || $type == 'signature') {
                $ext        = 'jpeg';
                $manager    = new ImageManager(Driver::class);
                $image      = $manager->read($newfile);
                $image->toJpeg(90, false, true);

                if($type == 'thumbnail') {
                    $image->resize(300, 300, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                $image->save($directory . DIRECTORY_SEPARATOR . $filename . '.' . $ext);

                // Set file identifier (change extension)
                $file->identifier   = $dir . DIRECTORY_SEPARATOR . $filename . '.' . $ext;
                $file->mime_type    = 'image/jpeg';
                $file->save();

                // Set file size for signature
                if($type == 'signature') {
                    $file->size = FileFacade::size($directory . DIRECTORY_SEPARATOR . $filename . '.' . $ext);
                    $file->save();
                }
            }
            
            // Move file
            else {
                FileFacade::copy($path, $directory . DIRECTORY_SEPARATOR . $filename . '.' . $ext);
            }

            return $file;
        });

        return $file;
    }

    public static function delete(File $file)
    {
        $file->delete();
    }
}
