<<<<<<< HEAD
module downcounter_4bit (output reg [3:0] count, input wire clk, input wire rst);
    always @(posedge clk or posedge rst) begin
        if (rst)
            count <= 4'b1111;
        else
            count <= count - 1;
    end
endmodule


module downcounter_4bit_tb;
    reg clk, rst;
    wire [3:0] count;

    downcounter_4bit uut (count, clk, rst);

    initial begin
        clk = 0;
        rst = 1;
        #1 rst = 0;
        repeat (15) begin
            #1 clk = 1;
            #1 clk = 0;
        end
        $finish;
    end

    initial begin
        $monitor("Time=%0t | rst=%b clk=%b | count=%b", $time, rst, clk, count);
        $dumpfile("downcounter_4bit.vcd");
        $dumpvars(0, downcounter_4bit_tb);
    end
endmodule
=======
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

>>>>>>> 55f2d18e9613380f9fb9ab8729cbe5ea5010167b
