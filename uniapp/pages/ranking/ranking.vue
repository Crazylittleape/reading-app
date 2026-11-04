<template>
	<view class="page-warp">
		
		<!-- <view class="top-warp">
			<view>顶部区域</view>
			<view style="font-size: 24rpx;">mescroll-uni 支持局部区域滚动,支持使用flex嵌在某个view</view>
		</view> -->
		
		<view class="center-warp">
			<!-- 左边 -->
			<scroll-view class="left-warp" :scroll-y="true">
				<view class="tab" :class="{active:i==tabIndex}" v-for="(tab,i) in tabs" :key="i" @click="tabChange(i)">
					<view :class="{act:i==tabIndex}">
						<view style="text-align: right;margin-right: 18px;">
						{{tab}}
						</view>
					</view>
				</view>
			</scroll-view>
			
			<view class="right-warp">
				<!--右边 :fixed="false", 高度跟随父元素 (不在组件上定义class,避免部分小程序平台编译丢失, 如支付宝,钉钉小程序) -->
				<mescroll-uni :fixed="false" ref="mescrollRef" @init="mescrollInit" :down="downOption" :up="upOption" @down="downCallback">
					<view class="wrap">
						<view class="item">
							<!-- <view class="title">
								{{class_title}}
							</view> -->
							<u-empty text="暂无内容" mode="list" iconSize="280" icon="/static/image/list.png" style="margin-top: 88px; margin-bottom: auto;" :show="data_null"></u-empty>
							<view class="bookcard">
								<view class="u-body-item" v-for="(item,index) in data" :key="index" @click="get_detail(item.id)">
									<!-- <image :src="item.pic" mode="aspectFill"></image> -->
									<!-- <u-lazy-load class="images" border-radius='10' height="180" :image="item.cover_url" threshold="300" img-mode="aspectFill"></u-lazy-load> -->
									<u--image :src="item.pic" radius='3' width="188rpx" height="240rpx" mode="aspectFill" :lazy-load="true" :showError="false" @error="imageError($event, index)">
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
							<u-divider lineColor="#fff" textSize="11" text="到底啦~不要再拉啦^_^" v-show="divider_show"></u-divider>
						</view>
					</view>
				</mescroll-uni>
			</view>
		</view>
		
		<!-- <view class="bottom-warp"> 底部区域 </view> -->
		
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
				divider_show: false,
				data_null: false,
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				img_websiteUrl: '',
				data: [], // 数据列表
				// tabs: [], // tabs异步获取
				tabs: ['人气榜','今日榜','本周榜','本月榜','完结榜'], //人气榜=总榜
				tabIndex: 0 ,// tab下标
				downOption: {
					auto: true,
					// offset: 50,
					beforeEndDelay: 300
				},
				upOption: {
					auto: false,
					use : false, 
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
			get_class_list(i){
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				if(i==0){
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=20&sort=hits&timestamp=" + time
				}
				if(i==1){
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=20&sort=rhits&timestamp=" + time
				}
				if(i==2){
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=20&sort=zhits&timestamp=" + time
				}
				if(i==3){
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=20&sort=yhits&timestamp=" + time
				}
				if(i==4){
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=1&size=20&sort=yhits&state=2&timestamp=" + time
				}
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/comic/data?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(i+"排行列表: ",res.data);
						if(res.data.code==1){
							this.data = res.data.list
							if(this.data.length==0){
								this.data_null = true
							}else{
								this.data_null = false
								this.divider_show = true
							}
							
							this.mescroll.endSuccess();
						}else{
							this.data_null = true
							this.mescroll.endErr()
							uni.showToast({
								icon:'none',
								title:res.data.msg
							})
						}
					}
				})
			},
			/*下拉刷新的回调 */
			downCallback() {
				this.get_class_list(this.tabIndex)
				// setTimeout(() => {
				//  	this.mescroll.endSuccess();
				// }, 2000);
				
			},
			// 切换菜单
			tabChange(i){
				if(this.tabIndex != i){
					this.divider_show = false
					this.data_null = false
					this.tabIndex = i
					this.data = []; // 先置空列表,显示加载进度条
					// this.mescroll.resetUpScroll(); // 重置列表数据
					this.mescroll.triggerDownScroll()
				}
			}
		}
	}
</script>

<style lang="scss">
	/*根元素需要有固定的高度*/
	page{
		height: 100%;
		// 支付宝小程序,钉钉小程序需添加绝对定位,否则height:100%失效: https://opendocs.alipay.com/mini/framework/acss#%E5%B8%B8%E8%A7%81%E9%97%AE%E9%A2%98
		/* #ifdef MP-ALIPAY || MP-DINGTALK*/
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		/* #endif */
		
		/*需给父元素设置height:100%*/
		.page-warp{
			height: 100%;
			display: flex;
			flex-direction: column;
			
			/* 顶部区域 */
			.top-warp{
				font-size: 28rpx;
				padding: 20rpx;
				text-align: center;
				background-color: #CFE0DA;
			}
			
			/* 中间 */
			.center-warp{
				flex: 1;
				min-width: 0;
				min-height: 0;/* 需给flex:1的元素加上最小高,否则内容超过会溢出容器 (如:小程序Android真机) */
				// border: 4px solid red;
				display: flex;
				
				// 左边
				.left-warp{
					width: 160rpx;
					height: 100%;
					background-color: #fff;
					.tab{
						font-size: 28rpx;
						padding: 28rpx 0 28rpx 28rpx;
						
					}
					.act{
						// height: 25px;
						padding: 5px 0 5px 0;
						border-radius: 50px;
						margin-left: -80rpx;
						background-color: #ff5500;
						color: #fff;
						font-weight: 500;
					}
				}
				
				// 右边
				.right-warp{
					flex: 1;
					min-width: 0;
				}
			}
			
			/* 底部区域 */
			.bottom-warp{
				padding: 20rpx;
				text-align: center;
				background-color: #FF6990;
			}
		}
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
			margin-left: 20rpx;
			.booktitle {
				font-size: 30rpx;
				font-weight: 500;
				line-height: 40rpx;
			}
			.bookcontent {
				font-size: 23rpx;
				margin-top: 0rpx;
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
</style>
