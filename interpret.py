#Name and surname: Juraj Dedič
#Login: xdedic07
import sys
import xml.etree.ElementTree as XML
import re

#prints to stderr
def eprint(*args, **kwargs):
    print(*args, file=sys.stderr, **kwargs)

#prints usage
def print_help():
    print("Usage: python3.8 interpret.py --source=<xml-file> --input=<input-file>")

sourcename = None
inputname = None

inputfile = ""
input_lines_read = 0

####### ARGS PARSING #######

for i in range(0,len(sys.argv)):
    argument = sys.argv[i]
    if(argument == "--help"):
        print_help()
        if(len(sys.argv) > 2):
            eprint("Error: Wrong parameters")
            exit(10)
        exit()
    elif(re.match("^--input=",argument)):
        inputname = argument[8:]
    elif(re.match("^--source=",argument)):
        sourcename = argument[9:]

if len(sys.argv) < 2 or ( inputname == None and sourcename == None ):
    eprint("Error: Too few arguments")
    exit(10)

if sourcename != None:
    try:
        sourcefile = open(sourcename,"r")
    except:
        eprint("Cannot open sourcefile",sourcename)
        exit(11)

if inputname != None:
    try:
        # inputfile = open(inputname,"r")
        with open(inputname) as file:
            inputfile = file.readlines()
    except:
        eprint("Cannot open inputfile",inputname)
        exit(11)

#returns one line from input file
def input_read_file():
    line = inputfile[storage.input_lines_read]
    storage.input_lines_read += 1
    return line

#Holds data for one instruction (opcode, order, index in storage.program array). Has array with arguments
class Instruction:
    opcode = None
    order = None
    args = {}
    index = None

    def __init__(self,opcode,order):
        check_opcode(opcode)
        self.opcode = opcode.upper()
        check_order(order)
        self.order = int(order)
    def __str__(self):
        strn = "Instruction: {order: "+str(self.order)+",\topcode: "+self.opcode+",\targs: "
        if len(self.args):
            for arg in self.args:
                strn += "\n\t"+str(arg)
        strn += "}"
        return strn

    #performs checks if the argument at position pos exists and returns it
    def get_arg(self,pos):
        if pos in self.args:
            return self.args[pos]
        eprint("Error: Expected argument (order:"+str(self.order)+")")
        exit(32)

#holds type and value of an argument
class Argument:
    type = None
    value = None
    def __str__(self):
        return "Argument: {type: "+self.type+",\tvalue: "+str(self.value)+"}"

    

#naming
# var_id is STRING variable name with frame (eg. GF@out)
# var_name is STRING only the variable name (eg. out)
# var is an instance of the Variable class

