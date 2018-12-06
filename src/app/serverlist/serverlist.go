package serverlist

import (
	"app/router"
	"net/http"

	"github.com/julienschmidt/httprouter"
)

func init() {
	//默认不提供首页访问
	//router.GET("/", Index)
	//提供列表以及Diffhash表
	router.ServeFiles("/serverinfo/*filepath", http.Dir("config/ServerInfo"))
	//提供资源热更新文件强制下载
	router.ServeFilesMustDownload("/download/*filepath", http.Dir("config/Res"))
}

func Index(w http.ResponseWriter, r *http.Request, _ httprouter.Params) {
	http.Error(w, "Forbidden", 403)
}
