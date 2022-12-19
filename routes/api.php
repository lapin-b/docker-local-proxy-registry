<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlobsController;

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
    Route::post('/{container_ref}/blobs/uploads', [BlobsController::class, 'initiateUpload'])->name('blobs.init_upload');
    Route::patch('/{container_ref}/blobs/uploads/{pending_container_layer}', [BlobsController::class, 'processPartialUpload'])
        ->name('blobs.process_upload');
});