#Singleton 
#has all the Variables stored inside (all frames and stack)
#has callstack
#stores labels
#stores index of current instruction (program_counter) and position in the input line
class DataStorage:
    GF = []     #list of variables
    LF = []     #stack of lists of variables
    TF = None
    stack = []

    callstack = []
    labels = []
    program = []
    program_counter = 0
    input_lines_read = 0

    #returns frame by var id
    def get_var_frame(self, var_id):
        frame_name = get_frame_name(var_id)
        return self.get_frame(frame_name)

    #returns frame by frame id
    def get_frame(self, frame_name):
        if frame_name == "GF":
            return self.GF
        if frame_name == "TF":
            return self.TF
        if frame_name == "LF":
            return self.LF[-1] if len(self.LF) else None
    
    #checks if var is inside the frame
    def exists_variable(self,var_id):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)
        #can cause error when frame undefined
        for variable in frame:
            if variable.name == var_name:
                return True
        return False
        
    #craetes variable without value and type inside a frame
    def create_variable(self, var_id):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)

        if(frame == None):
            eprint("Error: Frame "+frame_name+" does not exist!")
            exit(55)
        if self.exists_variable(var_id):
            eprint("Error: Variable "+var_id+" already defined!")
            exit(52)

        new_var = Variable()
        new_var.name = var_name

        frame.append(new_var)

    #assigns value and type to a variable (var_id)
    def assign_variable(self,var_id,value,type):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)
        if(frame == None):
            eprint("Error: Frame "+frame_name+" does not exist!")
            exit(55)
        
        for variable in frame:
            if variable.name == var_name:
                variable.value = value
                variable.type = type
                #variable.type_adjust()
                return
        eprint("Error: Variable "+var_id+" not defined!")
        exit(54)
        
    #returns variable (var_name) from frame
    def __get_var_by_name(self,frame,var_name):
        for variable in frame:
            if variable.name == var_name:
                return variable
        return None

    #returns variable (var_id) from frame
    def __get_var_by_id(self,frame,var_id):
        var_name = get_var_name(var_id)
        var = self.__get_var_by_name(frame,var_name)
        if var == None:
            eprint("Error: Variable "+var_id+" not defined!")
            exit(54)
        else:
            return var

    #returns variable (var_id), finds it frame
    def get_var(self,var_id):
        frame = self.get_var_frame(var_id)
        if(frame == None):
            eprint("Error: Frame for variable "+var_id+" does not exist!")
            exit(55)
        return self.__get_var_by_id(frame,var_id)

    #creates new temporary frame
    def create_frame(self):
        self.TF = []
    
    #pushes temporary frame into frame stack
    def push_frame(self):
        if self.TF == None:
            eprint("Error Temporary Frame does not exist!")
            exit(55)
        self.LF.append(self.TF)
        self.TF = None

    #pops frame from fram stack and sets it as temporary frame
    def pop_frame(self):
        if len(self.LF) == 0:
            eprint("Error Local Frame stack empty!")
            exit(55)
        self.TF = self.LF.pop()
    
#holds name and index of label inside the program
class Label:
    name = None
    index = None

    def __init__(self,name,index):
        self.name = name
        self.index = index
    
#holds name (without the frame identifier), type and value of variable
class Variable:
    name = None
    type = None
    value = None

    #adjsuts the type of the variable value
    #INT => converts it to int
    #BOOL => converts to bool
    #STRING => replaces alt codes
    def type_adjust(self):
        if self.type == None:
            eprint("INTERNAL ERROR: type_adjust on None type in "+str(self))
            exit(99)
        if self.type == "int":
            try:
                self.value = int(self.value)
            except:
                #!todo https://moodle.vut.cz/mod/forum/discuss.php?d=695 maybe should just return nil idk
                eprint("Error: Value",self.value," is not an int")
                exit(32)
        elif self.type == "bool":
            self.value = True if self.value == "true" else False
        elif self.type == "string":
            self.value = replace_altcodes(self.value)
        
    def str_name(self):
        return "NONE" if (self.name == None) else self.name
    def str_type(self):
        return "NONE" if (self.type == None) else self.type
    def str_value(self):
        return "NONE" if (self.value == None) else str(self.value)
    def __str__(self):
        return "Variable: {name: "+self.str_name()+",\t type:"+self.str_type()+",\t value:"+self.str_value()+"}"
    
    #performs check if the variable has value and returns it
    def get_value(self):
        if(self.value == None):
            eprint("Error: Variable "+str(self.name)+" missing value!")
            exit(56)
        # return self.value if self.type != "nil" else None
        return self.value

#for printing colored text into console
class bcolors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKCYAN = '\033[96m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'

storage = DataStorage()
instructions_executed = 0

#replaces altcodes by the apropriate UTF character in given string
def replace_altcodes(string):
    altcode_chars_left = 0
    altcode_str = ""
    result_str = ""
    for i in range(0,len(string)):
        current_char = string[i]
        if current_char == "\\":
            altcode_chars_left = 3
        elif altcode_chars_left > 0:
            altcode_str += current_char
            altcode_chars_left -= 1
            if altcode_chars_left == 0:
                result_str += chr(int(altcode_str))
                altcode_str = ""
        else:
            result_str += current_char
    return result_str

#returns the frame identifier (the part before @)
def get_frame_name(var_id):
    var_split = var_id.split("@")
    if(len(var_split) < 2):
        #!todo check if the code should really be 52
        eprint("Error: wrong variable name format in: "+var_id)
        exit(52)
    frame_name = var_split[0]
    return frame_name

