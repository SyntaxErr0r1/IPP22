import xml.etree.ElementTree as XML
from xml.dom import minidom

# from enum import Enum 
import sys

def eprint(*args, **kwargs):
    print(*args, file=sys.stderr, **kwargs)


sourcename = "program.xml"
inputname = "input.xml"

sourcefile = open(sourcename,"r")

# class arg_type(Enum):
#     NIL = 0
#     INT = 1
#     STRING = 2
#     BOOL = 3
#     LABEL = 4
#     TYPE = 5
#     VAR = 6

class Instruction:
    opcode = None
    order = None
    args = []
    action = None
    def __init__(self,opcode):
        self.opcode = opcode
    def __str__(self):
        strn = "Instruction: {opcode: "+self.opcode+",\targs: "
        if len(self.args):
            for arg in self.args:
                strn += "\n\t"+str(arg)
        strn += "}"
        return strn

class Argument:
    type = None
    value = None
    def __str__(self):
        return "Argument: {type: "+self.type+",\tvalue: "+str(self.value)+"}"

class Frame:
    data = []
    def get_variable(self,var_name):
        return self.data[var_name]
    
    def set_variable(self,var_name):
        return self.data[var_name]
    

#naming
# var_id is STRING variable name with frame (eg. GF@out)
# var_name is STRING only the variable name (eg. out)
# var is an instance of the Variable class

class DataStorage:
    GF = []     #list of variables
    LF = []     #stack of lists of variables
    TF = None
    stack = []

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
            return self.LF
    
    def exists_variable(self,var_id):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)
        #can cause error when frame undefined
        for variable in frame:
            if variable.name == var_name:
                return True
        return False
        
    def create_variable(self, var_id):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)

        if(frame == None):
            eprint("Error: Frame "+frame_name+" does no exist!")
            exit(55)
        if self.exists_variable(var_id):
            eprint("Error: Variable "+var_id+" already defined!")
            exit(52)

        new_var = Variable()
        new_var.name = var_name

        frame.append(new_var)

    def assign_variable(self,var_id,value,type):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)
        
        for variable in frame:
            if variable.name == var_name:
                variable.value = value
                variable.type = type
                variable.type_adjust()
                return
        eprint("Error: Variable "+var_id+" not defined!")
        exit(54)
        
    
    def __get_var_by_name(self,frame,var_name):
        for variable in frame:
            if variable.name == var_name:
                return variable
        return None

    def __get_var_by_id(self,frame,var_id):
        var_name = get_var_name(var_id)
        var = self.__get_var_by_name(frame,var_name)
        if var == None:
            eprint("Error: Variable "+var_id+" not defined!")
            exit(54)
        else:
            return var

    def get_var(self,var_id):
        frame = self.get_var_frame(var_id)
        return self.__get_var_by_id(frame,var_id)

    def create_frame(self):
        self.TF = []
    
    def push_frame(self):
        if self.TF == None:
            eprint("Error Temporary Frame does not exist!")
            exit(55)
        self.LF.append(self.TF)
        self.TF = None

    def pop_frame(self):
        if len(self.lf) == 0:
            eprint("Error Local Frame stack empty!")
            exit(55)
        self.TF = self.LF.pop()
    
    # def stack_push(self,var):
    #     self.
    

class Variable:
    name = None
    type = None
    value = None

    def type_adjust(self):
        if self.type == "int":
            self.value = int(self.value)
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

storage = DataStorage()

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

def get_frame_name(var_id):
    var_split = var_id.split("@")
    frame_name = var_split[0]
    return frame_name

def get_var_name(var_id):
    var_split = var_id.split("@")
    var_name = var_split[1]
    return var_name

#returns Variable object:
#if symbol_arg is type VAR  => returns variable from STORAGE
#if symbol_arg is VALUE     => returns NEW VARiable object (only temporary variable, not in storage)
def get_symbol(symbol_arg):
    if symbol_arg.type == "var":
        return storage.get_var(symbol_arg.value)
    else:
        symbol = Variable()
        symbol.value = symbol_arg.value
        symbol.type = symbol_arg.type
        symbol.type_adjust()
        return symbol

#INSTRUCTION ACTIONS

def defvar_action(instruction):
    var_id = instruction.args[0].value
    storage.create_variable(var_id)

def move_action(instruction):
    dest_var = instruction.args[0]
    origin_var = instruction.args[1]
    origin = get_symbol(origin_var)
    storage.assign_variable(dest_var.value,origin.value,origin.type)
def add_action(instruction):
    dest_var = instruction.args[0]
    number_arg1 = instruction.args[1]
    number_arg2 = instruction.args[2]

    number1 = get_symbol(number_arg1).value
    number2 = get_symbol(number_arg2).value
    storage.assign_variable(dest_var.value,number1+number2,"int")

def write_action(instruction):
    string_var = get_symbol(instruction.args[0])
    print(string_var.value, end="",flush=True)

            
#instruction action mapping
set = {}
set["DEFVAR"] = defvar_action
set["MOVE"] = move_action
set["ADD"] = add_action
set["WRITE"] = write_action

program = []
tree = XML.parse(sourcename)
program_element = tree.getroot()

for instruction_element in program_element:
    instruction = Instruction(instruction_element.get("opcode"))
    instruction.order = instruction_element.get("order")
    args = []
    for argument_element in instruction_element:
        argument = Argument()
        argument.type = argument_element.get("type")
        argument.value = argument_element.text
        args.append(argument)
    instruction.args = args
    # print(instruction)
    program.append(instruction)


for instruction in program:
    set[instruction.opcode](instruction)





# # storage.create_variable("GF@ahoj")
# # storage.assign_variable("GF@ahoj",12,"int")


# arg1 = Argument()
# arg1.type = "int"
# arg1.value = "10"
# # value1 = get_symbol_value(arg1)

# arg2 = Argument()
# arg2.type = "var"
# arg2.value = "GF@ahoj"
# # value2 = get_symbol_value(arg2)

# move_instr = Instruction("MOVE")
# move_instr.args.append(arg2)
# move_instr.args.append(arg1)

# instr = Instruction("MOVE")
# instr.args.append(arg2)
# instr.args.append(arg2)
# instr.args.append(arg2)

# write_instr = Instruction("MOVE")
# write_instr.args.append(arg2)
# defvar_action(instr)
# move_action(move_instr)
# add_action(instr)
# write_action(write_instr)
 
# print("plus ="+str(value1+value2))

# for var in storage.GF:
#     print(var)