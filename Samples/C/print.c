#include <stdio.h>

int main() {
    printf("A char is %ld bytes\n", sizeof(char));
    printf("An int is %ld bytes\n", sizeof(int));
    printf("A float is %ld bytes\n", sizeof(float));
    printf("A double is %ld byte\n", sizeof(double));
    printf("A short int is %ld bytes\n", sizeof(short int));
    printf("A long int is %ld bytes\n", sizeof(long int));
    printf("A long long int %ld bytes\n", sizeof(long long int));
    printf("A long double is %ld bytes\n", sizeof(long double));
    return 0;
}