#returns name of a variable (the part after @)
def get_var_name(var_id):
    var_split = var_id.split("@")
    if(len(var_split) < 2):
        #!todo check if the code should really be 52
        eprint("Error: wrong variable name format in: "+var_id)
        exit(52)
    var_name = var_split[1]
    return var_name

#returns Variable object:
#symbol_arg has type and value
#if symbol_arg is type VAR  => returns variable from STORAGE
#if symbol_arg is VALUE     => returns NEW VARiable object (only temporary variable, not in storage)
def get_symbol(symbol_arg):
    if symbol_arg.type == "var":
        return storage.get_var(symbol_arg.value)
    else:
        symbol = Variable()
        symbol.value = symbol_arg.value
        symbol.type = symbol_arg.type
        if symbol.type == "string" and symbol.value == None:
            symbol.value = ""
        symbol.type_adjust()
        return symbol

#checks if the symbol has given type
def check_type(symbol,type):
    if(symbol.type == None):
        eprint("Error: Variable "+str(symbol.name)+" missing value!")
        exit(56)
    if(symbol.type != type):
        eprint("Error: exptected type "+type+" but got "+str(symbol))
        exit(53)

#performs given arithmetic operation using instruction arguments
#operator: 0 => +, 1 => -, 2 => *, 3 => //
#instruction from which the arguments are loaded
def arithmetic_operation(instruction, operator):
    dest_var = instruction.get_arg(0)
    number1_symb = get_symbol(instruction.get_arg(1))
    number2_symb = get_symbol(instruction.get_arg(2))
    check_type(number1_symb,"int")
    check_type(number2_symb,"int")

    number1 = number1_symb.get_value()
    number2 = number2_symb.get_value()


    result = None
    if operator == 0:
        result = number1+number2
    elif operator == 1:
        result = number1-number2
    elif operator == 2:
        result = number1*number2
    elif operator == 3:
        if number2 == 0:
            eprint("Error: Division by 0")
            exit(57)
        result = number1//number2
    else:
        eprint("INTERNAL ERROR: arithmetic operation called with operator "+operator)
    storage.assign_variable(dest_var.value,result,"int")

#performs compare operation
#operator: 0 => <, 1 => >
#instrucion: holds the arguments
def compare_operation(instruction, operator):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    op1_val = op1.get_value()
    op2_val = op2.get_value()
    if(op1.type == "nil" or op2.type == "nil"):
        eprint("Error: nil in compare")
        exit(53)
    if(op1.type != op2.type):
        eprint("Error: Types are not matching!")
        exit(53)
    result = None
    if(operator == 0):
        result = op1_val<op2_val
    else:
        result = op1_val>op2_val

    storage.assign_variable(res_arg.value,result,"bool")

#performs logical operation
#operator: 0 => AND, 1 => OR
#instruction: holds the arguments
def logical_operation(instruction,operator):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    check_type(op1,"bool")
    check_type(op2,"bool")
    
    result = None
    if(operator == 0):
        result = op1.get_value() and op2.get_value()
    elif(operator == 1):
        result = op1.get_value() or op2.get_value()
    else:
        result = not op1.get_value()

    storage.assign_variable(res_arg.value,result,"bool")

#finds the label (based on label_name) inside the labels array
def get_label(label_name):
    for label in storage.labels:
        if label.name == label_name:
            return label
    return None

#adds new label into the storage.labels array
#instruction of the label
#index: index in the program
def label_register(instruction,index):
    label_name = instruction.get_arg(0).value
    if get_label(label_name) != None:
        eprint("Error: Label "+label_name+" already exists")
        exit(52)
    index = instruction.index
    label = Label(label_name,index)
    storage.labels.append(label)

#changes the program counter based on the provided label name
def jump(label_name):
    label = get_label(label_name)
    if label != None:
        # eprint("PC changed from",str(storage.program_counter),"to",str(label.index))
        storage.program_counter = label.index
        return
    else:
        eprint("Error: Label "+label_name+" does not exist")
        exit(52)

#debug print the frame
def eprint_frame(frame):
    if frame == None:
        eprint("Frame not created")
        return
    for var in frame:
        eprint(var)

