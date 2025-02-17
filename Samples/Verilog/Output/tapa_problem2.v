module problem2(X, AB, AC, A, B, C);
    input A, B, C;
    output X, AB, AC;
    assign AB = A & B;
    assign AC = A & C;
    assign X = AB | AC;
endmodule