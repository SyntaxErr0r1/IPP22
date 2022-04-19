<?php
//Name and surname: Juraj Dedič
//Login: xdedic07
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
    error_log("--directory=<directory> | searches for test files in provided directory");
    error_log("--recursive | if present, will search tests in subdirectories");
    error_log("--parse-script=<path> | path to parser script");
    error_log("--int-script=<path> | path to interpret script");
    error_log("--parse-only | will only test parser if present");
    error_log("--int-only | will only test interpret if present");
    error_log("--jexampath=<path> | Directory which contains jexamxml.jar");
    error_log("--noclean | disable temporary file cleaning");
    exit(0);
}

class TestCase {
    public $directory;
    public $ret_actual;
    public $ret_expected;
    public $out_actual;
    public $out_expected;
    public $result;
    public $message;

    function get_message(){
        if($this->message == NULL){
            return "";
        }else{
            return $this->message;
        }   
    }

    function get_output($parse_only){
        if($parse_only){
            return '<span>Message:</span>
            <span class="test-content-line-result no-flex--inline">
                '.$this->get_message().'
            </span>';
        }else{
            return '<span>Program output:</span>
            <span class="test-content-line-result">
                <span class="code-line">'.htmlspecialchars($this->out_actual).'</span>
                <span class="code-line">'.htmlspecialchars($this->out_expected).'</span>
            </span>';
        }
    }
}

function get_arguments($argv){
    $str = "";
    $length = count($argv);
    for($i = 1; $i < $length; $i++){
        $str = $str.$argv[$i]." ";
    } 
    return $str;
}

function check_dir($path){
    if(!file_exists($path) || !is_dir($path)){
        error_log( "Error: Cannot open directory: ".$path );
        exit(41);
    }
}

function check_file($path){
    if(!file_exists($path)){
        error_log( "Error: Cannot open file: ".$path );
        exit(41);
    }
}

/**
 * ARGS GETTING
 */

$test_dir = "./";
if(array_key_exists("directory",$options))
    $test_dir = $options["directory"];
if($test_dir[strlen($test_dir)-1] != "/"){
    $test_dir = $test_dir."/"; 
}
check_dir($test_dir);

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


$jexampath = "/pub/courses/ipp/jexamxml/";
// $jexampath = "/mnt/c/Utils/jexamxml/";
if(array_key_exists("jexampath",$options)){
    $jexampath = $options["jexampath"];
    if($jexampath[strlen($jexampath)-1] != "/"){
        $jexampath = $jexampath."/"; 
    }
}

$noclean = false;
if(array_key_exists("noclean",$options))
    $noclean = true;

/**
 * ARGS CHECKING
 */
if($parse_only){
    if($int_only){
        error_log("Error: Cannot combine --int-only and --parse-only!");
        exit(10);
    }
    if(array_key_exists("int-script",$options)){
        error_log("Error: Cannot combine --parse-only and --int-script!");
        exit(10);
    }
    check_file($parse_script);
}elseif($int_only){
    if(array_key_exists("parse-script",$options)){
        error_log("Error: Cannot combine --int-only and --parse-script!");
        exit(10);
    }
    if(array_key_exists("jexampath",$options)){
        error_log("Error: Cannot combine --int-only and --jexampath!");
        exit(10);
    }
    check_file($int_script);
}else{
    check_file($parse_script);
    check_file($int_script);
}


/**
 * TEST DIRECTORY SEARCHING
 */

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
        array_push($file_list, $test_dir.str_replace('.src', '', $file[0]) );
    }
}

/**
 * TESTING
 */

