#MAKE_COM#  
ORG 100h
mov ax, 0b800h
mov ds, ax
mov cl, 'M'
mov ch, 01011111b
mov bx, 01Eh
mov [bx],cx

mov ax, 0b800h
mov ds, ax
mov cl, '0'
mov ch,01011111b
mov bx, 15Eh
mov [bx],cx

mov ax, 0b800h
mov ds, ax
mov cl, 'V'
mov ch, 01011111b
mov bx, 29Eh
mov [bx],cx

ret