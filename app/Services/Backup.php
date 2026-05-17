<?php

namespace App\Services;

use Carbon\Carbon;
use DB;
use Exception;
use File;
use Symfony\Component\Process\Process;
use ZipArchive;
use App\Utility\PclZip as Zip;
use Illuminate\Filesystem\Filesystem;
use App\Services as IMysqldump;

class Backup
{

    protected $file;

    protected $folder;

    public function __construct()
    {
        $this->file = new Filesystem();
    }

    public function createBackupFolder($request, $now1, $now2)
    {

//        dd($request->name);

        $backupFolder = $this->createFolder(base_path('databasebackups'));
//        $now = Carbon::now()->format('Y-m-d-h-i-s');
        $this->folder = $this->createFolder($backupFolder . DIRECTORY_SEPARATOR . $now1);

        $file = base_path('databasebackups/backup.json');


        $data = $this->getBackupList();
        if (isset($request->name)) {
            $data[$now1] = [
                'name' => $request->name ?? $now1,
//                'date' => Carbon::now()->toDateTimeString(),
                'date' => $now2,
                'klid' => $now1,
                'type' => $request->backuptype
            ];


        } else {

            $animals = [
                'aardvark', 'albatross', 'alligator', 'alpaca', 'ant', 'anteater', 'antelope', 'ape', 'armadillo', 'donkey',
                'baboon', 'badger', 'barracuda', 'bat', 'bear', 'beaver', 'bee', 'bison', 'boar', 'buffalo', 'galago', 'butterfly',
                'camel', 'caribou', 'cat', 'caterpillar', 'cattle', 'chamois', 'cheetah', 'chicken', 'chimpanzee', 'chinchilla',
                'chough', 'clam', 'cobra', 'cockroach', 'cod', 'cormorant', 'coyote', 'crab', 'crane', 'crocodile', 'crow', 'curlew',
                'deer', 'dinosaur', 'dog', 'dogfish', 'dolphin', 'donkey', 'dotterel', 'dove', 'dragonfly', 'duck', 'dugong', 'dunlin',
                'eagle', 'echidna', 'eel', 'eland', 'elephant', 'elephant-seal', 'elk', 'emu', 'falcon', 'ferret', 'finch', 'fish',
                'flamingo', 'fly', 'fox', 'frog', 'gaur', 'gazelle', 'gerbil', 'giant-panda', 'giraffe', 'gnat', 'gnu', 'goat', 'goose',
                'goldfinch', 'goldfish', 'gorilla', 'goshawk', 'grasshopper', 'grouse', 'guanaco', 'guinea-fowl', 'guinea-pig', 'gull',
                'hamster', 'hare', 'hawk', 'hedgehog', 'heron', 'herring', 'hippopotamus', 'hornet', 'horse', 'human', 'hummingbird', 'hyena',
                'jackal', 'jaguar', 'jay', 'jay, blue', 'jellyfish', 'kangaroo', 'koala', 'komodo-dragon', 'kouprey', 'kudu', 'lapwing',
                'lark', 'lemur', 'leopard', 'lion', 'llama', 'lobster', 'locust', 'loris', 'louse', 'lyrebird', 'magpie', 'mallard', 'manatee',
                'marten', 'meerkat', 'mink', 'mole', 'monkey', 'moose', 'mouse', 'mosquito', 'mule', 'narwhal', 'newt', 'nightingale',
                'octopus', 'okapi', 'opossum', 'oryx', 'ostrich', 'otter', 'owl', 'ox', 'oyster', 'panther', 'parrot', 'partridge', 'peafowl',
                'pelican', 'penguin', 'pheasant', 'pig', 'pigeon', 'pony', 'porcupine', 'porpoise', 'prairie-dog', 'quail', 'quelea', 'rabbit',
                'raccoon', 'rail', 'ram', 'rat', 'raven', 'red-deer', 'red-panda', 'reindeer', 'rhinoceros', 'rook', 'ruff', 'salamander',
                'salmon', 'sand-dollar', 'sandpiper', 'sardine', 'scorpion', 'sea-lion', 'sea-urchin', 'seahorse', 'seal', 'shark', 'sheep',
                'shrew', 'shrimp', 'skunk', 'snail', 'snake', 'spider', 'squid', 'squirrel', 'starling', 'stingray', 'stinkbug', 'stork',
                'swallow', 'swan', 'tapir', 'tarsier', 'termite', 'tiger', 'toad', 'trout', 'turkey', 'turtle', 'vicuña', 'viper', 'vulture',
                'wallaby', 'walrus', 'wasp', 'water-buffalo', 'weasel', 'whale', 'wolf', 'wolverine', 'wombat', 'woodcock', 'woodpecker', 'worm', 'wren', 'yak', 'zebra'
            ];

            $random_key = array_rand($animals);

            $picked_name = $animals[$random_key];

            $random_number = rand(1000, 9999);

            $name = $picked_name . '-' . $random_number;
            $data[$now1] = [
                'name' => $name ?? $now1,
//                'date' => Carbon::now()->toDateTimeString(),
                'date' => $now2,
                'klid' => $now1,
                'type' => $request->backuptype
            ];
        }

//        dd($data);

        $this->saveFileData($file, $data);
        return $data;
    }

