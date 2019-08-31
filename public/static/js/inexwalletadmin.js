// 登录页获取验证码倒计时
// function getCodeCountdown(){
//   $('#getcode').on('click',requsetCode)
// }
//发送验证码
function requsetCode(){
    var phone = $('#phone').val()
    var pro_no = $('#pro_no').val()
    $.ajax({
        url:'/'+pro_no+'/login/sendMessage',
        data: {
            phone: phone
        },
        success:function(result){
            countDown();

            var res='';
            res= eval("("+result+")");

            if (res.status !== 1) {
                window.alert(res.msg)
                window.location.reload();
            }

        },
        error:function(){
            window.alert('发送失败');
            window.location.reload();
        }
    })
}

function countDown(){
  $('#getcode').unbind('click')
  // 到计时时间
  var downTime = 60
  var timer = setInterval(function(){
  	downTime--
  	$('#getcode').text(downTime+"s")
  	
  	if(downTime<1){
  		clearInterval(timer)
  		$('#getcode').text('重新获取')
  		$('#getcode').on('click',requsetCode)
  	}
  },1000) 
}
// 验证码
// function captcha(){
//     console.log('asa')
//    window.initNECaptcha({
//         captchaId: '659ba10af0634d2d9451f90fdcadb336',
//         element: '#getcode',
//         mode: 'popup',
//         width: 400,
//         onReady: function (instance) {
//             console.log('asa')
//         },
//         onVerify: function (err, data) {
//             requsetCode()
//         },
//         onload:function  (instance) {
//           instance.popUp()
//         },
//         onerror:function  (err) {
//         console.error('callback err: ', err)
//       }
//     })
// }

function getCodeCountdown(){
    $('#getcode').on('click',function(){
        var pro_no = $('#pro_no').val()
        var phone = $('#phone').val()
        if(phone === ''){
            window.alert('请输入手机号')
            window.location.reload();
        }
        // var captchaIns;
        window.initNECaptcha({
                element: '#getcode',
                captchaId: '659ba10af0634d2d9451f90fdcadb336',
                mode: 'popup',
                width: '320px',
                onReady: function(a) {},
                onVerify: function(a, t) {
                    // console.log(a,t)
                    $.ajax({
                        url:'/'+pro_no+'/login/captcha',
                        data: {
                            validate: t
                        },
                        success:function(result){
                            var res='';
                            res= eval("("+result+")");

                            if (res.status !== 1) {
                                window.alert(res.msg)
                                // window.location.reload();
                            }
                            if (res.status === 1) {
                                requsetCode()
                            }

                        },
                        error:function(){
                            window.alert('滑块验证失败');
                            window.location.reload();
                        }
                    })
                }
            },
            function (instance) {
                // 初始化成功后得到验证实例instance，可以调用实例的方法
                instance.popUp()
            }, function (err) {
                // 初始化失败后触发该函数，err对象描述当前错误信息
            })
        // captchaIns&&captchaIns.
    })

}


//登陆
function doLogin(){
    $('#doLogin').on('click',requsetLogin)
}

function requsetLogin(){
    var name = $('#name').val()
    var password = $('#password').val()
    var server = $('#server').val();

    $.ajax({
        type:"post",
        url:'/admin/doLogin',
        data: {
            name: name,
            password: password,
            server: server,
        },
        success:function(result){
            var res='';
            res= eval("("+result+")");

            if (res.status !== 1) {
                window.alert(res.msg)
                window.location.reload();
            }
            if(res.status === 1){
                window.location.href="/admin/index";
            }
            // console.log(localStorage.getItem("_token"));
        },
        error:function(){
            window.alert('登陆失败');
            window.location.reload();
        }
    })
}


