<?php
ini_set('display_errors','stderr');

/**
 * Prints an error about unexpected operand string
 * 
 * @param current_str the actual string which we got
 * @param expected the operand object, which contains info about the expected type
 */
function error_unexpected_op($current_str,$expected){
    error_log("\033[31mSyntax error\033[0m: Wrong operand: \033[33m".$current_str."\033[0m, but ".get_OM_string($expected->accepted_operand)." expected.");
}

/**
 * Prints message with a debug prefix
 */
function debug_print ($msg) {
    fwrite(STDERR,"DEBUG: ".$msg."\n");
}

/**
 * Searches the instruction set for a particular instruction
 * 
 * @param IS Array containing the Instruction objects
 * @param instruction_name Name of the instruction which we want to get
 * 
 * @return Instruction based on the name
 */
function get_instruction($IS, $instruction_name){

    foreach ($IS as $instruction){
        if($instruction == NULL){
            error_log("Internal error: In get_instruction() instruction is NULL");
            return NULL;
        }
        if($instruction->name == $instruction_name){
            return clone $instruction;
        }
    }
    return NULL;

}

/**
 * REGEXES BASED ON OPERANDS TYPES
 */
const OP_TYPE = "/int|bool|string|nil/";
const OP_LABEL =  "/[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";
const OP_VAR = "/(GF|TF|LF)@[a-zA-Z%!?&$*_-][a-zA-Z0-9%!?&$*_-]*/";
const OP_SYMB_INT = "/int@(-|[0-9]*)[0-9]+/";
const OP_SYMB_BOOL = "/bool@(true|false)/";
const OP_SYMB_STRING = "/^string@.*$/";
// const OP_SYMB_STRING = "/^string@[^\\\]*$/";
const OP_SYMB_NIL = "/nil@nil/";


/**
 * OM - Operand Mode
 */
Enum OM {
    case NONE; case SYMBOL; case VARIABLE; case LABEL; case TYPE;
}

/**
 * Returns string based on the provided operation mode for printing
 */
function get_OM_string($mode){
    switch($mode){
        case OM::NONE:
            return "NONE";
        case OM::SYMBOL:
            return "SYMBOL";
        case OM::VARIABLE:
            return "VARIABLE";
        case OM::LABEL:
            return "LABEL";
        case OM::TYPE:
            return "TYPE";
        default:
            error_log("INTERNAL ERROR: Unhlandled OM in get_OM_string!");
    }
}

/**
 * Operand - Stores info about Operand and performs checks
 * @property type String containing the type attribute in XML
 * @property value Text content of the operand
 * @property accepted_operand OM Enum to check the syntax
 */
class Operand {
    public $type;
    public $value;

    public $accepted_operand;
    // public $expected_regex;

    /**
     * Stores provided value and determines argument type
     */
    function load_value($value){
        $this->value = $value;
        switch($this->accepted_operand){
            case OM::SYMBOL:
                if( preg_match(OP_SYMB_INT,$value) ){
                    $this->type = "var";
                }else if(preg_match(OP_SYMB_STRING,$value)){
                    $this->type = "string";
                }else if(preg_match(OP_SYMB_BOOL,$value)){
                    $this->type = "bool";
                }else if(preg_match(OP_SYMB_NIL,$value)){
                    $this->type = "nil";
                }else if(preg_match(OP_VAR,$value)){
                    $this->type = "var";
                }else{
                    error_log("INTERNAL ERROR: unchecked value in load_value!");
                }
                break;
            case OM::VARIABLE:
                $this->type = "var";
                break;
            case OM::LABEL:
                $this->type = "label";
                break;
            case OM::TYPE:
                $this->type = "type";
                break;
            case OM::NONE:
                error_log("INTERNAL ERROR (load_value): Trying to load value into operand which should be empty!");
                break;
            default:
                error_log("INTERNAL ERROR (load_value): Unhandled case!");
        }
    }

    function is_valid(){
        return $this->is_valid_value($this->value);
    }

