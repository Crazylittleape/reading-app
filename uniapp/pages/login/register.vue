<template>
	<view class="register">
	
		<view class="content">
			<!-- 头部logo -->
			<view class="header">
				<image :src="logoImage"></image>
			</view>
			<!-- 主体 -->
			<view class="main">
				<wInput
					v-model="email"
					type="text"
					maxlength="35"
					placeholder="请输入邮箱账号"
				></wInput>
				<wInput
					v-model="pass"
					type="password"
					maxlength="20"
					placeholder="请输入登录密码"
					isShowPass
				></wInput>
				<wInput
					v-model="repass"
					type="password"
					maxlength="20"
					placeholder="请确认登录密码"
					isShowPass
				></wInput>
				<wInput
					v-model="inviteid"
					type="text"
					maxlength="20"
					placeholder="推荐人ID(选填)"
				></wInput>
				<wInput
					v-model="verCode"
					type="number"
					maxlength="6"
					placeholder="验证码"
					
					isShowCode
					ref="runCode"
					@setCode="getVerCode()"
				></wInput>
					
			</view>
				
			<wButton 
				class="wbutton"
				text="注 册"
				:rotate="isRotate" 
				@click.native="startReg()"
			></wButton>
			
			<!-- 底部信息 -->
			<view class="footer">
				<text 
					@tap="isShowAgree" 
					class="cuIcon"
					:class="showAgree?'cuIcon-radiobox':'cuIcon-round'"
				> 同意</text>
				<!-- 协议地址 -->
				<navigator url="" open-type="navigate">《协议》</navigator>
			</view>
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
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				//logo图片 base64
				logoImage: '/static/logo.png',
				email:'', // 邮箱/用户/电话
				inviteid:'', //邀请id
				pass:'', //密码
				repass:'', //确认密码
				verCode:"", //验证码
				showAgree:true, //协议是否选择
				isRotate: false, //是否加载旋转
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
		onLoad() {
			
		},
		methods: {
			isShowAgree(){
				//是否选择协议
				_this.showAgree = !_this.showAgree;
			},
			getVerCode(){
				//获取验证码
				if (!uni.$u.test.email(this.email)) {
				     uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '邮箱不正确'
				    });
				    return false;
				}
				console.log("获取验证码")
				
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&email=" + this.email + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/login/emailcode/reg?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("注册结果: ",res);
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
				
				// this.$refs.runCode.$emit('runCode'); //触发倒计时（一般用于请求成功验证码后调用）
				// uni.showToast({
				//     icon: 'none',
				// 	position: 'bottom',
				//     title: '模拟倒计时触发'
				// });
				
				// setTimeout(function(){
				// 	_this.$refs.runCode.$emit('runCode',0); //假装模拟下需要 终止倒计时
				// 	uni.showToast({
				// 	    icon: 'none',
				// 		position: 'bottom',
				// 	    title: '模拟倒计时终止'
				// 	});
				// },3000)
			},
		    startReg() {
				
				//注册
				if(this.isRotate){
					//判断是否加载中，避免重复点击请求
					return false;
				}
				if (this.showAgree == false) {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '请先同意《协议》'
				    });
				    return false;
				}
				// if (this.phoneData.length !=11) {
				//     uni.showToast({
				//         icon: 'none',
				// 		position: 'bottom',
				//         title: '手机号不正确'
				//     });
				//     return false;
				// }
				if (!uni.$u.test.email(this.email)) {
				     uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '邮箱不正确'
				    });
				    return false;
				}
				// if (this.pidData =='') {
				//     uni.showToast({
				//         icon: 'none',
				// 		position: 'bottom',
				//         title: '推荐人不能为空'
				//     });
				//     return false;
				// }
				if (this.pass.length < 6 || this.repass.length < 6) {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '密码不能低于6位'
				    });
				    return false;
				}
				if (this.repass !== this.pass) {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '两次密码输入不一致'
				    });
				    return false;
				}
				if (this.verCode.length < 4) {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '验证码不正确'
				    });
				    return false;
				}
				_this.isRotate=true
				if(this.inviteid==''){
					this.inviteid = 10001
				}
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "code=" + this.verCode + "&deviceid=" + getApp().globalData.deviceid + "&email=" + this.email + "&facility=" + getApp().globalData.facility + "&inviteid=" + this.inviteid + "&pass=" + this.pass + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/login/reg?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("注册结果: ",res);
						if(res.data.code==1){
							_this.isRotate=false
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
							uni.navigateBack()
							console.log("注册成功")
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
				
				
				// setTimeout(function(){
				// 	_this.isRotate=false
				// },3000)
		    }
		}
	}
</script>

<style>
	@import url("../../components/watch-login/css/icon.css");
	@import url("./css/main.css");
</style>