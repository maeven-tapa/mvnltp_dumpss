module problem1_design (output wire F, input wire A, B, C, D);
    assign F = (A & ~B) | (~C & D);
endmodule

module problem1_tb;
    reg A, B, C, D;
    wire F;
    problem1_design uut(F, A, B, C, D);
    initial begin
        {A, B, C, D} = 4'b0000;
        repeat (15) begin
            #1 {A, B, C, D} = {A, B, C, D} + 1;
        end
    end
    initial begin
        $monitor("Time=%0t | A=%b B=%b C=%b D=%b | F=%b", $time, A, B, C, D, F);
        $dumpfile("problem1_sim.vcd");
        $dumpvars(0, problem1_tb);
    end
endmodule