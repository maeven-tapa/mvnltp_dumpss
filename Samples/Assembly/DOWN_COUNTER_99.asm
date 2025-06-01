org 100h 


Loop3:  mov al, 39h  

Loop2:  mov dl, al 
        mov ah, 02h 
        int 21h 
        push ax
        mov bl, 039h 


Loop1:  mov dl,bl 
        mov ah, 02h 
        int 21h 
        
        sub bl, 01h 
        mov ah, 039h 
        int 21h 


        mov ah, 02h 
        mov dl, 01h 
        int 10h
        
        cmp bl, 030h 
        jnc Loop1 
        
        mov ah, 02h 
        mov dl, 00h 
        int 10h 
        
        
        pop ax
        sub al, 01h 
        cmp al, 30h 
        jnc loop2 
        jmp loop3  
        
        mov ah, 4ch
        int 21h
ret