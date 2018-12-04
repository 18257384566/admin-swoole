#!/bin/bash
MANAGER_CONFIG=$HOME/project/warShip/serverConfig/managerConfig

echo  -e "\nStart compile and rebuild..."
CGO_ENABLED=0 GOPATH=$(pwd) GOOS=linux GOARCH=amd64 go build app
docker rmi gm
docker build --no-cache --rm=true -t gm .
rm app
echo -e "\ncomplete"

docker stop gm
docker rm gm
docker run -d --name gm -p 8008:8008 --link=elk:elk  -e ELK_HOST=http://elk:9200 --link=etcd:etcd -e ETCD_HOST=http://etcd:2379 -v ${MANAGER_CONFIG}:/go/config -v ${MANAGER_CONFIG}:/go/syncConfig -e PLATFORM=swarm://tmp/docker.sock#v1.29 gm
