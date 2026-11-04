<template>
	<view>
		<!-- <view style="background-color: #eee;position: fixed;top: 0;left: 0;right: 0;height: 1px;"></view> -->
		<mescroll-body ref="mescrollRef" @init="mescrollInit" @down="downCallback" @up="upCallback" :down="downOption" :up="upOption" >
		    <view style="margin: 0 30rpx;">
		    	<view style="border-bottom: 1px dashed #eee;padding: 30rpx 0;" v-for="(item,index) in data" :key="index">
		    		<view style="display: flex;height: 40rpx;justify-content: space-between;line-height: 40rpx;font-size: 28rpx;">
		    			<view style="color: #111;line-height: 40rpx;width: calc(100% - 230rpx);overflow: hidden;text-overflow: ellipsis;white-space: nowrap;">
		    				支出类型：用户消费
		    			</view>
		    			<view style="text-align: right;width: 230rpx;">
		    				<!-- <text style="color:#FB4246;" v-if="item.changeType==0">-</text> -->
		    				<text style="color: red;">-</text>
		    				<text>{{item.cion}}</text>
		    			</view>
		    		</view>
		    		<view style="color: #999;font-size: 24rpx;font-weight: 400;line-height: 34rpx;margin: 20rpx 0 8rpx;">
		    			<text>消费摘要：{{item.text}}</text>
		    		</view>
		    		<view style="color: #999;font-size: 24rpx;font-weight: 400;height: 34rpx;line-height: 34rpx;">
		    			<text>消费时间：{{item.addtime}}</text>
		    		</view>
		    	</view>
		    	<view v-if="data.length==0" style="height: 270rpx;margin: 100rpx auto 40rpx;position: relative;width: 100%;">
		    		<image style="width: 100%;height: 100%;" src="https://oss.byteox.com/byteox/gj7p2xsf/2/cdab1062586a42699d782b32a0bb4582.png"></image>
		    	</view>
		    	<view v-if="data.length==0" style="font-size: 26rpx;text-align:center;color:rgba(187,187,187,1);padding-bottom:86rpx;">
		    		<text>暂无明细</text>
		    	</view>
		    </view>
		</mescroll-body>
	</view>
</template>

<script>
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
			w_md5
		},
		mixins: [MescrollMixin], // 使用mixin
		data() {
			return {
				data: [],
				page: 1,
				size: 3,
				downOption: {
					use: true,
					auto: true, // 是否在初始化完毕之后自动执行下拉刷新的回调; 默认true
					beforeEndDelay: 0, // 延时结束的时长 (显示加载成功/失败的时长, android小程序设置此项结束下拉会卡顿, 配置后请注意测试)
					offset: 60, // 在列表顶部,下拉大于80upx,松手即可触发下拉刷新的回调
					bgColor: "#fff", // 背景颜色 (建议在pages.json中再设置一下backgroundColorTop)
					textColor: "gray", // 文本颜色 (当bgColor配置了颜色,而textColor未配置时,则textColor会默认为白色)
					textInOffset: '下拉刷新', // 下拉的距离在offset范围内的提示文本
					textOutOffset: '释放更新', // 下拉的距离大于offset范围的提示文本
					textLoading: '加载中 ...' // 加载中的提示文本
				},
				upOption: {
					use: true, // 是否启用上拉加载; 默认true
					auto: false, // 是否在初始化完毕之后自动执行上拉加载的回调; 默认true
					page: {
						num: 0, // 当前页码,默认0,回调之前会加1,即callback(page)会从1开始
						size: 10, // 每页数据的数量
						time: null // 加载第一页数据服务器返回的时间; 防止用户翻页时,后台新增了数据从而导致下一页数据重复;
					},
					noMoreSize: 6, // 如果列表已无数据,可设置列表的总数量要大于等于5条才显示无更多数据;避免列表数据过少(比如只有一条数据),显示无更多数据会不好看
					bgColor: "transparent", // 背景颜色 (建议在pages.json中再设置一下backgroundColorTop)
					textColor: "gray", // 文本颜色 (当bgColor配置了颜色,而textColor未配置时,则textColor会默认为白色)
					textLoading: '加载中 ...', // 加载中的提示文本
					textNoMore: '-- 没有更多数据啦 --', // 没有更多数据的提示文本
					empty: {
						use: false, // 是否显示空布局
						icon: "https://www.mescroll.com/img/mescroll-empty.png", // 图标路径
						tip: '~ 暂无相关数据 ~', // 提示
						btnText: '按钮', // 按钮
						fixed: false, // 是否使用fixed定位,默认false; 配置fixed为true,以下的top和zIndex才生效 (transform会使fixed失效,最终会降级为absolute)
						top: "100rpx", // fixed定位的top值 (完整的单位值,如 "10%"; "100rpx")
						zIndex: 99 // fixed定位z-index值
					},
				}
			}
		},
		onLoad() {
			// this.req_data()
		},
		methods: {
			req_data(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&page=" + this.page + "&size=" + this.size + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/user/consume?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("消费明细：",res.data)
						if(res.data.code==1){
							this.data = this.data.concat(res.data.list)
							this.mescroll.endByPage(res.data.list.length, res.data.pagejs);
						}else{
							this.mescroll.endErr();
							uni.showToast({
								icon:"none",
								position:"bottom",
								title:"数据请求错误"
							})
							uni.navigateBack({
								
							})
						}
				    }
				});
			},
			/*下拉刷新的回调 */
			downCallback() {
				// 这里加载你想下拉刷新的数据, 比如刷新轮播数据
				// loadSwiper();
				// 下拉刷新的回调,默认重置上拉加载列表为第一页 (自动执行 page.num=1, 再触发upCallback方法 )
				this.data = []
				this.mescroll.resetUpScroll()
			},
			/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
			upCallback(page) {
				this.page = page.num
				this.size = page.size
				this.req_data()
			}
		}
	}
</script>

<style>

</style>
