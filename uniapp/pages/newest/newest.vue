<template>
	<view>
		
		<mescroll-uni ref="mescrollRef" @init="mescrollInit" :down="downOption" @down="downCallback" :up="upOption" @up="upCallback">
			<view class="lists">
				<u-empty text="暂无内容" mode="list" iconSize="280" icon="/static/image/list.png" style="margin-top: auto; margin-bottom: auto;" :show="data_null"></u-empty>
			    <view v-for="(item,index) in data" :key="index" style="width: 32%;margin-left: 0px;margin-right: auto;" @click="get_detail(item.id)">
			    	
			    		<u-image width="100%" height="150px" :src="item.pic" :lazy-load="true" @error="imageError($event, index)"></u-image> 
						<text class="c_title">{{ item.name }}</text>
			    	<!-- <u--text :lines="1" :text="item.book_name"></u--text> -->
			    	
			    	<!-- {{item}} -->
			    </view>
			</view>
		</mescroll-uni>
	</view>
</template>

<script>
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		mixins: [MescrollMixin], // 使用mixin
		components: {
	        w_md5
	    },
		data() {
			return {
				data_null: false,
				top: 0,
				num: 0,
				data: [],
				tabs: [{tag_name:'全部'}],
				ends: [{id:-1,end_name:'全部'},{id:0,end_name:'连载'},{id:1,end_name:'完结'}],
				tag_name: '全部',
				end_id: -1,
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				downOption: {
					auto: true,
					offset: 50
					// bgColor: "#000",
				},
				upOption: {
					auto: false,
					use : true, 
					isBounce: true,
					textColor: "gray",
					// bgColor: "#000",
					page: {
						num : 0 , 
						size : 20 , 
						time : null 
					},
					empty:{
						use : true , 
						icon : null , 
						tip : "暂无相关数据",
						btnText : "", 
						fixed: false, 
						top: "100rpx", 
						zIndex: 99 
					}
				}
			}
		},
		onLoad() {
			
		},
		methods: {
			imageError: function(e, index) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
			    this.data[index]['pic'] = '../../static/img_error.jpg'
			},
			get_detail(name){
				console.log(name)
				uni.navigateTo({
					url:'../detail/detail?id=' + name
				})
			},
			get_class_list(){
				
				this.num = 1
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.num + "&pay=0&size=30&sort=addtime&state=0&timestamp=" + time
				console.log(param)
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("漫画列表: ",res.data);
						this.data = res.data.list
						if(this.data.length==0){
							this.data_null = true
						}else{
							this.data_null = false
							this.divider_show = true
						}
						// this.tabs = this.tabs.concat(res.data.tags)
						this.mescroll.endSuccess();
					}
				})
			},
			get_class_list_more(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "cid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.num + "&pay=0&size=30&sort=addtime&state=0&timestamp=" + time
				console.log(param)
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						this.data = this.data.concat(res.data.list)
						console.log("漫画列表: ",res.data);
						// this.tabs = this.tabs.concat(res.data.tags)
						this.mescroll.endErr();
					}
				})
			},
			/*下拉刷新的回调 */
			downCallback() {
				this.get_class_list()
				// setTimeout(() => {
				//  	this.mescroll.endSuccess();
				// }, 2000);
				
			},
			/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
			upCallback(page) {
				this.num = this.num+1
				this.get_class_list_more(this.num)
				// setTimeout(() => {
				//  	this.mescroll.endErr();
				// }, 2000);
			}
		}
	}
</script>

<style>
	
	.c_title{
		/* padding: 5px 0 5px 0; */
		font-size: 14px;
		font-weight: 300;
		color: #000;
		display: -webkit-box;
		/* -webkit-box-orient: vertical; */
		/*! autoprefixer: off */
		-webkit-box-orient: vertical;
		/* autoprefixer: on */
		-webkit-line-clamp: 1;
		overflow: hidden;
		line-height: 30px;
	}
	
	.lists{
		margin-top: 5px;
		width: 96%;
		margin-left: auto;
		margin-right: auto;
		display: flex;
		text-align: left;
		flex-direction: row;
		flex-wrap: wrap;
		justify-content: space-around;
		align-items: flex-start;
	}
	.tabs{
		margin-top: 5px;
		width: 96%;
		margin-left: auto;
		margin-right: auto;
		display: flex;
		text-align: left;
		flex-direction: row;
		flex-wrap: wrap;
		/* justify-content: space-around; */
		align-items: flex-start;
		color: #A9A9A9;
	}
	.active{
		color: #FFD74B;
	}
	
	.top-warp{
		z-index: 9990;
		position: fixed;
		top: --window-top; /* css变量 */
		left: 0;
		width: 100%;
		height: 365upx;
		background-color: white;
	}
	.top-warp .tip{
		font-size: 28upx;
		height: 60upx;
		line-height: 60upx;
		text-align: center;
	}
</style>
