module doro (input wire A, B, C, D, output wire F);
	assign F = (A & ~B | ~C & D);
endmodule

module doro_tb;
	reg A, B, C, D;
	wire F;
		doro uut (A, B, C, D, F);
		initial begin
		{A, B, C, D} = 4'b0000;
		repeat (15) begin
			#1 {A, B, C, D} = {A, B, C, D} +1;
		end
	end
	initial begin
	
	$monitor ("time=%0t A=%b B=%b C=%b D=%b F=%b", $time, A, B, C, D, F);
	$dumpfile ("doro.vcd");
	$dumpvars (0, doro_tb);
	end
endmodule	

		