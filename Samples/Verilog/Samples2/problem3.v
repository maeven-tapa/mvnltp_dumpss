module problem3 (input wire A, B, C, D, E, output wire F);
	assign F = ((A || !B || C) && (!A || D) && (B || !C || !E)) ? 1'b1 : 1'b0;
endmodule

// module problem3 (input wire A, B, C, D, E,output reg F);
    // always @(*) begin
        // if ((A || !B || C) && (!A || D) && (B || !C || !E))
            // F = 1'b1;
        // else
            // F = 1'b0;
    // end
// endmodule

module problem3_tb;
reg A, B, C, D, E;
wire F;
problem3 uut(A, B, C, D, E, F);
initial begin
$dumpfile("problem3.vcd");
$dumpvars(0, problem3_tb);
$monitor("Time=%0t | A=%b B=%b C=%b D=%b E=%b |  F=%b", $time, A, B, C, D, E, F);
{A, B, C, D, E} = 4'b0000;
repeat (31) begin
#1 {A, B, C, D, E} = {A, B, C, D, E} + 1;
end
end
endmodule

