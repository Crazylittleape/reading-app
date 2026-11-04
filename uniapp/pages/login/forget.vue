<template>
	<view class="forget">
		
		<view class="content">
			<!-- 头部logo -->
			<view class="header">
				<image :src="logoImage"></image>
			</view>
			<!-- 主体 -->
			<view class="main">
				<view class="tips">若你忘记了密码，可在此重置新密码。</view>
				<!-- <wInput
					v-model="nameData"
					type="text"
					maxlength="20"
					placeholder="请输入用户名"
				></wInput> -->
				<wInput
					v-model="mailData"
					type="text"
					maxlength="20"
					placeholder="请输入常用邮箱"
				></wInput>
				<wInput
					v-model="passData"
					type="password"
					maxlength="20"
					placeholder="请输入新密码"
					isShowPass
				></wInput>
				
				<wInput
					v-model="verCode"
					type="number"
					maxlength="6"
					placeholder="验证码"
					
					isShowCode
					codeText="获取验证码"
					setTime="60"
					ref="runCode"
					@setCode="getVerCode()"
				></wInput>
			</view>
			
			<wButton 
				class="wbutton"
				text="重置密码"
				:rotate="isRotate" 
				@click.native="startRePass()"
			></wButton>

		</view>
	</view>
</template>

<script>
	let _this;
	import wInput from '../../components/watch-login/watch-input.vue' //input
	import wButton from '../../components/watch-login/watch-button.vue' //button
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		data() {
			return {
				// logoImage: 'http://q1.qlogo.cn/g?b=qq&nk=1660875559&s=100',
				logoImage: '/static/logo.png',
				nameData: "", //电话
				mailData: "", //电话
				passData: "", //密码
				verCode:"", //验证码
				isRotate: false, //是否加载旋转
				app_key: '',
			}
		},
		components:{
			wInput,
			wButton,
			w_md5
		},
		mounted() {
			_this= this;
		},
		methods: {
			getVerCode(){
				//获取验证码
				
				if (!uni.$u.test.email(this.mailData)) {
				     uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '邮箱不正确'
				    });
				    return false;
				}
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&email=" + this.mailData + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/login/emailcode/pass?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("邮件发送结果: ",res);
						if(res.data.code==1){
							this.$refs.runCode.$emit('runCode'); //触发倒计时（一般用于请求成功验证码后调用）
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
						}else{
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
						}
					}
				})
				console.log("获取验证码")
			},
			startRePass() {
				//重置密码
				if(this.isRotate){
					//判断是否加载中，避免重复点击请求
					return false;
				}
				if (!uni.$u.test.email(this.mailData)) {
				     uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '邮箱不正确'
				    });
				    return false;
				}
				if (this.verCode == '') {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '请输入验证码'
				    });
				    return false;
				}
			    if (this.passData == '') {
			        uni.showToast({
			            icon: 'none',
						position: 'bottom',
			            title: '请输入新密码邮箱'
			        });
			        return false;
			    }
				_this.isRotate=true
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "code=" + this.verCode + "&deviceid=" + getApp().globalData.deviceid + "&email=" + this.mailData + "&facility=" + getApp().globalData.facility + "&pass=" + this.passData + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/login/pass?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("找回密码结果: ",res.data);
						if(res.data.code==1){
							_this.isRotate=false
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
							uni.removeStorageSync('user')
							uni.navigateBack()
							console.log("重置密码成功")
						}else{
							_this.isRotate=false
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
						}
					}
				})
				
			}
		}
	}
</script>

<style>
	@import url("../../components/watch-login/css/icon.css");
	@import url("./css/main.css");
</style>