    /**
     * Checks if the provided value is valid (matches the expected regex based on the mode (type))
     */
    function is_valid_value($value){

        if($this->accepted_operand == OM::SYMBOL){
            return 
                preg_match(OP_SYMB_INT,$value)||
                preg_match(OP_SYMB_STRING,$value)||
                preg_match(OP_SYMB_BOOL,$value)||
                preg_match(OP_SYMB_NIL,$value)||
                preg_match(OP_VAR,$value);
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
            if($this->accepted_operand == OM::NONE){
                if($value == "")
                    return true;
                else
                    return false;
            }
            error_log("Internal Error: Unhlandled accepted operand in is_valid()");
        }
    }

}

/**
 * Instruction - stores info about an instruction & allows to check operands
 */
class Instruction {
    // Properties
    public $name;
    public $order;
    public $opcode;

    protected $operand1;
    protected $operand2;
    protected $operand3;

    /**
     * Creates the instruction obejct with expected operand types
     * @param name Instruction name
     * @param operandN_mode OM Enum which is expected at the particular place (N - number <1,3>)
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

    public function __clone(){
        $this->operand1 = clone $this->operand1;
        $this->operand2 = clone $this->operand2;
        $this->operand3 = clone $this->operand3;
    }
    
    /**
     * @brief checks operands of the instruction
     * @param $operands_array an array of operands to be checked
     * @return true if provided operands are ok
    */
    public function check_operands($operands_array){
        $op1 = true;
        $op2 = true;
        $op3 = true;
        if($this->operand1->accepted_operand != OM::NONE){
            $current_operand = array_shift($operands_array);
            $op1 = $this->operand1->is_valid_value($current_operand);

            if(!$op1)
                error_unexpected_op($current_operand,$this->operand1);
            // if($op1 == false)
            //     return false;
        }
        if($this->operand2->accepted_operand != OM::NONE){
            $current_operand = array_shift($operands_array);
            $op2 = $this->operand2->is_valid_value($current_operand);

            if(!$op2)
                error_unexpected_op($current_operand,$this->operand2);
            // if($op2 == false)
            //     return false;
        }
        if($this->operand3->accepted_operand != OM::NONE){
            $current_operand = array_shift($operands_array);
            $op3 = $this->operand3->is_valid_value($current_operand);

            if(!$op3)
                error_unexpected_op($current_operand,$this->operand3);
            // if($op3 == false)
            //     return false;
        }


        error_log("OPERANDS SYNTAX:"."OP1 ".$op1.", OP2 ".$op2.", OP3 ".$op3);
        if(!empty($operands_array)){
            $remaining_operands = join($operands_array);
            if($remaining_operands != "" && !ctype_space($remaining_operands)){
                error_log("\033[31mSyntax error\033[0m: Unexpected character sequence: \033[33m".join(" ",$operands_array)."\033[0m");
                return false;
            }
        }
        return $op1 && $op2 && $op3;
    }

    function load_operands($operands_array){
        if($this->check_operands($operands_array)){
            if($this->operand1->accepted_operand != OM::NONE)
                $this->operand1->load_value($operands_array[0]);
            if($this->operand2->accepted_operand != OM::NONE)
                $this->operand2->load_value($operands_array[1]);
            if($this->operand3->accepted_operand != OM::NONE)
                $this->operand3->load_value($operands_array[2]);

            return true;
        }else{
            error_log("Error: Couldn't load operands");
            return false;
        }
    }
    // function generate_xml(){

    // }
}

// Instruction set block
// Storing all the instructions in the instruction set array

