package manager

import (
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
)

type ErrorCode struct {
	ErrorC    uint8  `json:"errno"`
	ErrorDesc string `json:"error"`
}

//func (this *ErrorCode) MarshalJSON() ([]byte, error) {
//return []byte("{\"error\": \"errno:" + strconv.FormatUint(uint64(this.ErrorC), 10) + " error:" + this.ErrorDesc + "\"}"), nil
//}

type Message map[string]interface{}

func SendErr(w http.ResponseWriter, code uint8, desc string) {
	e := ErrorCode{code, "errno:" + strconv.FormatUint(uint64(code), 10) + "->desc:" + desc}
	b, _ := json.Marshal(&e)
	fmt.Fprint(w, string(b))
}

func SendSuccess(w http.ResponseWriter, args Message) {
	if args != nil {
		args["success"] = true
		b, _ := json.Marshal(args)
		//fmt.Println(string(b))
		fmt.Fprint(w, string(b))
	} else {
		fmt.Fprint(w, `{"success":true}`)
	}
}
