//buffer
module buffer (output wire B, input wire A);
    assign B = (A == 1'b1) ? 1'b1 : 1'b0;
endmodule

//not gate
module not_gate (input wire A, output wire B);
    assign B = (A == 1'b0) ? 1'b1 : 1'b0;
endmodule

//and gate
module and_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b0:
				((A == 1'b0) && (B == 1'b1)) ? 1'b0:
				((A == 1'b1) && (B == 1'b0)) ? 1'b0:
				((A == 1'b1) && (B == 1'b1)) ? 1'b1:
				1'b0;
endmodule

//or gate
module or_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b0:
				((A == 1'b0) && (B == 1'b1)) ? 1'b1:
				((A == 1'b1) && (B == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B == 1'b1)) ? 1'b1:
				1'b0;
endmodule

//xor gate
module xor_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b0:
				((A == 1'b0) && (B == 1'b1)) ? 1'b1:
				((A == 1'b1) && (B == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B == 1'b1)) ? 1'b0:
				1'b0;
endmodule

//nand gate
module nand_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b1:
				((A == 1'b0) && (B == 1'b1)) ? 1'b1:
				((A == 1'b1) && (B == 1'b0)) ? 1'b1:
				((A == 1'b1) && (B == 1'b1)) ? 1'b0:
				1'b0;
endmodule

//nor gate
module nor_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b1:
				((A == 1'b0) && (B == 1'b1)) ? 1'b0:
				((A == 1'b1) && (B == 1'b0)) ? 1'b0:
				((A == 1'b1) && (B == 1'b1)) ? 1'b0:
				1'b0;
endmodule

//xnor gate
module xnor_gate (input wire A, B, output wire C);
	assign C = ((A == 1'b0) && (B == 1'b0)) ? 1'b1:
				((A == 1'b0) && (B == 1'b1)) ? 1'b0:
				((A == 1'b1) && (B == 1'b0)) ? 1'b0:
				((A == 1'b1) && (B == 1'b1)) ? 1'b1:
				1'b0;
endmodule

module logicgates_tb;
    reg A, B;
    wire C_and, C_or, C_xor, C_nand, C_nor, C_xnor;
    and_gate   u1(A, B, C_and);
    or_gate    u2(A, B, C_or);
    xor_gate   u3(A, B, C_xor);
    nand_gate  u4(A, B, C_nand);
    nor_gate   u5(A, B, C_nor);
    xnor_gate  u6(A, B, C_xnor);
    initial begin
		$dumpfile("logicgates.vcd");
		$dumpvars(0, logicgates_tb);
        $display("Time | A B | AND | OR | XOR | NAND | NOR | XNOR");
        $monitor("%4t  | %b %b |  %b |  %b |  %b |   %b  |  %b |   %b", 
                 $time, A, B, C_and, C_or, C_xor, C_nand, C_nor, C_xnor);
        {A, B} = 2'b00;
        repeat (4) begin
            #1 {A, B} = {A, B} + 1;
        end
    end
endmodule

//Test benches
// module and_gate_tb;
	// reg A, B;
	// wire C;
	// and_gate uut(A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("andgate.vcd");
		// $dumpvars(0, and_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule

// module or_gate_tb;
	// reg A, B;
	// wire C;
	// or_gate uut (A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("orgate.vcd");
		// $dumpvars(0, or_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule

// module xor_gate_tb;
	// reg A, B;
	// wire C;
	// xor_gate uut (A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("orgate.vcd");
		// $dumpvars(0, xor_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule

// module nand_gate_tb;
	// reg A, B;
	// wire C;
	// nand_gate uut (A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("orgate.vcd");
		// $dumpvars(0, nand_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule

// module nor_gate_tb;
	// reg A, B;
	// wire C;
	// nor_gate uut (A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("orgate.vcd");
		// $dumpvars(0, nor_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule

// module xnor_gate_tb;
	// reg A, B;
	// wire C;
	// xnor_gate uut (A, B, C);
	// initial begin
		// $monitor("Time=%0t | A=%b B=%b | C=%b", $time, A, B, C);
		// $dumpfile("orgate.vcd");
		// $dumpvars(0, xnor_gate_tb);
		// {A , B} = 2'b00;
		// repeat (3) begin
			// #1 {A , B} = {A , B} + 1;
		// end
	// end
// endmodule


