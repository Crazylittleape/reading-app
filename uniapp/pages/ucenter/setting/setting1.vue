<template>
	<view>
		<u-cell-group>
			<u-cell title="头像" @click='avatar_click'>
				<u-image width='100rpx' height='100rpx' slot="right-icon" :src="avatar" shape="circle"></u-image>
			</u-cell>
			<u-cell title="ID" :value="username"></u-cell>
			<u-cell isLink title="昵称" :value="nick_name||'未设置昵称'" @click="rename()"></u-cell>
			<u-cell isLink title="密码" value="修改密码" @click="repwd()"></u-cell>
		</u-cell-group>
		<kx-modal ref="renameModal" modalTitle="请输入新昵称" placeholder="请输入新昵称" @onClickCancel="cancel" @onClickConfirm="nick_confirm"></kx-modal>
		<kx-modal ref="repwdModal" modalTitle="请输入新密码" placeholder="请输入新密码" @onClickCancel="cancel" @onClickConfirm="pass_confirm"></kx-modal>
	</view>
</template>

<script>
	import kxmodal from "../../../components/kx-modal/kx-modal.vue";
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	export default {
		components:{
			kxmodal,
			w_md5
		},
		data() {
			return {
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				username: '',
				nick_name: '',
				avatar: 'https://mh.vpstj.net/static/images/mrtx.gif'
			}
		},
		onLoad(option) {
			this.nick_name = option.nick_name
			this.username = option.username
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
				uni.navigateTo({
					url:'../../login/login'
				})
			}else{
				console.log("已登录",uni.getStorageSync('user'))
			}
		},
		methods: {
			nick_confirm(e){
				console.log(e)
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&nichen=" + e + "&timestamp=" + time + "&type=nichen&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/user/edit?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("昵称修改结果：",res.data)
						if(res.data.code==1){
							this.nick_name = e
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}else{
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}
				    }
				});
			},
			pass_confirm(e){
				console.log(e)
				
				this.statusHeight = uni.getSystemInfoSync().statusBarHeight + 30
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&pass=" + e + "&timestamp=" + time + "&type=pass&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken + "&ypass=1660875559"
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/user/edit?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("密码修改结果：",res.data)
						if(res.data.code==1){
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
							getApp().globalData.uid = ''
							getApp().globalData.utoken = ''
							uni.removeStorageSync('user')
							uni.navigateBack({
								
							})
						}else{
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}
				    }
				});
			},
			cancel(e){
				console.log(e)
			},
			avatar_click(){
				uni.showToast({
					icon:'none',
					title:"暂不支持修改头像"
				})
			},
			rename(){
				this.$refs['renameModal'].showModal()
			},
			repwd(){
				this.$refs['repwdModal'].showModal()
			}
		}
	}
</script>

<style>

</style>
