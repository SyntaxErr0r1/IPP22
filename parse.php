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


const OP_TYPE = "/int|bool|string|nil/";
const OP_LABEL =  "/[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";
const OP_VAR = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";
const OP_SYMB_INT = "/int@(-|[0-9]*)[0-9]+/";
const OP_SYMB_BOOL = "/bool@(true|false)/";
const OP_SYMB_STRING = "/^string@.*$/";
const OP_SYMB_NIL = "/nil@nil/";


/**
 * OM - Operand Mode
 * 
 */
Enum OM {
    case NONE; case SYMBOL; case VARIABLE; case LABEL; case TYPE;
}

class Operand {
    public $type;
    public $value;

    public $accepted_operand;
    public $expected_regex;



    function is_valid(){
        return $this->is_valid_value($this->value);
    }

    function is_valid_value($value){
        if($this->accepted_operand == OM::SYMBOL){
            return preg_match(OP_SYMB_INT,$value)||preg_match(OP_SYMB_STRING,$value)||preg_match(OP_SYMB_BOOL,$value)||preg_match(OP_SYMB_NIL,$value);
        }else{
            if($this->accepted_operand == OM::VARIABLE){
                return preg_match(OP_VAR,$value);
            }
            if($this->accepted_operand == OM::LABEL){
                return preg_match(OP_LABEL,$value);
            }
            if($this->accepted_operand == OM::TYPE){
                return preg_match(OP_TYPE,$value);
            }
            if($this->accepted_operand == OM::LABEL){
                return preg_match(OP_LABEL,$value);
            }
            if($this->accepted_operand == OM::NONE){
                if($value == "")
                    return true;
                else
                    return false;
            }
        }
        error_log("Internal Error: Unhlandled accepted operand in is_valid()");
    }

}

class Instruction {
    // Properties
    public $name;
    public $order;
    public $opcode;

    protected $operand1;
    protected $operand2;
    protected $operand3;

    /**
     * @brief creates the isntruction obejct
     */
    public function __construct($name, $operand1_mode = OM::NONE, $operand2_mode = OM::NONE, $operand3_mode = OM::NONE){
        $this->name = strtoupper( $name );
        $this->operand1 = new Operand();
        $this->operand2 = new Operand();
        $this->operand3 = new Operand();


        $this->operand1->accepted_operand = $operand1_mode;
        $this->operand2->accepted_operand = $operand2_mode;
        $this->operand3->accepted_operand = $operand3_mode;
    }
    
    /**
     * @brief checks operands of the instruction
     * @param $operands_array an array of operands to be checked
     * @return true if provided operands are ok
    */
    public function check_operands($operands_array){
        $op1 = NULL;
        $op2 = NULL;
        $op3 = NULL;
        if($this->operand1->accepted_operand != OM::NONE){
            $op1 = $this->operand1->is_valid_value($operands_array[0]);
            // if($op1 == false)
            //     return false;
        }
        if($this->operand2->accepted_operand != OM::NONE){
            $op2 = $this->operand2->is_valid_value($operands_array[1]);
            // if($op2 == false)
            //     return false;
        }
        if($this->operand3->accepted_operand != OM::NONE){
            $op3 = $this->operand3->is_valid_value($operands_array[2]);
            // if($op3 == false)
            //     return false;
        }
        error_log("OPERANDS SYNTAX:"."OP1 ".$op1.", OP2 ".$op2.", OP3 ".$op3);
        return true;
    }
    // function generate_xml(){

    // }
}



// $OP_SYMB = new Operand();
// // $OP_SYMB.type = "symb";
// $OP_SYMB->expected_regex = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

// $OP_VAR = new Operand();
// $OP_VAR->type = "var";
// $OP_VAR->expected_regex = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

// $OP_LABEL = new Operand();
// $OP_LABEL->type = "label";
// $OP_LABEL->expected_regex = "/[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";

// $OP_TYPE = new Operand();
// $OP_LABEL->type = "type";
// $OP_TYPE->expected_regex = "/int|bool|string|nil/";


$INSTRUCTION_SET = [];

array_push($INSTRUCTION_SET, new Instruction("MOVE",OM::VARIABLE,OM::SYMBOL) );
array_push($INSTRUCTION_SET, new Instruction("CREATEFRAME") );
array_push($INSTRUCTION_SET, new Instruction("PUSHFRAME") );
array_push($INSTRUCTION_SET, new Instruction("POPFRAME") );
array_push($INSTRUCTION_SET, new Instruction("CALL",OM::LABEL) );
array_push($INSTRUCTION_SET, new Instruction("RETURN"));

array_push($INSTRUCTION_SET, new Instruction("PUSHS",OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("POPS",OM::VARIABLE));

array_push($INSTRUCTION_SET, new Instruction("ADD",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("SUB",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("MUL",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("DIV",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("LT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("GT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("EQ",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));

// array_push($INSTRUCTION_SET, new Instruction("AND",$OP_VAR,$OP_SYMB,$OP_SYMB));

array_push($INSTRUCTION_SET, new Instruction("INT2CHAR",OM::VARIABLE,OM::SYMBOL));

array_push($INSTRUCTION_SET, new Instruction("STR2INT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));

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
        continue;
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
        error_log("Found ".$instruction->name."");
        //found instruction, checking operand syntax
        array_shift($line_split);
        // var_dump($line_split);
        $operands_valid = $instruction->check_operands($line_split);
        if($operands_valid){
            error_log("Operands ok\n");
        }else{
            error_log("Error: Operands error\n");
        }
    }else{
        error_log("Error: Invalid instruction\n");
    }

}


if(!$lang_id){
    fwrite(STDERR,"Error: header '.IPPcode22' missing.\n");
}

?>