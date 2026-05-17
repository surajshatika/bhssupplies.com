<?php

//me add for create backup restore database
//backup and restore

use App\Http\Controllers\BackupController;

//Admin

//Route::group(['prefix' =>'admin', 'middleware' => ['auth', 'admin']], function(){
//
//    Route::controller(BackupController::class)->group(function () {
//    Route::get('/backups', 'index')->name('backups');
//    Route::post('/backups', 'store')->name('backups.store');
//    Route::get('/backups/download/database/{key}', 'downloadDatabase')->name('backups.download.database');
//    Route::get('/backups/download/storage/{key}', 'downloadStorage')->name('backups.download.storage');
//    Route::post('/backups/restore', 'restore')->name('backups.restore');
//    Route::delete('/backups/destroy', 'destroy')->name('backups.destroy');
//});
//});


Route::controller(BackupController::class)->group(function () {
    Route::get('/backups', 'index')->name('backups');
    Route::post('/backups', 'store')->name('backups.store');
    Route::post('/backups/storeaddons', 'storeaddons')->name('backups.storeaddons');
    Route::get('/backups/download/database/{key}', 'downloadDatabase')->name('backups.download.database');
    Route::get('/backups/download/storage/{key}/{backuptype}', 'downloadStorage')->name('backups.download.storage');
    Route::post('/backups/restore', 'restore')->name('backups.restore');
    Route::delete('/backups/destroy', 'destroy')->name('backups.destroy');
    Route::post('/backups/send', 'send')->name('backups.send');
});


//additional info:
//create section backup_restore_settings in /opt/lampp/htdocs/aec/resources/views/backend/inc/admin_sidenav.blade.php
//create /opt/lampp/htdocs/aec/app/Services/Backup.php
//create /opt/lampp/htdocs/aec/app/Libraries/pclzip.php ==> if sytem zip not work app use this liberary for create zip file
//create /opt/lampp/htdocs/aec/app/Services/Mysqldump.php ==> for dump sql database data
//create /opt/lampp/htdocs/aec/app/Http/Controllers/BackupController.php
//create /opt/lampp/htdocs/aec/resources/views/backend/backup/backups.blade.php

//me end


