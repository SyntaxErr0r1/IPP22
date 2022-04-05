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

    def get_frame(self, frame):
        if frame == "GF":
            return self.GF
        if frame == "TF":
            return self.TF
        if frame == "LF":
            return self.LF

    def exists_variable(self,var_id):
        var_name = get_var_name(var_id)
        frame_name = get_var_frame(var_id)
        frame = self.get_frame(frame_name)
        
        for variable in frame:
            if variable.name == var_name:
                return 1
        return 0
        
    def create_variable(self, var_id):
        var_name = get_var_name(var_id)
        frame_name = get_var_frame(var_id)
        frame = self.get_frame(frame_name)

        if(frame == None):
            eprint("Error: frame does no exist!")
            return
        
        new_var = Variable()
        new_var.name = var_name

        frame.append(new_var)

    def assign_variable(self,var_id,value):
        var_name = get_var_name(var_id)
        frame_name = get_var_frame(var_id)
        frame = self.get_frame(frame_name)
        
        for variable in frame:
            if variable.name == var_name:
                variable.value = value
                return
        

    def get_var(frame,var_name):
        for variable in frame:
            if variable.name == var_name:
                return variable
        return None

class Variable:
    name = None
    type = None
    value = None
    def get_type(self):
        return "NONE" if (self.type == None) else self.type
    def get_value(self):
        return "NONE" if (self.value == None) else self.value
    def __str__(self):
        return "Variable: {name: "+self.name+",\t type:"+self.get_type()+",\t value:"+self.get_value()+"}"

storage = DataStorage()


def get_var_frame(var_id):
    var_split = var_id.split("@")
    frame_name = var_split[0]
    return frame_name

def get_var_name(var_id):
    var_split = var_id.split("@")
    var_name = var_split[1]
    return var_name


#instruction actions

def defvar_action(instruction):
    var_id = instruction.args[0].value
    storage.create_variable(var_id)

def move_action(instruction):
    var_id = instruction.args[0].value
    type = instruction.args[1].type
    value = instruction.args[1].value
    #can be either existing var (copy type and value)
    if(type == "var"):
        eprint("not implemented")
    #or value with type specified
    else:
        storage.assign_variable(var_id,value)

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
storage.assign_variable("GF@ahoj","ciao")

for var in storage.GF:
    print(var)