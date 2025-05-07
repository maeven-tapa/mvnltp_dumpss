module sample3(A, B, C, D, F);
input A, B, C, D;
output F;
wire G, H, I;
assign G = A ~| B;
assign H = ~C;
assign I = C | D;
assign F = G & H & I;
endmodule
