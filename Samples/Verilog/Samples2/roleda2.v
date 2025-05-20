module roleda_2 (input wire A, B, C, D, E,  output wire F);
	assign F = ((A == 1'b1) && (B== 1'b0) && (C== 1'b1) && (D == 1'b1) && (E == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B== 1'b1) && (C== 1'b0) && (D == 1'b1) && (E == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B== 1'b0) && (C== 1'b1) && (D == 1'b1) && (E == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B== 1'b1) && (C== 1'b0) && (D == 1'b1) && (E == 1'b0)) ? 1'b1:
				1'b0;
endmodule
	
module roleda1_tb; 
	reg A, B, C, D, E;
	wire F;
		roleda_2 uut (A, B, C, D, E, F);
		initial begin
		{A, B, C, D, E} = 5'b00000;
		repeat (31) begin
			#1 {A, B, C, D} = {A, B, C, D} + 1;
		end
	end
	initial begin
		
		$monitor (" time=%0t A=%b B=%b C=%b D=%b E=%b F=%b", $time, A, B, C, D, E, F);
		
		$dumpfile ("roleda_2.vcd");
		
		$dumpvars (0, roleda1_tb);
		
	end
endmodule