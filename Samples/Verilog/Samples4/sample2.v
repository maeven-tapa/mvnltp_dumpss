module seg_a (output reg F, input wire A, B, C, D);
	always @(*) begin
		if (A || (~B && ~D) || (B && D) || C)
			F = 1'b1;
		else
			F = 1'b0;
	end
endmodule

module seg_a_tb;
    reg A, B, C, D;
    wire F;
    seg_a uut(F, A, B, C, D);
    initial begin
        {A, B, C, D} = 4'b0000;
        repeat (9) begin
            #1 {A, B, C, D} = {A, B, C, D} + 1;
        end
    end
    initial begin
        $monitor("Time=%0t | A=%b B=%b C=%b D=%b | F=%b", $time, A, B, C, D, F);
        $dumpfile("problem1_sim.vcd");
        $dumpvars(0, seg_a_tb);
    end
endmodule