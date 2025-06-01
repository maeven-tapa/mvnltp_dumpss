data segment
        num1 db ?
        num2 db ?
        res db ?
        msg1 db 10,13,"First number: $"
        msg2 db 10,13,"Second number: $"
        msg3 db 10,13,"Product: $"
assume cs:code, ds: data
code segment
    start:  mov ax, data
            mov ds, ax
            
            lea dx, msg1
            mov ah, 9h
            int 21h
            
            mov ah, 1h
            int 21h
            sub al,30h
            mov num1,al
            
            lea dx, msg2
            mov ah, 9h
            int 21h
            
            mov ah, 1h
            int 21h
            sub al,30h
            mov num2,al
            
            mov al,num1
            mul num2
            mov ah,0
            aaa
            add al,30h
            
            mov bx, ax
            lea dx, msg3
            mov ah, 9h
            int 21h
            
            mov ah, 2h
            mov dl,bh
            int 21h
            
            mov ah,2
            mov dl,bl
            int 21h
            
            mov ah, 4ch
            int 21h

code ends
end start