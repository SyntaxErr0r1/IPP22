<?php

$options = getopt('', [
    'help',
    'directory:',
    'recursive',
    'parse-script:',
    'int-script:',
    'parse-only',
    'int-only',
    'jexampath:',
    'noclean',
]);

if(array_key_exists("help", $options)){
    error_log("Usage: php8.1 test.php [options]");
    error_log("Options:");
    error_log("--help | shows this text");
    error_log("--directory <directory> | searches for test files in provided directory");
    error_log("--recursive | if present, will search tests in subdirectories");
    error_log("--parse-script <path> | path to parser script");
    error_log("--int-script <path> | path to interpret script");
    error_log("--parse-only | will only test parser if present");
    error_log("--int-only | will only test interpret if present");
    error_log("--jexampath <path> | Directory which contains jexamxml.jar");
    error_log("--noclean | disable temporary file cleaning");
}


$test_dir = "./";
if(array_key_exists("directory",$options))
    $test_dir = $options["directory"];
if($test_dir[strlen($test_dir)-1] != "/"){
    $test_dir = $test_dir."/"; 
}
// var_dump($test_dir);

$recursive = false;
if(array_key_exists("recursive",$options))
    $recursive = true;

$parse_script = "./parse.php";
if(array_key_exists("parse-script",$options))
    $parse_script = $options["parse-script"];

$int_script = "./interpret.py";
if(array_key_exists("int-script",$options))
    $int_script = $options["int-script"];

$parse_only = false;
if(array_key_exists("parse-only",$options))
    $parse_only = true;

$int_only = false;
if(array_key_exists("int-only",$options))
    $int_only = true;


// $jexampath = "/pub/courses/ipp/jexamxml/";
$jexampath = "/mnt/c/Utils/jexamxml/";
if(array_key_exists("jexampath",$options))
    $jexampath = $options["jexampath"];

$noclean = false;
if(array_key_exists("noclean",$options))
    $noclean = $options["noclean"];

$file_list = [];
if($recursive){
    $directory = new RecursiveDirectoryIterator($test_dir);
    $iterator = new RecursiveIteratorIterator($directory);
    $regex = new RegexIterator($iterator, '/^.+\.src$/i', RecursiveRegexIterator::GET_MATCH);

    foreach($regex as $file){
        array_push($file_list, str_replace('.src', '', $file[0]) );
    }

}else{
    $dir_iterator = new DirectoryIterator($test_dir);
    $regex = new RegexIterator($dir_iterator, '/^.+\.src$/i', RegexIterator::GET_MATCH);

    foreach($regex as $file){
        array_push($file_list, str_replace('.src', '', $file[0]) );
    }
}

// var_dump($file_list);
// $files = scandir($test_dir);
// foreach($dir_iterator as $file){
//     // if($file->isDot()) continue;
//     echo $file->getFilename() . "<br>\n";

// }
$test_res = [];
$test_cnt = 0;
$test_passed = 0;
// if($parse_only){
    foreach($file_list as $file){
        $srcfile_name = $file.".src";
        $infile_name = $file.".in";
        $outfile_name = $file.".out";
        $rcfile_name = $file.".rc";

        // $src = file_get_contents($srcfile_name);

        //INPUT FILE
        if(!file_exists($infile_name)){
            $infile = fopen($infile_name, 'w');
            fclose($infile);
        }

        //OUTPUT FILE
        $out_expected = "";
        if(!file_exists($outfile_name)){
            $outfile = fopen($outfile_name, 'w');
            fclose($outfile);
        }else{
            $out_expected = file_get_contents($outfile_name);
        }

        //RETURN CODE FILE
        $ret_expected = 0;
        if(!file_exists($rcfile_name)){
            $rcfile = fopen($rcfile_name, 'w');
            fwrite($rcfile,"0");
            fclose($rcfile);
        }else{
            $ret_expected = file_get_contents($rcfile_name);
        }
        //todo use shellexec instead of popen
        //RUNNING THE SCRIPT(S)
        if($parse_only){
            $ret_actual = shell_exec("php8.1 ".$parse_script." < ".$srcfile_name." > test.out ; echo $?");
        }else if($int_only){
            $ret_actual = shell_exec("python3 ".$int_script." --source ".$srcfile_name." < ".$infile_name." > test.out ; echo $?");
        }else{
            $ret_parser = shell_exec("php8.1 ".$parse_script." < ".$srcfile_name." > test.xml ; echo $?");
            if($ret_parser == 0){
                $ret_actual = shell_exec("python3 ".$int_script." --source test.xml < ".$infile_name." > test.out ; echo $?");
            }else{
                $ret_actual = $ret_parser;
            }
            $res = 0;
        }
        $out_actual = "";
        
        if(file_exists("test.out")){
            $out_actual = file_get_contents("test.out");
        }
        
        //VALIDATION
        if($ret_expected == $ret_actual){
            // echo "[".$file."] Return codes are matching\n";
            if($ret_actual == 0){
                //IF PARSE ONLY => VALIDATE USING jexamxml
                if($parse_only){
                    #POPEN NOT ALLOWED ON MERLIN
                    $jexamres = popen("java -jar ".$jexampath."jexamxml.jar ".$outfile_name." test.out", "r");
                    $jexamres_ret = pclose($jexamres);
                    if($jexamres_ret == 0){
                        $test_passed++;
                    }else{
                        echo "[".$file."] JEXAMRES: ".$jexamres_ret."\n";
                    }
                }else{
                    $jexamres = popen("diff ".$outfile_name." test.out", "r");
                    $jexamres_ret = pclose($jexamres);
                    if($jexamres_ret == 0){
                        $test_passed++;
                    }else{
                        echo "[".$file."] DIFF: ".$jexamres_ret."\n";
                    }
                }
            }else{
                $test_passed++;
            }
        }else{
            echo "[".$file."] Return code expected: ".$ret_expected.". But got: ".$ret_actual."\n";
        }
        
        $test_res[$file] = $ret_expected == $ret_actual;
        $test_cnt++;
    }
    echo "--Summary--\n";
    echo "Passed ".$test_passed."/".$test_cnt." tests\n";
// }

?>