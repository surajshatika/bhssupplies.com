<?php

namespace App\Http\Controllers;

use Faker\Provider\Uuid;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Services\Backup;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Redirect;


use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use ZipArchive;

class BackupController extends Controller
{
    protected $backup;

    public function __construct(Backup $backup)
    {
        $this->backup = $backup;

    }


    /**
     * Simple helper to debug to the console
     *
     * @param $data object, array, string $data
     * @param $context string  Optional a description.
     *
     * @return string
     */
    function debug_to_console($data, $context = 'Debug in Console')
    {

        // Buffering to solve problems frameworks, like header() in this and not a solid return.
        ob_start();

        $output = 'console.info(\'' . $context . ':\');';
        $output .= 'console.log(' . json_encode($data) . ');';
        $output = sprintf('<script>%s</script>', $output);

        echo $output;
    }

    /**
     * Display backups.
     */
    public function index(Request $request)
    {
        $backups = $this->backup->getBackupList();
        $tables = DB::select('SHOW TABLES');
        foreach ($backups as $key => $backup) {
            $backups[$key]['size'] = $this->backup->sizeFormat(Backup::folderSize(base_path('databasebackups') . DIRECTORY_SEPARATOR . $key));
        }

        $sort_by = null;
        $sort_by = $request->sort;
        switch ($request->sort) {
            case 'newest':
                krsort($backups);//Sort Array (Descending Order), According to Key - krsort()
                //$this->debug_to_console('newest done :  '.$request->sort.'  '.$sort_by);
                break;
            case 'oldest':
                ksort($backups);//Sort Array (Ascending Order), According to Key - ksort()
                //$this->debug_to_console('oldest done :  '.$request->sort.'  '.$sort_by);
                break;
            default:
                krsort($backups);//Sort Array (Descending Order), According to Key - krsort()
                //$this->debug_to_console('defailt newest done :  '.$request->sort.'  '.$sort_by);
                $sort_by = 'newest';
                break;
        }


//tips for working paginate for array is these two parametes as option
//        $paginate = new LengthAwarePaginator($backups, count($backups), 3, 1, [
//            'path' =>  request()->url(),
//            'query' => request()->query()
//        ]);

        $pageNum = 0;
        $rowsPerPage = 5;
        $backups = $this->paginate($backups, $rowsPerPage);

        $pageNum = $pageNum ?: (Paginator::resolveCurrentPage() ?: 1);
//        return view('backend.backup.backups')->with(compact('backups', 'tables', 'sort_by', ))->with('no', 1); //pass no to table for show row in table
        return view('backend.backup.backups')->with(compact('backups', 'tables', 'sort_by', 'pageNum', 'rowsPerPage' ));
    }

