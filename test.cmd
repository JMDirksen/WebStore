@echo off

curl -s -X PUT -d "key,value" "http://localhost:8080/<mysecret>?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "test,value1" "http://localhost:8080/<mysecret>?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "test,value2" "http://localhost:8080/<mysecret>?maxlines=2&headerlines=1">url.tmp
curl -s -X PATCH -d "test,value3" "http://localhost:8080/<mysecret>?maxlines=2&headerlines=1">url.tmp

set /p url=<url.tmp

curl -s "%url%">output.tmp

echo ======= This =======
type output.tmp
echo ====================
echo.
echo === Should equal ===
echo key,value
echo test,value2
echo test,value3
echo ====================

del url.tmp
del output.tmp
