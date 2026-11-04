<template>
	<view>
		
		<u-navbar
		    :title="chapter_name"
		    :autoBack="true"
			v-if="menu_show"
		>
		</u-navbar>
		
		<!-- <view style="width: 100%;" :style="'height:' + pmHeight + 'px'" @click="menu_open()"> -->
		<mescroll-uni ref="mescrollRef" @init="mescrollInit" @down="downCallback" @up="upCallback" :down="downOption" :up="upOption" height="100%">
		<u-loading-page :loading="loading_status" imgStyle="width: 111px;height: 135px" loading-text="漫漫加载中" image="/static/loading.gif"></u-loading-page>
		<view @click="menu_open()">
		    <view v-for="(item,index) in chapter_content_list" :key="index" style="width: 100%;height: auto;">
		    	<u-image :src="img_websiteUrl + item.pic" width="100%" height="100%" mode="widthFix" :lazy-load="true" @error="imgerror(index)">
		    		<view slot="loading" style="font-size: 24px;width: 100%;"><image src="../../static/mh_loading.gif" style="width: 100%;" mode="aspectFit"></image></view>
		    	</u-image>
		    </view>
			<view :style="'height:' + pmHeight + 'px'" style="display: flex;align-items: center;" v-if="data.pay==1">
			    <view style="padding: 46rpx;width: 100%;">
			    	<view style="background-color: #f6f6f7;padding: 10px 0px;border-radius: 10px;">
			    		<view style="color: #999999;font-size: 28rpx;text-align: center;">
			    			{{data.name}}
			    		</view>
			    		<view style="color: #28292d;font-size: 30rpx;text-align: center;margin-top: 5px;">
			    			当前章节仅VIP会员可解锁阅读~
			    		</view>
			    	</view>
			    	<view style="width: 100%;margin-top: 30rpx;" @click.stop="$u.route('/pages/ucenter/vip/vip')">
			    		<image src="/static/image/ktvip.png" style="height: auto;width: 100%;" mode="widthFix"></image>
			    	</view>
			    	<!-- {{data}} -->
			    </view>
			</view>
			<view :style="'height:' + pmHeight + 'px'" style="display: flex;align-items: center;" v-if="data.pay==2">
			    <view style="padding: 46rpx;width: 100%;">
			    	<view style="background-color: #f6f6f7;padding: 10px 0px;border-radius: 10px;">
			    		<view style="color: #999999;font-size: 28rpx;text-align: center;">
			    			{{data.name}}
			    		</view>
			    		<view style="color: #28292d;font-size: 30rpx;text-align: center;margin-top: 5px;">
			    			当前章节是付费漫画，需要购买阅读哦~
			    		</view>
			    	</view>
			    	<view style="margin-top: 30rpx;display: flex;font-weight: 500;justify-content: center;">
			    		<view style="color: #28292d;">
			    			购买需支付：
			    		</view>
			    		<view style="color: #ff7830;">
			    			{{data.cion}}{{data.comic.cion_name}}
			    		</view>
			    	</view>
			    	<view style="margin-top: 30rpx;display: flex;justify-content: space-between;">
			    		<view style="font-size: 28rpx;color: #999999;">
			    			我的{{data.comic.cion_name}}：{{data.user.cion}}
			    		</view>
			    		<view style="display: flex;align-items: center;" @click.stop="autoBuy?autoBuy=false:autoBuy=true">
			    			<view v-if="autoBuy">
			    				<u-icon color="#ff620e" size="14" name="checkmark-circle-fill"></u-icon>
			    			</view>
			    			<view v-else>
			    				<u-icon color="#777777" size="14" name="checkmark-circle-fill"></u-icon>
			    			</view>
			    			<view style="font-size: 28rpx;color: #999999;">
			    				<text>自动购买下一章</text>
			    			</view>
			    		</view>
			    	</view>
			    	<view v-if="data.cion-0>data.user.cion-0" @click.stop="$u.route('/pages/ucenter/coin/coin')" style="background: linear-gradient(to right, #ff8441, #ff534a);border-radius: 50px;height: 80rpx;line-height: 80rpx;text-align: center;width: 100%;margin-top: 30rpx;color: #fff;">
			    		<text>{{data.comic.cion_name}}不足，去充值</text>
			    	</view>
			    	<view @click.stop="buy_chapter()" style="width: 100%;margin-top: 30rpx;color: #fff;background: linear-gradient(to right, #ff8441, #ff534a);border-radius: 50px;height: 80rpx;line-height: 80rpx;text-align: center;" v-else>
			    		<text>立即购买</text>
			    	</view>
			    	<!-- {{data}} -->
			    </view>
			</view>
			<view style="font-size: 28rpx;width: 100%;margin: 5px auto;display: flex;justify-content: center;" v-if="next-0==0">
				主人，已经到底了哦~
			</view>
		</view>
		</mescroll-uni>
		<!-- </view> -->
		
		<!-- 弹出菜单 -->
		<!-- <u-overlay :show="menu_show" :duration="400" :z-index ="999" :opacity="0.3"></u-overlay> -->
		<u-popup :show="menu_show" :overlay="true" :round="3" mode="buttom" class="xuanfu" @close="menu_open()">
			<view style="z-index: 999999;background-color: #fff;">
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
			</view>
		</u-popup>
		
		<u-popup :show="chapter_list_show" :round="6" mode="right" class="list_xuanfu" @close="list_close">
			<view style="position: fixed;top: 0;bottom: 0;left: 30%;right: 0;">
			    <scroll-view scroll-y="true" :style="'height:' + pmHeight + 'px'" style="background-color: #fff;width: 100%;">
			    	<view :style="'height:' + statusHeight + 'px'" style="position: fixed;top: 0;background-color: #fff;left: 30%;right: 0;z-index: 99999;"></view>
			    	<view style="position: fixed;padding: 20rpx 20rpx;background-color: #fff;left: 30%;right: 0;z-index: 99999;" :style="'top:' + statusHeight + 'px'">
			    	    <view style="padding: 10px 6px;background-color: #f6f6f7;border-radius: 6px;display: flex;justify-content: space-between;">
			    	    	<view style="display: flex;align-items: center;">
			    	    		<view style="font-weight: 500;font-size: 32rpx;">
			    	    			<text>目录</text>
			    	    		</view>
			    	    		<view style="color: #3b3c40;font-size: 28rpx;margin-left: 3px;">
			    	    			<text> 共{{chapter_list.length}}话</text>
			    	    		</view>
			    	    	</view>
			    	    	<view style="display: flex;align-items: center;" @click="c_sort()">
			    	    		<view style="margin-right: 2px;">
			    	    			<image :src="chapter_sort==1?'/static/image/mkz_ic_read_sort_down.png':'/static/image/mkz_ic_read_sort_up.png'" style="height: 23rpx;width: 27rpx;"></image>
			    	    		</view>
			    	    		<view style="color: #3b3c40;font-size: 27rpx;">
			    	    			<text>{{chapter_sort==1?'倒叙':'正序'}}</text>
			    	    		</view>
			    	    	</view>
			    	    </view>
			    	</view>
			    	<view :style="'margin-top:' + statusHeight + 'px'" style="margin-bottom: 40rpx;">
			    		<view style="padding: 10px 5px;background-color: #f6f6f7;border-radius: 8px;display: flex;justify-content: space-between;">
			    			<view style="font-weight: 500;font-size: 32rpx;">
			    				<text>连载中</text>
			    			</view>
			    		</view>
			    	</view>
			    	<view style="padding:25rpx 20rpx;font-size:30rpx;color:#3b3c40;display: flex;align-items: center;" v-for="(item,index) in chapter_list" @click="read_chapter(item.id)">
						<view style="display:flex;margin-top: auto;margin-bottom: auto;" v-if="item.cion!=='0'">
							<image src="/static/image/mkz_ic_readlist_wz_ff.png" style="width: 30rpx;height: 30rpx;" lazy-load></image>
						</view>
						<view style="display:flex;margin-top: auto;margin-bottom: auto;" v-else-if="item.vip!=='0'">
							<image src="/static/detail/mkz_ic_readlist_vip.png" style="width: 56rpx;height: 28rpx;" lazy-load></image>
						</view>
			    		<view style="display:flex;margin-top: auto;margin-bottom: auto;margin-left: 3px;">
							<text>{{item.name}}</text>
						</view>
			    	</view>
			    </scroll-view>
			</view>
		</u-popup>
		
		<u-popup :show="login_show" :round="10" mode="center" :closeOnClickOverlay="false" :customStyle="{'width':'80%'}">
			<view style="padding: 15px;">
				<view style="font-size: 30rpx;color: #aaaaaa;margin-bottom: 15px;text-align: center;">
					付费章节，请登陆后购买！
				</view>
				<view style="display: flex;justify-content: space-between;">
					<view style="background-color: #eaeaea;display: flex;justify-content: center;padding: 8px;width: 40%;border-radius: 50px;" @click="back()">
						<text style="font-size: 32rpx;color: #000;">返回</text>
					</view>
					<view style="background-color: #4e98f0;display: flex;justify-content: center;padding: 8px;width: 40%;border-radius: 50px;" @click="login()">
						<text style="font-size: 32rpx;color: #fff;">去登录</text>
					</view>
				</view>
			</view>
		</u-popup>
		
	</view>
