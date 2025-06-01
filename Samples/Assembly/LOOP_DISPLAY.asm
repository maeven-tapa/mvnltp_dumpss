.MODEL SMALL
.STACK 100H

.DATA
    count DW 10
    message DB 10,13, "Welcome to the fantasy world!$"
.CODE
    MAIN PROC
        MOV AX, @DATA
        MOV DS, AX
        
        MOV CX, count
     L1:
        MOV AH, 09H
        LEA DX, message
        INT 21H
        
        LOOP L1
        
        MOV AH, 4CH
        INT 21H
    MAIN ENDP

END MAIN
