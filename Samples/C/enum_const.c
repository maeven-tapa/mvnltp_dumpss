#include <stdio.h>

int main(){
    enum months {JAN = 1, FEB, MAR, APR, MAY, JUN, JUL, AUG, SEP,OCT, NOV, DEC};
    enum months the_months;
    the_months = FEB;

    if (the_months == 1) {
        printf("The months is January!\n");
    }
    else if (the_months == 2) {
        printf("The months is Febraury!\n");
    }
    else {
        printf("The months is not January andf February!\n");
    }
    return 0;
}