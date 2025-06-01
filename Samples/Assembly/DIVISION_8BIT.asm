data segment
    num1 db ?
    num2 db ?
    remainder db ?
    res db ?
    msg1 db 10,13,"Enter the dividend: $"
    msg2 db 10,13,"Enter the divisor: $"
    msg3 db 10,13,"Result of division is: $"
    msg4 db 10,13,"Remainder: $"
data ends

assume cs:cose, ds:data

code segment
start: mov ax, data
    mov ds, ax
    
    lea dx, msg1
    mov ah, 9h
    int 21h
    
    mov ah,1h
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
    xor ah, ah
    div num2
    
    mov res,al
    mov remainder, ah
    mov ah, 0  
    
    aaa
    
    add ah,30h
    add al,30h
    add remainder, 30h
    
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
    
    lea dx, msg4
    mov ah, 9h
    int 21h
    
    mov ah,2
    mov dl,remainder
    int 21h
    
    mov ah, 4ch
    int 21h

code ends
end start