//生成钱包地址
function addUserWallet(){
    showloading();
    var pro_no = $('#pro_no').val()
    var chain_id = $('.chain_id').val()
    var count = $('.add-wallet-count').val()
    var password1 = $('.add-wallet-password1').val()
    var password2 = $('.add-wallet-password2').val()
    var password3 = $('.add-wallet-password3').val()
    var password_prompt = $('.password_prompt').val()
    $.ajax({
        type:"post",
        url:'/'+pro_no+'/walletaddress/add',
        data: {
            chain_id: chain_id,
            count: count,
            password1: password1,
            password2: password2,
            password3: password3,
            password_prompt: password_prompt,
        },
        success:function(result){
            var res='';
            res= eval("("+result+")");

            if (res.status !== 1) {
                window.alert(res.msg)
                window.location.reload();
            }
            if(res.status === 1){
                var batch_no = res.data
                $('#wallet-generate-suc span').text(batch_no)
                $('#wallet-generate-suc').addClass('show')
            }
            hideloading();
        },
        error:function(){
            hideloading();
            window.alert('失败');
            window.location.reload();
        }
    })
}

function clickBgHide() {
    $('.dialog').not('.dialog-not-click').on('click',function(e){

        if($(e.target).hasClass('show')){
            $(this).removeClass('show')
        }

    })
}

// 侧边栏
function sidebarNav(){
  $('#sidebarnav>li>div').on('click',function(e){
     $($(this).parent()[0]).toggleClass('active')
  })
  $('#sidebarnav li li').on('click',function(){
    $('#sidebarnav li li').removeClass('active')
    $(this).addClass('active') 
  })
}

function adminTableCheck(){
  // 全选反选
  $('#choose-permissions .per-item thead').each(function(){
    $(this).change(function(e){
      var currentStatus = $(e.target).prop('checked')
      $(this).next().find('input').each(function(){
        console.log(currentStatus)
        $(this).prop('checked',currentStatus)
      })
    })
  })

  $('#choose-permissions .per-item tbody').each(function(){
    $(this).change(function(e){
        var a = $(this).prev().find('input')
        $(this).find('input').each(function(){
          if(!$(this).prop('checked')){
           a.prop('checked',false)
           return false
          }
          a.prop('checked',true)
        })
    })
  })
}
function manageSelect(){
  $('#all-checkbox').change(function(e){
    $("#out-manage tbody input[type='checkbox']").prop('checked', $(this).prop('checked'))
  })
  $("#out-manage tbody input[type='checkbox']").change(function(e){
    $("#out-manage tbody input[type='checkbox']").each(function(e){
      if (!$(this).prop('checked')) {
        $('#all-checkbox').prop('checked', false)
        return false
      }
      $('#all-checkbox').prop('checked', true)
    })
  })
}
// 冷钱包
function coldWalletShow(){
    $('#project-detail .cold-wallet-address .copy-edit.active').each(function(){
        var a = this
        $(this).find('i').on('click',function(){
            $(a).hide()
            $(a).prev().show()
            $(a).prev().find('button').on('click',function(){
                $(this).unbind()
                $('#walletd-login-pwd').addClass('show')
            })
        })
    })
}

//时间
function chooseTime(){
    $('.J-datepicker-day').datePicker({
        // hasShortcut: true,
        max: moment().format('YYYY-MM-DD HH:mm:ss'),
        format: 'YYYY-MM-DD HH:mm:ss'
    });
}

//生成出账钱包和手续费钱包的密码确认框
function showDialog(){
    $('.out-verb-btn').each(function(){
        $(this).on('click',function () {
            var chain_symbol = $("#transferChains").find("option:selected").html();
            if(chain_symbol === 'EOS'){
                $('#pwd-confirm-title').html('账户确认');
                $('#pwd-confirm-notice').html('账户生成成功后，将无法修改。');
            };

            $('#pwd-confirm .chain_id').val($(this).parent().find('.admin-select').val())
            $('#pwd-confirm .password').val($(this).parent().find("input[type='password']").val())
            $('#pwd-confirm .address').val($(this).parent().find("input[type='text']").val())
            $('#pwd-confirm .type').val($(this).parent().find("input[type='hidden']").val())
            $('#pwd-confirm .memo').val($(this).parent().find('.memo').val())

            $('#pwd-confirm').addClass('show').find('.pwd-confirm-btn .cancel').each(function(){
               $(this).on('click',function(e){
                    $('#pwd-confirm').removeClass('show')
                    return false;
               })
            })

        })
    })
}

