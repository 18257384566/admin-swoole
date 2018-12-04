package main

import (
	_ "app/etcd"
	_ "app/manager"
	"app/router"
	_ "app/serverlist"
	"log"
	"net/http"
)

func main() {
	log.Fatal(http.ListenAndServe(":8008", router.Run()))
}
