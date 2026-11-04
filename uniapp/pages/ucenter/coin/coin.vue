<template>
	<view style="background-color: #fff;border-top: 1px solid #eee;color: #777;font-size: 28rpx;height: calc(100vh - 1px);">
		<view style="display: flex;height: 44rpx;justify-content: space-between;line-height: 44rpx;padding: 40rpx 30rpx 30rpx;">
			<view style="color: #111;font-size: 32rpx;font-weight: 600;">
				充值金额
			</view>
			<view style="color: #2796ff;" @click="guize()">
				查看《使用规则》
			</view>
		</view>
		<view style="background-color: #fff;display: flex;flex-wrap: wrap;justify-content: flex-start;padding: 20rpx 0rpx 0 30rpx;">
			<view class="boxItem" v-for="(item,index) in list" :key="index" @click="listIndex=index" :style="listIndex==index?'background: #fff8eb;border: 1px solid #ffcb8c;':''">
				<view>
					<text>{{item.text}}</text>
				</view>
				<view style="color: #8b4900;font-size: 28rpx;font-weight: 400;height: 50rpx;line-height: 50rpx;">
					<text>￥</text>
					<text style="font-weight: 600;font-size: 40rpx;">{{item.rmb}}</text>
				</view>
			</view>
		</view>
		<view class="tip">
			<!-- <view>
				可得{{userinfo.cion_name}}：
				<text style="color: #0a87f8;font-size: 32rpx;font-weight: 500;">
					{{list[listIndex]?list[listIndex].cion:0}}
				</text>
				个
			</view> -->
			<view>
				限时优惠：
				<text style="color: red;font-size: 38rpx;font-weight: 500;">
					{{list[listIndex]?list[listIndex].rmb/list[listIndex].cion*100:0}}
				</text>
				折
			</view>
			<view style="font-size: 26rpx;color:#666666;">
				≈ {{$u.priceFormat(list[listIndex]?list[listIndex].rmb/list[listIndex].cion:0, 3)}}元/{{userinfo.cion_name}}
			</view>
		</view>
		<view style="height:16rpx;background-color:rgba(246,247,248,1);"></view>
		<view class="payCard">
			<view class="">选择支付方式</view>
			<view style="margin-top: 24rpx;margin-right: 40rpx;">
				<view @click="data.is_alipay==1?pay_type=1:''" style="display: flex;height: 108rpx;justify-content: space-between;line-height: 108rpx;border-bottom: 1px solid #f4f4f4;">
					<view style="display: flex;align-items: center;">
						<!-- <image src="/static/ucenter/alipay.png" style="height: 48rpx;width: 48rpx;vertical-align: middle;"></image> -->
						<u-icon name="zhifubao-circle-fill" color="#1296db" size="24"></u-icon>
						<text style="font-size: 32rpx;vertical-align: middle;color:rgba(51, 51, 51, 1);margin-left:20rpx;">
							{{data.is_alipay==1?'支付宝':'支付宝(暂不支持)'}}
						</text>
					</view>
					<view style="display: flex;margin: auto 0 auto auto;">
						<image :src="pay_type==1?'/static/image/mkz_ic_pay_choose_on.png':'/static/image/mkz_ic_pay_choose_off.png'" style="width: 40rpx;height: 40rpx;"></image>
					</view>
				</view>
				<view @click="data.is_wxpay==1?pay_type=2:''" style="display: flex;height: 108rpx;justify-content: space-between;line-height: 108rpx;border-bottom: 1px solid #f4f4f4;">
					<view style="display: flex;align-items: center;">
						<!-- <image src="/static/ucenter/wechat.png" style="height: 48rpx;width: 48rpx;vertical-align: middle;"></image> -->
						<u-icon name="weixin-circle-fill" color="#28c445" size="24"></u-icon>
						<text style="font-size: 32rpx;vertical-align: middle;color:rgba(51, 51, 51, 1);margin-left:20rpx;">
							{{data.is_wxpay==1?'微信支付':'微信支付(暂不支持)'}}
						</text>
					</view>
					<view style="display: flex;margin: auto 0 auto auto;">
						<image :src="pay_type==2?'/static/image/mkz_ic_pay_choose_on.png':'/static/image/mkz_ic_pay_choose_off.png'" style="width: 40rpx;height: 40rpx;"></image>
					</view>
				</view>
				<view @click="data.is_qqpay==1?pay_type=3:''" style="display: flex;height: 108rpx;justify-content: space-between;line-height: 108rpx;border-bottom: 1px solid #f4f4f4;">
					<view style="display: flex;align-items: center;">
						<!-- <image src="/static/ucenter/wechat.png" style="height: 48rpx;width: 48rpx;vertical-align: middle;"></image> -->
						<u-icon name="qq-circle-fill" color="#000000" size="24"></u-icon>
						<text style="font-size: 32rpx;vertical-align: middle;color:rgba(51, 51, 51, 1);margin-left:20rpx;">
							{{data.is_qqpay==1?'QQ钱包':'QQ钱包(暂不支持)'}}
						</text>
					</view>
					<view style="display: flex;margin: auto 0 auto auto;">
						<image :src="pay_type==3?'/static/image/mkz_ic_pay_choose_on.png':'/static/image/mkz_ic_pay_choose_off.png'" style="width: 40rpx;height: 40rpx;"></image>
					</view>
				</view>
			</view>
		</view>
		<view style="position: fixed;bottom: 20rpx;width: 100%;">
			<view style="height:16rpx;background:#F6F7F8;"></view>
			<view @click="agree?agree=false:agree=true" style="background-color: #fff;height: 80rpx;line-height: 80rpx;padding: 0 30rpx;display: flex;color: #777;align-items: center;">
				<view v-if="agree">
					<u-icon color="#2796ff" size="14" name="checkmark-circle-fill"></u-icon>
				</view>
				<view v-else>
					<u-icon color="#777777" size="14" name="checkmark-circle"></u-icon>
				</view>
				<view style="margin-left:16rpx;">
					<text>勾选并同意</text>
				</view>
				<view style="color: #2796ff;" @tap="xieyi()">
					<text>《充值服务协议》</text>
				</view>
			</view>
			<view class="footer">
				<view style="color: #ffa036;font-size: 28rpx;height: 88rpx;line-height: 88rpx;text-align: left;">
					￥
					<text style="font-size: 48rpx;font-weight: 600;">{{list[listIndex]?list[listIndex].rmb:0}}</text>
				</view>
				<view style="width: 360rpx;color: #fff;" class="buyBtn" @click="buy()">
					立即支付
				</view>
			</view>
		</view>
		
		<u-popup mode="bottom" :show="pop_show" :round="16" :closeOnClickOverlay="false">
			<view style="display: flex;flex-direction: column;width: 100%;">
				<view style="display: flex;height: 100rpx;justify-content: space-between;line-height: 100rpx;padding: 0 30rpx 0 30rpx;">
					<view style="display: inline-block;font-size: 32rpx;font-weight: 500;color: #000;">
						<text>{{pop_title}}</text>
					</view>
					<view style="display: flex;margin: auto 0;" @click="pop_show=false">
						<u-icon color="#777777" size="16" name="close"></u-icon>
					</view>
				</view>
				<scroll-view style="padding: 0 30rpx;width: calc(100% - 60rpx);height: 70vh;" scroll-y="true">
					{{pop_content}}
				</scroll-view>
				<view style="height: 10px;"></view>
			</view>
		</u-popup>
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
				pop_show: false,
				list: [],
				listIndex: 0,
				pay_type: 1, //1=支付宝，2=微信，3=QQ
				agree: false,
				pop_title: "充值服务协议",
				pop_content: "",
				userinfo: "",
				data: {}
			}
		},
		onBackPress(e){
			console.log(e)
			if(e.from=='backbutton'){
				if(this.pop_show){
					this.pop_show = false
				}else{
					uni.navigateBack({})
				}
				return true
			}
		},
		onLoad() {
			this.req_data()
		},
		methods: {
			xieyi(){
				this.pop_title = "充值服务协议"
				this.pop_content = "充值服务协议,充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议充值服务协议"
				this.pop_show = true
			},
			guize(){
				this.pop_title = "使用规则"
				this.pop_content = "使用规则内容哈哈哈"
				this.pop_show = true
			},
			// 获取充值内容
			req_data(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/pay/init?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("数据: ",res.data);
						if(res.data.code==1){
							this.userinfo = res.data.user
							this.list = res.data.cionlist
							this.data = res.data
							uni.setNavigationBarTitle({
								title: '充值'+res.data.user.cion_name
							})
						}else{
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: '请求失败'
							});
							uni.navigateBack({
								
							})
						}
						// this.get_comment_list()
				    }
				});
			},
			// 立即支付
			buy(){
				if(!this.agree){
					uni.showToast({
						icon: 'none',
						position: 'bottom',
						title: '请先同意服务协议'
					})
					return;
				}
				let payType = "alipay"
				if(this.pay_type==1){
					payType = "alipay"
				}else if(this.pay_type==2){
					payType = "wxpay"
				}else if(this.pay_type==3){
					payType = "qqpay"
				}
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&num=" + this.list[this.listIndex].cion + "&pay=" + payType + "&rmb=" + this.list[this.listIndex].rmb + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/pay/save?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("数据: ",res.data);
						// uni.showModal({
						// 	content: JSON.stringify(res.data)
						// })
						uni.navigateTo({
							url:'../../webview/webview?uri=' + res.data.payurl
						})
						// this.get_comment_list()
				    }
				});
			}
		}
	}
