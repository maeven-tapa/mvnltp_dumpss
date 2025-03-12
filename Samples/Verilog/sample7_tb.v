module sample7_tb;
    reg A, B, Cin;
    wire S, Cout;
    adder uut(S, Cout, A, B, Cin);
    initial begin
        A = 0; B = 0; Cin = 0;
        #1 B = 1;
        #1 A = 1; B = 0;
        #1 B = 1;
        #1 A = 0; B = 0; Cin = 1;
        #1 B = 1;
        #1 A = 1; B = 0; 
        #1 B = 1;
    end
    initial begin
        $monitor("Time=%0t | A=%b B=%b Cin=%b | S=%b Cout=%b", $time, A, B, Cin, S, Cout);
        $dumpfile("sample7_tb.vcd");
        $dumpvars(0, sample7_tb);
    end
endmodule