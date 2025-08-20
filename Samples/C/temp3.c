#include <stdio.h>

int main(void) {
    int farh;

    for (farh = 0;  farh <= 300; farh = farh + 20)
    printf("%d\t%.2f\n", farh, (5.0 / 9.0) * (farh-32));
    return 0;

}