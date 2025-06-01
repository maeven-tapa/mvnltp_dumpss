module down_counter_09 (input wire clk, input wire rst, output reg [3:0] count);
    always @(posedge clk or posedge rst) begin
        if (rst)
            count <= 4'b1001;      
        else
            count <= count - 1;     
    end
endmodule

module tb_09_counter;
    reg clk, rst;
    wire [3:0] count;

    down_counter_09 uut (clk, rst, count);

    initial begin;
        clk = 0;
        rst = 1;
        #1 rst = 0;
            repeat (10) begin
                #1 clk = 1;
                #1 clk = 0;
            end
        $finish;
    end

    initial begin
        $dumpfile("0-9_down_counter.vcd");
        $dumpvars(0, tb_09_counter);
    end

    always @(posedge clk) begin
        if (!rst)
            $display("Time=%0t | rst=%b clk=%b | count=%b", $time, rst, clk, count);
    end
endmodule
    