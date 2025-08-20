#include <stdio.h>
#define UPPER 300
#define LOWER 0
#define STEP 20

int main(void) {
    
    int farh;

    for (farh = UPPER; farh >= LOWER; farh -= STEP)
    printf("%d\t%.2f\n", farh, (5.0 / 9.0) * (farh-32));
    return 0;
}