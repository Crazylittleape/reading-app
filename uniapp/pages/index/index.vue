<template>
	<view>
		
		<u-navbar
		    :safeAreaInsetTop="true"
			:placeholder="placeholder"
			:border="false"
			:fixed="true"
			:bgColor="nav_color"
		>
		    <view
		        class=""
				style="display: flex;justify-content: flex-start;width: 100%;"
		        slot="left"
		    >
		        <u-tabs :current="tabIndex" lineWidth="0" lineHeight="0" :list="tabs" @click="click_tab" :inactiveStyle="inactiveStyle" :activeStyle="activeStyle"></u-tabs>
		    </view>
			<view
				style="display: flex;justify-content: flex-end;width: 100%;"
			    slot="right"
			>
			    <u-search :inputStyle="{'font-size':'13px'}" placeholder="搜搜漫画名或者作者名" height="60rpx" :placeholderColor="search_color" :searchIconColor="search_color" :bgColor="search_bgColor" :disabled="true" :show-action="false" @click="search()"></u-search>
			</view>
		</u-navbar>
		
		<swiper :style="{height: height}" :current="tabIndex" @change="swiperChange">
			<swiper-item v-for="(tab,i) in tabs" :key="i">
				<mescroll-item ref="mescrollItem" :i="i" :index="tabIndex" :tabs="tabs" @myEvent="getMsg" :up_nav_status="up_nav_status" :up_nav_height="statusHeight"></mescroll-item>
			</swiper-item>
		</swiper>
		
		<u-modal :show="notice_show" >
			<view class="slot-content">
				<rich-text :nodes="notice_data"></rich-text>
			</view>
			<view slot="confirmButton" style="border-radius: 50px;background-color: #ff5500;font-size: 14px;color: #fff;display: flex;justify-content: center;" @click="notice_show=false">
				<view style="margin: 12px auto;">
					确认
				</view>
			</view>
		</u-modal>
		
		<app-update ref="app_update" :tabbar="true"></app-update>
	</view>
</template>

<script>
	import MescrollItem from "./mescroll-swiper-item.vue";
	import appUpdate from "@/components/yzhua006-update/app-update.vue"
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
            w_md5,
			appUpdate,
			MescrollItem
        },
		data() {
			return {
				notice_show: false, //公告显示状态
				notice_data: "", //公告内容
				up_nav_status: true,
				placeholder: false, //自定义导航栏是否填充高度禁止穿透
				height: "600px", // 需要固定swiper的高度
				tabIndex: 0, // 当前tab的下标
				isLogin: true,
				nav_status: false,
				nav_color: "rgba(0,0,0,0)",
				search_bgColor: "rgba(245,245,245,0.3)",
				search_color: "#fff",
				inactiveStyle: {color:'#ffffff'},
				activeStyle: {color:'#ffffff','font-size':'20px','font-weight':'500'},
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
			// 需要固定swiper的高度
			this.height = uni.getSystemInfoSync().windowHeight + 'px'
			
			this.statusHeight = uni.getSystemInfoSync().statusBarHeight + 44 + 'px'
			// this.req_index()
			this.getNotice()
		},
		onReady() {
			this.$refs.app_update.update();
		},
		onPageScroll : function(e) { //nvue暂不支持滚动监听，可用bindingx代替
			// if(e.scrollTop>28){
			//  this.nav_color = "#fff"
			// 	this.inactiveStyle.color = "#000000"
			// 	this.activeStyle.color = "#000000"
			// 	this.search_bgColor = "#f7f7f7"
			// 	this.search_color = "#6f6f6f"
			// }else{
			// 	this.nav_color = "rgba(0,0,0,0)"
			// 	this.inactiveStyle.color = "#ffffff"
			// 	this.activeStyle.color = "#ffffff"
			// 	this.search_bgColor = "rgba(245,245,245,0.3)"
			// 	this.search_color = "#fff"
			// }
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
			getNotice(){
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/kxapps/notice",
					success: (res) => {
						console.log(res.data)
						this.notice_data = res.data.text
						if(parseInt(res.data.mode)==1){
							this.notice_show = true
						}
					}
				})
			},
			getMsg(res){
				if(res){
					this.nav_color = "#fff"
					this.inactiveStyle.color = "#000000"
					this.activeStyle.color = "#000000"
					this.search_bgColor = "#f7f7f7"
					this.search_color = "#6f6f6f"
					this.nav_status = true
				}else{
					this.nav_color = "rgba(0,0,0,0)"
					this.inactiveStyle.color = "#ffffff"
					this.activeStyle.color = "#ffffff"
					this.search_bgColor = "rgba(245,245,245,0.3)"
					this.search_color = "#fff"
					this.nav_status = false
				}
			},
			// 轮播菜单
			swiperChange(e){
				this.tabIndex = e.detail.current
				if(this.tabIndex==1){
					// this.up_nav_status = false
					// this.placeholder = true
					this.nav_color = "#fff"
					this.inactiveStyle.color = "#000000"
					this.activeStyle.color = "#000000"
					this.search_bgColor = "#f7f7f7"
					this.search_color = "#6f6f6f"
				}else{
					// this.up_nav_status = true
					// this.placeholder = false
					if(this.nav_status){
						this.nav_color = "#fff"
						this.inactiveStyle.color = "#000000"
						this.activeStyle.color = "#000000"
						this.search_bgColor = "#f7f7f7"
						this.search_color = "#6f6f6f"
						this.nav_status = true
					}else{
						this.nav_color = "rgba(0,0,0,0)"
						this.inactiveStyle.color = "#ffffff"
						this.activeStyle.color = "#ffffff"
						this.search_bgColor = "rgba(245,245,245,0.3)"
						this.search_color = "#fff"
						this.nav_status = false
					}
				}
			},
			toLogin(){
				uni.navigateTo({
					url:'../login/login'
				})
			},
			click_tab(item) {
				this.tabIndex = item.index
                // console.log('item', item);
				if(this.tabIndex==1){
					// this.placeholder = true
					this.nav_color = "#fff"
					this.inactiveStyle.color = "#000000"
					this.activeStyle.color = "#000000"
					this.search_bgColor = "#f7f7f7"
					this.search_color = "#6f6f6f"
				}else{
					// this.placeholder = false
					if(this.nav_status){
						this.nav_color = "#fff"
						this.inactiveStyle.color = "#000000"
						this.activeStyle.color = "#000000"
						this.search_bgColor = "#f7f7f7"
						this.search_color = "#6f6f6f"
						this.nav_status = true
					}else{
						this.nav_color = "rgba(0,0,0,0)"
						this.inactiveStyle.color = "#ffffff"
						this.activeStyle.color = "#ffffff"
						this.search_bgColor = "rgba(245,245,245,0.3)"
						this.search_color = "#fff"
						this.nav_status = false
					}
				}
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
