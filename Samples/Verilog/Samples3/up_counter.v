module up_counter_4bit (input wire clk, input wire rst, output reg [3:0] count);
    always @(posedge clk or posedge rst) begin
        if (rst)
            count <= 4'b0000;    
        else
            count <= count + 1;  
    end
endmodule

module tb_up_counter_4bit;
    reg clk, rst;
    wire [3:0] count;

    up_counter_4bit uut (clk, rst, count);

    initial begin;
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
        $dumpfile("up_counter.vcd");
        $dumpvars(0, tb_up_counter_4bit);
    end
endmodule
    