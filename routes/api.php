<?php

use App\Http\Controllers\BlobsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('/v2/')->group(function(){
    Route::post('/{container_ref}/blobs/uploads', [UploadsController::class, 'initiateUpload'])->name('blobs.init_upload');
    Route::patch('/{container_ref}/blobs/uploads/{pending_container_layer}', [UploadsController::class, 'process_partial_update'])->name('blobs.process_upload');
    Route::put('/{container_ref}/blobs/uploads/{pending_container_layer}', [UploadsController::class, 'process_partial_update']);

    Route::get('/{container_ref}/blobs/{blob_ref}', [BlobsController::class, 'get_blob'])->name('blobs.get');
});
