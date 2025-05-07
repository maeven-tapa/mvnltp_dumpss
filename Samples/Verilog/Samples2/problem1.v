// Data Flow
module problem1_design (output wire F, input wire A, B, C, D);
    wire Bn, Cn, m0, m1;
    assign Bn = ~B;
    assign Cn = ~C;
    assign m0 = A & Bn;
    assign m1 = Cn & D;
    assign F = m0 | m1;
endmodule

// Optimized Method
module problem1_design_simple (output wire F, input wire A, B, C, D);
    assign F = ((A == 1'b0) && (B == 1'b0) && (C == 1'b0) && (D == 1'b1)) ? 1'b1:
                ((A == 1'b0) && (B == 1'b1) && (C == 1'b0) && (D == 1'b1)) ? 1'b1:
                ((A == 1'b1) && (B == 1'b0) && (C == 1'b0) && (D == 1'b0)) ? 1'b1:
                ((A == 1'b1) && (B == 1'b0) && (C == 1'b0) && (D == 1'b1)) ? 1'b1:
                ((A == 1'b1) && (B == 1'b0) && (C == 1'b1) && (D == 1'b0)) ? 1'b1:
                ((A == 1'b1) && (B == 1'b0) && (C == 1'b1) && (D == 1'b1)) ? 1'b1:
                ((A == 1'b1) && (B == 1'b1) && (C == 1'b0) && (D == 1'b1)) ? 1'b1:
                1'b0;
endmodule


// test bench
module problem1_tb;
reg A, B, C, D;
wire F;
problem1_design uut(F, A, B, C, D);
initial begin
    A = 0; B = 0; C = 0; D = 0;
    #1 D = 1;
    #1 C = 1; D = 0;
    #1 C = 1; D = 1;
    #1 B = 1; C = 0; D = 0;
    #1 B = 1; C = 0; D = 1;
    #1 B = 1; C = 1; D = 0;
    #1 B = 1; C = 1; D = 1;
    #1 A = 1; B = 0; C = 0; D = 0;
    #1 A = 1; B = 0; C = 0; D = 1;
    #1 A = 1; B = 0; C = 1; D = 0;
    #1 A = 1; B = 0; C = 1; D = 1;
    #1 A = 1; B = 1; C = 0; D = 0;
    #1 A = 1; B = 1; C = 1; D = 0;
    #1 A = 1; B = 1; C = 1; D = 1;
end
initial begin
    $monitor("Time=%0t | A=%b B=%b C=%b D=%b | Bn=%b Cn=%b | m0=%b m1=%b | F=%b", $time, A, B, C, D, uut.Bn, uut.Cn, uut.m0, uut.m1, F);
    $dumpfile("problem1.vcd");
    $dumpvars(0, problem1_tb);
end
endmodule
