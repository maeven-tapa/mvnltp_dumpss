// F = (A + B + C')' (D + D' + B')

module problem2_design (output wire F, input wire A, B, C, D);
    assign F = ~(A | B | ~C) & (D | ~D | ~B);
endmodule

module problem2_design_optimized (output wire F, input wire A, B, C, D);
    assign F = ((A == 1'b0) && (B == 1'b0) && (C == 1'b1) && (D == 1'b0)) ? 1'b1:
                ((A == 1'b0) && (B == 1'b0) && (C == 1'b1) && (D == 1'b1)) ? 1'b1:  
                1'b0;
endmodule


module problem2_tb;
    reg A, B, C, D;
    wire F;
    problem2_design uut(F, A, B, C, D);
    initial begin
        $dumpfile("problem2.vcd");
        $dumpvars(0, problem2_tb);
        $monitor("%t | A=%b B=%b C=%b D=%b |F=%b", $time, A, B, C, D, F);
        {A, B, C, D} = 4'b0000;
        repeat (15) begin
            #1 {A, B, C, D} = {A, B, C, D} + 1;
        end
    end
endmodule