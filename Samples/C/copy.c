#include <stdio.h>

int main(void) {
    int c, line_char;
    line_char = 0;

    while ((c = getchar()) != EOF) {
        if (c != ' ' || line_char != ' ') {
            putchar(c);
            line_char = c;
        }
    }
    return 0;
}