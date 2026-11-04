<template>
	<view>
		<mescroll-uni height="100%" @init="mescrollInit" :down="downOption" :up="upOption" @down="downCallback">
			<u-empty text="暂无评论" mode="list" iconSize="280" icon="/static/image/list.png" style="margin-top: auto; margin-bottom: auto;" :show="comment_list.length==0"></u-empty>
			<view style="width: 100%;margin: 30rpx; font-size:28rpx;" v-for="(item,index) in comment_list">
				<view style="display: flex;">
				    <u-avatar
				        :src="item.upic"
				        fontSize="18"
				        randomBgColor
						style="margin-right: 10px;"
				    ></u-avatar>
					<view style="width: 100%;margin-right: 30px;">
						<text style="color:#A9A9A9;font-weight: bold;">
				            {{item.unichen}}
						</text>
						<view style="font-size:32rpx;margin-top: 10rpx;">
							{{item.text}}
						</view>
						<text style="color:#A9A9A9;font-weight: 300;font-size:22rpx;">
						    {{item.addtime}}
						</text>
						<view style="height: 10px;"></view>
						<view @click="get_detail(item.mid)" style="background-color: #F0F0F0;width: 100%;height: 80px;padding: 10px 0 10px 0px;display: flex;border-radius: 3px;">
							<u--image :src="item.comic.pic" width="60px" height="80px" style="margin-left: 10px;" @error="imageError($event, index)">
								<view slot="loading" style="font-size: 24px;">加载中...</view>
							</u--image>
							<view style="margin-left: 10px;display: flex;flex-direction: column;justify-content: space-between;">
								<view style="font-weight: bold;">
									{{item.comic.name}}
								</view>
								<view style="font-size: 25rpx;color: #A9A9A9;">
									状态: {{item.comic.serialize}}
								</view>
							    <view style="font-size: 25rpx;color: #A9A9A9;">
									<u-text :text="item.comic.text" :lines="2" size="25rpx" color="#A9A9A9">
									</u-text>
							    </view>
							</view>
						</view>
						<!-- <u-line></u-line> -->
						<view style="height: 10px;"></view>
						<u-gap height="0.5" bgColor="#e8e8e8"></u-gap>
					</view>
				</view>
			</view>
		</mescroll-uni>
	</view>
</template>

<script>
	import w_md5 from "../../../js_sdk/zww-md5/w_md5.js"
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	export default {
		mixins: [MescrollMixin], // 使用mixin
		components: {
		    w_md5
		},
		data() {
			return {
				username: '神秘人',
				comment_list: '',
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				downOption: {
					use : true,
					auto: true,
					autoShowLoading: true,
					offset: 50
				},
				upOption: {
					use : false
				}
			}
		},
		onLoad(option) {
			this.username = option.username
			this.get_comment_list()
		},
		methods: {
			imageError: function(e, index) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
			    this.comment_list[index]['book']['cover_url'] = '../../../static/img_error.jpg'
			},
			get_detail(book_id){
				console.log(book_id)
				uni.navigateTo({
					url:'../../detail/detail?id=' + book_id
				})
			},
			get_comment_list(){
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/kxapps/comment?" + param + "&sign=" + sign,
					success: (res) => {
						console.log("评论列表: ",res.data);
						this.comment_list = res.data.list
						this.mescroll.endSuccess();
					}
				})
				
			},
			downCallback() {
				console.log("目录下拉刷新")
				this.get_comment_list()
				// setTimeout(() => {
				//  	this.mescroll.endSuccess();
				// }, 2000);
			},
		}
	}
</script>

<style>

</style>
