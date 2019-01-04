/**
 * @name dom  linkto 属性增强
 * @author topqiang
 * @version 1.0
 * **/
function top_linkto(){
	$("[linkto]").on('click',function(){
		var self = $(this);
		var url = $.trim(self.attr("linkto"));
		if( url == ""){
			return;
		}
		//匹配全路径跳转
		if( url.indexOf("http://") == 0 || url.indexOf("https://") == 0 ){
			window.location.href = url;
		}else if( url.indexOf("/") == 0 ){
			//匹配   类似于Thinkphp类型的URL {:U('Index/index')} => /项目名/index.php/Index/index  
			var hostname = getRootPath();
			url = hostname + url;
			window.location.href = url;
		}else if( url.indexOf("tel:") == 0){
			window.location.href = url;
		}else if( url.indexOf("javascript:") == 0){
			var start = url.indexOf(":");
			var str = url.substring(start+1);
			eval(str);
		}else if( url.indexOf("./") == 0 || url.indexOf("/") == -1){
			//匹配静态跳转，已经相对路径跳转。
			var curUrl = window.location.href;
			url = curUrl.substr(0,curUrl.lastIndexOf("/")+1) + url;
			window.location.href = url;
		}
	});
}
function getRootPath(){
    var curWwwPath=window.document.location.href;
    var pathName=window.document.location.pathname;
    var pos=curWwwPath.indexOf(pathName);
    var localhostPaht=curWwwPath.substring(0,pos);
    var projectName=pathName.substring(0,pathName.substr(1).indexOf('/')+1);
    return(localhostPaht);
}
/**
 * @name check响应
 * @author topqiang
 * @version 1.0
 * **/
function top_check(){
	$(".top_radio").off('click');
	$(".top_check").off('click');
	$(".top_radio").on('click',function(){
		var self = $(this);
		self.addClass("on");
		self.siblings().removeClass("on");

		if (typeof radioAfter === 'function') {
			radioAfter(self);
		}
	});
	$(".top_check").on('click',function(){
		var self = $(this);
		if(self.hasClass("on")){
			self.removeClass("on");
		}else{
			self.addClass("on");
		}

		if (typeof checkAfter === 'function') {
			checkAfter(self);
		}
	});
}
/**
 * @name  星级评价组件
 * @author topqiang
 * @version 1.0
 * **/
function top_rate(){
	$(".top_rate span").on('click',function(){
		var self = $(this);
		self.nextAll().removeClass("on");
		self.prevAll().addClass("on");
		if(!self.hasClass("on")){
			self.addClass("on");
		}
	});
}
/**
 * @name  购物车显示隐藏
 * @author topqiang
 * @version 1.0
 * **/
function top_ingley(){
	$(".gley .icongley").on('click',function(){
		if($(this).parents('.gley').hasClass('on')){
			$(".zhao").toggleClass('disn');
			$(".zhcon").toggleClass('disn');
		}
	});
}
/**
 * @name 隐式下拉框交互
 * @author topqiang
 * @version 1.0
 * **/
function top_select(){
	$("select.selpstime").on('change',function(){
		var self = $(this);
		var elename = self.attr("forele");
		if($.trim(elename) != ""){
			var ele = self.parent().find("[ele="+elename+"]");
			var value = self.val();
			var inValue = self.find('option[value='+value+']').text();
			// ele.text(self.val());
			ele.text(inValue);
		}

		if (typeof selectAfter === 'function') {
			selectAfter(self);
		}
	});
}

/**
 * @name 回顶部
 * @author topqiang
 * @version 1.0
 * **/
function top_gohead(){
	$(".top_gohead").on('click',function(){
		var height = $(document).scrollTop();
		$("html,body").animate({scrollTop:0},200);
	});
}

/**
 * @name  切换商品查看样式
 * @author topqiang
 * @version 1.0
 * **/
function top_jeep(){
	$(".iconv").on('click',function(){
		var self = $(this);
		if(self.hasClass('on')){
			$(".good").removeClass('good').addClass('goodh');
		}else{
			$(".goodh").removeClass('goodh').addClass('good');
		}
		self.toggleClass('on');

		if (typeof jeepAfter == 'function') {
			jeepAfter(self);
		}
	});
}

/**
 * @name 分类筛选
 * @author topqiang
 * @version 1.0
 * **/
function top_sifting(){
	$(".dosifting").on('click',function(){
		$(".sifting").slideToggle();
	});
}

/**
*@name 自定弹窗
*@author topqiang
*@version 1.0
**/
var curobj = $("<div/>").css({"width": "0px","transform": "scale(0)"}).appendTo("body");
// window.alert = function(msg,timeout = 2000){
// 	if (msg) {
// 		curobj.html(msg).addClass("xq_alert");
// 		setTimeout(function(){
// 			curobj.removeClass("xq_alert");
// 		},timeout);
// 	}
// }

