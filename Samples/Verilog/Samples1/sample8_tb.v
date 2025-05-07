module sample8_tb;
  reg A, B, C;
  wire F;
  
  sample8 uut (
    .F(F),
    .A(A),
    .B(B),
    .C(C)
  );
  
  initial begin
    {A, B, C} = 3'b000; #10;
    {A, B, C} = 3'b001; #10;
    {A, B, C} = 3'b010; #10;
    {A, B, C} = 3'b011; #10;
    {A, B, C} = 3'b100; #10;
    {A, B, C} = 3'b101; #10;
    {A, B, C} = 3'b110; #10;
    {A, B, C} = 3'b111; #10;
  end
  
  initial begin
    $monitor("Time=%0t | A=%b B=%b C=%b | F=%b", $time, A, B, C, F);
    $dumpfile("sample8_tb.vcd");
    $dumpvars(0, sample8_tb);
  end
endmodule