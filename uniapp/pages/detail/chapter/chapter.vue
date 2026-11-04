<template>
	<view>
		<view style="height: 88rpx;left: 0;right: 0;top: 0;display: flex;margin: auto 15px;justify-content: space-between;">
			<view style="display: flex;margin-top: auto;margin-bottom: auto;">
				<text style="font-weight: 600;font-size: 16px;">连载中({{chapter_list_data.length}})</text>
			</view>
			<view style="display: flex;margin-top: auto;margin-bottom: auto;justify-content: space-between;width: 20%;">
				<view style="font-size: 28rpx;" :class="sort==0?'active':''" @click="sort_click(0)">
					<text>正序</text>
				</view>
				<view style="color: grey;font-size: 12px;">
					<text>|</text>
				</view>
				<view style="font-size: 28rpx;" :class="sort==1?'active':''" @click="sort_click(1)">
					<text>倒叙</text>
				</view>
			</view>
		</view>
		<virtual-list :items="chapter_list_data" :size="size" :remain="remain">
			<template v-slot:default="slotItem">
				<list-item :item="slotItem.item" class="a"></list-item>
			</template>
		</virtual-list>
	</view>
</template>

<script>
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	import VirtualList from '@/components/xc-virtual-list/xc-virtual-list.vue'
	import ListItem from '@/components/xc-virtual-list/xc-list-item.vue';
	export default {
		components: {
		    w_md5,
			VirtualList,
			ListItem
		},
		data() {
			return {
				chapter_height: 0,
				chapter_list_data: [{"id":"1","name":"开心漫画","addtime":"","cion":"0","vip":"0"}],
				id: 0,
				size: 0,
				item_height: 0,
				remain: 1,
				sort: 0 // 0=正序，1=倒叙
			}
		},
		onLoad: function (option) {
			this.id=option.id
			this.chapter_height = uni.getSystemInfoSync().screenHeight - 44
			
		},
		mounted() {
			this.get_chapter_list()
		},
		methods: {
			sort_click(id){
				if(id==0){
					if(this.sort!==0){
						this.chapter_list_data = this.chapter_list_data.reverse()
						this.sort = 0
					}
				}else{
					if(this.sort!==1){
						this.chapter_list_data = this.chapter_list_data.reverse()
						this.sort = 1
					}
				}
			},
			read() {
				let _this = this;
				uni.getSystemInfo({
				  success: function (res) { // res - 各种参数
				
				    let obj = uni.createSelectorQuery().select('.a')
				
				    obj.boundingClientRect(function (data) { // data - 各种参数
				      
				      console.log(data)
					  _this.item_height = data.height
					  console.log(_this.item_height)
					  // this.size = +uni.upx2px(52);
					  _this.size = _this.item_height
					  console.log(_this.size)
					  _this.remain = _this.chapter_height / _this.size / 10 * 9
					  console.log(_this.remain.toFixed(2))
				
				    }).exec()
				  }
				})
				
			},
			get_chapter_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.id + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/chapter?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						// this.loading_show = false
						console.log(res.data.list)
						// if(res.data.list.length >= 200){
						// 	this.chapter_list_data = res.data.list
						// 		// this.chapter_list_data = res.data.list.slice(0,200)
						// 		// this.chapter_list_data1 = res.data.list.slice(201,400)
						// 	// uni.showToast({
						// 	// 	title:"大于20章"
						// 	// })
						// }else{
						// 	this.chapter_list_data = res.data.list
						// 	// uni.showToast({
						// 	// 	title:"小于20章"
						// 	// })
						// }
						this.chapter_list_data = res.data.list
						this.read()
						// this.chapter_page()
				    }
				});
			},
		}
	}
</script>

<style>
	.active{
		color: #fd610e;
	}
</style>