</script>

<style>
	.boxItem{
		background: #fff;
		border: 1px solid #eee;
		border-radius: 12rpx;
		box-shadow: 0 4rpx 16rpx 0 rgba(0,0,0,.08);
		display: flex;
		flex-direction: column;
		height: 88rpx;
		justify-content: space-around;
		margin-bottom: 48rpx;
		margin-right: 20rpx;
		padding: 32rpx 0;
		position: relative;
		text-align: center;
		width: 216rpx;
	}
	.tip{
		background: #fff9f2;
		border-radius: 8rpx;
		color: #111;
		font-size: 28rpx;
		font-weight: 400;
		line-height: 44rpx;
		margin: 0 30rpx 40rpx;
		padding: 20rpx;
		text-align: center;
	}
	.payCard{
		border-bottom: 1px solid #f4f4f4;
		color: #000;
		font-size: 32rpx;
		font-weight: 500;
		height: 44rpx;
		line-height: 44rpx;
		margin: 42rpx 0 0;
		padding-bottom: 24rpx;
		padding-left: 40rpx;
	}
	.footer{
		background-color: #fff;
		border-top: 1px solid #f4f4f4;
		display: flex;
		justify-content: space-between;
		overflow: hidden;
		padding: 16rpx 40rpx 20rpx;
		text-align: center;
		width: calc(100% - 80rpx);
	}
	.buyBtn{
		background-color: #ff5500;
		border-radius: 8rpx;
		color: #191919;
		font-size: 28rpx;
		font-weight: 500;
		height: 88rpx;
		line-height: 88rpx;
	}
</style>
