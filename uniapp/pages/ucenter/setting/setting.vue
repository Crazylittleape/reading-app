<template>
	<view>
		<view class="box" style="margin-top: 20rpx;">
			<view style="font-size: 32rpx;">
				<text>邀请码</text>
			</view>
			<view style="font-size: 28rpx;height: 116rpx;line-height: 116rpx;color: #ff7214;display: flex;" @click="copy">
				<text style="margin-right: 6rpx;">{{id}}</text>
				<uni-icons type="vip-filled" color="#ff7214" size="16"></uni-icons>
			</view>
		</view>
		<view class="box">
			<view style="font-size: 32rpx;">
				<text>章节自动购买</text>
			</view>
			<view style="font-size: 28rpx;height: 116rpx;line-height: 116rpx;color: #7e7e7e;display: flex;align-items: center;">
				<u-switch activeColor="#5ac725" v-model="autoBuy" size="20" @change="autoBuy_set()"></u-switch>
			</view>
		</view>
		<!-- <view class="box">
			<view style="font-size: 32rpx;">
				<text>手机号</text>
			</view>
			<view style="font-size: 28rpx;height: 116rpx;line-height: 116rpx;color: #7e7e7e;display: flex;" @click="changePhone">
				<text style="margin-right: 12rpx;">修改手机号</text>
				<uni-icons type="right" color="#999" size="16"></uni-icons>
			</view>
		</view>
		<view class="box">
			<view style="font-size: 32rpx;">
				<text>邮箱</text>
			</view>
			<view style="font-size: 28rpx;height: 116rpx;line-height: 116rpx;color: #7e7e7e;display: flex;" @click="changeEmail">
				<text style="margin-right: 12rpx;">修改邮箱</text>
				<uni-icons type="right" color="#999" size="16"></uni-icons>
			</view>
		</view> -->
		<view class="box">
			<view style="font-size: 32rpx;">
				<text>登陆密码</text>
			</view>
			<view style="font-size: 28rpx;height: 116rpx;line-height: 116rpx;color: #7e7e7e;display: flex;" @click="changePassword">
				<text style="margin-right: 12rpx;">修改密码</text>
				<uni-icons type="right" color="#999" size="16"></uni-icons>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: '666VIP',
				autoBuy: false
			}
		},
		onLoad(option) {
			this.id = (option.id-0) + 10000
			if(uni.getStorageSync('autoBuy')==''){
				uni.setStorageSync('autoBuy', false)
			}
			this.autoBuy = uni.getStorageSync('autoBuy')
		},
		onShow() {
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
				uni.showToast({
					icon:"none",
					position:"bottom",
					title:"请重新登录"
				})
				uni.navigateBack({
					
				})
			}else{
				console.log("已登录",uni.getStorageSync('user'))
				
			}
		},
		methods: {
			// 设置自动购买
			autoBuy_set(){
				console.log(this.autoBuy)
				if(this.autoBuy){
					uni.setStorageSync('autoBuy', true)
					
				}else{
					uni.setStorageSync('autoBuy', false)
					
				}
			},
			// 复制账号
			copy(){
				uni.setClipboardData({
				    data: ''+this.id,
				    success: () => {
				        uni.showToast({
							icon: 'none',
				            title: '复制成功'
				        })
				    }
				})
			},
			// 跳转修改手机号页面
			changePhone(){
				uni.showToast({
					icon: 'none',
				    title: '暂不支持'
				})
				return;
				uni.navigateTo({
					url: '../changePhone/changePhone'
				})
			},
			// 跳转修改邮箱页面
			changeEmail(){
				uni.showToast({
					icon: 'none',
				    title: '暂不支持'
				})
				return;
				uni.navigateTo({
					url: '../changePhone/changePhone'
				})
			},
			// 跳转修改密码页面
			changePassword(){
				// uni.showToast({
				// 	icon: 'none',
				//     title: '暂不支持'
				// })
				// return;
				uni.navigateTo({
					url: '/pages/login/forget'
				})
			}
		}
	}
</script>

<style>
	page{
		background-color: #f7f8fb;
	}
	.box{
		background: #fff;
		color: #000;
		display: flex;
		font-weight: 400;
		height: 116rpx;
		justify-content: space-between;
		line-height: 116rpx;
		margin-bottom: 20rpx;
		padding: 0 40rpx;
	}
</style>
