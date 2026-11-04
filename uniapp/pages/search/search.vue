<template>
	<view>
		<!-- <u-navbar title="" :is-back="false" height="700" :border-bottom="false">
			<view style="width: 100%;">
				<zy-search :is-focus="true" :theme="themeClass" :show-want="true" :speechEngine="speechEngine"
				:hot-list="hotList" @getSearchText="getSearchText"></zy-search>
				<u-search shape="square" height="70" color="#909399" margin="30rpx" :disabled="false" :show-action="false"></u-search>
			</view>
		</u-navbar> -->
		<zy-search :is-focus="true" :theme="themeClass" :show-want="true" :speechEngine="speechEngine"
		:hot-list="hotList" @getSearchText="getSearchText">
		
		<mescroll-body v-if="search_status" @init="mescrollInit" :down="downOption" :up="upOption" @down="downCallback" @up="upCallback">
			<view class="wrap">
				<view class="item">
					<!-- <view class="title">
						猜你喜欢
					</view> -->
					<u-empty text="暂无数据" mode="list" iconSize="280" icon="/static/image/list.png" style="margin-top: 88px; margin-bottom: auto;" :show="data_null"></u-empty>
					<u-loading-icon :show="loading_show"></u-loading-icon>
					<view class="bookcard">
						<view class="u-body-item" v-for="(item,index) in data" :key="index" @click="get_detail(item.id)">
							<!-- <image :src="item.pic" mode="aspectFill"></image> -->
							<!-- <u-lazy-load class="images" border-radius='10' height="180" :image="item.cover_url" threshold="300" img-mode="aspectFill"></u-lazy-load> -->
							<u--image :src="item.pic" radius='3' width="188rpx" height="240rpx" mode="aspectFill" :lazy-load="true" @error="imageError($event, index)">
								<view slot="loading" style="font-size: 24px;">加载中...</view>
							</u--image>
							<view class="content">
								<view class="booktitle">{{item.name}}</view>
								<view class="bookcontent u-line-3 u-light-color">
									{{item.text}}
								</view>
								<view class="bookfoot">
									<view class="tag bookfoot u-light-color" style="font-size: 12px;">
										作者：{{item.author || '未知'}}
										<!-- <u-tag style="margin-right: 10rpx;" borderColor="#A9A9A9" color="#A9A9A9" text="神魔" type="info" mode="plain" size="mini" /> -->
										<!-- <u-tag style="margin-right: 10rpx;" text="奇幻玄幻" type="info" mode="plain" size="mini" /> -->
									</view>
									<!-- <view class="msg u-light-color">
										{{item.large==0?"连载中":'完结'}}
									</view> -->
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
		</mescroll-body>
		</zy-search>
	</view>
</template>

<script>
	import zySearch from '../../components/zy-search/zy-search.vue'
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		mixins: [MescrollMixin],
		components: {
			zySearch,
			w_md5
		},
		data() {
			return {
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				data_null: false,
				keyword: '', //搜索关键词
				num: 0, //搜索页码
				loading_show: false,
				search_status: false, //搜索状态
				themeClass: 'circle', //black/块，circle/圆
				speechEngine: 'baidu', //语音识别引擎
				hotList: [],	//初始化推荐列表
				websiteUrl: '',
				app_key: '',
				data: [],
				downOption: {
					auto: false,
					offset: 50
				},
				upOption: {
					auto: false,
					use : false, 
					isBounce: true,
					textColor: "gray",
					noMoreSize: 1,
					textNoMore: "没有更多数据了",
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
			this.time = Math.round(new Date() / 1000)
			this.app_key = getApp().globalData.websiteKey
			this.token = w_md5.hex_md5_32(this.app_key + this.time)
			this.websiteUrl = getApp().globalData.websiteUrl
			this.img_websiteUrl = getApp().globalData.img_websiteUrl
			console.log(this.time)
			console.log(this.token)
			if(this.time!==''||this.app_key!==''||this.token!==''||this.websiteUrl!==''||this.img_websiteUrl!==''){
				this.get_hot_list()
			}
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
			
			tabinfo(id){
				this.$u.route({
					url: 'pages/book/bookinfo?id=' + id
				})
			},
			
			getSearchText(e) {
				this.data_null = false
				this.num = 0
				
				this.keyword = e.value
				
				// this.hotList = []
				if(this.search_status){
					this.mescroll.triggerDownScroll()
				}else{
					this.search_status = true
					this.get_novel_list(this.keyword)
					this.loading_show = true
				}
				// this.mescroll.triggerDownScroll()
				// uni.showToast({
				// 	title:'回调的搜索信息: ' + e.value,
				// 	icon:"none"
				// })
			},
			
			get_hot_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/search?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.hotList = res.data.list
					}
				})
			},
			
			get_novel_list(keyword){
				console.log(keyword)
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&key=" + keyword + "&page=1&size=20&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.data = res.data.list
						console.log(this.data)
						if(this.data.length==0){
							this.data_null = true
						}else{
							this.data_null = false
						}
						// console.log(this.data);
						this.loading_show = false
						this.mescroll.endSuccess();
					}
				})
			},
			/*下拉刷新的回调 */
			downCallback() {
				this.get_novel_list(this.keyword)
				// setTimeout(() => {
				//  	this.mescroll.endSuccess();
				// }, 2000);
				
			},
			/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
			upCallback(page) {
				console.log("页码: ",this.num)
				console.log("上拉加载")
				this.num = this.num+1
				this.get_novel_list_more(this.keyword)
				// setTimeout(() => {
				//  	this.mescroll.endErr();
				// }, 2000);
			},
		},
	}
</script>

<style scoped lang="scss">
	
	.images {
		width: 125rpx;
		flex: 0 0 120rpx;
		// height: 180rpx;
		// border-radius: 6rpx;
		margin-left: 15rpx;
	}
	
	.wrap {
		padding: 18rpx;
	}
	.item {
		margin: 30rpx 0;
		margin-top: 20rpx;
		.title {
			font-size: 38rpx;
			font-weight: bold;
			position: relative;
			line-height: 1;
			padding-left: 25rpx;
		}
	}
	.bookcard {
		margin-top: 20rpx;
	}
	.u-body-item {
		display: flex;
		height: auto;
		font-size: 32rpx;
		color: #333;
		padding: 18rpx 10rpx;
		.content {
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			margin-left: 30rpx;
			.booktitle {
				font-size: 35rpx;
				line-height: 40rpx;
			}
			.bookcontent {
				font-size: 28rpx;
				margin-top: 10rpx;
			}
			.bookfoot {
				align-items: flex-end;
				display: flex;
				justify-content: space-between;
				width: 100%;
				.tag {
					justify-content: flex-start;
				}
				.msg {
					font-size: 12px;
					align-content: flex-start;
				}
			}
		}
	}
		
	.u-body-item image {
		width: 125rpx;
		flex: 0 0 120rpx;
		height: 180rpx;
		border-radius: 8rpx;
		margin-left: 15rpx;
	}
</style>
