#include <stdio.h>

int main(void) {
    int c = getchar();
    for (;;){
        putchar(c);
        c = getchar();
    }
    return 0;
}