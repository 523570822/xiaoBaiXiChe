$(document).ready(function() {
	$('#fullpage').fullpage({
		//Navigation
		menu: '#myMenu',
		lockAnchors: false,
		anchors:['firstPage', 'secondPage','thirdPage'],//（默认设置）链接的锚（#的例子是显示在URL）的每个部分
		navigation: true,
		navigationPosition: 'right',
		navigationTooltips: ['firstSlide', 'secondSlide'],
		showActiveTooltip: false,
		slidesNavigation: false,
		slidesNavPosition: 'bottom',

		//Scrolling
		css3: true,//（默认为true）确定是否使用JavaScript和CSS3转换滚动在切片和幻灯片。如果此选项设置为true，浏览器不支持CSS3，jQuery回退将代替
		scrollingSpeed: 700,//（默认：700）的速度在milliseconds scrolling转换
		autoScrolling: true,//（默认为true）定义是否使用自动滚动或正常滚动
		fitToSection: true,//（默认为true）当设置为true时，当前活动部分将始终填充整个视口。否则用户将在一个区段（在）中随意停止。
		fitToSectionDelay: 1000,//（默认为1000）。如果fittosection设置为true，这延迟的毫秒的安装配置。
		scrollBar: false,//（默认为false）决定网站是否使用滚动条
		easing: 'easeInOutCubic',//（默认设置easeinoutcubic）的过渡效果使用的垂直和水平滚动
		easingcss3: 'ease',  //（默认设置）：ease）的过渡效果使用对CSS3：true的情况
		loopBottom: true,//（默认为false）定义是否向下滚动在最后一节要滚动到第一个
		loopTop: false,//（默认为false）定义是否向上滚动的第一部分应该滚动到最后一个
		loopHorizontal: false,//（默认为true）定义是否水平滑块将达到过去或前或后循环
		continuousVertical: false,//留下
		continuousHorizontal: false,
		scrollHorizontally: false,
		interlockedSlides: false,
		dragAndMove: 'vertical',//用鼠标或者手指 （可选的值有：true,false,vertical,horizontal,fingersonly,mouseonly）
		offsetSections: false,
		resetSliders: false,
		fadingEffect: false,
		normalScrollElements: '#element1, .element2',
		scrollOverflow: false,
		scrollOverflowReset: false,
		scrollOverflowOptions: null,
		touchSensitivity: 15,
		normalScrollElementTouchThreshold: 5,
		bigSectionsDestination: null,

		//Accessibility
		keyboardScrolling: true,//可以使用键盘
		animateAnchor: true,
		recordHistory: true,

		//Design
		controlArrows: true,//（默认为true）决定是否使用控制箭头向左或向右移动幻灯片。
		verticalCentered: true,//（默认为true）的含量在剖面垂直居中。当设置为true时，内容将由库包装。考虑使用委托或加载您的其他脚本中的afterrender回调。
		sectionsColor : ['#DE5780', '#FE8E63','#E35957'],//（非默认）定义的CSS背景色为每个部分财产
		paddingTop: '5rem',//（默认为0）在使用固定表头的情况下有用
		paddingBottom: '2.5rem',//（默认为0）在使用固定页脚的情况下有用
		fixedElements: '#header, .footer',
		responsiveWidth: 0,
		responsiveHeight: 0,
		responsiveSlides: false,
		parallax: false,
		parallaxOptions: {type: 'reveal', percentage: 62, property: 'translate'},

		//Custom selectors
		sectionSelector: '.section',
		slideSelector: '.slide',

		lazyLoading: true,

		//events
		onLeave: function(index, nextIndex, direction){},
		afterLoad: function(anchorLink, index){},
		afterRender: function(){},
		afterResize: function(){},
		afterResponsive: function(isResponsive){},
		afterSlideLoad: function(anchorLink, index, slideAnchor, slideIndex){},
		onSlideLeave: function(anchorLink, index, slideIndex, direction, nextSlideIndex){}
	});
});