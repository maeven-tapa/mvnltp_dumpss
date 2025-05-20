module exam1 (input wire A, B, C, D, output wire F);
	assign F = (A & B | ~C & ~D) & (A & ~B | C & D) & (A & ~C | D & ~B);
endmodule
	
module exam1_tb;
	reg A, B, C, D;
	wire F;
		exam1 uut (A, B, C, D, F);
		initial begin
		{A, B, C, D}=  4'b0000;
		repeat (15) begin
			#1 {A, B, C, D} = {A, B, C, D} +1;
			end
		end
		initial begin
			
			$monitor ("time=%0t A=%b B=%b C=%b D=%b F=%b", $time, A, B, C, D, F);
			
			$dumpfile ("exam1.vcd");
			
			$dumpvars (0, exam1_tb);
		end
endmodule