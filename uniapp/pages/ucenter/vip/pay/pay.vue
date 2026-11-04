<template>
	<view style="background-color: #fff;color: #777;font-size: 28rpx;height: calc(100vh - 1px);">
		<view style="background: rgba(255,160,54,.1);color: #ffa036;font-size: 26rpx;font-weight: 400;height: 32rpx;line-height: 32rpx;padding: 24rpx 40rpx;text-align: center;">
			主人，充值出现问题请及时联系客服解决哦~
		</view>
		<view style="margin-bottom: 24rpx;margin-top: 80rpx;">
			<view style="color: #f70;font-size: 36rpx;font-weight: 300;height: 66rpx;line-height: 40rpx;margin-bottom: 10rpx;text-align: center;">
				<text>￥</text>
				<text style="font-size: 64rpx;font-weight: 500;">
					{{rmb}}
				</text>
			</view>
		</view>
		<view style="color: #111;font-size: 28rpx;font-weight: 400;height: 34rpx;line-height: 34rpx;margin-bottom: 96rpx;text-align: center;">
			购买会员：{{day}}天
		</view>
		<view style="height:16rpx;background-color:rgba(246,247,248,1);"></view>
		<view class="payCard">
			<view class="payCard" style="padding-left: 0;">选择支付方式</view>
			<view style="margin-right: 40rpx;">
				<view @click="data.is_alipay==1?pay_type=1:''" style="display: flex;height: 108rpx;justify-content: space-between;line-height: 108rpx;border-bottom: 1px solid #f4f4f4;">
					<view style="display: flex;align-items: center;">
						<!-- <image src="/static/ucenter/alipay.png" style="height: 48rpx;width: 48rpx;vertical-align: middle;"></image> -->
						<u-icon name="zhifubao-circle-fill" color="#1296db" size="24"></u-icon>
						<text style="font-size: 32rpx;vertical-align: middle;color:rgba(51, 51, 51, 1);margin-left:20rpx;">
							{{data.is_alipay==1?'支付宝':'支付宝(暂不支持)'}}
						</text>
					</view>
					<view v-if="pay_type==1">
						<uni-icons color="#ff7214" size="18" type="checkbox"></uni-icons>
					</view>
					<view v-else>
						<uni-icons color="#777777" size="18" type="circle"></uni-icons>
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
					<view v-if="pay_type==2">
						<uni-icons color="#ff7214" size="18" type="checkbox"></uni-icons>
					</view>
					<view v-else>
						<uni-icons color="#777777" size="18" type="circle"></uni-icons>
					</view>
				</view>
				<view @click="data.is_qqpay==1?pay_type=3:''" style="display: flex;height: 108rpx;justify-content: space-between;line-height: 108rpx;">
					<view style="display: flex;align-items: center;">
						<!-- <image src="/static/ucenter/wechat.png" style="height: 48rpx;width: 48rpx;vertical-align: middle;"></image> -->
						<u-icon name="qq-circle-fill" color="#000000" size="24"></u-icon>
						<text style="font-size: 32rpx;vertical-align: middle;color:rgba(51, 51, 51, 1);margin-left:20rpx;">
							{{data.is_qqpay==1?'QQ钱包':'QQ钱包(暂不支持)'}}
						</text>
					</view>
					<view v-if="pay_type==3">
						<uni-icons color="#ff7214" size="18" type="checkbox"></uni-icons>
					</view>
					<view v-else>
						<uni-icons color="#777777" size="18" type="circle"></uni-icons>
					</view>
				</view>
			</view>
		</view>
		<view style="margin-top: 360rpx;padding: 40rpx;" @click="buy(1)">
			<button class="payBtn">支付</button>
		</view>
		<view style="color: #191919;font-size: 28rpx;font-weight: 400;height: 40rpx;line-height: 40rpx;text-align: center;" @click="buy(2)">
			<text>邀请朋友帮我付</text>
		</view>
	</view>
</template>

<script>
	import w_md5 from "../../../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
		    w_md5
		},
		onLoad(option) {
			this.day = option.day
			this.rmb = option.rmb
			this.req_data()
		},
		data() {
			return {
				day: 0,
				rmb: 0,
				pay_type: 1, //1=支付宝，2=微信，3=qq
				data: {}
			}
		},
		methods: {
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
							this.data = res.data
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
			buy(data){
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
				var param =  "day=" + this.day + "&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&pay=" + payType + "&rmb=" + this.rmb + "&timestamp=" + time + "&type=vip&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/pay/save?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("数据: ",res.data);
						if(data==1){
							uni.navigateTo({
								url:'../../../webview/webview?uri=' + res.data.payurl
							})
						}else{
							uni.shareWithSystem({
							  summary: '',
							  href: res.data.payurl,
							  success(){
							    // 分享完成，请注意此时不一定是成功分享
							  },
							  fail(){
							    // 分享失败
							  }
							})
						}
				    }
				});
			}
		}
	}
</script>

<style>
	button::after{border: initial;}
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
	.payBtn{
		background-color: #fae53f;
		border-radius: 44rpx;
		color: #191919;
		font-size: 32rpx;
		font-weight: 500;
		height: 88rpx;
		line-height: 88rpx;
	}
</style>
