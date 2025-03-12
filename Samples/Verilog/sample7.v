module adder(output reg S, Cout, input A, B, Cin);
reg H, I, J;

always @(A or B or Cin) begin
    if ((A ^ B) == 1'b1) 
        H = 1'b1;
    else
        H = 1'b0;
    if ((A & B) == 1'b1)
        I = 1'b1;
    else
        I = 1'b0;
    if ((H & Cin) == 1'b1) 
        J = 1'b1;
    else
        J = 1'b0;
    if ((H ^ Cin) == 1'b1)
        S = 1'b1;
    else
        S = 1'b0;
    if ((I | J) == 1'b1)
        Cout = 1'b1;
    else
        Cout = 1'b0;
end
endmodule
