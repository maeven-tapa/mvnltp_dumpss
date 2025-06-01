data segment
    num1 db ?
    num2 db ? 
    res db ? 
    rem db ? 
    quo db ? 
    
    msg1 db 10,13,"enter first number: $" 
    msg2 db 10,13,"enter second number: $" 
    msg3 db 10,13,"addition: $" 
    msg4 db 10,13,"subtraction: $" 
    msg5 db 10,13,"multiplication: $" 
    msg6 db 10,13,"division: $" 
    msg7 db 10,13,"remainder: $" 
data ends

assume cs:code, ds:data 

code segment
start:
    mov ax, data 
    mov ds, ax  
    
    lea dx, msg1 
    mov ah, 9h 
    int 21h 
    
    mov ah, 1h 
    int 21h 
    sub al, 30h 
    mov num1, al 
    
    lea dx, msg2 
    mov ah, 9h 
    int 21h 
    
    mov ah, 1h 
    int 21h 
    sub al, 30h 
    mov num2, al 
    
    ; ADDITION
    add al, num1 
    mov res, al 
    mov ah, 0 
    aaa 
    add ah, 30h 
    add al, 30h 
    
    mov bx, ax 
    lea dx, msg3 
    mov ah, 9h
    int 21h 
    
    mov ah, 2h 
    
    mov dl, bh 
    int 21h 
    
    mov ah, 2h 
    mov dl, bl 
    int 21h 

    ; SUBTRACTION
    mov al, num1 
    sub al, num2 
    mov res, al 
    mov ah, 0
    aas 
    add al, 30h 
    
    mov bx, ax 
    lea dx, msg4 
    mov ah, 9h 
    int 21h
    
    mov ah, 2h 
    mov dl, bh 
    int 21h
    
    mov ah, 2h 
    mov dl, bl 
    int 21h 
    
    ; MULTIPLICATION
    mov al, num1 
    mul num2 
    mov res, al
    mov ah, 0 
    aam 
    add al, 30h 
    add ah, 30h 
    
    mov bx, ax 
    lea dx, msg5 
    mov ah, 9h 
    int 21h 
    
    mov ah, 2h 
    mov dl, bh 
    int 21h 
    
    mov ah, 2h 
    mov dl, bl 
    
    int 21h 
    
    ; DIVISION
    mov al, num1 
    xor ah, ah 
    div num2 
    mov quo, al 
    mov rem, ah 
    aaa 
    add quo, 30h
    add rem, 30h 
    
    mov bx, ax 
    lea dx,msg6
    mov ah,9h
    int 21h
    
    mov ah,2h 
    mov dl,quo 
    int 21h 
    lea dx,msg7
    mov ah,9h
    int 21h
    
    mov ah,2h
    mov dl,rem
    
    int 21h
    
    mov ah,4ch
    int 21h

code ends
end start

