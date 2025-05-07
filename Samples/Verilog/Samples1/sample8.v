module sample8(output reg F, input wire A, B, C);

always @(*) begin
    if ((A == 1'b0) && (B == 1'b0) && (C == 1'b0))
        F = 1'b1;
    else if ((A == 1'b0) && (B == 1'b1) && (C == 1'b0))
        F = 1'b1;
    else if ((A == 1'b1) && (B == 1'b1) && (C == 1'b0))
        F = 1'b1;
    else
        F = 1'b0;
end

endmodule
