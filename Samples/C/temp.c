#include <stdio.h>

int main(void) {
    int lower = 0;
    int upper = 300;
    int step = 20;

    int fahr = lower;
    while (fahr <= upper) {
        float celsius = (5.0 / 9.0) * (fahr-32);
        printf("C=%6.1f\tF=%d\n", celsius, fahr);
        fahr = fahr + step;
    }
    return 0;
}