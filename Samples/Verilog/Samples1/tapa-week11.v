module week11_circuit (output wire F, input wire A, B, C);
    wire An, Bn, Cn;
    wire m0, m1, m2, m3;
    assign An = ~A;
    assign Bn = ~B;
    assign Cn = ~C;
    assign m0 = A  & Bn & Cn; 
    assign m1 = A  & Bn &  C;  
    assign m2 = An & Bn &  C; 
    assign m3 = An & Bn & Cn;
    assign F = m0 | m1 | m2 | m3;
endmodule

module week11_circuit (output wire F, input wire A, B, C);
assign F = ((A == 1'b0) && (B == 1'b0) && (C == 1'b0)) ? 1'b1 : 
           ((A == 1'b0) && (B == 1'b0) && (C == 1'b1)) ? 1'b1 : 
           ((A == 1'b0) && (B == 1'b1) && (C == 1'b0)) ? 1'b0 :
           ((A == 1'b0) && (B == 1'b1) && (C == 1'b1)) ? 1'b0 : 
           ((A == 1'b1) && (B == 1'b0) && (C == 1'b0)) ? 1'b1 : 
           ((A == 1'b1) && (B == 1'b0) && (C == 1'b1)) ? 1'b1 : 
           ((A == 1'b1) && (B == 1'b1) && (C == 1'b0)) ? 1'b0 : 
           ((A == 1'b1) && (B == 1'b1) && (C == 1'b1)) ? 1'b0 : 
           1'b0; 
endmodule

module week11_circuit (output wire F, input wire A, B, C);
assign F = ((A == 1'b0) && (B == 1'b0) && (C == 1'b0)) ? 1'b1 : 
           ((A == 1'b0) && (B == 1'b0) && (C == 1'b1)) ? 1'b1 : 
           ((A == 1'b1) && (B == 1'b0) && (C == 1'b0)) ? 1'b1 : 
           ((A == 1'b1) && (B == 1'b0) && (C == 1'b1)) ? 1'b1 : 
           1'b0; 
endmodule