//用ajax生成出账钱包和手续费钱包
function projectWalletAdd() {
    var chain_id = $('.chain_id').val()
    var chain_symbol =  chain_id.split(",")[1];
    var password = $('.password').val()
    var address = $('.address').val()
    var type = $('.type').val()
    var memo = $('.memo').val()
    var pro_no = $('#pro_no').val()

    $.ajax({
        type:"post",
        url:'/'+pro_no+'/projectWallet/add',
        data: {
            chain_id: chain_id,
            password: password,
            address: address,
            type: type,
            memo: memo,
        },
        success:function(result){
            var res='';
            res= eval("("+result+")");

            if (res.status !== 1) {
                window.alert(res.msg)
                window.location.reload();
            }
            if(res.status === 1){
                if(chain_symbol === 'EOS'){
                    var wallet_password = res.data
                    $('#password-copy-text').text(wallet_password)
                    $('#copy-password').attr("value",wallet_password)
                    $('#password-copy-confirm .address').attr("value",address)
                    $('#pwd-confirm').removeClass('show')
                    $('#password-copy-confirm').addClass('show')
                }else{
                    window.location.href="/"+pro_no+"/project/detail";
                }
            }
        },
        error:function(){
            window.alert('操作失败');
            window.location.reload();
        }
    })
}

//确认密码的框
function passwordConfirmForm(){
    $('#password-confirm-button').each(function(){
        $(this).on('click',function(){
            $('#password-copy-confirm').removeClass('show')
            $('#password-confirm-form').addClass('show')
        })
    })
}

//确认密码
function passwordConfirm(){
    var password = $('.copyPassword').val()
    var address = $('.address').val()
    var pro_no = $('.pro_no').val()

    $.ajax({
        type:"post",
        url:'/'+pro_no+'/projectWallet/passwordConfirm',
        data: {
            password: password,
            address: address,
            pro_no: pro_no,
        },
        success:function(result){
            var res='';
            res= eval("("+result+")");

            if (res.status !== 1) {
                $('.copyPassword').val("")
                showMessage(res.msg)
            }
            if(res.status === 1){
                showMessage('创建成功')
                window.location.reload();
            }
        },
        error:function(){
            window.alert('操作失败');
            window.location.reload();
        }
    })
}





function setValue(id){
    $('.coldId').val(id);
    $('.coldData').val($('#coldAddressData_'+id).val());
    $('.coldMemo').val($('#coldAddressMemo_'+id).val());
}


// 转出冷钱包-密码确认弹框
function showOutPwdDialog(){
    $('.show-cold-dialog').each(function(){
        $(this).on('click',function () {
            var chain_symbol = $(this).parent().parent().find('.chain_symbol').html()
            $('#chain_symbol').attr('value',chain_symbol)
            var pro_no = $("#pro_no").html()
            var eos_address = $('#eos_address').html()
            var enough_resource = $('#enough_resource').html()
            if(chain_symbol === 'EOS'){
                if(eos_address != '' && (enough_resource === '-1' || enough_resource === '0')){
                    showMessage('EOS资源不足')
                    window.location.href="/"+pro_no+"/eosResourceManage";
                    return
                }else {
                    $('#eos_show').css('display', 'block')
                }
            }else{
                $('#eos_show').css('display','none')
            }
            $('#roll-out-cold').addClass('show')
            $('#out-cold-prompt-text').text($(this).find('span').text())
            $('#current-out-id').val($(this).attr('value'))

        })
    })
}
// 转出冷钱包历史-失败重转-密码确认弹框
function transferHistoryDialog(){
    $('.transfer-failed-enew-btn').each(function(){
        $(this).on('click',function(){
            var coin_symbol = $(this).parent().parent().parent().find('.coin_symbol').html()
            $('#coin_symbol').attr('value',coin_symbol)
            var pro_no = $("#pro_no").html()
            var eos_address = $('#eos_address').html()
            var enough_resource = $('#enough_resource').html()
            if(coin_symbol === 'EOS'){
                if(eos_address != '' && (enough_resource === '-1' || enough_resource === '0')){
                    showMessage('EOS资源不足')
                    window.location.href="/"+pro_no+"/eosResourceManage";
                    return
                }else{
                    $('#eos_show').css('display','block')
                }
            }else{
                $('#eos_show').css('display','none')
            }
            $('#transfer-history-pwd-dialog').addClass('show')
            $('#out-cold-prompt-text').text($(this).find('span').text())
            $('#second-current-out-transaction_no').val($(this).attr('value'))
        })
    })
}

