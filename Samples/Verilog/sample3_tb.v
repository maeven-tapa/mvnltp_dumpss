module sample3_tb;
reg A, B, C, D;
wire F;
sample3 uut(A, B, C, D, F);
initial begin
$dumpfile("sample3_tb.vcd");
$dumpvars(0, sample3_tb);
$display("A B C D F");
$monitor(A, " ", B, " ", C, " ", D, " ", F);
A = 0; B = 0; C = 0; D = 0;
#10 A = 0; B = 0; C = 0; D = 1;
#10 C = 1; D = 0;
#10 D = 1;
#10 B = 1; C = 0; D = 0;
#10 D = 1;
#10 C = 1; D = 0;
#10 D = 1;
#10 A = 1; B = 0; C = 0; D = 0;
#10 D = 1;
#10 C = 1; D = 0;
#10 D = 1;
#10 B = 1; C = 0; D = 0;
#10 D = 1;
#10 C = 1; D = 0;
#10 D = 1;
end
endmodule