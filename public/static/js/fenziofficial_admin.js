(function($) {
    $.fn.withdrawDealing = function(options){
    	manageSelect()
    	function manageSelect(){
	      var selectID = ''
		  var totalNum = 0
		  $('#all-checkbox').change(function(e){
		    $("#out-manage tbody input[type='checkbox']").prop('checked', $(this).prop('checked'))
		    if($(this).prop('checked')){
		      selectID = []
		      var back = pushSelectId()
		      selectID = back.arr
		      totalNum = back.total
		      $('#select-all-total').text(back.total)
		    }else{
		      selectID = []
		      totalNum = 0
		      $('#select-all-total').text('0.00')
		    }
		  })
		  $("#out-manage tbody input[type='checkbox']").change(function(e){

		    $("#out-manage tbody input[type='checkbox']").each(function(e){
		      if (!$(this).prop('checked')) {
		        $('#all-checkbox').prop('checked', false)
		        return false
		      }
		      $('#all-checkbox').prop('checked', true)
		    })
		      var backs = pushSelectIds()
		      selectID = backs.arr
		      totalNum = backs.total
		      $('#select-all-total').text(backs.total)
		   
		  })
		  $('#batch-mention-money').on('click',function(){
		  	if(!selectID){
		  		showMessage('请先勾选')
		  		return
		  	}
            //判断eos资源
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
              }else if(coin_symbol === 'WICC'){
                  showMessage('该币种不支持批量提现')
				  return
			  }

              $('#out-wallet-pwd-dialog-select').addClass('show').find('span').text(totalNum)
		    $('#select-ok-id').attr('value',selectID)
		  })
	     }
			function pushSelectId(){
			  var idArr = ''
			  var total = 0
			  $("#out-manage tbody input[type='checkbox']").each(function(){

			        idArr = idArr? idArr+','+$(this).val():idArr+$(this).val()
			          // total = total -(-$(this).parent().next().next().text())
			        // total = numAdd(total, $(this).parent().next().next().text())
			        total = plus(total, $(this).parent().next().next().text())
			  })

			  return {arr:idArr,total: total}
			}
			function pushSelectIds(){
			  var idArr = ''
			  var total = 0
			  $("#out-manage tbody input[type='checkbox']").each(function(){
			      if($(this).prop('checked')){
			        idArr = idArr? idArr+','+$(this).val():idArr+$(this).val()
			        // total = total -(-$(this).parent().next().next().text())
			        // total = numAdd(total, $(this).parent().next().next().text())
			        total = plus(total, $(this).parent().next().next().text())
			      }
			  })
			  return {arr:idArr,total: total}
			}
    }
    $.fn.projectDetail = function(){
    	$('#copy-text-btn').on('click',function(){
    		var a = document.getElementById('copy-text')
			a.select()
		    var b = document.execCommand("Copy")
		    if(b){
		    	showMessage('复制成功')
		    }else{
		    	showMessage('复制失败')
		    }
    	})
        $('#copy-text-btn2').on('click',function(){
            var a = document.getElementById('copy-text2')
            a.select()
            var b = document.execCommand("Copy")
            if(b){
                showMessage('复制成功')
            }else{
                showMessage('复制失败')
            }
        })
        $('#copy-password-btn').on('click',function(){
            var a = document.getElementById('copy-password')
            a.select()
            var b = document.execCommand("Copy")
            if(b){
                showMessage('复制成功')
            }else{
                showMessage('复制失败')
            }
        })
    }
    $.fn.resourceManage = function(){
        $('.buy_ram').on('click',function () {
            var ram_num = $('.ram_num').val()
            var dot = ram_num.indexOf(".");
            var dotCnt = ram_num.substring(dot+1,ram_num.length);
            if(ram_num <= '0'){
                showMessage('请检查输入内容')
            }else if(ram_num === ''){
                showMessage('输入框不可为空')
            }else if(dot != -1 && dotCnt.length > 4){
                showMessage('请检查输入内容')
            }else{
                $('#ram_num').val(ram_num)
            	$('#buyRam').addClass('show')
			}
        })
        $('.buy_cpu_and_net').on('click',function () {
            var cpu_num = $('.cpu_num').val()
            var dotCpu = cpu_num.indexOf(".");
            var dotCntCpu = cpu_num.substring(dotCpu+1,cpu_num.length);
            var net_num = $('.net_num').val()
            var dotNet = net_num.indexOf(".");
            var dotCntNet = net_num.substring(dotNet+1,net_num.length);
            if(cpu_num === '' ){
                cpu_num = 0;
            }
            if(net_num === ''){
                net_num = 0;
            }
            if((cpu_num <= '0' && net_num <= '0') || cpu_num < '0' || net_num < '0'){
                showMessage('请检查输入内容')
            }else if(dotCpu != -1 && dotCntCpu.length > 4){
                showMessage('请检查输入内容')
            }else if(dotNet != -1 && dotCntNet.length > 4){
                showMessage('请检查输入内容')
            }else{
                $('#cpu_num').val(cpu_num)
                $('#net_num').val(net_num)
                $('#buyCpuNet').addClass('show')
            }
        })
    }
    $.fn.walletTypeAdd = function(){
      $('#min').on('input',function(){
      	$(this).val($(this).val().replace(/[^\d.]/g,""))
      })
    }
    $.fn.walletTypeDetail = function(){
    	 $('#wallet-type-detail .detail-desc-min .icon').on('click',function(){
          $('#wallet-type-detail .detail-desc-min').fadeOut(0,function(){
            $('#wallet-type-detail .detail-desc-min-edit').fadeIn(0)
          })
        })
    }
    $.fn.adminAdd = function(){
      if($('#is-root').prop('checked')){
      	$('.addadmin-permissions').fadeOut(0)
      }
      $('#is-root').change(function(){
		if($(this).prop('checked')){
			// $('#choose-permissions input[type="checkbox"]').each(function(){
				$('.addadmin-permissions').fadeOut(0)
			// })
		}
  	  })
  	  $('#no-root').change(function(){
		if($(this).prop('checked')){
			$('.addadmin-permissions').fadeIn(0)
		}
  	  })
  	  $('table tbody tr').each(function(){
  	  	var _this = $(this)
  	  	$(this).find('.do').on('click', function(){	
  	  		if($(this).prop('checked')){
  	  		  _this.find('.read').prop("checked",true)
  	  		}
  	  	})
  	  })
    }
    $.fn.adminInfo = function(){
      if($('#is-root').prop('checked')){
      	$('.addadmin-permissions').fadeOut(0)
      }
      $('#is-root').change(function(){
		if($(this).prop('checked')){
			$('.addadmin-permissions').fadeOut(0)
		}
  	  })
  	  $('#no-root').change(function(){
		if($(this).prop('checked')){
			$('.addadmin-permissions').fadeIn(0)
		}
  	  })
  	  $('table tbody tr').each(function(){
  	  	var _this = $(this)
  	  	$(this).find('.do').on('click', function(){	

  	  		if($(this).prop('checked')){
  	  		  _this.find('.read').prop("checked",true)
  	  		}
  	  	})
  	  })
    }
    // 时间插件
    $.fn.chooseTime = function(options){
    	// console.log('sasa')
 		$('.J-datepicker-day').datePicker({
        // hasShortcut: true,
          max: moment().format('YYYY-MM-DD HH:mm:ss'),
          format: 'YYYY-MM-DD HH:mm:ss'
  		});
    }

    function numAdd(num1, num2) { 
		var baseNum, baseNum1, baseNum2; 
		try { 
		baseNum1 = num1.toString().split(".")[1].length; 
		} catch (e) { 
		baseNum1 = 0; 
		} 
		try { 
		baseNum2 = num2.toString().split(".")[1].length; 
		} catch (e) { 
		baseNum2 = 0; 
		} 
		baseNum = Math.pow(10, Math.max(baseNum1, baseNum2)); 
		return (num1 * baseNum + num2 * baseNum) / baseNum; 
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

	// 运算
	function plus(num1, num2) {
      const baseNum = Math.pow(10, Math.max(digitLength(num1), digitLength(num2)));
      return (times(num1, baseNum) + times(num2, baseNum)) / baseNum;
    }
	function times(num1, num2) {

	  const num1Changed = float2Fixed(num1);
	  const num2Changed = float2Fixed(num2);
	  const baseNum = digitLength(num1) + digitLength(num2);
	  const leftValue = num1Changed * num2Changed;

	  checkBoundary(leftValue);

	  return leftValue / Math.pow(10, baseNum);
	}
	function checkBoundary(num) {
	  if (num > Number.MAX_SAFE_INTEGER || num < Number.MIN_SAFE_INTEGER) {
	    console.warn( num + 'is beyond boundary when transfer to integer, the results may not be accurate');
	  }
    }
    function float2Fixed(num) {
	  if (num.toString().indexOf('e') === -1) {
	    return Number(num.toString().replace('.', ''));
	  }
	  const dLen = digitLength(num);
      return dLen > 0 ? num * Math.pow(10, dLen) : num;
    }

	
    function digitLength(num){

	  const eSplit = num.toString().split(/[eE]/);
	  const len = (eSplit[0].split('.')[1] || '').length - (+(eSplit[1] || 0));
	  return len > 0 ? len : 0;
	}
	// 运算
})(jQuery)