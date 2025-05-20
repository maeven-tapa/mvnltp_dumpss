module upcounter_4bit (output reg [3:0] count, input wire clk, input wire rst);
    always @(posedge clk or posedge rst) begin
        if (rst)
            count <= 4'b0000;
        else
            count <= count + 1;
    end
endmodule


module upcounter_4bit_tb;
    reg clk, rst;
    wire [3:0] count;

    upcounter_4bit uut (count, clk,rst);

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

    // initial begin
    //     clk = 0;
    //     rst = 1;
    //     #1 rst = 0;

    //     #1 clk = 1; #1 clk = 0;
    //     #1 clk = 1; #1 clk = 0;
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0;
    //     #1 clk = 1; #1 clk = 0;
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     #1 clk = 1; #1 clk = 0; 
    //     $finish;
    // end

    initial begin
        $monitor("Time=%0t | rst=%b clk=%b | count=%b", $time, rst, clk, count);
        $dumpfile("upcounter_4bit.vcd");
        $dumpvars(0, upcounter_4bit_tb);
    end
endmodule
