# WebStore
REST API to store some data, like json, csv or txt.

# Docker commands
Helpful docker commands

## Dev environment
```
docker compose build
docker compose down
docker compose up -d
docker compose logs -f
```

# Usage example
```
curl -X PUT -d "key,value" "http://hostname:8080/mysecret?maxlines=2&headerlines=1"
curl -X PATCH -d "test,value1" "http://hostname:8080/mysecret?maxlines=2&headerlines=1"
curl -X PATCH -d "test,value2" "http://hostname:8080/mysecret?maxlines=2&headerlines=1"
curl -X PATCH -d "test,value3" "http://hostname:8080/mysecret?maxlines=2&headerlines=1"
```
The output of each of the above commands will be the same url: `http://hostname:8080/123...abc`
This is the URL to request the data
```
curl "http://hostname:8080/123...abc"
```
