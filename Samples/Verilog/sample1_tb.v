`timescale 1ns / 1ns
`include "sample1.v"

module sample1_tb;

reg A;
wire B;
sample1 uut(A,B);

initial begin   
    $dumpfile("sample1_tb.vcd");
    $dumpvars(0, sample1_tb);

    A = 0;
    #20;

    A = 1;
    #20;

    A = 0;
    #20;

    $display("My 1st simulation");
end

endmodule