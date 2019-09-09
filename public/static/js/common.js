$(function(){
	// url = "http://" + RemoteAddr + ":" + Port + "/gm/getzonelist";
	// url = $('#serverUrl').text() + "/gm/getzonelist";
	url = "/admin/getzonelist";
	var zoneStr, channelStr;
    $.ajax({
        url: url,
        type: "GET",
        dataType:"json",
        async: false,
        success: function(data){
            //On ajax success do this
            console.info("success.");
            console.log(data["success"]);
            if (data["success"] == true){
            	delete(data["success"])
            	// console.log(data);
                // alert("Settings is Ok. The Machine is rebooting.");
                $.each(data, function(idx,val){
                	//zoneStr += "<option value="+idx+">"+val.ServerName;
                    zoneStr += "<option value="+val.ServerStatus+">"+val.ServerName;
                	if (val.ServerStatus == 0) {
                		zoneStr += " (维护中)";
                	}
                	zoneStr += "</option>";
                });
            }else{
                return;
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            //On error do this
            console.info("error.");
            // if (xhr.status == 200) {
            //     alert(ajaxOptions);
            // } else {
            //     alert(xhr.status);
            //     alert(thrownError);
            // }
            // window.location.href = "500.html";
        }
    });
    $.each(ChannelList, function(idx,val){
    	channelStr +="<option value="+idx+">"+val;
    });
    $("select[name='zone']").html(zoneStr);
    $("select[name='zone[]']").html(zoneStr);
    $("select[name='channel']").html(channelStr);
    $("select[name='channel[]']").html(channelStr);
});