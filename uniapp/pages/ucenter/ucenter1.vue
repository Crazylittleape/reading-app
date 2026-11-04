<template>
	<view>
		<view :style="'margin-top:' + statusHeight + 'px'" style="margin: 20px;display: flex;justify-content: space-between;align-items: center;">
			<!-- <u-image width="30px" height="30px" src="https://mh.vpstj.net/static/images/mrtx.gif"></u-image> -->
			<u-avatar
			    :src="userinfo.picx"
			    size="60"
			    randomBgColor
			></u-avatar> 
			<view v-if="utoken!==''" style="margin-left: 20px;margin-right: auto;display: flex; flex-direction: column;">
				<text style="font-weight: 500;font-size: 18px;">{{userinfo.nichen||userinfo.name}}</text>
				<text style="font-size: 14px;color: #A9A9A9;">ID: {{userinfo.id}}</text>
			</view>
			<view v-else style="margin-left: 20px;margin-right: auto;display: flex; flex-direction: column;">
				<text style="font-weight: 500;font-size: 18px;" @click="login()">点我登陆/注册</text>
			</view>
			<u-button v-if="utoken!==''" type="warning" shape="circle" size="mini" style="width: 20%;margin-right: 0px;" @click="setting()">修改资料</u-button>
		</view>
		<view v-if="img_gg!==''">
		    <image @click="ad()" style="margin: 0 20px 0 20px;height: 70px;border-radius: 6px;" :src="img_gg"></image>
		</view>
		<u-cell-group :border="false" style="margin: 20px 6px 20px 6px;">
			<u-cell @click="coin()" :border="false" icon="setting-fill" title="金币充值" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="calendar"></u-icon>
			</u-cell>
			<u-cell @click="vip()" :border="false" icon="setting-fill" title="开通会员" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="calendar"></u-icon>
			</u-cell>
			<u-cell @click="ticket()" :border="false" icon="setting-fill" title="充值月票" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="calendar"></u-icon>
			</u-cell>
			<u-cell @click="history()" :border="false" icon="setting-fill" title="阅读历史" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="calendar"></u-icon>
			</u-cell>
			<u-cell @click="comments()" :border="false" icon="setting-fill" title="我的评论" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="coupon"></u-icon>
			</u-cell>
			<u-cell @click="update()" :border="false" icon="setting-fill" title="检查更新" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="hourglass"></u-icon>
				<text
				    v-if="update_status"
				    slot="value"
				    style="background-color: red;padding: 0 5px 0 5px;border-radius: 10px;color: #fff;font-size: 12px;"
				>有更新</text>
			</u-cell>
			<!-- <u-cell @click="qunliao()" :border="false" icon="setting-fill" title="加入群聊" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="chat"></u-icon>
			</u-cell> -->
			<u-cell @click="about()" :border="false" icon="setting-fill" title="关于我们" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="question-circle"></u-icon>
			</u-cell>
			<u-cell @click="logout()" v-if="utoken!==''" :border="false" icon="setting-fill" title="退出登录" :isLink="true" style="margin-bottom: 10px;">
				<u-icon slot="icon" color="#FFD74B" size="21px" name="warning"></u-icon>
			</u-cell>
			<!-- <u-cell :border="false" icon="integral-fill" title="会员等级" value="新版本"></u-cell> -->
		</u-cell-group>
		<u-modal :show="modal_show" :title="modal_title" :content='modal_content' confirmColor="#FFD74B" @cancel="cancel" @confirm="confirm" asyncClose showCancelButton></u-modal>
		<!-- <u-button type="warning" @click="login" v-if="utoken==''">登录</u-button>
		<u-button type="warning" @click="logout" v-else>退出</u-button> -->
		<app-update ref="app_update" :force="false" :tabbar="false"></app-update>
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
				update_status: false,
				webUrl: '',
				version: '',
				platform: '',
				username: '神秘人',
				modal_show:false,
				modal_title:'温馨提示',
				modal_content:'是否确认退出？',
				img_gg: '',
				url_gg: '',
				// img_gg: '/static/mh_loading.gif',
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				userinfo: '',
				statusHeight: 0
			}
		},
		onLoad() {
			this.req_ad()
		},
		onShow() {
			this.utoken = uni.getStorageSync('user')
			this.statusHeight = uni.getSystemInfoSync().statusBarHeight + 30
			// #ifdef APP-PLUS
			// 获取手机系统信息
			var _this = this;
			uni.getSystemInfo({
				success: function(res) {
					_this.platform = res.platform //ios  or android
					console.log('手机系统信息', _this.platform);
				}
			});
			// 获取版本号
			plus.runtime.getProperty(plus.runtime.appid, function(inf) {
				_this.version = inf.version
			});
			console.log('当前版本', this.version);
			// #endif
			this.get_userinfo()
			this.get_update()
		},
		methods: {
			// 跳转充值金币页面
			coin(){
				uni.navigateTo({
					url: 'coin/coin'
				})
			},
			// 跳转VIP充值页面
			vip(){
				uni.navigateTo({
					url: './vip/vip'
				})
			},
			// 跳转充值月票页面
			ticket(){
				uni.navigateTo({
					url: './ticket/ticket'
				})
			},
			req_ad(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/kxapps/ads?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.img_gg = res.data.list[1].img
						this.url_gg = res.data.list[1].url
					}
				})
			},
			update(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&version=" + getApp().globalData.version
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				//向后台发起请求，获取最新版本号
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/init/update?" + param + "&sign=" + sign, // 应用更新接口
					success: (res) => {
						console.log("+++++++++++++++++++++++++++++++++++++",res.data)
						
						if(res.data.code!==0){
							this.update_status = true
							console.log("有更新")
							
							let data = [{
								os: getApp().globalData.facility,
								version: res.data.version, //最新版本
								note: res.data.text,
								download_url: res.data.downurl, //下载地址 (微信测试)
								must: res.data.force, // 是否强制更新
							}];
							
							// 循环获取当前设备对应的更新数据
							for (let i = 0; i < data.length; i++) {
								if (this.platform == data[i]['os']) {
									if(data[i]['version']!==this.version){
										this.update_status = true
										console.log("有更新")
										this.$refs.app_update.update();
									}else{
										this.update_status = false
										console.log("没更新")
										uni.showToast({
											icon:"none",
											title:"已是最新版本"
										})
									}
									break;
								}
							}
							
						}else{
							this.update_status = false
							console.log("没更新")
							uni.showToast({
								icon:"none",
								title:res.data.msg
							})
						}
						
					}
				});
			},
			get_update(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&version=" + getApp().globalData.version
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				//向后台发起请求，获取最新版本号
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/init/update?" + param + "&sign=" + sign, // 应用更新接口
					success: (res) => {
						console.log("+++++++++++++++++++++++++++++++++++++",res.data)
						
						if(res.data.code!==0){
							this.update_status = true
							console.log("有更新")
						}else{
							this.update_status = false
							console.log("没更新")
						}
						
					}
				});
			},
			comments(){
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					uni.navigateTo({
						url:"comment/comment?username=" + this.username
					})
				}
			},
			ad(){
				// plus.runtime.openWeb(this.url_gg)
				uni.navigateTo({
					url:'../webview/webview?uri=' + this.url_gg
				})
			},
			qunliao(){
				plus.runtime.openURL('mqqopensdkapi://bizAgent/qm/qr?url=http%3A%2F%2Fqm.qq.com%2Fcgi-bin%2Fqm%2Fqr%3Ffrom%3Dapp%26p%3Dandroid%26jump_from%3Dwebapi%26k%3DHVDATEiQl-PQBRRLKk5felFG6cyznVxg')
			},
			about(){
				uni.navigateTo({
					url:'about/about'
				})
			},
			setting(){
				uni.navigateTo({
					url:"setting/setting?nick_name=" + this.userinfo.nichen + "&username=" + this.userinfo.id
				})
			},
			history(){
				uni.navigateTo({
					url:"history/history"
				})
			},
			confirm(){
				this.logout_ok()
			},
			cancel(){
				this.modal_show = false
			},
			get_userinfo(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/user/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("用户信息：",res.data)
						this.userinfo = res.data.user
				    }
				});
			},
			login(){
				uni.navigateTo({
					url:"../login/login"
				})
			},
			logout(){
				this.modal_show = true
			},
			logout_ok(){
				this.modal_show = false
				getApp().globalData.uid = ''
				getApp().globalData.utoken = ''
				this.utoken = ''
				uni.removeStorageSync('user')
				console.log("退出登录: ",uni.getStorageSync('user'));
			}
		}
	}
</script>

<style>

</style>