#INSTRUCTION ACTIONS
#each of these functions is executed when its opcode is interpreted
def defvar_action(instruction):
    var_id = instruction.get_arg(0).value
    storage.create_variable(var_id)

def move_action(instruction):
    dest_var = instruction.get_arg(0)
    origin_var = instruction.get_arg(1)
    origin = get_symbol(origin_var)
    storage.assign_variable(dest_var.value,origin.get_value(),origin.type)
def add_action(instruction):
    arithmetic_operation(instruction,0)
def sub_action(instruction):
    arithmetic_operation(instruction,1)
def mul_action(instruction):
    arithmetic_operation(instruction,2)
def idiv_action(instruction):
    arithmetic_operation(instruction,3)

def write_action(instruction):
    string_var = get_symbol(instruction.get_arg(0))
    out = None
    if string_var.type == "nil":
        out = ""
    elif string_var.type == "bool":
        out = "true" if string_var.get_value() else "false"
    else:
        out = string_var.get_value()
    print(out, end="",flush=True)

def createframe_action(instruction):
    storage.create_frame()
def pushframe_action(instruction):
    storage.push_frame()
def popframe_action(instruction):
    storage.pop_frame()

def pops_action(instruction):
    if len(storage.stack) == 0:
        eprint("Error: Stack is empty!")
        exit(56)
    var_from_stack = storage.stack.pop()
    var_id = instruction.get_arg(0)
    storage.assign_variable(var_id.value,var_from_stack.get_value(),var_from_stack.type)

def pushs_action(instruction):
    arg = instruction.get_arg(0)
    variable = get_symbol(arg)
    if(variable.value == None):
        eprint("Error: Variable "+str(variable.name)+" missing value!")
        exit(56)
    storage.stack.append(variable)

def eq_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    op1_val = op1.get_value()
    op2_val = op2.get_value()
    if(op1.type != op2.type and (op1.type != "nil" and op2.type != "nil")):
        eprint("Error: Types are not matching!")
        exit(53)
    storage.assign_variable(res_arg.value,op1_val==op2_val,"bool")
def lt_action(instruction):
    compare_operation(instruction,0)
def gt_action(instruction):
    compare_operation(instruction,1)

def and_action(instruction):
    logical_operation(instruction,0)
def or_action(instruction):
    logical_operation(instruction,1)
def not_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    check_type(op1,"bool")
    storage.assign_variable(res_arg.value,not op1.get_value(), "bool")

def int2char_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    check_type(op1,"int")
    value = op1.get_value()
    if(value < 0 or value > 1114111):
        eprint("Error: INT2CHAR accepts integers from 0 to 1114111")
        exit(58)
    storage.assign_variable(res_arg.value,chr(value),"string")

def stri2int_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    check_type(op1,"string")
    check_type(op2,"int")
    string = op1.get_value()
    pos = op2.get_value()
    if(pos < 0 or pos > len(string)-1):
        eprint("Error: STRI2INT wrong position: "+str(pos)+" in string: "+string)
        exit(58)
    storage.assign_variable(res_arg.value,ord(string[pos]),"int")

def read_action(instruction):
    res_arg = instruction.get_arg(0)
    type_arg = instruction.get_arg(1)
    type = type_arg.value
    user_input = None
    fail = False
    try:
        # if --input is provided => read from file
        if(inputname != None):
            user_input = input_read_file()
        else:
            user_input = input()
    except:
        storage.assign_variable(res_arg.value,"nil","nil")
        return

    if(type == "int"):
        try:
            user_input = int(user_input)
        except:
            storage.assign_variable(res_arg.value,"nil","nil")
            return
            # eprint("Error: Input cannot be converted to int: "+user_input)
            # exit(57)
        storage.assign_variable(res_arg.value,user_input,"int")
    elif(type == "bool"):
        user_input = True if user_input.lower() == "true" else False
        storage.assign_variable(res_arg.value,user_input,"bool")
    elif(type == "string"):
        storage.assign_variable(res_arg.value,user_input,"string")
    else:
        storage.assign_variable(res_arg.value,"nil","nil")
        # eprint("Error: Wrong type in READ: "+type)
        # exit(57)