$results = [];
$test_cnt = 0;
$test_passed = 0;
foreach($file_list as $file){
    $case = new TestCase();
    $case->directory = $file;

    $srcfile_name = $file.".src";
    $infile_name = $file.".in";
    $outfile_name = $file.".out";
    $rcfile_name = $file.".rc";

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
    $case->out_expected = $out_expected; 

    //RETURN CODE FILE
    $ret_expected = 0;
    if(!file_exists($rcfile_name)){
        $rcfile = fopen($rcfile_name, 'w');
        fwrite($rcfile,"0");
        fclose($rcfile);
    }else{
        $ret_expected = file_get_contents($rcfile_name);
    }
    $case->ret_expected = $ret_expected;

    //RUNNING THE SCRIPT(S)
    if($parse_only){
        $ret_actual = shell_exec("php8.1 ".$parse_script." < ".$srcfile_name." > ".$file.".test.out ; echo $?");
    }else if($int_only){
        $ret_actual = shell_exec("python3 ".$int_script." --source=".$srcfile_name." < ".$infile_name." > ".$file.".test.out ; echo $?");
    }else{
        $ret_parser = shell_exec("php8.1 ".$parse_script." < ".$srcfile_name." > ".$file.".test.xml ; echo $?");
        if($ret_parser == 0){
            $ret_actual = shell_exec("python3 ".$int_script." --source=".$file.".test.xml < ".$infile_name." > ".$file.".test.out ; echo $?");
        }else{
            $ret_actual = $ret_parser;
        }
        // $res = 0;
    }
    $out_actual = "";
    
    if(file_exists($file.".test.out")){
        $out_actual = file_get_contents($file.".test.out");
    }
    $case->out_actual = $out_actual;
    $case->ret_actual = $ret_actual;
    
    //VALIDATION
    if($ret_expected == $ret_actual){
        
        if($ret_actual == 0){
            //IF PARSE ONLY => VALIDATE USING jexamxml
            if($parse_only){
                $jexamres_out = shell_exec("java -jar ".$jexampath."jexamxml.jar ".$outfile_name." ".$file.".test.out");
                $jexamres_ret = shell_exec("echo $?");
                $jexamres_out_arr = explode(" ", $jexamres_out);
                $jexamres_out_line = $jexamres_out_arr[count($jexamres_out_arr) - 2];
                // error_log("JEXAM ret: ".$jexamres_ret);
                // error_log("JEXAM out: ".$jexamres_out);
                // error_log("JEXAM out line: ".$jexamres_out_line);
                $result_str_ok = "are" == $jexamres_out_line;
                if($jexamres_ret == 0 && $result_str_ok){
                    $test_passed++;
                    $case->result = true;
                }else{
                    if($jexamres_ret != 0)
                        $case->message = "JEXAMRES return: ".$jexamres_ret;
                    else
                        $case->message = "JEXAMRES: Two files are not identical";
                    $case->result = false;
                }
            }else{
                $diff_ret = shell_exec("diff ".$outfile_name." ".$file.".test.out ; echo $?");
                if($diff_ret == 0){
                    $test_passed++;
                    $case->result = true;
                }else{
                    $case->message = "diff return code: ".$diff_ret;
                    $case->result = false;
                }
            }
        }else{
            $test_passed++;
            $case->result = true;
        }
    }else{
        $case->result = false;
    }
    array_push($results,$case);
    $test_cnt++;

    //CLEANUP
    if(!$noclean){
        unlink($file.".test.out");

        if(!$parse_only && !$int_only){
            unlink($file.".test.xml");
        }
    }
}
// echo "--Summary--\n";
// echo "Passed ".$test_passed."/".$test_cnt." tests\n";
// var_dump($results)

echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>test.php by Juraj Dedic (xdedic07)</title>
</head>
<body>
    <div class="container">
        <div class="test-summary">
            <h3>Test summary</h3>
            <p>Result : <strong>'.$test_passed.' tests passed. '.$test_cnt-$test_passed.' tests failed. Out of '.$test_cnt.' tests.</strong></p>
            <p>Directory: '.$test_dir.'</p>
            <p>Arguments: '.get_arguments($argv).'</p>
        </div>
        <h3>Tests list</h3>
        <div class="test-list">';

