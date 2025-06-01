.MODEL SMALL
.STACK 100H
.DATA
MSG1 DB 10, 13,'Enter the first binary number ( max 8-digits > : $'
MSG2 DB 10, 13,'Enter the second binary number < max 8-digits > : $'
MSG3 DB 10, 13,'The SUM of given binary numbers in binary form is : $'
ILLEGAL DB 10, 13,'Illegal character. Try again.$'

.CODE
    MAIN PROC
    MOV AX, @DATA
    MOV DS, AX
    
    @START_2:
        XOR BX, BX
        
        LEA DX, MSG1
        MOV AH, 9
        INT 21H
        
        MOV CX, 8
        MOV AH, 1
        
        @LOOP_1:
            
            INT 21H
            
            CMP AL, 0DH
            JNE @SKIP_1
            JMP @EXIT_LOOP_1
            
            @SKIP_1:
                AND AL, 0FH
                SHL BL, 1
                OR BL, AL
        LOOP @LOOP_1
        
        @EXIT_LOOP_1:
        LEA DX, MSG2
        MOV AH, 9
        INT 21H
        
        MOV CX, 8
        MOV AH, 1
        @LOOP_2:
            INT 21H
            
            CMP AL, 0DH
            JNE @SKIP_2
            JMP @EXIT_LOOP_2
    
            @SKIP_2:
                AND AL, 0FH
                SHL BH, 1
                OR BH, AL
        LOOP @LOOP_2
        
        @EXIT_LOOP_2:
        LEA DX, MSG3
        MOV AH, 9
        INT 21H
        
        ADD BL, BH
        JNC @SKIP
            MOV AH, 2
            MOV CL, 31H
            INT 21H
        @SKIP:
        MOV CX, 8
        MOV AH, 2
        
        @LOOP_3:
            SHL BL, 1
            JC @ONE
            MOV DL, 30H
            JMP @DISPLAY
            
            @ONE:
                MOV DL, 31H
            @DISPLAY:
                INT 21H
            LOOP @LOOP_3
        MOV AH, 4CH
        INT 21H
    MAIN ENDP
END MAIN