<?php

namespace App\Http\Controllers;

use App\Models\Reel;
use Illuminate\Http\Request;
use App\Http\Resources\ReelResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;

class ReelController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Reel::class, 'reel');
    }

    /**
     * Index Reels
     * 
     * Display a listing of reels with view counts.
     */
    public function index()
    {
        $reels = Reel::orderBy('created_at', 'desc')
            ->get();

        if ($reels->isEmpty()) {
            return $this->error('No reels found', 404);
        }

        return $this->success('Reels retrieved successfully', ReelResource::collection($reels));
    }

    /**
     * Store Reels
     * 
     * Store a newly uploaded reel with chunk upload support.
     */
    public function store(Request $request)
    {
        if ($request->has('dzuuid') || $request->has('dztotalfilesize')) {
            // Get original filename from the request
            $originalName = $request->file('video') ? $request->file('video')->getClientOriginalName() : null;

            if ($originalName) {
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['mp4', 'mov', 'avi', 'wmv'];

                if (!in_array($extension, $allowedExtensions)) {
                    return $this->error('Invalid file type. Only MP4, MOV, AVI, and WMV files are allowed.', 422);
                }
            }

            // Validate total file size (from metadata)
            if ($request->has('dztotalfilesize')) {
                $totalSize = $request->input('dztotalfilesize');
                $maxSize = 1048576 * 1024; // 1GB in KB (matching your max:1048576 which is in KB)

                if ($totalSize > $maxSize) {
                    return $this->error('File too large. Maximum size is 1GB.', 422);
                }
            }
        }

        try {
            // Create the file receiver
            $receiver = new FileReceiver('video', $request, HandlerFactory::classFromRequest($request));

            // Check if the upload is success, throw exception or return response you need
            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }

            // Receive the file
            $save = $receiver->receive();

            // Check if the upload has finished (in chunk mode it will send smaller files)
            if ($save->isFinished()) {
                // Save the file and return newly saved file
                return $this->saveFile($save->getFile());
            }

            // We are in chunk mode, lets send the current progress
            /** @var AbstractHandler $handler */
            $handler = $save->handler();

            return $this->success('Chunk uploaded', [
                'done' => $handler->getPercentageDone(),
                'status' => 'chunk_uploaded',
            ]);

        } catch (UploadMissingFileException $e) {
            return $this->error('File missing from request', 422);
        } catch (\Exception $e) {
            return $this->error('Failed to upload reel', 500, $e->getMessage());
        }
    }

    /**
     * Save the uploaded file to storage and create reel record.
     */
    protected function saveFile($file)
    {
        $videoPath = $this->moveVideoFile($file);

        // Create reel record with filename as title
        $reel = Reel::create([
            // 'title' => pathinfo($fileName, PATHINFO_FILENAME),
            'video_path' => $videoPath,
            'view_count' => 0,
        ]);

        $reel->generateThumbnail();

        $reel->refresh();

        return $this->success('Reel uploaded successfully', new ReelResource($reel), 201);
    }

    /**
     * Create unique filename for the uploaded file.
     */
    protected function createFilename($file)
    {
        $extension = $file->getClientOriginalExtension();
        $filename = str_replace('.' . $extension, '', $file->getClientOriginalName());
        $filename .= '_' . md5(time()) . '.' . $extension;

        return $filename;
    }

    /**
     * Move uploaded video to storage and return relative path.
     */
    protected function moveVideoFile($file)
    {
        $fileName = $this->createFilename($file);

        // Store the file on the public disk
        $diskPath = 'reels/videos';
        Storage::disk('public')->putFileAs($diskPath, $file, $fileName);

        return 'storage/' . $diskPath . '/' . $fileName;
    }

    /**
     * Show Reel
     * 
     * Display the specified reel and increment view count.
     */
    public function show(Reel $reel)
    {
        // Increment view count
        $reel->incrementViewCount();

        // Refresh to get updated view count
        $reel->refresh();

        return $this->success('Reel retrieved successfully', new ReelResource($reel));
    }

    /**
     * Update Reel
     * 
     * Update the specified reel video.
     */
    public function update(Request $request, Reel $reel)
    {
        $isChunked = $request->has('dzuuid') || $request->has('dztotalfilesize');

        if ($isChunked) {
            // Validate extension from original filename if present
            $originalName = $request->file('video') ? $request->file('video')->getClientOriginalName() : null;

            if ($originalName) {
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedExtensions = ['mp4', 'mov', 'avi', 'wmv'];

                if (!in_array($extension, $allowedExtensions)) {
                    return $this->error('Invalid file type. Only MP4, MOV, AVI, and WMV files are allowed.', 422);
                }
            }

            // Validate total file size (from metadata)
            if ($request->has('dztotalfilesize')) {
                $totalSize = $request->input('dztotalfilesize');
                $maxSize = 1048576 * 1024; // 1GB in KB (matching your max:1048576 which is in KB)

                if ($totalSize > $maxSize) {
                    return $this->error('File too large. Maximum size is 1GB.', 422);
                }
            }
        } else {
            $validator = Validator::make($request->all(), [
                'video' => 'required|file|mimes:mp4,mov,avi,wmv|max:1048576',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }
        }

        try {
            // Create the file receiver (supports chunked and single uploads)
            $receiver = new FileReceiver('video', $request, HandlerFactory::classFromRequest($request));

            if ($receiver->isUploaded() === false) {
                throw new UploadMissingFileException();
            }

            $save = $receiver->receive();

            if ($save->isFinished()) {
                $oldVideoPath = $reel->video_path;
                $oldThumbnailPath = $reel->thumbnail_path;
                $newVideoPath = $this->moveVideoFile($save->getFile());

                $reel->video_path = $newVideoPath;
                $reel->save();

                $reel->generateThumbnail();

                // Remove old file after successful replacement
                if ($oldVideoPath) {
                    $oldVideoDiskPath = Reel::normalizePublicDiskPath($oldVideoPath);
                    if ($oldVideoDiskPath) {
                        Storage::disk('public')->delete($oldVideoDiskPath);
                    }
                }

                if ($oldThumbnailPath) {
                    $oldThumbnailDiskPath = Reel::normalizePublicDiskPath($oldThumbnailPath);
                    if ($oldThumbnailDiskPath) {
                        Storage::disk('public')->delete($oldThumbnailDiskPath);
                    }
                }

                return $this->success('Reel video updated successfully', new ReelResource($reel));
            }

            /** @var AbstractHandler $handler */
            $handler = $save->handler();

            return $this->success('Chunk uploaded', [
                'done' => $handler->getPercentageDone(),
                'status' => 'chunk_uploaded',
            ]);
        } catch (UploadMissingFileException $e) {
            return $this->error('File missing from request', 422);
        } catch (\Exception $e) {
            return $this->error('Failed to update reel video', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete reel
     * 
     * Remove the specified reel.
     */
    public function destroy(Reel $reel)
    {
        try {
            // Delete video file
            if ($reel->video_path) {
                $videoDiskPath = Reel::normalizePublicDiskPath($reel->video_path);
                if ($videoDiskPath) {
                    Storage::disk('public')->delete($videoDiskPath);
                }
            }

            if ($reel->thumbnail_path) {
                $thumbnailDiskPath = Reel::normalizePublicDiskPath($reel->thumbnail_path);
                if ($thumbnailDiskPath) {
                    Storage::disk('public')->delete($thumbnailDiskPath);
                }
            }

            $reel->delete($reel);

            return $this->success('Reel deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete reel', 500, ['error' => $e->getMessage()]);
        }
    }
}
