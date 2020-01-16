var wsurl = 'ws://118.31.109.21:8811';

var websocket = new WebSocket(wsurl);

//实例对象的onopen属性
websocket.onopen = function(evt){
    websocket.send('send:hello');
    console.log("conected-swoole-success");

    // alert('连接成功')

}

//实例化 onmessage
websocket.onmessage = function(evt){
    push(evt.data);
    console.log("web-server-return-data:" + evt.data);

    // var data = jQuery.parseJSON(evt.data);
    // switch (data.type) {
    //     case 'open':
    //         webim.appendUser(data.user.name, data.user.avatar, data.user.fd);
    //         webim.notice(data.message);
    //         break;
    //     case 'close':
    //         webim.removeUser(data.user.fd);
    //         webim.notice(data.message);
    //         break;
    //     case 'openSuccess':
    //         webim.data.info = data.user;
    //         webim.showAllUser(data.all);
    //         break;
    //     case 'message':
    //         webim.newMessage(data);
    //         break;
    // }
}

websocket.onclose = function(evt){
    console.log("close");
    // alert('不妙，链接断开了1');
}

websocket.onerror = function(evt, e){
    console.log("error:" + evt.data);
}

function push(data){


    // data = JSON.parse(data);
    // html = "<div class='comment'>";
    // html += '<span>'+data.user+'&nbsp;</span>';
    // html += '<span>'+data.content+'</span>';
    // html += '</div>';
    // $('#comments').append(html);

    data = JSON.parse(data);
    html = "<div class='col-xs-10 col-xs-offset-2 msg-item '><div class='col-xs-1 no-padding pull-right'><div class='avatar'><img src='undefined' width='50' height='50' class='img-circle'></div></div><div class=\"col-xs-11\"><div class=\"col-xs-12\"><div class='username pull-right'>undefined</div><div><div class='col-xs-12 no-padding'><div class='msg pull-right'>33</div></div></div></div></div></div>";
}



