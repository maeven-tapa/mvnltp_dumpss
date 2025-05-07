module sample_logic (output reg F, input A, B, C);
reg H, I, J;

always @(A or B or C) begin
    if ((~A & ~C) == 1'b1)
        H = 1'b1;
    else
        H = 1'b0;
    if ((A & ~H & ~B) == 1'b1)
        I = 1'b1;
    else
        I = 1'b0;
    if ((A & B & C) == 1'b1)
        J = 1'b1;
    else
        J = 1'b0;
    if ((I | J) == 1'b1)
        F = 1'b1;
    else
        F = 1'b0;
end
endmodule
