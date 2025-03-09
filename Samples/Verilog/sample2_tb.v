module sample2_tb;
    reg A, B, C;
    wire F;
    sample2 dut(A, B, C, F);
    initial begin
        $dumpfile("sample2.vcd");
        $dumpvars(0, sample2_tb);
        $display("A B C F");
        $monitor(A, " ", B, " ", C, " ", F);
        A = 0; B = 0; C = 0;
        #10 C = 1;
        #10 B = 1; C = 0;
        #10 C = 1;
        #10 A = 1; B = 0; C = 0;
        #10 C = 1;
        #10 B = 1; C = 0;
        #10 C = 1;
    end
endmodule