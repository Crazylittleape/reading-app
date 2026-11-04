<template>
	<view>
		
		<u-navbar
		    :safeAreaInsetTop="true"
			:placeholder="false"
			:border="false"
			:fixed="true"
			:bgColor="nav_color"
		>
		    <view
		        class=""
				style="display: flex;"
		        slot="left"
		    >
		        <u-tabs lineWidth="0" :list="tabs" @click="click_tab" :inactiveStyle="inactiveStyle" :activeStyle="activeStyle"></u-tabs>
		    </view>
			<view
			    class=""
				style="display: flex;"
			    slot="right"
			>
			    <u-search placeholder="搜搜漫画名或者作者名" height="60rpx" :placeholderColor="search_color" :searchIconColor="search_color" :bgColor="search_bgColor" :disabled="true" :show-action="false" @click="search()"></u-search>
			</view>
		</u-navbar>
		
		
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
		
		<view style="margin: 30rpx 30rpx 30rpx 30rpx;" v-if="isLogin">
			<u-image src="/static/image/mkz_pic_index_xyhfl.png" width="100%" height="50px" radius="8" @click="toLogin"></u-image>
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
				<u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="chakangengduo(itemm.id)"></u-icon>
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
				<u-icon label="查看更多" labelPos="left" labelSize="12px" name="arrow-right" @click="chakangengduo(itemm.id)"></u-icon>
			</view>
			<view>
				<u-image :src="hits_one.pic" width="100%" height="150px" radius="5" :lazy-load="true">
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
		<u-divider text="没有更多了"></u-divider>
		<app-update ref="app_update" :tabbar="true"></app-update>
	</view>
</template>

<script>
	import appUpdate from "@/components/yzhua006-update/app-update.vue"
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
            w_md5,
			appUpdate
        },
		data() {
			return {
				isLogin: true,
				nav_color: "rgba(0,0,0,0)",
				search_bgColor: "rgba(0,0,0,0.2)",
				search_color: "#fff",
				inactiveStyle: {color:'#ffffff'},
				activeStyle: {color:'#ffffff','font-size':'18px','font-weight':'500'},
				tabs: [
					{
						name:"推荐"
					},
					{
						name:"更新"
					},
				],
				grid_list: [
					{
						icon:'/static/menu/mkz_ic_home_ph.png',
						name:'排行榜'
					},
					{
						icon:'/static/menu/mkz_ic_home_vip.png',
						name:'VIP专区'
					},
					{
						icon:'/static/menu/mkz_ic_home_novel.png',
						name:'小说'
					},
					{
						icon:'/static/menu/mkz_ic_home_sd.png',
						name:'书单'
					},
					{
						icon:'/static/menu/mkz_ic_home_sc.png',
						name:'商城'
					}
				],
				gj_loading: true,
				lunbo_loading: true,
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				statusHeight: 0,
				hits_list: [],
				hits_one: {},
				lunbo_list: [],
				new_list: [],
				jx_list: [], //APP精选必看总列表
				jx_data: [], //APP精选必看内容,显示6个
			}
		},
		onPullDownRefresh() {
			this.index_data()
		},
		onLoad() {
			this.statusHeight = uni.getSystemInfoSync().statusBarHeight
			this.req_index()
		},
		onReady() {
			// this.$refs.app_update.update();
		},
		onPageScroll : function(e) { //nvue暂不支持滚动监听，可用bindingx代替
			if(e.scrollTop>28){
				this.nav_color = "#fff"
				this.inactiveStyle.color = "#000000"
				this.activeStyle.color = "#000000"
				this.search_bgColor = "#f7f7f7"
				this.search_color = "#6f6f6f"
			}else{
				this.nav_color = "rgba(0,0,0,0)"
				this.inactiveStyle.color = "#ffffff"
				this.activeStyle.color = "#ffffff"
				this.search_bgColor = "rgba(0,0,0,0.2)"
				this.search_color = "#fff"
			}
		},
		onShow() {
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
				this.isLogin = true
			}else{
				console.log("已登录",uni.getStorageSync('user'))
				this.isLogin = false
			}
		},
		methods: {
			toLogin(){
				uni.navigateTo({
					url:'../login/login'
				})
			},
			click_tab(item) {
                console.log('item', item);
            },
			chakangengduo(id){
				uni.navigateTo({
					url:'../class/class?id=' + id
				})
			},
			imageError: function(e, index) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
			    this.new_list[index]['cover_url'] = '../../static/img_error.jpg'
			},
			req_index(){
				var _this = this
				uni.getSystemInfo({
					success: function(res) {
						getApp().globalData.facility = res.platform //ios  or android
						console.log('手机系统信息', getApp().globalData.facility);
					}
				});
				if(getApp().globalData.facility=='ios'){
					plus.device.getInfo({
						success: function(e) {
							getApp().globalData.deviceid = e.uuid;
							console.log(getApp().globalData.deviceid)
							_this.index_data()
							_this.$refs.app_update.update();
						},
						fail: function(e) {
							console.log(e);
						}
					});
				}else{
					plus.device.getAAID({
						success: function(e) {
							getApp().globalData.deviceid = e.aaid;
							console.log(getApp().globalData.deviceid);
							_this.index_data()
							_this.$refs.app_update.update();
						},
						fail: function(e) {
							console.log(e);
							plus.device.getInfo({
								success: (res) => {
									getApp().globalData.deviceid = res.uuid;
									console.log(getApp().globalData.deviceid);
									_this.index_data()
									_this.$refs.app_update.update();
								}
							})
						}
					});
				}
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
				console.log(index)
				if(index==0){
					uni.navigateTo({
						url:'../newest/newest'
					})
				}
				if(index==1){
					uni.navigateTo({
						url:'../ranking/ranking'
					})
				}
				if(index==2){
					uni.navigateTo({
						url:'../class/class'
					})
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
			index_data(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&reco_size=7&timestamp=" + time
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
						uni.stopPullDownRefresh()
					}
				})
			},
		}
	}
</script>

<style lang="scss">
	.lists{
		margin-top: 5px;
		width: 100%;
		margin-left: auto;
		margin-right: auto;
		display: flex;
		text-align: left;
		flex-direction: row;
		flex-wrap: wrap;
		justify-content: space-around;
		align-items: flex-start;
	}
	button::after { border: none }
	.i_new_title{
		font-size: 14px;
		font-weight: 300;
		color: #fff;
		display: -webkit-box;
		/* -webkit-box-orient: vertical; */
		/*! autoprefixer: off */
		-webkit-box-orient: vertical;
		/* autoprefixer: on */
		-webkit-line-clamp: 1;
		overflow: hidden;
	}
	
	.grid-text{
		font-size: 12px;
        /* color: #909399; */
        padding: 5rpx 0 20rpx 0rpx;
	}
	
	.scroll-list {
		@include flex(column);
	
		&__goods-item {
			margin-right: 10px;
	
			&__image {
				width: 80px;
				height: 110px;
				border-radius: 8px;
			}
	
			&__text {
				color: #f56c6c;
				text-align: center;
				font-size: 12px;
				margin-top: 5px;
			}
		}
	
		&__show-more {
			background-color: #fff0f0;
			border-radius: 3px;
			padding: 3px 6px;
			@include flex(column);
			align-items: center;
	
			&__text {
				font-size: 12px;
				width: 12px;
				color: #f56c6c;
				line-height: 16px;
			}
		}
	}
</style>
