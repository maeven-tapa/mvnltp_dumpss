.model stack
.stack 100h
.data

CR EQU 0DH
LF EQU 0AH

msg1 db ' Enter a Lower Case Letter $'
msg2 db 0DH, 0AH, 'Upper Case Result: '
msg3 db ?, '$'

.code

    main proc
        mov ax, @data
        mov ds, ax
        
        lea dx, msg1
        mov ah, 9
        int 21h
        
        mov ah, 1
        int 21h
        sub al,20h
        mov msg3,al
        
        lea dx, msg2
        mov ah, 9
        int 21h
        
        mov ah,4ch
        int 21h
    
    main endp
end main