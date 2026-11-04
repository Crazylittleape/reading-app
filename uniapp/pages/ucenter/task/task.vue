<template>
	<view>
		<u-navbar
            title="任务中心"
            :autoBack="true"
			:bgColor="navBgColor"
			:leftIconColor="navColor"
			:titleStyle="{'color':navColor,'font-weight':'500'}"
        >
        </u-navbar>
		<view style="height: 450rpx;width: 100%;">
			<image src="/static/ucenter/mkz_ic_my_task_center_top_image_bg.png" style="height: 100%;width: 100%;" mode="bottom"></image>
		</view>
		<view class="a" style="margin: 0 30rpx;background-color: #fff;border-radius: 10px;padding: 10rpx 26rpx;position: absolute;left: 0;right: 0;" :style="'top:'+status_height">
			<view style="display: flex;justify-content: space-between;margin-top: 15px;margin-bottom: 15px;" v-for="(item,index) in data" :key="index">
				<view>
					<view style="font-size: 36rpx;">
						<text>{{item.name}}</text>
					</view>
					<view style="font-size: 30rpx;color: #999999;">
						<text v-if="item.cion!=='0'">+{{item.cion}}金币</text>
						<text v-if="item.vip!=='0'">+{{item.vip}}天会员</text>
					</view>
				</view>
				<view @click="taskTo(item.id)" v-if="item.init==0" style="border: 1px solid #ff620e;border-radius: 50px 50px 50px 15px;color: #ff620e;padding: 2px 10px;display:flex;margin: auto 0 auto auto;">
					{{item.id==1?'立即签到':item.id==2?'去邀请':item.daynum==0?'去完成':item.daynum==1?'去完成':'去完成'+item.nums+'/'+item.daynum}}
				</view>
				<view v-else style="border: 1px solid #999999;border-radius: 50px 50px 50px 15px;color: #999999;padding: 2px 10px;display:flex;margin: auto 0 auto auto;">
					已完成
				</view>
			</view>
		</view>
		
		<view style="margin: 15px 15px 15px 15px;background-color: #fff;border-radius: 10px;position: absolute;left: 0;right: 0;" :style="'top:'+task_top+'px'">
			<view style="display: flex;justify-content: space-between;margin: 10px auto 10px 10px;">
				<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">今日热漫</text>
			</view>
			<u-scroll-list :indicator="false">
			    <view style="margin-left: 10px;display: flex;">
					<view v-for="(item, index) in love_list" :key="index" @click="detail(item.id)">
						<view style="position: relative;">
						    <u-image width="105px" height="140px" radius="5" :src="item.pic" style="margin-right: 10px;margin-bottom: 8px;">
						    	<view slot="error" style="font-size: 24rpx;">加载失败</view>
						    </u-image>
							<!-- <view style="position: absolute;bottom: 3px;left: 3px; z-index: 2;text-shadow: 0px 4px 5px #000;">
								<u--text :lines="1" :text="item.addtime" size="11" color="#ffffff"></u--text>
							</view> -->
						</view>
						<u--text :lines="1" :text="item.name" size="14" color="#444444"></u--text>
					</view>
				</view>
			</u-scroll-list>
		</view>
	</view>
</template>

<script>
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
		    w_md5
		},
		data() {
			return {
				data: [],
				app_key: '',
				status_height: 0,
				love_list: [],
				task_top: 500,
				navColor: '#fff',
				navBgColor: 'rgba(0,0,0,0)'
			}
		},
		onLoad() {
			this.get_tasklist()
			this.get_love_list()
			this.status_height = uni.getSystemInfoSync().statusBarHeight+48+'px'
		},
		onPageScroll : function(e) {
		    if (e.scrollTop >= 10 ) {
				this.navBgColor = "#fff"
				this.navColor = "#000"
			}else{
				this.navBgColor = "rgba(0,0,0,0)"
				this.navColor = "#fff"
			}
		},
		methods: {
			yuansu(){
				let _this = this;
				uni.getSystemInfo({
				  success: function (res) { // res - 各种参数
				
				    let obj = uni.createSelectorQuery().select('.a')
				
				    obj.boundingClientRect(function (data) { // data - 各种参数
				      
				      console.log(data)
					  _this.task_top = data.bottom
					  console.log(_this.task_top)
				
				    }).exec()
				  }
				})
			},
			taskTo(id){
				if(id=='1'){
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&tid=" + id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/task/add?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							console.log("任务结果：",res.data)
							if(res.data.code==1){
								uni.showToast({
									icon:"success",
									title:res.data.msg
								})
								this.get_tasklist()
							}else{
								uni.showToast({
									icon:"none",
									position:"bottom",
									title:"数据请求失败"
								})
							}
					    }
					});
				}else{
					uni.switchTab({
						url: '/pages/index/index'
					})
				}
			},
			get_tasklist(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/task/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("任务列表：",res.data)
						if(res.data.code==1){
							this.data = res.data.list
							setTimeout(() => {
							    this.yuansu()
							}, 800);
						}else{
							uni.showToast({
								icon:"none",
								position:"bottom",
								title:"数据请求失败"
							})
							uni.navigateBack({
								
							})
						}
				    }
				});
			},
			// 推荐热漫
			get_love_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=10&sort=rhits&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("今日推荐: ",res.data);
						if(res.data.code==1){
							this.love_list = res.data.list
						}else{
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}
					}
				})
			},
			detail(id){
				console.log(id)
				uni.navigateTo({
					url:'/pages/detail/detail?id=' + id
				})
			},
		}
	}
</script>

<style>
	page{
		background-color: #f6f6f7;
	}
</style>
