#include <stdio.h>

int main(){
    int a = 100, b = 2, c = 25, d =4;
    int result1, result2, result3;
    result1 = a * b * c * d;
    result2 = (a * b) + (c * d);
    result3 = a * (b + c) * d;
    printf ("result1 = %d, result2 = %d, result3 =  %d", 
    result1, result2, result3);
    return 0;
}