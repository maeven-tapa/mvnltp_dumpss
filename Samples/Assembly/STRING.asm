.model smalI 

.stack                                                     s

.data 
message db "BET-COET$"   

.code 

main proc
    mov ax, seg Message
    
    mov ds, ax
    
    mov dx,offset Message
    
    mov ah,9
    
    int 21h
    
    mov ax,4c00h 
    
    int 21h

main endp

end main