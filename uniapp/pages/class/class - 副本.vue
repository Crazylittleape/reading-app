<template>
	<view>
		<view>
		    <view class="tabs">
		    	<view v-for="(item,index) in tabs" :key="index" style="margin-left: 0px;margin-right: auto;">
		    	    <view style="padding: 10px;font-size: 13px;" :class="{active:item.id==tag_id}" @click="class_click(item.id)">{{item.name}}</view>
		    	</view>
		    </view>
		    <view class="tabs sc">
		    	<view v-for="(item,index) in ends" :key="index">
		    	    <view style="padding: 10px;font-size: 13px;" :class="{active:item.id==end_id}" @click="end_click(item.id)">{{item.end_name}}</view>
		    	</view>
		    </view>
			<view class="tabs sc">
				<view v-for="(item,index) in sorts" :key="index">
				    <view style="padding: 10px;font-size: 13px;" :class="{active:item.id==sort_id}" @click="sort_click(item.id)">{{item.name}}</view>
				</view>
			</view>
		</view>
		
		<mescroll-uni ref="mescrollRef" :top="top*3+15" @init="mescrollInit" :down="downOption" @down="downCallback" :up="upOption" @up="upCallback">
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
				num: 1,
				data: [],
				tabs: [{id:0,name:'全部'}],
				ends: [{id:0,end_name:'全部'},{id:1,end_name:'连载'},{id:2,end_name:'完结'}],
				sorts: [{id:0,name:'最新'},{id:1,name:'最热'},{id:2,name:'评分'},{id:3,name:'新漫'}],
				tag_id: 0,
				end_id: 0,
				sort_id: 0,
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				downOption: {
					auto: false,
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
		onLoad(options) {
			if(options.id!==undefined){
				this.tag_id = options.id
			}
			this.get_tabs()
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
			class_click(id){
				console.log(id)
				this.tag_id = id
				this.num = 1
				this.mescroll.triggerDownScroll()
				
			},
			end_click(end_id){
				this.end_id = end_id
				this.num = 1
				this.mescroll.triggerDownScroll()
			},
			sort_click(sort_id){
				this.sort_id = sort_id
				this.num = 1
				this.mescroll.triggerDownScroll()
			},
			get_class_list(){
				
				let st = 'addtime'
				if(this.sort_id==0){
					st = 'addtime'
				}
				if(this.sort_id==1){
					st = 'hits'
				}
				if(this.sort_id==2){
					st = 'score'
				}
				if(this.sort_id==3){
					st = 'id'
				}
				
				this.num = 1
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "cid=" + this.tag_id + "&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.num + "&pay=0&size=30&sort=" + st + "&state=" + this.end_id + "&timestamp=" + time
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
				var param = "cid=" + this.tag_id + "&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.num + "&pay=0&size=30&sort=addtime&state=" + this.end_id + "&timestamp=" + time
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
			get_tabs(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/type?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("分类列表: ",res.data);
						this.tabs = this.tabs.concat(res.data.list)
						this.mescroll.triggerDownScroll()
						this.get_top()
					}
				})
			},
			get_top(){
				
				const query = uni.createSelectorQuery().in(this);
				query.select('.sc').boundingClientRect(data => {
				  console.log("得到布局位置信息" + JSON.stringify(data));
				  this.top = data.top + data.height
				  console.log(this.top)
				}).exec();
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
