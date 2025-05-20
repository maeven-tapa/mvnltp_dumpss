<<<<<<< HEAD
module up_counter_4bit (input wire clk, input wire  rst, output reg [3:0] count);
    always @(posedge clk) begin
        if (rst)
            count <= 4'b0000;    
        else
            count <= count + 1;  
    end
endmodule


module tb_up_counter_4bit;
    reg clk;
    reg rst;
    wire [3:0] count;

    up_counter_4bit dut (.clk(clk), .rst(rst), .count(count));

    initial clk = 0;
    always #5 clk = ~clk;

    initial begin
        $display("clk\trst\tcount");
        $monitor("%b\t%b\t%b", clk, rst, count);
        $dumpfile("up_counter.vcd");
        $dumpvars(0, tb_up_counter_4bit);
        rst = 1;
        #10;
        rst = 0;
        #150;
        $finish;
    end

endmodule
=======
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
>>>>>>> 55f2d18e9613380f9fb9ab8729cbe5ea5010167b
