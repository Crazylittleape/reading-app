<template>
	<!-- 
	swiper中的transfrom会使fixed失效,此时用height固定高度; 
	swiper中无法触发mescroll-mixins.js的onPageScroll和onReachBottom方法,只能用mescroll-uni,不能用mescroll-body
	-->
	<!-- ref动态生成: 字节跳动小程序编辑器不支持一个页面存在相同的ref (如不考虑字节跳动小程序可固定值为 ref="mescrollRef") -->
	<!-- top的高度等于悬浮菜单tabs的高度 -->
	 <mescroll-uni @scroll="scroll" :ref="'mescrollRef'+i" @init="mescrollInit" :height="height" :down="downOption" @down="downCallback" :up="upOption" @up="upCallback" @emptyclick="emptyClick" :top="i==1?up_nav_height:0">
		<!-- 数据列表 -->
		<view v-if="i==0">
			<view style="margin-bottom: 30rpx;">
				<u-swiper
				    :list="lunbo_list"
					keyName="picx"
				    indicator
				    indicatorMode="line"
				    circular
					:loading="lunbo_loading"
					@click="lunbo_click"
					radius="0"
					height="260"
					imgMode=""
				></u-swiper>
			</view>
			
			<view style="margin: 30rpx 30rpx 30rpx 30rpx;">
				<u-image :src="utoken==''?'/static/image/mkz_pic_index_xyhfl.png':'/static/image/mkz_ic_mine_vip_right.png'" width="100%" height="50px" radius="8" @click="toLogin"></u-image>
			</view>
			
			<u-grid :col="5" :border="false">
				<u-grid-item  v-for="(item,index) in grid_list" :key="index" @click="grid_click(index)">
					<u-icon :name="item.icon" :size="40"></u-icon>
					<view class="grid-text">{{item.name}}</view>
				</u-grid-item>
			</u-grid>
			
			<view style="margin-left: 30rpx;margin-right: 30rpx;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 10px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">最近更新</text>
					<u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="$u.route('/pages/newest/newest')"></u-icon>
				</view>
				<view class="lists">
				    <view v-for="(item,index) in new_list" :key="index" style="width: 48%;margin-left: auto;margin-right: auto;" @click="get_detail(item.id)">
				    	
				    	<u-image width="100%" height="90px" radius="5" :src="item.pic" :lazy-load="true">
							<view slot="error" style="font-size: 24rpx;">加载失败</view>
						</u-image> 
				    		<!-- <text class="c_title">{{ item.name }}</text> -->
				    	<u--text :lines="1" :text="item.name" margin="10px 0 3px 0"></u--text>
						<u--text :lines="1" :text="item.text" margin="0 0 15px 0" size="12" color="#616161"></u--text>
				    	
				    	<!-- {{item}} -->
				    </view>
				</view>
			</view>
			
			<view style="margin-left: 30rpx;margin-right: 30rpx;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 10px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">推荐作品</text>
					<u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="chakangengduo()"></u-icon>
				</view>
				<view @click="get_detail(hits_one.id)">
					<u-image :src="hits_one.picx" width="100%" height="150px" radius="5" :lazy-load="true">
						<view slot="error" style="font-size: 24rpx;">加载失败</view>
					</u-image>
					<u--text :lines="1" :text="hits_one.name" margin="10px 0 3px 0"></u--text>
					<u--text :lines="1" :text="hits_one.text" margin="0 0 15px 0" size="12" color="#616161"></u--text>
				</view>
				<view class="lists">
				    <view v-for="(item,index) in hits_list" :key="index" style="width: 31%;margin-left: auto;margin-right: auto;" @click="get_detail(item.id)">
				    	
				    	<u-image width="100%" height="140px" radius="5" :src="item.pic" :lazy-load="true">
							<view slot="error" style="font-size: 24rpx;">加载失败</view>
						</u-image> 
				    		<!-- <text class="c_title">{{ item.name }}</text> -->
				    	<u--text :lines="1" :text="item.name" margin="10px 0 3px 0"></u--text>
						<u--text :lines="1" :text="item.text" margin="0 0 15px 0" size="12" color="#616161"></u--text>
				    	
				    	<!-- {{item}} -->
				    </view>
				</view>
			</view>
			
			
			<view style="margin-left: 30rpx;margin-right: 30rpx;" v-for="(itemm,i) in jx_data" :key="i">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 10px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">{{itemm.name}}</text>
					<u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="chakangengduo(itemm.id)"></u-icon>
				</view>
				<view class="lists">
				    <view v-for="(item,index) in itemm.comic" :key="index" style="width: 48%;margin-left: auto;margin-right: auto;" @click="get_detail(item.id)">
				    	
				    	<u-image width="100%" height="90px" radius="5" :src="item.pic" :lazy-load="true">
							<view slot="error" style="font-size: 24rpx;">加载失败</view>
						</u-image> 
				    		<!-- <text class="c_title">{{ item.name }}</text> -->
				    	<u--text :lines="1" :text="item.name" margin="10px 0 3px 0"></u--text>
						<u--text :lines="1" :text="item.text" margin="0 0 15px 0" size="12" color="#616161"></u--text>
				    	
				    	<!-- {{item}} -->
				    </view>
				</view>
				<!-- <view style="display: flex;justify-content: space-between;">
					<button @click="more_click()" style="color:#555555;font-size:12px; background-color:#F6F6F6;margin-left: 30rpx;margin-right: 10rpx;width: 100%;height:38px;display: flex;align-items: center;justify-content: center;">查看更多</button>
					<button @click="index_jx_suiji()" style="color:#555555;font-size:12px; background-color:#F6F6F6;margin-right: 30rpx;margin-left: 10rpx;width: 100%;height:38px;display: flex;align-items: center;justify-content: center;">换一换</button>
				</view> -->
			</view>
			
			<view style="margin-left: 30rpx;margin-right: 30rpx;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 10px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">猜你喜欢</text>
					<!-- <u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="chakangengduo(itemm.id)"></u-icon> -->
				</view>
				<view class="lists">
				    <view v-for="(item,index) in love_list" :key="index" style="width: 48%;margin-left: auto;margin-right: auto;margin-bottom: 8px;" @click="get_detail(item.id)">
				    	
						<view style="position: relative;">
				    	    <!-- <u-image width="100%" height="220px" radius="5" :src="item.pic" :lazy-load="true"> -->
						    <u-image width="100%" height="100%" mode='widthFix' radius="5" :src="item.pic" :lazy-load="true">
						    	<view slot="error" style="font-size: 24rpx;">加载失败</view>
						    </u-image> 
				    	    	<!-- <text class="c_title">{{ item.name }}</text> -->
							<view style="position: absolute;bottom: 8px;left: 5px; z-index: 2;">
								<!-- <view style="background-color: #e45656;border-radius: 50px;display: flex;justify-content: center;align-items: center;padding: 2px;"> -->
									<text style="color: #ffffff;font-size: 11px;background-color: rgba(255,85,0,0.8); padding: 2px 8px 2px 8px;border-radius: 50px;">{{item.hits}}人气</text>
								<!-- </view> -->
								<u--text :lines="1" :text="item.name" style="text-shadow: #000000 2px 2px 8px;margin-top: 3px;margin-bottom: 3px;" size="14" bold color="#ffffff"></u--text>
								<u--text :lines="1" :text="item.text" style="text-shadow: #000000 3px 4px 5px;" size="12" color="#ffffff"></u--text>
				    	        <!-- <u--text :lines="1" :text="item.name" margin="10px 0 3px 0"></u--text>
						        <u--text :lines="1" :text="item.text" margin="0 0 15px 0" size="12" color="#616161"></u--text> -->
							</view>
				    	</view>
				    	<!-- {{item}} -->
				    </view>
				</view>
			</view>
			
			<!-- <u-divider text="没有更多了"></u-divider> -->
		</view>
		
		<view v-if="i==1">
			<!-- <view :style="{height: up_nav_height}" v-if="up_nav_status"></view> -->
			<view v-for="(item,index) in update_data" :key="index" @click="get_detail(item.id)">
				<view style="display: flex;justify-content: space-between;">
					<view style="margin: 10px 0 10px 15px;">
						<view style="margin-bottom: 6px;">
							<text>{{item.name}}</text>
						</view>
						<view style="display: flex;">
						    <view style="background-color: #FDF6EC;border-radius: 3px;display: flex;justify-content: center;align-items: center;padding: 2px;">
						    	<text style="color: #e45656;font-size: 11px;margin: auto 3px auto 3px;">{{item.state}}</text>
						    </view>
						    <view style="background-color: #F3F4F6;border-radius: 3px;display: flex;justify-content: center;align-items: center;padding: 2px;margin-left: 5px;">
						    	<text style="color: #606266;font-size: 11px;margin: auto 3px auto 3px;">{{item.author}}</text>
						    </view>
						</view>
					</view>
					<view style="margin: auto 15px auto auto;display: flex;">
						<!-- <text style="font-size: 11px;color: #909399;">详情</text> -->
						<u-icon label="详情" labelPos="left" labelSize="11px" space="1px" labelColor="#909399" color="#909399" name="arrow-right" size="11"></u-icon>
					</view>
				</view>
				<view style="margin: 0 15px 10px 15px;">
					<u-image height="180px" radius="5px" width="100%" :src="item.picx"></u-image>
				</view>
				<view style="margin: 0 15px 10px 15px;display: flex;justify-content: space-between;">
					<view>
						<text style="font-size: 11px;color: #606266;">{{item.news_name}}</text>
					</view>
					<view style="margin: auto 0px auto auto;display: flex;">
						<u-icon color="#c0c4cc" name="eye-fill" size="14"></u-icon>
						<text style="font-size: 10px;color: #909399;margin-left: 3px;">{{item.hits}}</text>
					</view>
				</view>
				<view style="height: 5px;background-color: #f8f8f8;"></view>
			</view>
		</view>
		<u-no-network
			@disconnected="disconnected"
			@connected="connected"
			@retry="retry"
		></u-no-network>
	</mescroll-uni>