    public function backupDb($now,$checkboxeslist,$tablecount)
    {

//        dd($artistslist);

        $file = 'database-' . $now;
        $path = $this->folder . DIRECTORY_SEPARATOR . $file;

        //old method for create sql data and not work
//        $sql = 'mysqldump --column-statistics=0 --user=' . env('DB_USERNAME') . ' --password=' . env('DB_PASSWORD') . ' --host=' . env('DB_HOST') . ' ' . ' --port=' . env('DB_PORT') . ' ' . env('DB_DATABASE') . ' > ' . $path . '.sql';
//        system($sql);



//sample for dumpsetting
//        $dumpSettings = array(
//            'include-tables' => array('table1', 'table2'),
//            'exclude-tables' => array('table3', 'table4'),
//            'compress' => CompressMethod::GZIP, /* CompressMethod::[GZIP, BZIP2, NONE] */
//            'no-data' => false,
//            'add-drop-table' => false,
//            'single-transaction' => true,
//            'lock-tables' => false,
//            'add-locks' => true,
//            'extended-insert' => true
//        );
//
//        $dump = new MySQLDump('database','database_user','database_pass','localhost', $dumpSettings);
//        $dump->start('forum_dump.sql.gz');


//        dd(json_decode($checkboxeslist, true));

        $dumpSettings = array(
//            'exclude-tables' => array('countries', 'states', 'cities'),
            'exclude-tables' => json_decode($checkboxeslist, true),
            'add-drop-table' => true,
            'extended-insert' => true
        );



//        You can check database sizes with this query (will return size of all DBs on the server):
//
//SELECT
//    table_schema AS 'Database',
//    SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
//FROM
//    information_schema.TABLES
//GROUP BY table_schema





        //new for create sql data and work
        try {
//            $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=aryaclub_aec', 'aryaclub_user', 'NSmQ2KWUIg0m');
            $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'), $dumpSettings);
            $dump->start($path . '.sql');
            flash(translate('Database backup was done successfully'))->success();
        } catch (\Exception $e) {
            echo 'mysqldump-php error: ' . $e->getMessage();
            flash(translate('An error occurred while backing up the database') . $e->getMessage())->error();
        }

        //create zip file from backup
        $this->compressFileToZip($path, $file);

        if (file_exists($path . '.zip')) {
            chmod($path . '.zip', 0777);
            flash(translate('Database compression was done successfully'))->success();
        }


        return true;
    }

    public function restoreDb($file, $path)
    {

        //first unzip database file backup
        $this->restore($file, $path);
        $file = $path . DIRECTORY_SEPARATOR . File::name($file) . '.sql';

        if (!file_exists($file)) {
            return false;
        }


        //original method for restore database backup
        try {
            $dump = new IMysqldump\Mysqldump('mysql:host=localhost;dbname=' . env('DB_DATABASE'), env('DB_USERNAME'), env('DB_PASSWORD'));
            $dump->restore($file);
            flash(translate('The database was restored successfully'))->success();
            echo 'mysqldump-php ok: ';
        } catch (\Exception $e) {
            flash(translate('There was a problem restoring the database') . $e->getMessage())->error();
            echo 'mysqldump-php error: ' . $e->getMessage();
        }
        $this->deleteFile($file);
//        $this->renameFile($file);
        return true;

    }

