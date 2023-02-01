<?php

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\v1\Rest;
/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
return bcrypt('12345678');
    //return view('welcome');
});

Route::group(['prefix' => 'install'], function () {
        Route::get('/init/check', [Rest\InstallController::class, 'checkInitFile']);
        Route::post('/init/set', [Rest\InstallController::class, 'setInitFile']);
        Route::post('/database/update', [Rest\InstallController::class, 'setDatabase']);
        Route::post('/admin/create', [Rest\InstallController::class, 'createAdmin']);
        Route::post('/migration/run', [Rest\InstallController::class, 'migrationRun']);
        Route::post('/check/licence', [Rest\InstallController::class, 'licenceCredentials']);
        Route::post('/currency/create', [Rest\InstallController::class, 'createCurrency']);
        Route::post('/languages/create', [Rest\InstallController::class, 'createLanguage']);
    });
