<?php

use App\Http\Controllers\BlobsController;
use App\Http\Controllers\ManifestsController;
use App\Http\Controllers\RegistryBaseController;
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
    Route::get('/', [RegistryBaseController::class, 'base']);
    Route::post('/{container_ref}/blobs/uploads', [UploadsController::class, 'initiateUpload'])->name('blobs.init_upload');
    Route::patch('/{container_ref}/blobs/uploads/{pending_container_layer}', [UploadsController::class, 'process_partial_update'])->name('blobs.process_upload');
    Route::put('/{container_ref}/blobs/uploads/{pending_container_layer}', [UploadsController::class, 'process_partial_update']);
    Route::delete('/{container_ref}/blobs/uploads/{pending_container_layer}', [UploadsController::class, 'cancel_upload']);

    Route::get('/{container_ref}/blobs/{blob_ref}', [BlobsController::class, 'get_blob'])->name('blobs.get');
    Route::put('/{container_ref}/manifests/{manifest_ref}', [ManifestsController::class, 'upload_manifest'])->name('manifests.put');
    Route::get('/{container_ref}/manifests/{manifest_ref}', [ManifestsController::class, 'get_manifest'])->name('manifests.get');
});
