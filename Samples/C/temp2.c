#include <stdio.h>

int main(void){
    int upper = 300;
    int lower = 0;
    int step = 20;

    int celsius = lower;

    while (celsius <= upper) {
        float farh = celsius * (5.0 / 9.0) + 32;
        printf("C=%d\tF=%.1f\n", celsius, farh);
        celsius = celsius + step;
    }
    return 0;
}