module sample3 (output reg F, input A, B, C, D);
always @(A or B or C or D) begin
    if ((A ~| B) == 1'b1 && (~C) == 1'b1 && (C | D) == 1'b1) begin
        F = 1'b1;
    end
    else begin
        F = 1'b0;
    end
end
endmodule