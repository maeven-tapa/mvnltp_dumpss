module sample3_tb;
    reg A, B, C, D;  
    wire F;       
    sample3 uut(F, A, B, C, D);
    initial begin
        A = 0; B = 0; C = 0; D = 0;
        #1 A = 0; B = 0; C = 0; D = 1;
        #1 C = 1; D = 0;
        #1 D = 1;
        #1 B = 1; C = 0; D = 0;
        #1 D = 1;
        #1 C = 1; D = 0;
        #1 D = 1;
        #1 A = 1; B = 0; C = 0; D = 0;
        #1 D = 1;
        #1 C = 1; D = 0;
        #1 D = 1;
        #1 B = 1; C = 0; D = 0;
        #1 D = 1;
        #1 C = 1; D = 0;
        #1 D = 1;
    end
    initial begin
        $monitor("Time=%0t | A=%b B=%b C=%b D=%b | F=%b", $time, A, B, C, D, F);
        $dumpfile("sample3_tb.vcd");
        $dumpvars(0, sample3_tb);
    end

endmodule
