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
