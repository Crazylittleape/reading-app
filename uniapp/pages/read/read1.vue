<template>
	<view>
		<view style="width: 100%;" :style="'height:' + pmHeight + 'px'" @click="menu_open()">
		<u-count-down
		    :time="adtime * 1000"
		    format="DD:HH:mm:ss"
		    autoStart
		    @change="adtimes"
			@finish="finish"
			style="height: 0px;width: 0px;font-size: 0px;"
		><view style="height: 0px;width: 0px;font-size: 0px;">
            <text>{{ timeData.days }}&nbsp;天</text>
            <text>{{ timeData.hours>10?timeData.hours:'0'+timeData.hours}}&nbsp;时</text>
            <text>{{ timeData.minutes }}&nbsp;分</text>
            <text>{{ timeData.seconds }}&nbsp;秒</text>
        </view></u-count-down>
		<u-toast ref="uToast"></u-toast>
		<u-loading-page :loading="loading_status" imgStyle="width: 111px;height: 135px" loading-text="漫漫加载中" image="/static/loading.gif"></u-loading-page>
		<view v-if="loading_status==false">
		<view v-for="(item,index) in chapter_content_list" :key="index" style="width: 100%;height: auto;">
			<u-image :src="img_websiteUrl + item.pic" width="100%" height="100%" mode="widthFix" :lazy-load="true" @error="imgerror(index)">
				<view slot="loading" style="font-size: 24px;width: 100%;"><image src="../../static/mh_loading.gif" style="width: 100%;" mode="aspectFit"></image></view>
				<!-- <view slot="error" style="font-size: 24px;width: 100%;"><image src="../../static/error.jpg" style="width: 50%;" mode="aspectFit"></image></view> -->
			</u-image>
			<!-- <image :src="img_websiteUrl + item.img_url"></image> -->
			<!-- {{item.img_url}} -->
		</view>
		</view>
		
		<!-- 弹出菜单 -->
		<u-popup :show="menu_show" :overlay="false" :round="3" mode="buttom" class="xuanfu">
			<u-grid :col="5" :border="false">
			    <u-grid-item @click="prev_click()">
			    	<u-icon name="play-left" :size="20" style="padding: 5px;"></u-icon>
			    	<view class="grid-text">上一话</view>
			    </u-grid-item>
				<u-grid-item @click="list_click()">
					<u-icon name="order" :size="20" style="padding: 5px;"></u-icon>
					<view class="grid-text">目录</view>
				</u-grid-item>
				<u-grid-item @click="get_detail()">
					<u-icon name="more-circle" :size="20" style="padding: 5px;"></u-icon>
					<view class="grid-text">详情</view>
				</u-grid-item>
				<u-grid-item v-if="isfavor_status" @click="isfavor">
					<u-icon name="heart-fill" :size="20" style="padding: 5px;"></u-icon>
					<view class="grid-text">取关</view>
				</u-grid-item>
				<u-grid-item v-else @click="isfavor">
					<u-icon name="heart" :size="20" style="padding: 5px;"></u-icon>
					<view class="grid-text">关注</view>
				</u-grid-item>
				<u-grid-item @click="next_click()">
					<u-icon name="play-right" :size="20" style="padding: 5px;"></u-icon>
					<view class="grid-text">下一话</view>
				</u-grid-item>
			</u-grid>
		</u-popup>
		
		<u-popup :show="chapter_list_show" :round="6" mode="top" class="list_xuanfu" :style="'height: ' + list_height + 'px'" @close="list_close">
            <mescroll-uni v-if="chapter_list_show" :height="list_height + 'px'" @init="mescrollInit" :down="downOption" :up="upOption" @down="downCallback">
            	<view style="padding:30rpx 30rpx;font-size:28rpx;font-weight: bold;color:#606266;" v-for="(item,index) in chapter_list" @click="read_chapter(item.id)">
            		{{item.name}}
            	</view>
            </mescroll-uni>
		</u-popup>
		
		</view>
	</view>
</template>

