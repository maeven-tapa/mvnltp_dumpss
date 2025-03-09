module logic_circuit (output reg F, input A, B, C);
reg H, I, J, K;  

always @(A or B or C) begin
    if ((~A & ~B & ~C) == 1'b1) 
        H = 1'b1;
    else if ((~A & B & ~C) == 1'b1) 
        I = 1'b1;
    else if ((~A & ~B & C) == 1'b1) 
        J = 1'b1;
    else if ((~A & B & C) == 1'b1) 
        K = 1'b1;
    else begin
        H = 1'b0;
        I = 1'b0;
        J = 1'b0;
        K = 1'b0;
    end
    if ((H | I | J | K) == 1'b1) 
        F = 1'b1;
    else 
        F = 1'b0;
end
endmodule
