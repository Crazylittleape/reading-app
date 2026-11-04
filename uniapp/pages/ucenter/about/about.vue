<template>
	<view>
		<view style="width: 100%;display: flex;">
			<image style="width: 80px;height: 80px;margin-left: auto;margin-right: auto;margin-top: 60px;" src="../../../static/logo.png"></image>
		</view>
		<view style="width: 100%;display: flex;">
		    <text style="font-size: 18px;color: #000000;margin-left: auto;margin-right: auto;margin-top: 15px;">v{{version}}</text>
		</view>
		<u-cell-group :border="false" style="margin: 60px 6px 20px 6px;">
			<u-cell @click="openweb()" :border="false" icon="" title="访问官网" label="http://www.dkewl.com" value="" isLink></u-cell>
			<u-cell @click="qqkefu()" :border="false" icon="" title="联系客服" label="Qq号: 123456" value="找漫画/找漫友/问题反馈" isLink></u-cell>
			<u-cell @click="qunliao()" :border="false" icon="" title="官方qq群" label="群号: 123456" value="" isLink></u-cell>
			<u-cell @click="get_update(2)" :border="false" icon="" title="检查更新" :label="'当前版本号：v'+version" value="" isLink>
				<text
				    v-if="update_status"
				    slot="value"
				    style="background-color: red;padding: 0 5px 0 5px;border-radius: 10px;color: #fff;font-size: 12px;"
				>v{{new_version}}</text>
			</u-cell>
		</u-cell-group>
		
		<app-update ref="app_update" :force="false" :tabbar="false"></app-update>
	</view>
</template>

<script>
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	import appUpdate from "@/components/yzhua006-update/app-update.vue"
	export default {
		components:{
			w_md5,
			appUpdate
		},
		data() {
			return {
				version:0,
				new_version: 0,
				update_status: false,
			}
		},
		onShow() {
			// 获取版本号
			// this.version = plus.runtime.version
			// 获取版本号
			var _this = this;
			plus.runtime.getProperty(plus.runtime.appid, function(inf) {
				_this.version = inf.version
			});
			this.get_update(1)
		},
		methods: {
			qunliao(){
				plus.runtime.openURL('mqqopensdkapi://bizAgent/qm/qr?url=http%3A%2F%2Fqm.qq.com%2Fcgi-bin%2Fqm%2Fqr%3Ffrom%3Dapp%26p%3Dandroid%26jump_from%3Dwebapi%26k%3DHVDATEiQl-PQBRRLKk5felFG6cyznVxg')
			},
			qqkefu(){
				plus.runtime.openURL('mqqwpa://im/chat?chat_type=wpa&uin=1660875559')
			},
			openweb(){
				plus.runtime.openURL('https://www.manhua.pro')
			},
			get_update(type){
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
							this.new_version = res.data.version
							if(type==2){
								this.$refs.app_update.update();
							}
							console.log("有更新")
						}else{
							this.update_status = false
							if(type==2){
								uni.showToast({
									icon:"none",
									title:res.data.msg
								})
							}
							console.log("没更新")
						}
						
					}
				});
			},
		}
	}
</script>

<style>

</style>