</template>

<script>
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	import MescrollMoreItemMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mixins/mescroll-more-item.js";
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
		    w_md5
		},
		mixins: [MescrollMixin,MescrollMoreItemMixin], // 注意此处还需使用MescrollMoreItemMixin (必须写在MescrollMixin后面)
		data() {
			return {
				downOption:{
					auto:true ,// 不自动加载 (mixin已处理第一个tab触发downCallback)
					inOffsetRate: 0.4,
					minAngle: 60
				},
				upOption:{
					auto:false, // 不自动加载
					onScroll:true,
					textNoMore: "没有更多了",
					// page: {
					// 	num: 0, // 当前页码,默认0,回调之前会加1,即callback(page)会从1开始
					// 	size: 10 // 每页数据的数量
					// },
					noMoreSize: 4, //如果列表已无数据,可设置列表的总数量要大于半页才显示无更多数据;避免列表数据过少(比如只有一条数据),显示无更多数据会不好看; 默认5
					empty:{
						use: false,
						tip: '~ 网络不佳 ~', // 提示
						btnText: '刷新'
					},
					toTop: {
						// 回到顶部按钮,需配置src才显示
						// src: "https://vkceyugu.cdn.bspapp.com/VKCEYUGU-fc0da29a-7f60-4d5e-92de-6d664018ab77/d27c3887-1994-46fc-b816-5b88d40493ac.png", // 图片路径
						src: "/static/image/mkz_pic_detail_nlhd.png",
						offset: 1000, // 列表滚动多少距离才显示回到顶部按钮,默认1000
						duration: 300, // 回到顶部的动画时长,默认300ms (当值为0或300则使用系统自带回到顶部,更流畅; 其他值则通过step模拟,部分机型可能不够流畅,所以非特殊情况不建议修改此项)
						zIndex: 9990, // fixed定位z-index值
						left: null, // 到左边的距离, 默认null. 此项有值时,right不生效. (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
						right: 20, // 到右边的距离, 默认20 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
						bottom: 120, // 到底部的距离, 默认120 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
						safearea: false, // bottom的偏移量是否加上底部安全区的距离, 默认false, 需要适配iPhoneX时使用 (具体的界面如果不配置此项,则取mescroll组件props的safearea值)
						width: 72, // 回到顶部图标的宽度, 默认72 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
						radius: "50%" // 圆角, 默认"50%" (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
					},
				},
				list: [1,2,3,4,5,6] ,//列表数据
				nav_color: "rgba(0,0,0,0)",
				search_bgColor: "rgba(0,0,0,0.2)",
				search_color: "#fff",
				inactiveStyle: {color:'#ffffff'},
				activeStyle: {color:'#ffffff','font-size':'18px','font-weight':'500'},
				grid_list: [
					{
						icon:'/static/menu/mkz_ic_home_ph.png',
						name:'排行榜'
					},
					{
						icon:'/static/menu/mkz_ic_home_vip.png',
						name:'会员中心'
					},
					{
						icon:'/static/menu/mkz_ic_home_novel.png',
						name:'随看'
					},
					{
						icon:'/static/menu/mkz_ic_home_sd.png',
						name:'分类'
					},
					{
						icon:'/static/menu/mkz_ic_home_sc.png',
						name:'充值'
					}
				],
				gj_loading: true,
				lunbo_loading: true,
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				statusHeight: 0,
				hits_list: [],
				hits_one: {},
				lunbo_list: [],
				new_list: [],
				jx_data: [], //APP精选必看内容,显示6个
				love_list: [],
				love_page_num: 1,
				today_page_num: 1,
				update_data: [],
				nums: 0, //数据量
			}
		},
		props:{
			i: Number, // 每个tab页的专属下标 (除了支付宝小程序必须在这里定义, 其他平台都可不用写, 因为已在MescrollMoreItemMixin定义)
			index: { // 当前tab的下标 (除了支付宝小程序必须在这里定义, 其他平台都可不用写, 因为已在MescrollMoreItemMixin定义)
				type: Number,
				default(){
					return 0
				}
			},
			tabs: { // 为了请求数据,演示用,可根据自己的项目判断是否要传
				type: Array,
				default(){
					return []
				}
			},
			height: [Number,String] ,// mescroll的高度
			up_nav_status:{
				type:Boolean,
				default:false
			},
			up_nav_height: {
				type:String,
				default:'80px'
			}
		},
		methods: {
			//"重试"后，无网络触发
			disconnected() {
				console.log('disconnected');
			},
			//"重试"后，有网络触发
			connected() {
				console.log('connected');
				this.mescroll.triggerDownScroll()
			},
			//用户点击页面的"重试"按钮时触发
			retry() {
				console.log('retry');
			},
			scroll(e){
				// this.mescroll.lockUpScroll(true)
				// console.log('当前滚动条的位置:' + e.scrollTop + ', 是否向上滑:'+e.isScrollUp)
				if(this.i==0){
					if(e.isScrollUp){
						if(e.scrollTop>28){
							this.$emit('myEvent',true)
						}
					}else{
						if(e.scrollTop<28){
							this.$emit('myEvent',false)
						}
					}
				}
			},
			click(){
				
			},
			toLogin(){
				this.utoken = uni.getStorageSync('user')
				if(this.utoken==''){
					uni.navigateTo({
						url:'../login/login'
					})
				}else{
					uni.navigateTo({
						url:'../ucenter/vip/vip'
					})
				}
				
			},
			click_tab(item) {
			    console.log('item', item);
			},
			chakangengduo(id){
				uni.switchTab({
					url:'../class/class?id=' + id
				})
			},
			imageError: function(e, index) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
			    this.new_list[index]['cover_url'] = '../../static/img_error.jpg'
			},
			search(){
				uni.navigateTo({
					url:"../search/search"
				})
			},
			more_click(){
				uni.navigateTo({
					url:"../class/class"
				})
			},
			grid_click(index){
				this.utoken = uni.getStorageSync('user')
				console.log(index)
				if(index==0){
					uni.navigateTo({
						url:'../ranking/ranking'
					})
				}
				if(index==1){
					if(this.utoken==''){
						uni.navigateTo({
							url:'../login/login'
						})
					}else{
						uni.navigateTo({
							url:'../ucenter/vip/vip'
						})
					}
				}
				if(index==2){
					this.get_detail(uni.$u.random(1, this.nums))
					// uni.navigateTo({
					// 	url:'../newest/newest'
					// })
				}
				if(index==3){
					uni.switchTab({
						url: '/pages/class/class'
					})
				}
				if(index==4){
					if(this.utoken==''){
						uni.navigateTo({
							url:'../login/login'
						})
					}else{
						uni.navigateTo({
							url:'../ucenter/coin/coin'
						})
					}
				}
			},
			lunbo_click(index){
				this.get_detail(this.lunbo_list[index].id)
			},
			get_detail(id){
				console.log(id)
				uni.navigateTo({
					url:'../detail/detail?id=' + id
				})
			},
			req_index(){
				this.utoken = uni.getStorageSync('user')
				var _this = this
				uni.getSystemInfo({
					success: function(res) {
						getApp().globalData.facility = res.platform //ios  or android
						getApp().globalData.deviceid = res.deviceId
						_this.index_data()
						_this.love_data()
						_this.$refs.app_update.update();
						console.log('手机系统信息', getApp().globalData.facility);
					}
				});
				// if(getApp().globalData.facility=='ios'){
				// 	plus.device.getInfo({
				// 		success: function(e) {
				// 			getApp().globalData.deviceid = e.uuid;
				// 			console.log(getApp().globalData.deviceid)
				// 			_this.index_data()
				// 			_this.love_data()
				// 			_this.$refs.app_update.update();
				// 		},
				// 		fail: function(e) {
				// 			console.log(e);
				// 		}
				// 	});
				// }else{
				// 	plus.device.getAAID({
				// 		success: function(e) {
				// 			getApp().globalData.deviceid = e.aaid;
				// 			console.log(getApp().globalData.deviceid);
				// 			_this.index_data()
				// 			_this.love_data()
				// 			_this.$refs.app_update.update();
				// 		},
				// 		fail: function(e) {
				// 			console.log(e);
				// 			plus.device.getInfo({
				// 				success: (res) => {
				// 					getApp().globalData.deviceid = res.uuid;
				// 					console.log(getApp().globalData.deviceid);
				// 					_this.index_data()
				// 					_this.love_data()
				// 					_this.$refs.app_update.update();
				// 				}
				// 			})
				// 		}
				// 	});
				// }
			},
			index_data(){
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&reco_size=7&timestamp=" + time
				// console.log("aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa",param)
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/index?" + param + "&sign=" + sign,
					success: (res) => {
						// console.log(res.data)
						this.hits_list = res.data.data.reco
						this.hits_one = this.hits_list[0]
						this.hits_list.shift()  //删除数组第一个元素
						this.lunbo_list = res.data.data.banner
						this.new_list = res.data.data.news
						this.jx_data = res.data.data.class
						this.lunbo_loading = false
						this.gj_loading = false
						// uni.stopPullDownRefresh()
						this.mescroll.endSuccess()
					}
				})
			},
			love_data(){
				this.love_page_num = 1
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.love_page_num + "&pay=0&size=10&sort=hits&state=0&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.love_list = res.data.list
						this.nums = res.data.nums
						// uni.stopPullDownRefresh()
						this.mescroll.endSuccess()
					}
				})
			},
			love_data_more(){
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.love_page_num + "&pay=0&size=10&sort=hits&state=0&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.love_list = this.love_list.concat(res.data.list)
						// uni.stopPullDownRefresh()
						this.mescroll.endSuccess(res.data.list.length)
					}
				})
			},
			today_data(){
				this.today_page_num = 1
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.today_page_num + "&pay=0&size=10&sort=addtime&state=0&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.update_data = res.data.list
						// uni.stopPullDownRefresh()
						this.mescroll.endSuccess()
					}
				})
			},
			today_data_more(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.today_page_num + "&pay=0&size=10&sort=addtime&state=0&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.update_data = this.update_data.concat(res.data.list)
						// uni.stopPullDownRefresh()
						this.mescroll.endSuccess(res.data.list.length)
					}
				})
			},
			/*下拉刷新的回调 */
			downCallback() {
				
				let _this = this;
				uni.getNetworkType({
					success: function (res) {
						console.log(res.networkType);
						if(res.networkType=="none"){
							_this.mescroll.endErr()
							return
						}
					}
				});
				
				this.utoken = uni.getStorageSync('user')
				if(getApp().globalData.deviceid==''){
					this.req_index()
					return
				}
				if(getApp().globalData.facility==''){
					this.req_index()
					return
				}else{
					// 这里加载你想下拉刷新的数据, 比如刷新轮播数据
					// loadSwiper();
					if(this.i==0){
						this.index_data()
						this.love_data()
					}else{
						// console.log("=========================")
						this.today_data()
					}
					// 下拉刷新的回调,默认重置上拉加载列表为第一页 (自动执行 page.num=1, 再触发upCallback方法 )
					this.mescroll.resetUpScroll()
				}
				
			},
			/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
			upCallback(page) {
				//联网加载数据
				if(this.i==0){
					this.love_page_num = this.love_page_num + 1
					this.love_data_more()
					// this.mescroll.endUpScroll(true)
					// this.index_data()
				}else{
					this.today_page_num = this.today_page_num + 1
					// console.log("=========================")
					this.today_data_more()
				}
			},
			//点击空布局按钮的回调
			emptyClick(){
				uni.showToast({
					title:'点击了按钮,具体逻辑自行实现'
				})
			}
		}
	}
</script>