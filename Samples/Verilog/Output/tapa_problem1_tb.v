module problem1_tb;
    reg A, B, C;
    wire X;
    problem1 dut(X, A, B, C);
    initial begin
        $dumpfile("dump.vcd");
        $dumpvars(0, problem1_tb); 
        $display("A B C X");
        $monitor(A, " ", B, " ", C, " ", X);
        A = 0; B = 0; C = 0;
        #5 C = 1;
        #5 B = 1; C = 0;
        #5 C = 1;
        #5 A = 1; B = 0; C = 0;
        #5 C = 1;
        #5 B = 1; C = 0;
        #5 C = 1;
    end
endmodule