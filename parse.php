<?php
ini_set('display_errors','stderr');

function debug_print ($msg) {
    fwrite(STDERR,"DEBUG: ".$msg."\n");
}

function get_instruction($IS, $instruction_name){

    foreach ($IS as $instruction){
        if($instruction == NULL){
            error_log("Internal error: In get_instruction() instruction is NULL");
            return NULL;
        }
        if($instruction->name == $instruction_name){
            return $instruction;
        }
    }
    return NULL;

}

class Operand {
    public $type;
    public $value;

    public $expected_regex;
}

class Instruction {
    // Properties
    public $name;
    public $order;
    public $opcode;

    protected $operand1;
    protected $operand2;
    protected $operand3;

    public function __construct($name, $operand1 = NULL, $operand2 = NULL, $operand3 = NULL){
        $this->name = strtoupper( $name );
        $this->operand1 = $operand1;
        $this->operand2 = $operand2;
        $this->operand3 = $operand3;
    }

    // function generate_xml(){

    // }
}

$OP_SYMB = new Operand();
// $OP_SYMB.type = "symb";
$OP_SYMB->expected_regex = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

$OP_VAR = new Operand();
// $OP_VAR.type = "var";
$OP_VAR->expected_regex = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

$OP_LABEL = new Operand();
$OP_LABEL->type = "label";
$OP_LABEL->expected_regex = "/[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

$OP_TYPE = new Operand();
$OP_TYPE->expected_regex = "/int|bool|string|nil/";


$INSTRUCTION_SET = [];

array_push($INSTRUCTION_SET, new Instruction("MOVE",$OP_VAR,$OP_SYMB) );
array_push($INSTRUCTION_SET, new Instruction("CREATEFRAME") );
array_push($INSTRUCTION_SET, new Instruction("PUSHFRAME") );
array_push($INSTRUCTION_SET, new Instruction("POPFRAME") );
array_push($INSTRUCTION_SET, new Instruction("CALL",$OP_LABEL) );
array_push($INSTRUCTION_SET, new Instruction("RETURN"));

array_push($INSTRUCTION_SET, new Instruction("PUSHS",$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("POPS",$OP_VAR));

array_push($INSTRUCTION_SET, new Instruction("ADD",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("SUB",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("MUL",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("DIV",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("LT",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("GT",$OP_VAR,$OP_SYMB,$OP_SYMB));
array_push($INSTRUCTION_SET, new Instruction("EQ",$OP_VAR,$OP_SYMB,$OP_SYMB));

// array_push($INSTRUCTION_SET, new Instruction("AND",$OP_VAR,$OP_SYMB,$OP_SYMB));

array_push($INSTRUCTION_SET, new Instruction("INT2CHAR",$OP_VAR,$OP_SYMB));

array_push($INSTRUCTION_SET, new Instruction("STR2INT",$OP_VAR,$OP_SYMB,$OP_SYMB));

// array_push($INSTRUCTION_SET, new Instruction("AND",$OP_VAR,$OP_SYMB,$OP_SYMB));

if($argc > 1){
    if($argv[1] == "--help"){
        echo("Usage: php parser.php [options]\n");
        exit(0);
    }
}

$lang_id = false;

while($line = fgets(STDIN)){
    //clear new lines
    $line = explode("\r",$line)[0];
    //remove comments
    $line = explode("#",$line)[0];

    //check for header
    if(!$lang_id){
        $lang_id = strtolower($line) == ".ippcode22";
    }
    //skip if empty
    if($line == ""){
        echo ("skipping line\n");
        continue;
    }

    $line_split = explode(" ", $line);
    $instruction = NULL;
    $instruction = get_instruction($INSTRUCTION_SET, $line_split[0]);

    if($instruction != NULL){
        echo("Found ".$instruction->name."\n");
        //found instruction, checking operand syntax
        


    }else{
        echo("Russian warship go fuck yourself\n");
    }

}


if(!$lang_id){
    fwrite(STDERR,"Error: header '.IPPcode22' missing.\n");
}

?>