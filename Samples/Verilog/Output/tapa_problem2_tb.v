module problem2_tb;
    reg A, B, C;
    wire X, AB, AC;
    problem2 dut(X, AB, AC, A, B, C);
    initial begin
        $dumpfile("simulation.vcd");
        $dumpvars(0, problem2_tb);
        $display("A B C AB AC X");
        $monitor(A, " ", B, " ", C, " ", AB, " ", AC, " ", X);
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