

// $(function(){
//
//     // $('#send').keydown(function(event){
//     //     if(event.keyCode == 13){//回车事件
//     //         var text = $(this).val();
//     //         var url = '/admin/chat/send';
//     //         var data = {'content':text, 'game_id':1};
//     //
//     //         $.post(url,data,function(result){
//     //             //todo
//     //             $(this).val('');
//     //         },'json');
//     //     }
//     // });
//
//     $(".message .send").click(function(){
//         var url = '/admin/chat/send';
//         var data = {'content':text, 'game_id':1};
//
//         $.post(url,data,function(result){
//             //todo
//             $(this).val('');
//         },'json');
//
//
//
//         var text = $(".message .text textarea");
//         var msg = text.val();
//         var account = <?php ?>;
//
//         if ($.trim(msg) == '') {
//             this.layerErrorMsg('请输入消息内容');
//             return false;
//         }
//
//         // this.data.wsServer.send(msg);
//
//         var html = '<div class="col-xs-10 col-xs-offset-2 msg-item ">'
//             +'<div class="col-xs-1 no-padding pull-right">'
//             +'<div class="avatar">'
//             +'<img src="https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1579087727938&di=7154a64b5e4a93fd9b47ec538bbea201&imgtype=0&src=http%3A%2F%2Fimage.biaobaiju.com%2Fuploads%2F20180803%2F23%2F1533308847-sJINRfclxg.jpeg" width="50" height="50" class="img-circle">'
//             +'</div>'
//             +'</div>'
//
//             +'<div class="col-xs-11">'
//             +'<div class="col-xs-12">'
//             +'<div class="username pull-right">'+''+'</div>'
//             +'<div>'
//             +'<div class="col-xs-12 no-padding">'
//             +'<div class="msg pull-right">'+msg+'</div>'
//             +'</div>'
//             +'</div>';
//
//         $('.chat-list').append(html);
//
//         this.appendUser(this.data.info.name, this.data.info.avatr, this.data.info.fd);
//         this.scrollBottom();
//
//         text.val('');
//     });
//
//
//
// });