    public function backupStorage($source, $now, $backuptype = 0)
    {
//        $file = $this->folder . DIRECTORY_SEPARATOR . 'storage-' . Carbon::now()->format('Y-m-d-h-i-s') . '.zip';
//        dd($backuptype);
        if($backuptype < 3) {
            $file = $this->folder . DIRECTORY_SEPARATOR . 'storage-' . $now . '.zip';
        } else{
            $file = $this->folder . DIRECTORY_SEPARATOR . 'website-' . $now . '.zip';
        }

        // set script timeout value
        ini_set('max_execution_time', 10000);

        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            // create and open the archive
            if ($zip->open($file, ZipArchive::CREATE) !== true) {
                $this->deleteFolderBackup($this->folder);
            }
        } else {
            $zip = new Zip($file);
        }
        $arr_src = explode(DIRECTORY_SEPARATOR, $source);
        $path_length = strlen(implode(DIRECTORY_SEPARATOR, $arr_src) . DIRECTORY_SEPARATOR);
        // add each file in the file list to the archive
        $this->recurseZip($source, $zip, $path_length);
        if (class_exists('ZipArchive', false)) {
            $zip->close();
        }
        if (file_exists($file)) {
            chmod($file, 0777);
            if($backuptype == 3) {
                flash(translate('website backup was done successfully'))->success();
            } else{
                flash(translate('Storage backup was done successfully'))->success();
            }
        } else {
            if($backuptype == 3) {
                flash(translate('Backup from website encountered an error') )->error();
            } else{
                flash(translate('Backup from storage encountered an error') )->error();
            }
        }

return true;
    }

    public function backupAddons($source, $now, $backuptype = 5)
    {
        if($backuptype === 5) {
            $file = $this->folder . DIRECTORY_SEPARATOR . 'addons-' . $now . '.zip';
        }

        // set script timeout value
        ini_set('max_execution_time', 10000);

        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            // create and open the archive
            if ($zip->open($file, ZipArchive::CREATE) !== true) {
                $this->deleteFolderBackup($this->folder);
            }
        } else {
            $zip = new Zip($file);

        }
        $arr_src = explode(DIRECTORY_SEPARATOR, $source);
        $path_length = strlen(implode(DIRECTORY_SEPARATOR, $arr_src) . DIRECTORY_SEPARATOR);
        // add each file in the file list to the archive
        $this->recurseZip($source, $zip, $path_length);
        if (class_exists('ZipArchive', false)) {
            $zip->close();
        }
        if (file_exists($file)) {
            chmod($file, 0777);
            if($backuptype == 5) {
                flash(translate('addons backup was done successfully'))->success();
            }
        } else {
            if($backuptype == 3) {
                flash(translate('Backup from addons encountered an error') )->error();
           }
        }

