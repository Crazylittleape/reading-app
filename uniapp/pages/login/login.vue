<template>
	<view class="login">
		<view class="content">
			<!-- 头部logo -->
			<view class="header">
				<image :src="logoImage"></image>
			</view>
			<!-- 主体表单 -->
			<view class="main">
				<wInput
					v-model="email"
					type="text"
					maxlength="35" 
					placeholder="邮箱"
					:focus="isFocus"
				></wInput>
				<wInput
					v-model="pass"
					type="password"
					maxlength="20"
					placeholder="密码"
					isShowPass
				></wInput>
			</view>
			<wButton 
				class="wbutton"
				:text="an_text"
				:rotate="isRotate" 
				@click="startLogin"
			></wButton>
			
			<!-- 其他登录 -->
			<view class="other_login cuIcon">
				<view class="login_icon">
					<view class="cuIcon-weixin" @tap="login_weixin"></view>
				</view>
				<view class="login_icon">
					<view class="cuIcon-weibo" @tap="login_weibo"></view>
				</view>
				<view class="login_icon">
					<view class="cuIcon-github" @tap="login_github"></view>
				</view>
			</view>
			
			<!-- 底部信息 -->
			<view class="footer">
				<navigator url="forget" open-type="navigate">找回密码</navigator>
				<text>|</text>
				<navigator url="register" open-type="navigate">注册账号</navigator>
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
				//logo图片 base64
				logoImage: '/static/logo.png',
				email:'', //用户登陆邮箱
				pass:'', //密码
				isRotate: false, //是否加载旋转
				isFocus: true ,// 是否聚焦
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				an_text: '登 录' //登录按钮标题
			};
		},
		components:{
			wInput,
			wButton,
			w_md5
		},
		onLoad() {
			
		},
		mounted() {
			_this= this;
			//this.isLogin();
		},
		methods: {
			isLogin(){
				//判断缓存中是否登录过，直接登录
				// try {
				// 	const value = uni.getStorageSync('setUserData');
				// 	if (value) {
				// 		//有登录信息
				// 		console.log("已登录用户：",value);
				// 		_this.$store.dispatch("setUserData",value); //存入状态
				// 		uni.reLaunch({
				// 			url: '../../../pages/index',
				// 		});
				// 	}
				// } catch (e) {
				// 	// error
				// }
			},
		    startLogin(e){
				console.log(e)
				//登录
				if(this.isRotate){
					//判断是否加载中，避免重复点击请求
					return false;
				}
				if (!uni.$u.test.email(this.email)) {
				     uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '邮箱不正确'
				    });
				    return false;
				}
				if (this.pass.length < 6) {
				    uni.showToast({
				        icon: 'none',
						position: 'bottom',
				        title: '密码不正确'
				    });
				    return;
				}
				
				_this.isRotate=true
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&email=" + this.email + "&facility=" + getApp().globalData.facility + "&pass=" + this.pass + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/login/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log(res.data)
						if(res.data.code == 0){
							_this.isRotate=false
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
						}else{
							getApp().globalData.uid = res.data.uid
							getApp().globalData.utoken = res.data.token
							uni.setStorageSync('user', {uid:res.data.uid,utoken:res.data.token})
							_this.isRotate=false
							this.an_text = "登录成功"
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: "登录成功"
							});
							uni.navigateBack()
							console.log("登录成功",uni.getStorageSync('user'))
						}
				    }
				});
				
				
				
				
				
				// setTimeout(function(){
				// 	_this.isRotate=false
				// },3000)
				// uni.showLoading({
				// 	title: '登录中'
				// });
				// getLogin()
				// .then(res => {
				// 	//console.log(res)
				// 	//简单验证下登录（不安全）
				// 	if(_this.phoneData==res.data.username && _this.passData==res.data.password){
				// 		let userdata={
				// 			"username":res.data.username,
				// 			"nickname":res.data.nickname,
				// 			"accesstoken":res.data.accesstoken,
				// 		} //保存用户信息和accesstoken
				// 		_this.$store.dispatch("setUserData",userdata); //存入状态
				// 		try {
				// 			uni.setStorageSync('setUserData', userdata); //存入缓存
				// 		} catch (e) {
				// 			// error
				// 		}
				// 		uni.showToast({
				// 			icon: 'success',
				// 			position: 'bottom',
				// 			title: '登录成功'
				// 		});
				// 		uni.reLaunch({
				// 			url: '../../../pages/index',
				// 		});
				// 	}else{
				// 		_this.passData=""
				// 		uni.showToast({
				// 			icon: 'error',
				// 			position: 'bottom',
				// 			title: '账号或密码错误，账号admin密码admin'
				// 		});
				// 	}
				// 	uni.hideLoading();
				// }).catch(err => {
				// 	uni.hideLoading();
				// })
				
		    },
			login_weixin() {
				//微信登录
				uni.showToast({
					icon: 'none',
					position: 'bottom',
					title: '暂不支持'
				});
			},
			login_weibo() {
				//微博登录
				uni.showToast({
					icon: 'none',
					position: 'bottom',
					title: '暂不支持'
				});
			},
			login_github() {
				//github登录
				uni.showToast({
					icon: 'none',
					position: 'bottom',
					title: '暂不支持'
				});
			}
		}
	}
</script>

<style>
	@import url("../../components/watch-login/css/icon.css");
	@import url("./css/main.css");
</style>