    public function paginate($items, $perPage = 7, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
//        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query()
        ]);
    }

    public function store(Request $request)
    {
        //    dd(    json_decode($request['checkboxeslist'], true)   );
        //dd($request);
        try {

            $nowdatetime = Carbon::now();
           // $nowdatetime->addHour(5);

            $nowdatetime1 = $nowdatetime->format('Y-m-d-H-i-s');
            $nowdatetime2 = $nowdatetime->toDateTimeString();

            $data = $this->backup->createBackupFolder($request, $nowdatetime1, $nowdatetime2);

            $backuptype = $request->backuptype;
//            dd($backuptype);

//for remember
//                  public_path('storage') ==> "/home/aryaclub/aec.aryaclub.com/public/storage"
//                  storage_path('app/public') ==> "/home/aryaclub/aec.aryaclub.com/storage/app/public"
//                  base_path() ==> "/home/aryaclub/aec.aryaclub.com"

            switch ($backuptype) {
                case 0:
                    $this->backup->backupDb($nowdatetime1, $request['checkboxeslist'], $request['tablecount']);
                    $this->backup->backupStorage(public_path('uploads'), $nowdatetime1, $backuptype); //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"
                    break;
                case 1:
                    $this->backup->backupDb($nowdatetime1, $request['checkboxeslist'], $request['tablecount']);
                    break;
                case 2:
                    $this->backup->backupStorage(public_path('uploads'), $nowdatetime1, $backuptype); //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"
                    break;
                case 3:
                    $this->backup->backupDb($nowdatetime1, $request['checkboxeslist'], $request['tablecount']);
                    $this->backup->backupStorage(base_path(), $nowdatetime1, $backuptype); //base_path() ==> "/home/aryaclub/aec.aryaclub.com"
                    break;
                case 4:
                    $this->backup->backupStorage(base_path(), $nowdatetime1, $backuptype); //base_path() ==> "/home/aryaclub/aec.aryaclub.com"
                    break;
                default:
                    $this->backup->backupDb($nowdatetime1, $request['checkboxeslist'], $request['tablecount']);
                    $this->backup->backupStorage(public_path('uploads'), $nowdatetime1, $backuptype); //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"
            }


            return redirect()->route('backups');
        } catch (Exception $error) {
            return Redirect::back()
                ->withError($error->getMessage());
        }
    }

    public function storeaddons(Request $request)
    {

        $nowdatetime = Carbon::now();
//        $nowdatetime->addHour(5);
        $nowdatetime1 = $nowdatetime->format('Y-m-d-H-i-s');
        $nowdatetime2 = $nowdatetime->toDateTimeString();

        $data = $this->backup->createBackupFolder($request, $nowdatetime1, $nowdatetime2);
//        $backuptype = $request->backuptype;
        $backuptype = 5;

        try {
            $addonsdir = opendir(public_path('addons'));

            while (false !== $zipFile = readdir($addonsdir)) {
                // if the extension is '.zip'
                if (strtolower(pathinfo($zipFile, PATHINFO_EXTENSION)) == 'zip') {

//                echo "File $zipFile  found in $addonsdir.\n";
//                echo "<script>console.log('Debug Objects: " . public_path('addons/').$zipFile . "' );</script>";

////                // do the rename based on the current iteration if file name not equal
////                When a plugin wants to be installed, it is first saved with a name similar to "7al3OaIdFAfDoSab9heFlDx9NuartxV9bPqbkOfQ.zip"
////                in the "/public/addons" folder
////                We need to read the file "config.json" from inside zip file and its informations like name,unique_identifier,version
////                and minimum_item_version and then change the name of this compressed file to complete correct name like
////                "addon-backup_restore_system-v1.0-for-activee-commerce-v7.0.0"

                    $zip = new ZipArchive;
                    $res = $zip->open(public_path('addons/') . $zipFile);
                    $random_dir = Str::random(10);

                    $dir = trim($zip->getNameIndex(0), '/');
                    if ($res === true) {
                        $res = $zip->extractTo(base_path('temp/' . $random_dir . '/addonsx'));
                        $zip->close();
                    } else {
                        dd('could not open');
                    }

                    $str = file_get_contents(base_path('temp/' . $random_dir . '/addonsx/' . $dir . '/config.json'));
                    $json = json_decode($str, true);

                    $unique_identifier = $json['unique_identifier'];
                    $version = $json['version'];
                    $minimum_item_version = $json['minimum_item_version'];

                    $oldfilename = public_path('addons/') . $zipFile;

                    //like==>addon-backup_restore_system-v1.0-for-activee-commerce-v7.0.0"
                    $newfilename = public_path('addons/') . 'addon-' . sprintf('%06d', rand(1, 1000000)) . '-' . $unique_identifier . '-v' . $version . '-for-activee-commerce-v' . $minimum_item_version . '.zip';

                    $this->backup->renameFileaddons($oldfilename, $newfilename);


                }
            }
            $this->backup->deletefolderandfiles(base_path('temp/'));
            $this->backup->backupAddons(public_path('addons/'), $nowdatetime1, $backuptype); //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"


        return redirect()->route('backups');

        } catch (Exception $error) {
            return Redirect::back()
                ->withError($error->getMessage());
        }


//        try {
//
//            $nowdatetime = Carbon::now();
//
//            $nowdatetime1 = $nowdatetime->format('Y-m-d-h-i-s');
//            $nowdatetime2 = $nowdatetime->toDateTimeString();
//
//            $backuptype = 5;//$request->backuptype;
//            $data = $this->backup->createBackupFolder($request, $nowdatetime1, $nowdatetime2);
//
//            $this->backup->backupAddons(public_path('addons'),$nowdatetime1,$backuptype); //public_path('addons')=/opt/lampp/htdocs/aec/public/addons
//
//
//            return redirect()->route('backups');
//        } catch (Exception $error) {
//            return Redirect::back()
//                ->withError($error->getMessage());
//        }
    }

    public function downloadDatabase2($key)
    {
        $path = base_path('databasebackups/') . $key;
        foreach ($this->backup->scanFolder($path) as $file) {
            if (strpos(basename($file), 'database') !== false) {
                return response()->download($path . DIRECTORY_SEPARATOR . $file);
            }
        }
        return true;
    }

    public function downloadDatabase($key)
    {
        $path = base_path('databasebackups/') . $key;
        foreach ($this->backup->scanFolder($path) as $file) {
            if (strpos(basename($file), 'database') !== false) {


                //make Resumable Download
                $headers = array(
                    'Content-Type: application/zip',
                    'Content-Length: '. filesize($path . DIRECTORY_SEPARATOR . $file)
                );

                return response()->download($path . DIRECTORY_SEPARATOR . $file, basename($path . DIRECTORY_SEPARATOR . $file), $headers);

            }
        }
        return true;
    }

    public function downloadStorage2($key, $backuptype)
    {
//        dd($key.' ==================         '.$backuptype);

        $path = base_path('databasebackups/') . $key;
        foreach ($this->backup->scanFolder($path) as $file) {
//            if (strpos(basename($file), $backuptype < 3 ? 'storage' : 'website') !== false) {
                if (strpos(basename($file), $backuptype < 3 ? 'storage' : ($backuptype == 5 ? 'addons' : 'website')) !== false) {

                    dd($file.'=='.$path . DIRECTORY_SEPARATOR . $file.'==='.basename($path . DIRECTORY_SEPARATOR . $file));

                return response()->download($path . DIRECTORY_SEPARATOR . $file);
            }
        }
        return true;
    }

    public function downloadStorage($key, $backuptype)
    {
//        dd($key.' ==================         '.$backuptype);

        $path = base_path('databasebackups/') . $key;
        foreach ($this->backup->scanFolder($path) as $file) {
//            if (strpos(basename($file), $backuptype < 3 ? 'storage' : 'website') !== false) {
                if (strpos(basename($file), $backuptype < 3 ? 'storage' : ($backuptype == 5 ? 'addons' : 'website')) !== false) {

                    //make Resumable Download
                    $headers = array(
                        'Content-Type: application/zip',
                        'Content-Length: '. filesize($path . DIRECTORY_SEPARATOR . $file)
                    );

                return response()->download($path . DIRECTORY_SEPARATOR . $file, basename($path . DIRECTORY_SEPARATOR . $file), $headers);
            }
        }
        return true;
    }

    public function restore(Request $request)
    {


        $restoretype = $request->restoretype;

        switch ($restoretype) {
            case 1:
                try {
                    $path = base_path('databasebackups/') . $request->key;
                    foreach ($this->backup->scanFolder($path) as $file) {
                        if (strpos(basename($file), 'database') !== false) {
                            $this->backup->restoreDb($path . DIRECTORY_SEPARATOR . $file, $path);
                        }

                    }

                    return redirect()->route('backups');
                } catch (Exception $ex) {
                    return Redirect::back()
                        ->withError($ex->getMessage());
                }
                break;
            case 2:
                try {
                    $path = base_path('databasebackups/') . $request->key;
                    foreach ($this->backup->scanFolder($path) as $file) {

                        if (strpos(basename($file), 'storage') !== false) {
//                    $this->backup->restore($path . DIRECTORY_SEPARATOR . $file, public_path('storage')); //public_path('storage') ==> "/home/aryaclub/aec.aryaclub.com/public/storage"
//                    $this->backup->restore($path . DIRECTORY_SEPARATOR . $file, storage_path('app/public')); //storage_path('app/public') ==> "/home/aryaclub/aec.aryaclub.com/storage/app/public"
                            $this->backup->restoreStorage($path . DIRECTORY_SEPARATOR . $file, public_path('uploads'));  //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"
                        }
                    }

                    return redirect()->route('backups');
                } catch (Exception $ex) {
                    return Redirect::back()
                        ->withError($ex->getMessage());
                }
                break;
            default:
                try {
                    $path = base_path('databasebackups/') . $request->key;
                    foreach ($this->backup->scanFolder($path) as $file) {
                        if (strpos(basename($file), 'database') !== false) {
                            $this->backup->restoreDb($path . DIRECTORY_SEPARATOR . $file, $path);
                        }

                        if (strpos(basename($file), 'storage') !== false) {
//                    $this->backup->restore($path . DIRECTORY_SEPARATOR . $file, public_path('storage')); //public_path('storage') ==> "/home/aryaclub/aec.aryaclub.com/public/storage"
//                    $this->backup->restore($path . DIRECTORY_SEPARATOR . $file, storage_path('app/public')); //storage_path('app/public') ==> "/home/aryaclub/aec.aryaclub.com/storage/app/public"
                            $this->backup->restoreStorage($path . DIRECTORY_SEPARATOR . $file, public_path('uploads'));  //public_path('uploads') ==> "/home/aryaclub/aec.aryaclub.com/public/uploads"
                        }
                    }

                    return redirect()->route('backups');
                } catch (Exception $ex) {
                    return Redirect::back()
                        ->withError($ex->getMessage());
                }
                break;
        }


    }

    public function destroy(Request $request)
    {

        $deletetype = $request->deletetype;
        $backuptype = $request->backuptype;
        $path = base_path('databasebackups/') . $request->key;
//        dd('key:'.$path. '   deletetype:'.$deletetype.'   backuptype:'.$backuptype);

        try {
            $this->backup->deleteBackup(base_path('databasebackups/') . $request->key, $backuptype, $deletetype);
            return redirect()->route('backups');
        } catch (Exception $error) {
            return Redirect::back()
                ->withError($error->getMessage());
        }
    }

    public function send(Request $request)
    {


//        $remote_file_url = 'https://www.webappsplanet.com/filename.zip';
//
//        /* New file name and path for this file */
//        $local_file = 'files.zip';
//
//        /* Copy the file from source url to server */
//        $copy = copy( $remote_file_url, $local_file );
//
//        /* Add notice for success/failure */
//        if( !$copy ) {
//            echo "Doh! copy failed $file...\n";
//        }
//        else{
//            echo "WOOT! successfuly copied $file...\n";
//        }


        $deletetype = $request->deletetype;
        $backuptype = $request->backuptype;
        $path = base_path('databasebackups/') . $request->key;
//        dd('key:'.$path. '   deletetype:'.$deletetype.'   backuptype:'.$backuptype);

        try {
            //   $this->backup->deleteBackup(base_path('databasebackups/') . $request->key, $backuptype, $deletetype);
            return redirect()->route('backups');
        } catch (Exception $error) {
            return Redirect::back()
                ->withError($error->getMessage());
        }
    }
}