return true;
    }

    public function restoreStorage($fileName, $pathTo)
    {

        if (file_exists($fileName)) {

            if (class_exists('ZipArchive', false)) {
                $zip = new ZipArchive;
                if ($zip->open($fileName) === true) {
                    $zip->extractTo($pathTo);
                    $zip->close();
                    flash(translate('The storage was restored successfully'))->success();
                    return true;
                }
            } else {
                $archive = new Zip($fileName);
                $archive->extract(PCLZIP_OPT_PATH, $pathTo, PCLZIP_OPT_REMOVE_ALL_PATH);
                flash(translate('The storage was restored successfully'))->success();
                return true;
            }
        } else {
            flash(translate('There are no files to restore') )->error();
        }

        return false;
    }


    public function restore($fileName, $pathTo)
    {
        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive;
            if ($zip->open($fileName) === true) {
                $zip->extractTo($pathTo);
                $zip->close();
                return true;
            }
        } else {
            $archive = new Zip($fileName);
            $archive->extract(PCLZIP_OPT_PATH, $pathTo, PCLZIP_OPT_REMOVE_ALL_PATH);
            return true;
        }

        return false;
    }

    public function deleteBackup($path, $backuptype, $deletetype)
    {

        if($backuptype<3) { //backup type is storage&database or only database or only storage
            switch ($deletetype) {
                case 1: //we want to delete only database from backup
                    foreach ($this->scanFolder($path) as $item) {
                        if (strpos(basename($item), 'database') !== false) {


                            //if $item ==> "database-2023-12-15-11-59-56.zip" then i must get "2023-12-15-11-59-56" because i must find key in json file
                            $datakey = str_replace(
                                array("database-", ".zip"),
                                array("", ""),
                                $item
                            );
                            //now data key is "2023-12-15-11-59-56"


//                          json that stored in backup.json
//                        {
//                            "2023-12-15-11-59-56": {
//                               "name": "ram-5269",
//                               "date": "2023-12-15 23:59:56",
//                               "type": "0"
//                            }
//                        }
                            //decode my JSON:
                            $json_object = file_get_contents(base_path('databasebackups/backup.json'));
                            $data = json_decode($json_object, true);
//                        dd($data);

//                        When we delete the database from the backup that includes the database and storage, we must change the backup type to backup storage, which is 2.
//                        then we changed "type": "0" to "type": "2"
                            $data[$datakey]['type'] = '2';

//                        Finally rewrite it back on the file (or a newer one):
                            $json_object = json_encode($data);
                            file_put_contents(base_path('databasebackups/backup.json'), $json_object);


                            $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                        }
                    }


                    break;
                case 2: //we want to delete only storage from backup
                    foreach ($this->scanFolder($path) as $item) {
                        if (strpos(basename($item), 'storage') !== false) {


                            //if $item ==> "storage-2023-12-15-11-59-56.zip" then i must get "2023-12-15-11-59-56" because i must find key in json file
                            $datakey = str_replace(
                                array("storage-", ".zip"),
                                array("", ""),
                                $item
                            );
//                        dd($datakey);
                            //now data key is "2023-12-15-11-59-56"


//                          json that stored in backup.json
//                        {
//                            "2023-12-15-11-59-56": {
//                               "name": "ram-5269",
//                               "date": "2023-12-15 23:59:56",
//                               "type": "0"
//                            }
//                        }
                            //decode my JSON:
                            $json_object = file_get_contents(base_path('databasebackups/backup.json'));
                            $data = json_decode($json_object, true);
//                        dd($data);

//                        When we delete the storage from the backup that includes the database and storage, we must change the backup type to backup database, which is 1.
//                        then we changed "type": "0" to "type": "1"
                            $data[$datakey]['type'] = '1';
//                        dd($data);

//                    Finally rewrite it back on the file (or a newer one):
                            $json_object = json_encode($data);
//                        dd($json_object);
                            file_put_contents(base_path('databasebackups/backup.json'), $json_object);


                            $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                        }
                    }


                    break;
                default:
                    foreach ($this->scanFolder($path) as $item) {
                        $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                    }
                    $this->file->deleteDirectory($path);

                    $file = base_path('databasebackups/backup.json');
                    $data = $this->getBackupList();
                    if (!empty($data)) {
                        $tmp = explode('/', $path);
                        unset($data[end($tmp)]);
                        $this->saveFileData($file, $data);
                    }
            }
        } else if($backuptype == 5) { //backup type is addons
                    foreach ($this->scanFolder($path) as $item) {
                        $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                    }
                    $this->file->deleteDirectory($path);

                    $file = base_path('databasebackups/backup.json');
                    $data = $this->getBackupList();
                    if (!empty($data)) {
                        $tmp = explode('/', $path);
                        unset($data[end($tmp)]);
                        $this->saveFileData($file, $data);
                    }

        } else { //backup type is website&database or only website
            switch ($deletetype) {
                case 1: //we want to delete only database from backup
                    foreach ($this->scanFolder($path) as $item) {
                        if (strpos(basename($item), 'database') !== false) {

                            //if $item ==> "database-2023-12-15-11-59-56.zip" then i must get "2023-12-15-11-59-56" because i must find key in json file
                            $datakey = str_replace(
                                array("database-", ".zip"),
                                array("", ""),
                                $item
                            );
                            //now data key is "2023-12-15-11-59-56"
//                          json that stored in backup.json
//                        {
//                            "2023-12-15-11-59-56": {
//                               "name": "ram-5269",
//                               "date": "2023-12-15 23:59:56",
//                               "type": "3"
//                            }
//                        }
                            //decode my JSON:
                            $json_object = file_get_contents(base_path('databasebackups/backup.json'));
                            $data = json_decode($json_object, true);

//                        When we delete the database from the backup that includes the database and website, we must change the backup type to backup website, which is 4.
//                        then we changed "type": "3" to "type": "4"
                            $data[$datakey]['type'] = '4';

//                        Finally rewrite it back on the file (or a newer one):
                            $json_object = json_encode($data);
                            file_put_contents(base_path('databasebackups/backup.json'), $json_object);


                            $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                        }
                    }


                    break;
                case 2: //we want to delete only website from backup
                    foreach ($this->scanFolder($path) as $item) {
                        if (strpos(basename($item), 'website') !== false) {


                            //if $item ==> "storage-2023-12-15-11-59-56.zip" then i must get "2023-12-15-11-59-56" because i must find key in json file
                            $datakey = str_replace(
                                array("website-", ".zip"),
                                array("", ""),
                                $item
                            );
                            //now data key is "2023-12-15-11-59-56"
//                          json that stored in backup.json
//                        {
//                            "2023-12-15-11-59-56": {
//                               "name": "ram-5269",
//                               "date": "2023-12-15 23:59:56",
//                               "type": "0"
//                            }
//                        }
                            //decode my JSON:
                            $json_object = file_get_contents(base_path('databasebackups/backup.json'));
                            $data = json_decode($json_object, true);

//                        When we delete the website from the backup that includes the database and website, we must change the backup type to backup database, which is 1.
//                        then we changed "type": "0" to "type": "1"
                            $data[$datakey]['type'] = '1';

//                        Finally rewrite it back on the file (or a newer one):
                            $json_object = json_encode($data);
                            file_put_contents(base_path('databasebackups/backup.json'), $json_object);

                            $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                        }
                    }


                    break;
                default:
                    foreach ($this->scanFolder($path) as $item) {
                        $this->file->delete($path . DIRECTORY_SEPARATOR . $item);
                    }
                    $this->file->deleteDirectory($path);

                    $file = base_path('databasebackups/backup.json');
                    $data = $this->getBackupList();
                    if (!empty($data)) {
                        $tmp = explode('/', $path);
                        unset($data[end($tmp)]);
                        $this->saveFileData($file, $data);
                    }
            }
        }

    }

    public function createFolder($folder)
    {
        if (!$this->file->isDirectory($folder)) {
            $this->file->makeDirectory($folder);
            chmod($folder, 0777);
        }
        return $folder;
    }

    public function deleteFile($file)
    {
        if ($this->file->exists($file)) {
            $this->file->delete($file);
        }
    }

    public function renameFile($file)
    {
        if ($this->file->exists($file)) {
            rename($file, $file . Carbon::now()->format('Y-m-d-h-i-s'));
        }
    }

    public function renameFileaddons($oldfilename, $newfilename)
    {
        if ($this->file->exists($oldfilename)) {
            rename($oldfilename, $newfilename );
        }
    }

    function deletefolderandfiles($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir")
                        $this->deletefolderandfiles($dir . "/" . $object);
                    else unlink   ($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function getBackupList()
    {
        $file = base_path('databasebackups/backup.json');
        if (file_exists($file)) {
            return $this->getFileData($file);
        }
        return [];
    }

    public function compressFileToZip($path, $name)
    {
        $filename = $path . '.zip';

        if (class_exists('ZipArchive', false)) {
            $zip = new ZipArchive();
            if ($zip->open($filename, ZipArchive::CREATE) == true) {
                $zip->addFile($path . '.sql', $name . '.sql');
                $zip->close();
            }
        } else {
            $archive = new Zip($filename);
            $archive->add($path . '.sql', PCLZIP_OPT_REMOVE_PATH, $filename);
        }
        $this->deleteFile($path . '.sql');
    }

    function saveFileData($path, $data, $json = true)
    {
//        $this->debug_to_console($data);
        try {
            if ($json) {
                $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }



//            if ( ! is_writable($path)) {
//                flash($path . translate(' must writable!!!') )->error();
//
//                return false;
//            } else {
//                chmod($path, 0777);
//                File::put($path, $data);
//            }
//            chmod($path, 0777);



            File::put($path, $data);
             return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    function getFileData($file, $convert_to_array = true)
    {
        $file = File::get($file);
        if (!empty($file)) {
            if ($convert_to_array) {
                return json_decode($file, true);
            } else {
                return $file;
            }
        }
        return false;
    }

//    public function recurseZip($src, &$zip, $pathLength)
    public function recurseZip($src, $zip, $pathLength)
    {
        foreach ($this->scanFolder($src) as $file) {
            if ($this->file->isDirectory($src . DIRECTORY_SEPARATOR . $file)) {
                $this->recurseZip($src . DIRECTORY_SEPARATOR . $file, $zip, $pathLength);
            } else {
                if (class_exists('ZipArchive', false)) {
                    $zip->addFile($src . DIRECTORY_SEPARATOR . $file, substr($src . DIRECTORY_SEPARATOR . $file, $pathLength));

                } else {
                    $zip->add($src . DIRECTORY_SEPARATOR . $file, PCLZIP_OPT_REMOVE_PATH, substr($src . DIRECTORY_SEPARATOR . $file, $pathLength));
                }
            }
        }

    }

    function scanFolder($path, $ignore_files = [])
    {
        try {
            if (is_dir($path)) {
                $data = array_diff(scandir($path), array_merge(['.', '..'], $ignore_files));
                natsort($data);
                return $data;
            }
            return [];
        } catch (Exception $ex) {
            return [];
        }
    }

    public static function folderSize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : folderSize($each);
        }

        return ($size);
    }

    public static function sizeFormat($bytes, $precision = 2)
    {

        $base = log($bytes, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];

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
}