foreach ($results as $case) {
    if(!$case->result)
    echo '<div class="test-card">
            <div class="test-header">
                <div>
                    <h3 class="test-status '. (($case->result) ? ("success") : ("fail")) .'">'. (($case->result) ? ("OK") : ("FAIL")) .'</h3>
                    <h3 class="test-case-path">'.$case->directory.'</h3>
                </div>
                <div class="test-header-right">
                    <!--<span class="test-case-expand">V</span>-->
                </div>
            </div>
            <div class="test-content">
                <div class="test-content-line">
                    <span></span>
                    <span class="test-content-line-result">
                        <span>Actual</span>
                        <span>Expected</span>
                    </span>
                </div>
                <div class="test-content-line">
                    <span>Return code:</span>
                    <span class="test-content-line-result">
                        <span>'.$case->ret_actual.'</span>
                        <span>'.$case->ret_expected.'</span>
                    </span>
                </div>
                <div class="test-content-line">
                    '.$case->get_output($parse_only).'
                </div>
            </div>
        </div>';
}if($test_cnt == 0){
    echo '<div class="test-card">
    <div class="test-header">
        <div>
            <h3 class="test-status warn">WARN</h3>
            <h3>No tests have been found in the directory :(</h3>
        </div>
    </div>
</div>';
}elseif($test_passed == $test_cnt)
    echo '<div class="test-card">
    <div class="test-header">
        <div>
            <h3 class="test-status success">OK</h3>
            <h3>Congratulations! All tests passed</h3>
        </div>
    </div>
</div>';
if($test_passed > 0){
    echo '<div class="test-card passed-tests">
    <div class="test-header">
        <div>
            <h3 class="test-status success">OK</h3>
            <h3>List of passed tests</h3>
        </div>
        <div>
            <button id="toggle-passed">Toggle</button>
        </div>
    </div>
    <div class="test-content">';
    foreach($results as $case){
        if($case->result)
            echo '<p>'.$case->directory.'</p>';
    }
    echo '    </div>
    </div>';
}
echo "        
        </div>
    </div>
</div>
</body>

<style>
body{
    font-family: sans-serif;
}
.test-summary{
    padding: 10px;
    border: 1px solid black;
}
.test-header div h3 {
    display: inline-block;
    margin: 0;
}
.test-status{
    background-color: rgb(174, 174, 174);
    padding: 5px;
    width: 60px;
    text-align: center;
    line-height: 20px;
}
.test-case-path{
    /* font-family: 'Consolas';  */
    font-weight: 300;
}
.test-card{
    border: 1px solid black;
    padding: 10px;
    margin-bottom: 7px;
}
.container{
    max-width: 800px;
    margin: 0 auto;
}
.test-header{
    display: flex;
    justify-content: space-between;
}
.test-content{
    margin: 5px 0 0 0;
}
.test-content-line{
    display: flex;
    justify-content: space-between;
}
.test-content-line-result{
    width: calc(100% - 150px);
    display: flex;
    justify-content: space-around;
    align-items: flex-start;
}
.test-content-line-result span{
    box-sizing: border-box;
    padding: 5px;
    display: inline-block;
    width: 49%;
}
.code-line{
    background-color: rgb(174, 174, 174);
    font-family: 'Consolas';
    overflow: auto;
}
.passed-tests .test-content p{
    margin: 3px 0;
}
#toggle-passed{
    padding: 7px 10px;
    box-shadow: rgb(0 0 0 / 35%) 0px 1px 3px 0px;
    cursor: pointer;
    border-radius: 2px;
    border: none;
    background: rgb(186 186 186);
    font-weight: 600;
}
.success{background-color: #58BD6E;}
.warn{background-color: #dfce2f;}
.fail   {background-color: #FD6A61;}
.no-flex--inline{display:inline-block;}
.invisible{display: none;}
</style>

<script>
    document.querySelector('.passed-tests .test-content').classList.add('invisible')

    document.getElementById('toggle-passed').addEventListener('click', (event) => {
        document.querySelector('.passed-tests .test-content').classList.toggle('invisible')
    })
</script>

</html>";
?>