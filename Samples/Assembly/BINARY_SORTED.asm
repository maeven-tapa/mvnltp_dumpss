DATA SEGMENT
    ARRAY DB 20 DUP(?)
    LEN DB ?
    NUM DB ?
    BEG DB ?
    LAST DB ?
    TWO DB 2
    TEN DB 10
    msg1 DB 'Enter length of array: ','$'
    msg2 DB 10,'Enter element( ascending order ): ','$'
    msg3 DB 10,10,'Enter number to be searched: ','$'
    msg4 DB 10,10,'ELEMENT FOUND AT POSITION: ','$'
    msg5 DB 10,10,'ELEMENT NOT FOUND!','$'
DATA ENDS

CODE SEGMENT
ASSUME CS:CODE, DS:DATA

START:  MOV AX, DATA
        MOV DS, AX
        MOV ES, AX

        LEA DX, msg1
        CALL OUTPUT_MSG
        CALL READH

        CMP BL, 20
        JG EXIT
        MOV LEN, BL 
        XOR CX, CX
        MOV CL, LEN

        CLD
        MOV DI, OFFSET ARRAY

INPUT:  LEA DX, msg2
        CALL OUTPUT_MSG
        CALL READH
        MOV AL, BL
        STOSB
        LOOP INPUT
        
        LEA DX, msg3
        CALL OUTPUT_MSG
        CALL READH
        MOV NUM, BL

        CALL BSEARCH

EXIT: HLT
CODE ENDS

READH PROC NEAR
        XOR DX, DX
        XOR BX, BX
          
REPEAT: MOV AH, 01H
        INT 21H
        CMP AL, 13 
        JE RETURN
        
        AND AX, 0FH 
        MOV DL, AL
        XCHG AX, BX
        MUL TEN
        ADD BX, AX
        JMP REPEAT
        
RETURN: RET
READH ENDP

OUTPUT_MSG  PROC NEAR 
            MOV AH, 09H
            INT 21H
            RET
OUTPUT_MSG ENDP 


BSEARCH PROC NEAR 
        MOV BEG, 0 ; 
        MOV AL, LEN
        AND AX, 0FFH
        MOV LAST, AL
        DEC LAST

SEARCH: MOV AL, BEG
        CMP AL, LAST
        JG NOT_FOUND 
        ADD AL, LAST
        DIV TWO 
        AND AX, 0FFH
        MOV SI, AX
        MOV DL, NUM
        CMP DL, BYTE PTR ARRAY[SI]
        JL LEFT
        JG RIGHT
        JE FOUND
        
LEFT:   MOV LAST, AL 
        DEC LAST
        JMP SEARCH

RIGHT:  MOV BEG, AL 
        INC BEG
        JMP SEARCH

FOUND:  LEA DX,msg4 
        CALL OUTPUT_MSG
        MOV DX,SI
        ADD DL,30H
        MOV AH,2H
        INT 21H
        JMP RE

NOT_FOUND: LEA DX,msg5 
        CALL OUTPUT_MSG

RE: RET
BSEARCH ENDP

END START
