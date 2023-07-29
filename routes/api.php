<?php

use App\Http\Controllers\BlobsController;
use App\Http\Controllers\ManifestsController;
use App\Http\Controllers\ProxyRegistry\BlobsController as ProxyBlobsController;
use App\Http\Controllers\ProxyRegistry\ManifestsController as ProxyManifestsController;
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
    Route::get('{container_ref}/uploads/{upload}', [UploadsController::class, 'upload_status']);
    Route::match(['PATCH', 'PUT'], '{container_ref}/uploads/{upload}', [UploadsController::class, 'process_partial_update'])->name('blobs.process_upload');
    Route::delete('/uploads/{upload_ref}', [UploadsController::class, 'cancel_upload']);

    Route::get('/p/{registry}/{container_ref}/manifests/{manifest_ref}', [ProxyManifestsController::class, 'get_manifest'])->name('manifests.proxy.get');
    Route::get('/p/{registry}/{container_ref}/blobs/{blob_ref}', [ProxyBlobsController::class, 'get_blob'])->name('blobs.proxy.get');
    Route::get('/{container_ref}/blobs/{blob_ref}', function(){ return abort(501); })->name('blobs.get');

    Route::get('/{container_ref}/blobs/{blob_ref}', [BlobsController::class, 'get_blob'])->name('blobs.get');
    Route::put('/{container_ref}/manifests/{manifest_ref}', [ManifestsController::class, 'upload_manifest'])->name('manifests.put');
    Route::get('/{container_ref}/manifests/{manifest_ref}', [ManifestsController::class, 'get_manifest'])->name('manifests.get');
});