// 提现审核中
function dialogDealing() {
    $('.out-manage-deal-ok').each(function(){
        $(this).on('click',function(){
            var pro_no = $("#pro_no").html();
            var coin_symbol = $("#coin_id").find("option:selected").html();
            var eos_address = $('#eos_address').html()
            var enough_resource = $('#enough_resource').html()
            if(coin_symbol === 'EOS'){
                if(eos_address != '' && (enough_resource === '-1' || enough_resource === '0')){
                    showMessage('EOS资源不足')
                    window.location.href="/"+pro_no+"/eosResourceManage";
                    return
                }
            }

            var ok_id = $(this).attr('value')
            $('#ok-id').val(ok_id)
            if(coin_symbol === 'EOS'){
                $('#out-wallet-pwd-dialog').addClass('show').find('p>span').text($(this).parent().prev().prev().prev().text())
            }else{
                $('#out-wallet-pwd-dialog').addClass('show').find('p>span').text($(this).parent().prev().prev().text())
            }
        })
    })
    $('.out-manage-deal-no').each(function(){
        $(this).on('click',function(){
            var id = $(this).attr('value')
            $('#refused-input-id').val(id)
            $('#out-refused').addClass('show')

        })
    })
}
// 失败重转-提现
function withdraw() {
    $('.out-manage-deal-fail').each(function(){
        var ok_id = $(this).attr('value')
        $('#ok-id').val(ok_id)
        $(this).on('click',function(){
            var pro_no = $("#pro_no").html();
            var coin_symbol = $("#coin_id").find("option:selected").html();
            var eos_address = $('#eos_address').html()
            var enough_resource = $('#enough_resource').html()
            if(coin_symbol === 'EOS'){
                if(eos_address != '' && (enough_resource === '-1' || enough_resource === '0')){
                    showMessage('EOS资源不足')
                    window.location.href="/"+pro_no+"/eosResourceManage";
                    return
                }
            }
            if(coin_symbol === 'EOS'){
                $('#fail-out-wallet-pwd-dialog').addClass('show').find('p>span').text($(this).parent().prev().prev().prev().prev().text())
            }else {
                $('#fail-out-wallet-pwd-dialog').addClass('show').find('p>span').text($(this).parent().prev().prev().prev().text())
            }
        })
    })
}
function showloading(){
    $("#hide-loading").css("display","none");
    $("#showloading").css("display","block");
}
function hideloading(){
    $("#hide-loading").css("display","block");
    $("#showloading").css("display","none");
}


function showMessage(msg, fun){
    var currentTop, currentLeft;
    // currentLeft = ($(window).innerWidth() - 350)/2;
    currentTop = 40;
    $('.message').css({'left':currentLeft, 'top':currentTop});
    $('.message .message_title').text(msg);

    if(fun){
        $('.message').fadeIn(200).delay(2000).fadeOut(500, function(){fun();});
    } else {
        $('.message').fadeIn(200).delay(2000).fadeOut(500);
    }
}

