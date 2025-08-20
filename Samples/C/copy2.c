#include <stdio.h>

int main(void) {
    int c, line_char;
    line_char = 0;

    while ((c = getchar()) != EOF) {
        if (c == '\t') {
            putchar('\\');
            putchar('t');
        }
        else if (c == '\b') {
            putchar('\\');
            putchar('b');
        }
        else {
            putchar(c);
        }
    }
    return 0;
}