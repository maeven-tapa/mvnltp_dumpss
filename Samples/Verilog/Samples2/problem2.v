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