//查询未添加币种订单
function selectUnknowncoinOrder(){
    var pro_no = $('.pro_no').val()
    var token_contract = $('.token_contract').val()
    var abi = $('.abi').val()
    var deposit_address = $('.deposit_address').val()
    var hash = $('.hash').val()

    $.ajax({
        type:"post",
        url:'/'+pro_no+'/unknowncoin/selectOrder',
        data: {
            pro_no: pro_no,
            token_contract: token_contract,
            abi: abi,
            deposit_address: deposit_address,
            hash: hash
        },
        success:function(result){
            var res='';
            res= eval("("+result+")");
            if (res.status !== 1) {
                showMessage(res.msg)
            }
            if(res.status === 1){
                $('#deposit_address').html(res.data.deposit_address)
                $('#batch_no').html(res.data.batch_no)
                $('#hash').html(res.data.hash)
                $('#amount').html(Number(res.data.amount).toFixed(8))
                $('#coin_type').html(res.data.coin_type)
                $('#out-cold-batchNo-text').html(res.data.batch_no)
                $('#out-cold-prompt-text').html(res.data.password_prompt)
                $('.decimals').attr('value',res.data.decimals)

                // $('.batch_no').attr('value',res.data.batch_no)
                // $('.coin_type').attr('value',res.data.coin_type)
                // $('.address').attr('value',res.data.deposit_address)
                // $('.abi').attr('value',abi)
                // $('.token_contract').attr('value',token_contract)
                // $('.num').attr('value',res.data.amount)


                $('#show-unknowncoin-order').css('display','block')
            }
        },
        error:function(){
            window.alert('操作失败');
            window.location.reload();
        }
    })
}

function unknowncoinTransfer(){
    $('#unknowncoin-transfer').each(function(){
        $(this).on('click',function () {
            $('.password1').val("")
            $('.password2').val("")
            $('.password3').val("")
            $('#show-unknowncoin-transfer').addClass('show');
        })
    })
}

function transfer() {
    $('#transfer').each(function(){
        $(this).on('click',function () {
            var pro_no = $('.pro_no').val()
            var token_contract = $('.token_contract').val()
            var abi = $('.abi').val()
            var address = $('.deposit_address').val()
            var batch_no = $('#out-cold-batchNo-text').html()
            var coin_type = $('#coin_type').html()
            var num = $('#amount').html()
            var password1 = $('.password1').val()
            var password2 = $('.password2').val()
            var password3 = $('.password3').val()
            var decimals = $('.decimals').val()

            $.ajax({
                type:"post",
                url:'/'+pro_no+'/unknowncoin/transferColdWallet',
                data: {
                    pro_no: pro_no,
                    token_contract: token_contract,
                    abi: abi,
                    address: address,
                    batch_no: batch_no,
                    coin_type: coin_type,
                    num: num,
                    password1: password1,
                    password2: password2,
                    password3: password3,
                    decimals: decimals
                },
                success:function(result){
                    var res='';
                    res= eval("("+result+")");
                    if (res.status === -2) {
                        showMessage(res.msg)
                        window.location.href="/"+pro_no+"/logout";
                    }else if(res.status === -3){
                        showMessage(res.msg)
                        window.location.href="/"+pro_no+"/project/detail";
                    }else if(res.status === -1){
                        showMessage(res.msg)
                    }else if(res.status === 2 || res.status === 1){
                        $('#show-transfer-result').html(res.msg)
                        $('#show-unknowncoin-transfer').removeClass('show');
                        $('#show-unknowncoin-transfer-notice').addClass('show')
                    }

                },
                error:function(){
                    window.alert('操作失败');
                    window.location.reload();
                }
            })

        })
    })
}

$(document).ready(function(){
    doLogin()
    // captcha()
    getCodeCountdown()
    sidebarNav()
    adminTableCheck()
    // manageSelect()
    coldWalletShow()
    // chooseTime()

    showDialog()
    passwordConfirmForm()
    clickBgHide()
    showOutPwdDialog()
    transferHistoryDialog()
    dialogDealing()
    withdraw()
    unknowncoinTransfer()
    transfer()
})