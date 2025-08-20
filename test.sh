#!/bin/bash

url=$(curl -s -X PUT -d "key,value" "http://localhost:8080/test?maxlines=2&headerlines=1")
url=$(curl -s -X PATCH -d "test,value1" "http://localhost:8080/test?maxlines=2&headerlines=1")
url=$(curl -s -X PATCH -d "test,value2" "http://localhost:8080/test?maxlines=2&headerlines=1")
url=$(curl -s -X PATCH -d "test,value3" "http://localhost:8080/test?maxlines=2&headerlines=1")

output=$(curl -s -X GET "$url")

# Variable with multiline string
compare="key,value
test,value2
test,value3"

# Compare variable against output
if [ "$output" == "$compare" ]; then
    echo "OK"
else
    echo "Output does not match expected values."
    echo "Expected:"
    echo "$compare"
    echo "Got:"
    echo "$output"
fi