<script>
	import AD from "../../ad.js"
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	export default {
		mixins: [MescrollMixin], // 使用mixin
		components: {
	        w_md5
	    },
		data() {
			return {
				pmHeight: 0,
				timeData: {},
				adtime: 0,
				adtimee: 0,
				adstat: false,
				list_height: 0,
				isfavor_status: false,
				loading_status: false,
				menu_show: false,
				chapter_list_show: false,
				time: '',
				token: '',
				utoken: '',
				app_key: '',
				webUrl: '',
				websiteUrl: '',
				img_websiteUrl: '',
				book_id: 0,
				id: 0,  //章节id
				chapter_name: '',
				book_name: '',
				book_img: '',
				prev: 0, //上一话
				next: 0, //下一话
				chapter_list: [],
				chapter_content_list: [], //章节内容列表
				downOption: {
					use : true
				},
				upOption: {
					use : false
				}
			}
		},
		onLoad(option) {
			console.log(option.book_id)
			this.book_id = option.book_id
			console.log("章节ID：",option.id)
			if(option.id==undefined){
				console.log("没有指定章节ID")
				this.get_mh_history()
			}else{
				console.log("指定章节ID",option.id)
				this.id = option.id
			}
			this.pmHeight = uni.getSystemInfoSync().windowHeight
			this.adtime = uni.getStorageSync('mh_adtime')
			this.adtime = 9999999999999999999999
			this.loading_status = true
			this.get_chapter_list()
			
			if(this.adtime=="" && !Number.isFinite(this.adtime)){
				console.log("没有设置广告倒计时")
				this.get_adtime()
			}else{
				// this.get_adtime()
				console.log("广告倒计时：",this.adtime)
			}
		},
		onUnload() {
			this.set_mh_history()
			uni.setStorageSync('mh_adtime', this.adtimee)
		},
		onHide() {
			this.set_mh_history()
			uni.setStorageSync('mh_adtime', this.adtimee)
		},
		onPageScroll() {
			this.menu_show = false
			if(this.adtime==0){
				if(!this.adstat){
					this.adstat = true
					uni.showToast({
						icon:'none',
						title:"3秒后进入广告"
					})
					setTimeout(() => {
						this.showRewardedVideoAd()
					}, 3000);
				}
				
				// var _this = this
				// this.$refs.uToast.show({
				// 	message:'3秒后进入广告',
				// 	duration: 3000,
				// 	complete() {
				// 		_this.showRewardedVideoAd()
				// 	}
				// })
			}
		},
		onReachBottom() {
			setTimeout(() => {
			 	this.menu_show = true
			}, 500);
		},
		onShow() {
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
			}else{
				console.log("已登录",uni.getStorageSync('user'))
			}
		},
		methods: {
			imgerror(index){
				this.chapter_content_list[index].img_url = "../../static/error.png"
			},
			adtimes(e){
				this.timeData = e
				console.log("一秒",e)
				this.adtimee = (e.days * 86400) + (e.hours * 3600) + (e.minutes * 60) + e.seconds
				console.log(this.adtimee)
			},
			finish(){
				this.adtime = 0
				console.log("倒计时结束",this.adtime)
				// uni.showToast({
				// 	icon:'none',
				// 	title:"3秒后进入广告"
				// })
				// setTimeout(() => {
				// 	this.showRewardedVideoAd()
				// }, 3000);
			},
			get_adtime(){
				uni.request({
				    url: this.webUrl + '/adtime.txt', //仅为示例，并非真实接口地址。
				    success: (res) => {
						this.adstat = false
						console.log("自定义阅读时间: ",res.data);
						this.adtime = res.data
						uni.setStorageSync('mh_adtime', this.adtime)
				    }
				});
			},
			get_detail(){
				console.log(this.book_id)
				uni.navigateTo({
					url:'../detail/detail?id=' + this.book_id
				})
			},
			menu_open(){
				if(this.menu_show){
					this.menu_show = false
				}else{
					this.menu_show = true
				}
			},
			get_mh_history(){
				let mh_history = uni.getStorageSync('mh_history')
				if (typeof mh_history !== 'object') {
					mh_history = []
				}
				mh_history.forEach((value) => {
					if (value.book_id === this.book_id) {
						this.id = value.id
					}
				})
			},
			set_mh_history() {
				let mh_history = uni.getStorageSync('mh_history')
				if (typeof mh_history !== 'object') {
					mh_history = []
				}
				let have = false
				mh_history.forEach((value) => {
					if (value.book_id === this.book_id) {
						have = true
						value.chapter_name = this.chapter_name
						value.id = this.id
						value.book_name = this.book_name
						value.book_img = this.book_img
					}
				})
				if (!have) {
					mh_history.unshift({
						book_id: this.book_id,
						chapter_name: this.chapter_name,
						id: this.id,
						book_name: this.book_name,
						book_img: this.book_img
					})
				}
				uni.setStorageSync('mh_history', mh_history)
				console.log("保存阅读记录: ",mh_history)
			},
			get_chapter_list(){
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.book_id + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/chapter?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						this.loading_show = false
						console.log("章节目录",res.data)
						this.chapter_list = res.data.list
						if(this.chapter_list.length==0){
							uni.navigateBack({})
							uni.showToast({
								icon:'none',
								title:'章节还未更新'
							})
						}else{
							if(this.id!==0){
								this.get_chapter_content()
							}else{
								this.id = res.data.list[0].id
								console.log(this.id)
								this.get_chapter_content()
							}
						}
				    }
				});
			},
			get_chapter_content(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.book_id + "&timestamp=" + time + "&zid=" + this.id
				}else{
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.book_id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken + "&zid=" + this.id
				}
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/read?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("章节内容: ",res.data);
						// this.chapter_content_list = res.data.chapter.photos.reverse()
						this.chapter_content_list = res.data.data.piclist
						uni.setNavigationBarTitle({
						  title: res.data.data.name
						})
						this.chapter_name = res.data.data.name
						this.book_name = res.data.data.comic.name
						this.book_img = getApp().globalData.book_img
						this.prev = res.data.data.szid
						this.next = res.data.data.xzid
						this.loading_status = false
						
						if(res.data.data.comic.fav==1){
							this.isfavor_status = true
						}else{
							this.isfavor_status = false
						}
						
				    }
				});
			},
			isfavor(){
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					
					if(this.isfavor_status){
						console.log("已收藏在这里执行取消收藏")
						var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&ids=" + this.book_id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
						var sign = w_md5.hex_md5_32Upper(param + this.app_key)
						uni.request({
						    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/del/comic?' + param + "&sign=" + sign,
							header: {
							    'content-type': 'application/json' //自定义请求头信息
							},
						    success: (res) => {
								console.log("删除书架成功")
								this.isfavor_status = false
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: res.data.msg
								});
						    }
						});
					}else{
						var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.book_id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
						var sign = w_md5.hex_md5_32Upper(param + this.app_key)
						uni.request({
						    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/add?' + param + "&sign=" + sign,
							header: {
							    'content-type': 'application/json' //自定义请求头信息
							},
						    success: (res) => {
								console.log("加入书架成功")
								this.isfavor_status = true
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: res.data.msg
								});
						    }
						});
					}
				}
			},
			prev_click(){
				if(this.prev!==0){
					this.loading_status = true
					this.id = this.prev
					this.get_chapter_list()
					this.menu_show = false
				}else{
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有上一话了"
					});
				}
			},
			next_click(){
				if(this.next!==0){
					this.loading_status = true
					this.id = this.next
					this.get_chapter_list()
					this.menu_show = false
				}else{
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有下一话了"
					});
				}
			},
			list_click(){
				this.menu_show = false
				this.chapter_list_show = true
				this.list_height = 500
			},
			list_close(){
				this.list_height = 0
				this.chapter_list_show = false
			},
			// detail(){
			// 	uni.navigateBack({
					
			// 	})
			// },
			read_chapter(id){
				this.id = id
				this.loading_status = true
				this.get_chapter_content()
				this.list_height = 0
				this.chapter_list_show = false
			},
			showRewardedVideoAd() {
			  // 调用后会显示 loading 界面
			  AD.show({
			    adpid: getApp().globalData.sigmob, // HBuilder 基座测试广告位
			    adType: "RewardedVideo",
			    urlCallback: {// 服务器回调透传参数
			      userId: 'testuser',
			      extra: 'testdata'
			    }
			    //singleton: false // 设置此参数后，每次调用 show 方法将重新创建广告实例，预载将失效，如果广告回调每次要透传用户信息需要设置 false
			  }, (res) => {
			    // 用户点击了【关闭广告】按钮
			    if (res && res.isEnded) {
			      // 正常播放结束
			      console.log("onClose " + res.isEnded);
				  this.get_adtime()
			    } else {
					this.adstat = false
			      // 播放中途退出
			      console.log("onClose " + res.isEnded);
				  uni.showToast({
				  	icon:'none',
					title:'没有获得阅读时长'
				  })
			    }
			    // 在此处理服务器回调逻辑
			  }, (err) => {
				  this.adstat = false
			    // 广告加载错误
			    console.log(err)
				uni.showToast({
					icon:'none',
					title:'本次免广告'+err.code
				})
				this.adtime = 300
			  })
			},
		}
	}
</script>

<style>
	.xuanfu {
		width: 100%;
		height: 55px;
		position: fixed;
		bottom: 0px;
		/* background-color: #F0F0F0; */
		left: 0;
		right: 0;
		margin: auto;
	}
	
	.list_xuanfu {
		width: 100%;
		/* height: 500px; */
		position: fixed;
		top: 0px;
		/* bottom: 0px; */
		/* background-color: #F0F0F0; */
		left: 0;
		right: 0;
		margin: auto;
	}
	
	.grid-text{
		font-size: 12px;
	    /* color: #909399; */
	    padding: 0rpx 0 10px 0rpx;
	}
</style>