$INSTRUCTION_SET = [];
//frames & function calls
array_push($INSTRUCTION_SET, new Instruction("MOVE",OM::VARIABLE,OM::SYMBOL) );
array_push($INSTRUCTION_SET, new Instruction("CREATEFRAME") );
array_push($INSTRUCTION_SET, new Instruction("PUSHFRAME") );
array_push($INSTRUCTION_SET, new Instruction("POPFRAME") );
array_push($INSTRUCTION_SET, new Instruction("DEFVAR",OM::VARIABLE) );
array_push($INSTRUCTION_SET, new Instruction("CALL",OM::LABEL) );
array_push($INSTRUCTION_SET, new Instruction("RETURN"));
//data stack
array_push($INSTRUCTION_SET, new Instruction("PUSHS",OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("POPS",OM::VARIABLE));
//Arhytmetic, relational, boolean & conversion
array_push($INSTRUCTION_SET, new Instruction("ADD",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("SUB",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("MUL",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("IDIV",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("LT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("GT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("EQ",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("AND",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("OR",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("NOT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("INT2CHAR",OM::VARIABLE,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("STR2INT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
//input output
array_push($INSTRUCTION_SET, new Instruction("READ",OM::VARIABLE,OM::TYPE));
array_push($INSTRUCTION_SET, new Instruction("WRITE",OM::SYMBOL));
//strings
array_push($INSTRUCTION_SET, new Instruction("CONCAT",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("STRLEN",OM::VARIABLE,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("GETCHAR",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("SETCHAR",OM::VARIABLE,OM::SYMBOL,OM::SYMBOL));
//types
array_push($INSTRUCTION_SET, new Instruction("TYPE",OM::VARIABLE,OM::SYMBOL));
//program flow
array_push($INSTRUCTION_SET, new Instruction("LABEL",OM::LABEL));
array_push($INSTRUCTION_SET, new Instruction("JUMP",OM::LABEL));
array_push($INSTRUCTION_SET, new Instruction("JUMPIFEQ",OM::LABEL,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("JUMPIFNEQ",OM::LABEL,OM::SYMBOL,OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("EXIT",OM::SYMBOL));
//debug
array_push($INSTRUCTION_SET, new Instruction("DPRINT",OM::SYMBOL));
array_push($INSTRUCTION_SET, new Instruction("BREAK",OM::VARIABLE));


//arguments check
if($argc > 1){
    if($argv[1] == "--help"){
        echo("Usage: php parser.php [options]\n");
        exit(0);
    }
}


$lang_id = false;
$parse_result = true;
$line_cnt = 1;
$instruction_cnt = 0;
$exit_code = 0;
$program = [];

while($line = fgets(STDIN)){
    //clear new lines
    $line = explode("\r",$line)[0];
    //remove comments
    $line = explode("#",$line)[0];

    //check for header
    if(!$lang_id){
        $lang_id = strtolower($line) == ".ippcode22";
        goto while_end;
    }
    //skip if empty
    if($line == "" || ctype_space($line)){
        // error_log ("skipping line");
        goto while_end;
    }

    $line_split = explode(" ", $line);
    $instruction_str = $line_split[0];
    $instruction = NULL;
    $instruction = get_instruction($INSTRUCTION_SET, $instruction_str);
    $instruction->opcode = $instruction_str;

    if($instruction != NULL){
        //found instruction, checking operand syntax
        array_shift($line_split);
        // $operands_valid = $instruction->check_operands($line_split);

        $operands_valid = $instruction->load_operands($line_split);
        if($operands_valid){
            // error_log("Operands ok\n");
        }else{
            error_log("\033[31mSyntax error \033[0m(line \033[33m".$line_cnt."\033[0m): Operands error\n");
            $parse_result = false;
            $exit_code = 23;
        }

        $instruction->order = $instruction_cnt++;
    }else{
        error_log("\033[31mSyntax error \033[0m(line \033[33m".$line_cnt."\033[0m): Invalid instruction opcode: \033[33m".($instruction_str)."\033[0m\n");
        $parse_result = false;
        $exit_code = 22;
    }

    array_push($program,$instruction);

    while_end:
        $line_cnt++;
}


if(!$lang_id){
    fwrite(STDERR,"Error: header '.IPPcode22' missing.\n");
    $exit_code = 21;
}

$parse_result_str = $parse_result ? "ok" : "error";
error_log("Parse result is ". $parse_result_str);

// var_dump($program);

exit($exit_code);

?>