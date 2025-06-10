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

    // Instantiate segment modules
    seg_a uut_a (Fa, A, B, C, D);
    seg_b uut_b (Fb, A, B, C, D);
    seg_c uut_c (Fc, A, B, C, D);
    seg_d uut_d (Fd, A, B, C, D);
    seg_e uut_e (Fe, A, B, C, D);
    seg_f uut_f (Ff, A, B, C, D);
    seg_g uut_g (Fg, A, B, C, D);

    initial begin
        $dumpfile("seg_tb.vcd");
        $dumpvars(0, seg_tb);

        // --- Segment Fa ---
        $display("Segment Fa:");
        $display("Time | A B C D | Fa");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fa);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        // --- Segment Fb ---
        $display("Segment Fb:");
        $display("Time | A B C D | Fb");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fb);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        // Repeat for Fc, Fd, Fe, Ff, Fg
        $display("Segment Fc:");
        $display("Time | A B C D | Fc");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fc);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        $display("Segment Fd:");
        $display("Time | A B C D | Fd");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fd);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        $display("Segment Fe:");
        $display("Time | A B C D | Fe");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fe);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        $display("Segment Ff:");
        $display("Time | A B C D | Ff");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Ff);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        $display("Segment Fg:");
        $display("Time | A B C D | Fg");
        {A, B, C, D} = 4'b0000;
        repeat (10) begin
            #1;
            $display("%4t | %b %b %b %b |  %b", $time, A, B, C, D, Fg);
            {A, B, C, D} = {A, B, C, D} + 1;
        end
        $display("");

        $finish;
    end
endmodule