</template>

<script>
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	export default {
		mixins: [MescrollMixin], // 使用mixin
		components: {
	        w_md5
	    },
		data() {
			return {
				login_show: false, //是否需要登录查看
				autoBuy: false,
				data: {},
				pmHeight: 0,
				statusHeight: 0,
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
				read_chapter_type: 0, //0=覆盖内容列表，1=合并内容数组列表
				chapter_sort: 0, //0=正序，1=倒叙
				chapter_list: [],
				chapter_content_list: [], //章节内容列表
				downOption: {
					use: true,
					auto: false, // 是否在初始化完毕之后自动执行下拉刷新的回调; 默认true
					beforeEndDelay: 0, // 延时结束的时长 (显示加载成功/失败的时长, android小程序设置此项结束下拉会卡顿, 配置后请注意测试)
					offset: 60, // 在列表顶部,下拉大于80upx,松手即可触发下拉刷新的回调
					bgColor: "#fff", // 背景颜色 (建议在pages.json中再设置一下backgroundColorTop)
					textColor: "gray", // 文本颜色 (当bgColor配置了颜色,而textColor未配置时,则textColor会默认为白色)
					textInOffset: '下拉获取上一话', // 下拉的距离在offset范围内的提示文本
					textOutOffset: '放开手就看到上一话了~', // 下拉的距离大于offset范围的提示文本
					textLoading: '正在拉取上一话呢~' ,// 加载中的提示文本
					textSuccess: '',
					textErr: '已经是第一话了',
					beforeEndDelay: 1000,
				},
				upOption: {
					use: true, // 是否启用上拉加载; 默认true
					auto: false, // 是否在初始化完毕之后自动执行上拉加载的回调; 默认true
					page: {
						num: 0, // 当前页码,默认0,回调之前会加1,即callback(page)会从1开始
						size: 1, // 每页数据的数量
						time: null // 加载第一页数据服务器返回的时间; 防止用户翻页时,后台新增了数据从而导致下一页数据重复;
					},
					noMoreSize: 6, // 如果列表已无数据,可设置列表的总数量要大于等于5条才显示无更多数据;避免列表数据过少(比如只有一条数据),显示无更多数据会不好看
					bgColor: "transparent", // 背景颜色 (建议在pages.json中再设置一下backgroundColorTop)
					textColor: "gray", // 文本颜色 (当bgColor配置了颜色,而textColor未配置时,则textColor会默认为白色)
					textLoading: '正在拉取下一话呢~', // 加载中的提示文本
					textNoMore: '主人，已经到底了哦~', // 没有更多数据的提示文本
					toTop: {
						src: '' //为空禁用回到顶部按钮
					},
					empty: {
						use: false, // 是否显示空布局
						icon: "https://www.mescroll.com/img/mescroll-empty.png", // 图标路径
						tip: '~ 暂无相关数据 ~', // 提示
						btnText: '按钮', // 按钮
						fixed: false, // 是否使用fixed定位,默认false; 配置fixed为true,以下的top和zIndex才生效 (transform会使fixed失效,最终会降级为absolute)
						top: "100rpx", // fixed定位的top值 (完整的单位值,如 "10%"; "100rpx")
						zIndex: 99 // fixed定位z-index值
					},
				}
				// downOption: {
				// 	use : true
				// },
				// upOption: {
				// 	use : false
				// }
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
			this.statusHeight = uni.getSystemInfoSync().statusBarHeight
			this.loading_status = true
			this.get_chapter_list()
			this.book_img = getApp().globalData.book_img
			
			if(!uni.getStorageSync('autoBuy')){
				uni.setStorageSync('autoBuy', false)
			}
			this.autoBuy = uni.getStorageSync('autoBuy')
			console.log("是否自动购买章节",this.autoBuy)
		},
		onUnload() {
			this.set_mh_history()
			plus.navigator.setFullscreen(false);
		},
		onHide() {
			this.set_mh_history()
		},
		onPageScroll() {
			this.menu_show = false
		},
		// onReachBottom() {
		// 	// setTimeout(() => {
		// 	//  	this.menu_show = true
		// 	// }, 500);
		// 	this.next_click()
		// },
		onShow() {
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
			}else{
				console.log("已登录",uni.getStorageSync('user'))
				if(this.login_show){
					this.login_show = false
					this.get_chapter_content()
				}
			}
			plus.navigator.setFullscreen(true);
		},
		methods: {
			// 返回
			back(){
				uni.navigateBack({
					
				})
			},
			// 登录
			login(){
				uni.navigateTo({
					url: '../login/login'
				})
			},
			// 章节列表排序
			c_sort(){
				if(this.chapter_sort==0){
					this.chapter_list.reverse()
					this.chapter_sort = 1
				}else{
					this.chapter_list.reverse()
					this.chapter_sort = 0
				}
			},
			imgerror(index){
				this.chapter_content_list[index].img_url = "../../static/error.png"
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
					plus.navigator.setFullscreen(true);
				}else{
					this.menu_show = true
					plus.navigator.setFullscreen(false);
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
						console.log("章节目录",res.data.list)
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
						// uni.showModal({
						// 	content:this.chapter_content_list.length + ''
						// })
						
						uni.setNavigationBarTitle({
						  title: res.data.data.name
						})
						this.data = res.data.data
						this.chapter_name = res.data.data.name
						this.book_name = res.data.data.comic.name
						
						this.prev = res.data.data.szid
						this.next = res.data.data.xzid
						this.loading_status = false
						
						if(res.data.data.comic.fav==1){
							this.isfavor_status = true
						}else{
							this.isfavor_status = false
						}
						
						if(this.read_chapter_type==0){
							this.chapter_content_list = res.data.data.piclist
						}else{
							if(this.chapter_content_list.length>100){
								this.mescroll.scrollTo( 0, 0 )
								this.chapter_content_list = res.data.data.piclist
							}else{
								this.chapter_content_list = this.chapter_content_list.concat(res.data.data.piclist)
							}
						}
						
						this.mescroll.endSuccess(res.data.data.piclist.length);
						
						if(this.data.pay==-1){
							this.login_show = true
						}
						
						if(this.data.pay==2){
							if(this.autoBuy){
								this.buy_chapter()
							}
						}
						
				    }
				});
			},
			// 购买章节
			buy_chapter(){
				uni.setStorageSync('autoBuy', this.autoBuy)
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "auto=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.book_id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken + "&zid=" + this.id
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/buy?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("章节购买结果：",res.data)
						if(res.data.code==1){
							uni.showToast({
								icon:"none",
								position:"bottom",
								title:res.data.msg
							})
							this.read_chapter_type = 1
							this.get_chapter_content()
						}else{
							uni.showToast({
								icon:"none",
								position:"bottom",
								title:res.data.msg
							})
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
				plus.navigator.setFullscreen(true);
				this.menu_show = false
				//主动触发下拉刷新
				this.mescroll.triggerDownScroll()
			},
			next_click(){
				plus.navigator.setFullscreen(true);
				this.menu_show = false
				if(this.next-0!==0){
					// this.loading_status = true
					this.read_chapter(this.next)
				}else{
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有下一话了"
					});
				}
				//主动触发上拉加载
				// this.mescroll.triggerUpScroll()
			},
			list_click(){
				this.menu_show = false
				this.chapter_list_show = true
			},
			list_close(){
				this.chapter_list_show = false
				plus.navigator.setFullscreen(true);
			},
			read_chapter(id){
				this.id = id
				// this.chapter_content_list = []
				this.chapter_list_show = false
				this.loading_status = true
				this.read_chapter_type = 0
				this.mescroll.scrollTo( 0, 0 )
				this.get_chapter_content()
				//主动触发下拉刷新
				// this.mescroll.triggerDownScroll()
			},
			/*下拉刷新的回调 */
			downCallback() {
				if(this.prev-0!==0){
					this.loading_status = true
					this.id = this.prev
					// this.mescroll.resetUpScroll()
					this.read_chapter_type = 0
					this.get_chapter_content()
				}else{
					this.mescroll.optDown.textErr = "已经是第一页了"
					this.mescroll.endErr()
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有上一话了"
					});
				}
				
			},
			/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
			upCallback(page) {
				if(this.next-0!==0){
					// this.loading_status = true
					this.id = this.next
					this.read_chapter_type = 1
					this.get_chapter_content()
					this.menu_show = false
				}else{
					this.mescroll.optUp.textNoMore = "主人，已经到底了哦~"
					this.mescroll.endErr()
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有下一话了"
					});
				}
			}
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
