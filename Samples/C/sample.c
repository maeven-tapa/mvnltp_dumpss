#include <stdio.h>

int main(void) {
    char name;
    int age;

    printf("What's your name? ");
    scanf("%s", &name);
    printf("How old are you? ");
    scanf("%d", &age);
    printf("Your name is %s and you are %d years old.\n", &name, age);
    printf("In two years your are %d years old\n", age + 2);
    return 0;
}