<template>
	<view>
		<view class="avatar" style="margin-bottom: 28rpx;margin-top: 56rpx;" @click="avatar_click">
			<image :src="userInfo.picx" style="width: 100%;height: 100%;border-radius: 50%;"></image>
		</view>
		<view style="margin: 0 auto;display: flex;justify-content: center;line-height: 56rpx;color: #2d3135;font-size: 36rpx;font-weight: 500;">
			<text>{{userInfo.nichen || userInfo.name}}</text>
		</view>
		<view style="background: #fff;margin: 76rpx 30rpx 0;">
			<view class="box">
				<view style="color: #525252;width: 160rpx;font-size:30rpx;">
					<text>ID</text>
				</view>
				<view style="height: 108rpx;line-height: 108rpx;text-align: right;width: calc(100% - 160rpx);">
					<text>{{(userInfo.id-0) + 10000}}</text>
				</view>
			</view>
			<view class="box">
				<view style="color: #525252;width: 160rpx;font-size:30rpx;">
					<text>账号</text>
				</view>
				<view style="height: 108rpx;line-height: 108rpx;text-align: right;width: calc(100% - 160rpx);">
					<text>{{userInfo.name}}</text>
				</view>
			</view>
			<view class="box">
				<view style="color: #525252;width: 160rpx;font-size:30rpx;">
					<text>昵称</text>
				</view>
				<view style="height: 108rpx;line-height: 108rpx;text-align: right;width: calc(100% - 160rpx);display: flex;justify-content: flex-end;" @click="rename()">
					<text v-if="userInfo.nichen">{{userInfo.nichen}}</text>
					<text v-else style="color: gray;">请输入新昵称</text>
					<u-icon color="#606266" name="arrow-right" size="28rpx"></u-icon>
				</view>
			</view>
		</view>
		
		<kx-modal ref="renameModal" modalTitle="请输入新昵称" placeholder="请输入新昵称" @onClickCancel="cancel" @onClickConfirm="nick_confirm"></kx-modal>
		<!-- <kx-modal ref="repwdModal" modalTitle="请输入新密码" placeholder="请输入新密码" @onClickCancel="cancel" @onClickConfirm="pass_confirm"></kx-modal> -->
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
				userInfo: {},
				app_key: ''
			}
		},
		onShow() {
			this.get_userinfo()
		},
		methods: {
			cancel(e){
				console.log(e)
			},
			// 修改昵称
			rename(){
				this.$refs['renameModal'].showModal()
			},
			// 提交修改昵称
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
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
							this.get_userinfo()
						}else{
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}
				    }
				});
			},
			// 选择头像
			avatar_click(){
				uni.chooseImage({  
				  count: 1,  //图片可选择数量
				  sizeType: ['original'],  //original 原图，compressed 压缩图，默认二者都有
				  sourceType: ['album','camera'],   //album 从相册选图，camera 使用相机，默认二者都有。
				  crop: {  //是否进行裁剪，此配置一般用于头像上传时裁剪成正方形等
				  width: 100,
				  height: 100
				},
				  success: res => {
				    let imgFiles = res.tempFilePaths   //图片的本地文件路径列表
				    this.uppic(imgFiles)
				  }
				})
			},
			// 获取用户信息
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
						this.userInfo = res.data.user
				    }
				});
			},
			// 上传头像
			uppic(img){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				console.log(img)
				uni.uploadFile({
					url: getApp().globalData.websiteUrl + '/index.php/appv1/user/uppic', //仅为示例，非真实的接口地址
					filePath: img[0],
					name: 'file',
					formData: {
						'deviceid': getApp().globalData.deviceid,
						'facility': getApp().globalData.facility,
						'timestamp': time,
						'user_id': uni.getStorageSync('user').uid,
						'user_token': uni.getStorageSync('user').utoken,
						'sign': sign
					},
					success: (uploadFileRes) => {
						console.log(uploadFileRes.data);
						uni.showToast({
							icon:"none",
							position:"bottom",
							title:"头像修改成功"
						})
						this.get_userinfo()
					}
				});
			}
		}
	}
</script>

<style>
	/* page{
		background: linear-gradient(0deg,#fff 80%,#f5f6fa);
	} */
	.avatar{
		background: #fff;
		border: 2rpx solid #eee;
		border-radius: 50%;
		box-shadow: 0 2px 4px 0 hsla(0,0%,60%,.1);
		height: 158rpx;
		margin: 0 auto;
		padding: 10rpx;
		position: relative;
		text-align: center;
		width: 158rpx;
	}
	.box{
		border-bottom: 1px solid #ededed;
		color: #2d3135;
		display: flex;
		font-size: 28rpx;
		font-weight: 400;
		justify-content: space-between;
		line-height: 108rpx;
		margin: 0 10rpx;
	}
</style>
