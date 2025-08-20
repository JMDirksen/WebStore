@echo off

curl -s -X PUT -d "key,value" "http://localhost:8080/test?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "A,value1" "http://localhost:8080/test?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "B,value2" "http://localhost:8080/test?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "C,value3" "http://localhost:8080/test?maxlines=2&headerlines=1">url.tmp
curl -s -d "D,value4" "http://localhost:8080/test/">url.tmp

set /p url=<url.tmp

curl -s "%url%">output.tmp

echo key,value>expected.tmp
echo B,value2>>expected.tmp
echo C,value3>>expected.tmp
echo D,value4>>expected.tmp

fc output.tmp expected.tmp > nul
if %errorlevel% neq 0 (
    echo Output does not match expected values.
    echo.
    echo Expected:
    type expected.tmp
    echo.
    echo Got:
    type output.tmp
) else (
    echo OK
)

del url.tmp
del output.tmp
del expected.tmp
