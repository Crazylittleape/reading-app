<template>
	<view>
		<web-view :webview-styles="webviewStyles" :src="uri"></web-view>
	</view>
</template>

<script>
	var wv;
	export default {
		data() {
			return {
				uri:'',
				webviewStyles: {
                    progress: {
                        color: '#FF3333'
                    }
                }
			}
		},
		onLoad: function (option) {
			console.log(option.uri)
			this.uri = option.uri
			// this.openurl()
		},
		onReady() {
		    // #ifdef APP-PLUS
			var webView = this.$mp.page.$getAppWebview();  
			webView.setTitleNViewButtonStyle(0,{
			    width: '0'  
			});
		    var currentWebview = this.$scope.$getAppWebview() //此对象相当于html5plus里的plus.webview.currentWebview()。在uni-app里vue页面直接使用plus.webview.currentWebview()无效
		    setTimeout(function() {
		        wv = currentWebview.children()[0]
				// 拦截所有页面跳转，可使用参数拦截weibo.com域名之外的跳转（{mode:'allow',match:'.*weibo\.com/.*'}）
		        wv.overrideUrlLoading({mode:'reject',match:'.*\.apk'}, function(e){
		            console.log('reject url: '+e.url);
					plus.runtime.openURL(e.url)
		        });
				// 监听页面开始加载
				// onclose: Webview窗口关闭事件
				// onerror: Webview窗口错误事件
				// onloaded: Webview窗口页面加载完成事件
				// onloading: Webview窗口页面开始加载事件
				wv.addEventListener('loading', function() {
					// 查询Webview窗口是否可后退
					wv.canBack( function(e){
						console.log('Back：'+e.canBack);
						if(e.canBack){
							webView.setTitleNViewButtonStyle(0,{
							    width: '44'  
							});
						}else{
							webView.setTitleNViewButtonStyle(0,{
							    width: '0'  
							});
						}
					});
				}, false);
				
		    }, 1000); //如果是页面初始化调用时，需要延时一下
		    // #endif
		},
		onNavigationBarButtonTap(e) {
			this.$scope.$getAppWebview().children()[0].close()    
			setTimeout(() => {    
			    uni.navigateBack()    
			}, 100)    
		},
		methods: {
			
		}
	}
</script>

<style>

</style>
