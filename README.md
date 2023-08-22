# IPP22
Project for IPP22 @ VUT FIT

## Introduction
This project contains an interpreter for the specified assembly language (IPPcode22).
The interpreter is composed of 2 (3 with tester) main modules:
 - parse.php
 - interpret.py
 - test.php

The programming languages used for the modules are mandatory.

## Parse.php 
- this file parses the input assembly file and outputs it in XML representation
- [documentation (slovak)](readme1.md)

## Interpret.py 
- this script executes the instructions based on the input XML file
- [documentation (slovak)](readme2.md)

## Test.php
- Script for evaluating the system (parser and interpreter) using test samples
- The test samples consist of:
  - input assembly file
  - input stdin file
  - expected stdout
  - expected return code
