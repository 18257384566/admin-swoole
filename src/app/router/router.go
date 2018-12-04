package router

import (
	"net/http"

	"github.com/julienschmidt/httprouter"
)

var router *httprouter.Router

func init() {
	router = httprouter.New()
}

func GET(path string, handle httprouter.Handle) {
	router.GET(path, handle)
}

func POST(path string, handle httprouter.Handle) {
	router.POST(path, handle)
}

func ServeFiles(path string, root http.FileSystem) {
	router.ServeFiles(path, root)
}

func ServeFilesMustDownload(path string, root http.FileSystem) {
	if len(path) < 10 || path[len(path)-10:] != "/*filepath" {
		panic("path must end with /*filepath in path '" + path + "'")
	}

	fileServer := http.FileServer(root)

	router.GET(path, func(w http.ResponseWriter, req *http.Request, ps httprouter.Params) {
		w.Header().Set("Content-Type", "application/octet-stream")
		req.URL.Path = ps.ByName("filepath")
		fileServer.ServeHTTP(w, req)
	})
}

func Run() *httprouter.Router {
	return router
}
