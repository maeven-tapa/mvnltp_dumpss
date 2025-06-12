// F = (A) | (~B & ~D) | (B & D) | (C);
module seg_a (output reg F, input wire A, B, C, D);
	always @(*) begin
		if (A || (~B && ~D) || (B && D) || C)
			F = 1'b1;
		else
			F = 1'b0;
	end
endmodule

// F = (A) | (~B) | (C & D) | (~C & ~D);
module seg_b (output reg F, input wire A, B, C, D);
	always @(*) begin
		if (A || ~B || (C && D) || (~C && ~D))
			F = 1'b1;
		else
			F = 1'b0;
	end
endmodule
	
// F = (B) | (D) | (~C & ~D);
module seg_c (output reg F, input wire A, B, C, D);
	always @(*) begin
	if (B || D || (~C && ~D))
		F = 1'b1;
	else
		F = 1'b0;
	end
endmodule

// F = (A) | (~B & ~D) | (~B && C) | (~B & C);
module seg_d (output reg F, input wire A, B, C, D);
	always @(*) begin
	if (A || (~B && ~D) || (~B && C) || (C & ~D) || (B && ~C && D))
		F = 1'b1;
	else
		F = 1'b0;
	end
endmodule

// F = (A) | (C & ~D);
module seg_e (output reg F, input wire A, B, C, D);
	always @(*) begin
	if ((~B && ~D) || (C && ~D))
		F = 1'b1;
	else
		F = 1'b0;
	end
endmodule

// F = (A) | (B & ~D) | (~C & ~D) | (B & ~C);
module seg_f (output reg F, input wire A, B, C, D);
	always @(*) begin
	if (A || (B && ~D) || (~C && ~D) || (B && ~C))
		F = 1'b1;
	else
		F = 1'b0;
	end
endmodule

// F = (A) | (C & ~D) | (~B & C) | (B & ~C);
module seg_g (output reg F, input wire A, B, C, D);
	always @(*) begin
	if (A || (C && ~D) || (~B && C) || (B && ~C))
		F = 1'b1;
	else
		F = 1'b0;
	end
endmodule
	
	

module seg_tb;
    reg A, B, C, D;
    wire Fa, Fb, Fc, Fd, Fe, Ff, Fg;
    seg_a uut_a (Fa, A, B, C, D);
    seg_b uut_b (Fb, A, B, C, D);
    seg_c uut_c (Fc, A, B, C, D);
    seg_d uut_d (Fd, A, B, C, D);
    seg_e uut_e (Fe, A, B, C, D);
    seg_f uut_f (Ff, A, B, C, D);
    seg_g uut_g (Fg, A, B, C, D);

    initial begin
        {A, B, C, D} = 4'b0000;
        repeat (9) begin
            #1 {A, B, C, D} = {A, B, C, D} + 1;
        end
        #1 $finish;
    end

    initial begin
        $monitor("Digit=%0t | A=%b B=%b C=%b D=%b | Fa=%b Fb=%b Fc=%b Fd=%b Fe=%b Ff=%b Fg=%b", 
                  $time, A, B, C, D, Fa, Fb, Fc, Fd, Fe, Ff, Fg);
        $dumpfile("problem1_sim.vcd");
        $dumpvars(0, seg_tb);
    end
endmodule