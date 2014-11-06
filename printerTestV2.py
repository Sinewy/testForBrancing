#! /usr/bin/python
import subprocess32
lpr =  subprocess32.Popen("/usr/bin/lpr", stdin=subprocess32.PIPE)
lpr.stdin.write("Tester")