def concat_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    check_type(op1, "string")
    check_type(op2, "string")
    storage.assign_variable(res_arg.value,op1.get_value()+op2.get_value(),"string")

def strlen_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    check_type(op1, "string")
    storage.assign_variable(res_arg.value,len(op1.get_value()),"int")

def getchar_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    check_type(op1,"string")
    check_type(op2,"int")
    string = op1.get_value()
    pos = op2.get_value()
    if(pos < 0 or pos > len(string)-1):
        eprint("Error: GETCHAR wrong position: "+str(pos)+" in string: "+string)
        exit(58)
    storage.assign_variable(res_arg.value,string[pos],"string")

def setchar_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    check_type(op1,"int")
    check_type(op2,"string")
    pos = op1.get_value()
    str2 = op2.get_value()
    if str2 == "":
        eprint("Error: str2 empty "+str2)
        exit(58)
    string_var = storage.get_var(res_arg.value)
    string = string_var.get_value()
    check_type(string_var, "string")
    if(pos < 0 or pos > len(string)-1):
        eprint("Error: SETCHAR wrong position: "+str(pos)+" in string: "+string)
        exit(58)
    string = string[:pos] + str2[0] + string[pos + 1:]
    storage.assign_variable(res_arg.value,string,"string")

def type_action(instruction):
    res_arg = instruction.get_arg(0)
    op1 = get_symbol(instruction.get_arg(1))
    type = "" if op1.type == None else op1.type
    storage.assign_variable(res_arg.value,type,"string")

def dprint_action(instruction):
    op1 = get_symbol(instruction.get_arg(0))
    eprint(op1.get_value(),end='')

def label_action(instruction):
    return

def jump_action(instruction):
    label_name = instruction.get_arg(0).value
    jump(label_name)

def jumpifeq_action(instruction):
    label_name = instruction.get_arg(0).value
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    op1_value = op1.get_value()
    op2_value = op2.get_value()
    if(op1.type != op2.type and (op1.type != "nil" and op2.type != "nil")):
        eprint("Error: Types are not matching!")
        exit(53)
    if op1_value == op2_value:
        jump(label_name)

def jumpifneq_action(instruction):
    label_name = instruction.get_arg(0).value
    op1 = get_symbol(instruction.get_arg(1))
    op2 = get_symbol(instruction.get_arg(2))
    op1_value = op1.get_value()
    op2_value = op2.get_value()
    if(op1.type != op2.type and (op1.type != "nil" and op2.type != "nil")):
        eprint("Error: Types are not matching!")
        exit(53)
    if op1_value != op2_value:
        jump(label_name)

def exit_action(instruction):
    code_var = get_symbol(instruction.get_arg(0))
    code = code_var.get_value()
    check_type(code_var,"int")
    if 0 <= code and code <= 49:
        exit(code)
    else:
        eprint("Wrong return code in EXIT:",code)
        exit(57) 

def call_action(instruction):
    label_name = instruction.get_arg(0).value
    storage.callstack.append(instruction.index)
    jump(label_name)

def return_action(instruction):
    if(len(storage.callstack) == 0):
        eprint("Error: Call stack empty")
        exit(56)
    index = storage.callstack.pop()
    storage.program_counter = index

def break_action(instruction):
    eprint("--------"+bcolors.WARNING+"BREAK"+bcolors.ENDC+"--------")
    eprint("Current instruction order:\t",instruction.order)
    eprint("Total instruction executed:\t",instructions_executed)
    eprint(bcolors.HEADER+"Global Frame:"+bcolors.ENDC)
    eprint_frame(storage.GF)
    eprint(bcolors.HEADER+"Temporary Frame:"+bcolors.ENDC)
    eprint_frame(storage.TF)
    eprint(bcolors.HEADER+"Local Frame "+bcolors.ENDC+"(From bottom of the stack):")
    if len(storage.LF):
        for frame in storage.LF:
            eprint("Frame:")
            eprint_frame(frame)
    else:
        eprint("Frame stack empty")
    eprint("---------------------")