function xq_alert(msg){
	if (msg) {
		curobj.html(msg).addClass("xq_alert");
		setTimeout(function(){
			curobj.removeClass("xq_alert");
		},1000);
	}
}
/**
 * @name range
 * @author topqiang
 * @version 1.0
 * */
function top_range(){
	$(".range").each(function(){
		var self = $(this);
		var total = self.attr("total");
		var cur = self.attr("cur");
		var width = cur / total * 100;
		if (!total || !cur) {
			var completeness = self.attr("completeness");
			width = completeness * 100;
		}
		self.find("span").css({"width" : width+"%" });
	});
}

/**
*ajax 请求封装
*@author topqiang
*@name requestUrl
**/ 
function requestUrl(URL,DATA,CALLBACK,TYPE,DATATYPE){
	if (!URL) return;
	if (!TYPE) TYPE ="post";
	if (!DATATYPE) DATATYPE ="json";
	loading.show();
	$.ajax({
		"url":URL,
		"data" : DATA,
		"dataType" : DATATYPE,
		"type" : TYPE,
		"success" : function(res){
			if (res.flag == "success" && res.data && res.data.un_read_num) {
				var un_read_num = res.data.un_read_num;
				console.log("un_read_num"+un_read_num);
				un_read_num = un_read_num ? un_read_num : sessionStorage.getItem("un_read_num");
				if (un_read_num != 0 && un_read_num != "0") {
					sessionStorage.setItem("un_read_num",un_read_num);
					$(".jiaob").show().text(un_read_num);
				}else{
					$(".jiaob").hide();
				}
			}
			loading.hide();
			if (typeof CALLBACK == 'function') {
				CALLBACK(res);
			}
		}
	});
}

/**
*获取用户信息
*@author  topqiang
***/
var top_user;
function getUserInfo(URL){
	var jsonstr = sessionStorage.getItem("top_user");
	if (jsonstr) {
		top_user = JSON.parse(jsonstr);
		return top_user;
	}else{
		if(!URL){
			console.warn("请在获取用户信息时，填写登录页面URL");
			return "";
		}else{
			window.location.href = URL;
		}
	}
}


//function getCode(url,type){
//    $(".getcode,.getlcode").on('click',function(){
//        var self = $(this);
//        var account = $(".account").val();
//        if (self.attr('disabled')) return;
//        if ( account == "" ) {
//            alert("请填写手机号！");
//            return;
//        }
//        requestUrl(url,{account,type},function( res ){
//            if (res.flag == "success") {
//                var i = 60;
//                var time = setInterval(function(){
//                    self.text(i+"秒").attr("disabled","true");
//                    --i;
//                    if (i == 0) {
//                        clearInterval(time);
//                        self.text("获取验证码").removeAttr("disabled");
//                    }
//                },1000);
//            }else{
//                alert(res.message);
//            }
//        });
//    });
//}
//将用户信息挂在到
var jsonstr = sessionStorage.getItem("top_user");
	if (jsonstr) {
		top_user = JSON.parse(jsonstr);
	}else{
		top_user = false;
	}
var loading = $(".loading");

// loading = $("<div/>",{"class":"loading"}).css({"position": "fixed","top": "50px","left": "0px","right": "0px","bottom": "0px","text-align": "center","background": "rgba(0,0,0,0.6)"}).html('<img src="http://www.taomim.com/Public/Wap/img/rotate.png" style="margin-top: 30%;width: 4rem;border-radius: 50%;animation: rotate 0.5s ease-out infinite alternate;"/>').hide().appendTo("body");
loading = $("<div/>",{"class":"loading"}).css({"position": "fixed","top": "50px","left": "0px","right": "0px","bottom": "0px","text-align": "center","background": "rgba(0,0,0,0.4)","z-index":10}).html('<div class="loader-inner line-spin-fade-loader"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>').hide().appendTo("body");


/**
*@name 开启置顶滑动一段距离显示
*@author topqiang
**/
function showgohead(){
	var ele = $(".top_gohead").addClass("disn");
	$(window).on('scroll',function(){
		var scrolltop = $(this).scrollTop();
		if (scrolltop > 500 && ele.hasClass("disn")) {
			ele.removeClass("disn");
		}else if (scrolltop < 500 && !ele.hasClass("disn")) {
			ele.addClass("disn");
		}
	});
}

$(function(){
	//吊起linkto增强页面跳转
	top_linkto();
	//吊起check响应
	top_check();
	//开启星级评价
	top_rate();
	//开启购物车
	top_ingley();
	//吊起统一下拉框
	top_select();
	//吊起回顶部
	top_gohead();
	//切换预览商品版图
	top_jeep();
	//吊起分类刷选事件
	top_sifting();
	//显示进度条
	top_range();
	showgohead();
	$(".rigshe").on('click',function(){
        $(".goinmsg").toggle();
    }).on('blur',function () {
        $(".goinmsg").hide();
    });
});
