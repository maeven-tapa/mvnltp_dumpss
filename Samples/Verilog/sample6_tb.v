module sample6_tb;
    reg A, B, C;
    wire F;
    sample_logic uut(F, A, B, C);
    initial begin
        A = 0; B = 0; C = 0;
        #1 A = 0; B = 0; C = 1;
        #1 A = 0; B = 1; C = 0;
        #1 A = 0; B = 1; C = 1;
        #1 A = 1; B = 0; C = 0;
        #1 A = 1; B = 0; C = 1;
        #1 A = 1; B = 1; C = 0;
        #1 A = 1; B = 1; C = 1;
    end
    initial begin
        $monitor("Time=%0t | A=%b B=%b C=%b | F=%b", $time, A, B, C, F);
        $dumpfile("sample6_tb.vcd");
        $dumpvars(0, sample6_tb);
    end
endmodule