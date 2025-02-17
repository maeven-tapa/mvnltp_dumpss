module problem1(X, A, B, C);
    input A, B, C;
    output X;
    wire or_gate;
    assign or_gate = B & C;
    assign X = or_gate & A;
endmodule