#instruction action mapping
#each opcode has the function mapped here
set = {}
set["MOVE"] = move_action
set["CREATEFRAME"] = createframe_action
set["PUSHFRAME"] = pushframe_action
set["POPFRAME"] = popframe_action
set["DEFVAR"] = defvar_action
set["CALL"] = call_action
set["RETURN"] = return_action
set["PUSHS"] = pushs_action
set["POPS"] = pops_action
set["ADD"] = add_action
set["SUB"] = sub_action
set["MUL"] = mul_action
set["IDIV"] = idiv_action
set["EQ"] = eq_action
set["LT"] = lt_action
set["GT"] = gt_action
set["AND"] = and_action
set["OR"] = or_action
set["NOT"] = not_action
set["INT2CHAR"] = int2char_action
set["STRI2INT"] = stri2int_action
set["READ"] = read_action
set["WRITE"] = write_action
set["CONCAT"] = concat_action
set["STRLEN"] = strlen_action
set["GETCHAR"] = getchar_action
set["SETCHAR"] = setchar_action
set["TYPE"] = type_action
set["LABEL"] = label_action
set["JUMP"] = jump_action
set["JUMPIFEQ"] = jumpifeq_action
set["JUMPIFNEQ"] = jumpifneq_action
set["EXIT"] = exit_action
set["DPRINT"] = dprint_action
set["BREAK"] = break_action

#checks if the expected and actual tag names are equal
def check_tag(expected,actual):
    if expected != actual:
        eprint("Error: Wrong tag! Expected:",expected,"got:",actual)
        exit(32)

#appends argument object into args array
#tag is the tagname of arg (decides to which place it will be assigned)
def append_argument(args,argument,tag):
    if not re.match("^arg[1-3]$",tag):
        eprint("Error: Invalid instruction argument tag:",tag)
        exit(32)
    pos = int(tag[3])-1
    if pos in args:
        eprint("Error: Duplicate instruction argument tag:",tag)
        exit(32)
    else:
        args[pos] = argument

#checks if the order is ok and not duplicate
def check_order(order):
    try:
        order = int(order)
    except:
        eprint("Error: Wrong instruction order",order)
        exit(32)
    if(order < 0):
        eprint("Error: Wrong instruction order",order)
        exit(32)
    for instruction in storage.program:
        if instruction.order == order:
            eprint("Error: Two instructions with same order")
            exit(32)

#checks if the opcode was specified and found in the instruction set
def check_opcode(opcode):
    if opcode == None:
        eprint("Invalid opcode",opcode)
        exit(32)
    for code in set:
        if opcode.upper() == code:
            return
    eprint("Invalid opcode",opcode)
    exit(32)

###### PARSING THE SOURCE FILE ######

try:
    tree = None
    if sourcename != None:
        tree = XML.parse(sourcename)
    else:
        tree = XML.parse(sys.stdin)
    program_element = tree.getroot()
except:
    eprint("Error: Invalid XML structure")
    exit(31)

###### ITERATING THROUGH program ######

check_tag(program_element.tag,"program")
for instruction_element in program_element:
    check_tag("instruction",instruction_element.tag)
    instruction = Instruction(instruction_element.get("opcode"),instruction_element.get("order"))
    args = {}
    for argument_element in instruction_element:
        argument = Argument()
        argument.type = argument_element.get("type")
        argument.value = argument_element.text
        append_argument(args,argument,argument_element.tag)
    instruction.args = args
    storage.program.append(instruction)

# keyfun = operator.attrgetter("order")
# storage.program.sort(key=keyfun, reverse=False)
storage.program.sort(key=lambda x: x.order, reverse=False)

program_length = len(storage.program)

#registering the labels
for i in range(0,program_length):
    instruction = storage.program[i]
    instruction.index = i
    if instruction.opcode == "LABEL":
        label_register(instruction,i)

#executing the program
while storage.program_counter < program_length:
    instruction = storage.program[storage.program_counter]
    # eprint("EXECUTING(",str(instruction),")")
    set[instruction.opcode](instruction)
    instructions_executed += 1
    storage.program_counter += 1
