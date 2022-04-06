from xml.etree.ElementTree import XML
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

class Argument:
    type = None
    value = None

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

    def get_var_frame(self, var_id):
        frame_name = get_frame_name(var_id)
        return self.get_frame(frame_name)

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
        
        for variable in frame:
            if variable.name == var_name:
                return 1
        return 0
        
    def create_variable(self, var_id):
        var_name = get_var_name(var_id)
        frame_name = get_frame_name(var_id)
        frame = self.get_frame(frame_name)

        if(frame == None):
            eprint("Error: frame does no exist!")
            return
        
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
        
    def get_var_by_name(self,frame,var_name):
        for variable in frame:
            if variable.name == var_name:
                return variable
        return None
    def get_var_by_id(self,frame,var_id):
        var_name = get_var_name(var_id)
        return self.get_var_by_name(frame,var_name)

class Variable:
    name = None
    type = None
    value = None

    def type_adjust(self):
        if self.type == "int":
            self.value = int(self.value)
        elif self.type == "bool":
            self.value = 1 if self.value == "true" else 0
        
    def str_type(self):
        return "NONE" if (self.type == None) else self.type
    def str_value(self):
        return "NONE" if (self.value == None) else str(self.value)
    def __str__(self):
        return "Variable: {name: "+self.name+",\t type:"+self.str_type()+",\t value:"+self.str_value()+"}"

storage = DataStorage()


def get_frame_name(var_id):
    var_split = var_id.split("@")
    frame_name = var_split[0]
    return frame_name

def get_var_name(var_id):
    var_split = var_id.split("@")
    var_name = var_split[1]
    return var_name

def get_var_value(var_id):
    return get_var(var_id).value

def get_var(var_id):
    frame = storage.get_var_frame(var_id)
    return storage.get_var_by_id(frame,var_id)

def get_symbol(symbol_arg):
    if symbol_arg.type == "var":
        return get_var(symbol_arg.value)
    else:
        symbol = Variable()
        symbol.value = symbol_arg.value
        symbol.type = symbol_arg.type
        symbol.type_adjust()
        return symbol

def get_symbol_value(symbol_arg):
    return get_symbol(symbol_arg).value
    

#instruction actions
def defvar_action(instruction):
    var_id = instruction.args[0].value
    storage.create_variable(var_id)

def move_action(instruction):
    dest_var = instruction.args[0]
    origin_var = instruction.args[1]
    #can be either existing var (copy type and value)
    origin = get_symbol(origin_var)
    # print("assigning to "+dest_var.value+" from "+str(origin))
    storage.assign_variable(dest_var.value,origin.value,origin.type)
def add_action(instruction):
    dest_var = instruction.args[0]
    number_arg1 = instruction.args[1]
    number_arg2 = instruction.args[2]

    number1 = get_symbol(number_arg1).value
    number2 = get_symbol(number_arg2).value
    storage.assign_variable(dest_var.value,number1+number2,"int")

#probably could make universal interface for symbol (variables/values) to work with the value.


# def setup_instruction(instruction):
#     match instruction.opcode:
#         case "DEFVAR":
#             print("ciao")
            

#for instruction in program
    #get opcode
    #new_instr = new Instruction()
    #
    #for argument in instruction
        #new_instr.add_arg(argument)


#sort instructions by order

#for instruction in instructions
    #instruction.execute()



    


# defvar.action()

set = {}
set["DEFVAR"] = defvar_action
set["DEFVAR"]


storage.create_variable("GF@ahoj")
storage.assign_variable("GF@ahoj",12,"int")

arg1 = Argument()
arg1.type = "int"
arg1.value = "10"
value1 = get_symbol_value(arg1)

arg2 = Argument()
arg2.type = "var"
arg2.value = "GF@ahoj"
value2 = get_symbol_value(arg2)

instr = Instruction("MOVE")
instr.args.append(arg2)
instr.args.append(arg2)
instr.args.append(arg2)
# move_action(instr)
add_action(instr)
 
# print("plus ="+str(value1+value2))

for var in storage.GF:
    print(var)