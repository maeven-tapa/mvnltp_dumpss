#include <stdio.h>

int main() {
  int a = 0, b = 5;
  printf("a AND b evaluates to: %d\n", (a && b));
  printf("Logical NOT of a and b: %d, %d\n", !a, !b);
  printf("b=%d, !b=%d\n", b, !b);
  return 0;
}