module roleda1 (input wire A, B, C, D, E,  output wire F);
	assign F = (A + ~B + C) & (~A + D) & (B + ~C + ~E);
endmodule

module roleda1_tb; 
	reg A, B, C, D, E;
	wire F;
		roleda1 uut (A, B, C, D, E, F);
		initial begin
		{A, B, C, D, E} = 5'b00000;
		repeat (31) begin
			#1 {A, B, C, D} = {A, B, C, D} + 1;
		end
	end
	initial begin
		
		$monitor (" time=%0t A=%b B=%b C=%b D=%b E=%b F=%b", $time, A, B, C, D, E, F);
		
		$dumpfile ("roleda_1.vcd");
		
		$dumpvars (0, roleda1_tb);
		
	end
endmodule