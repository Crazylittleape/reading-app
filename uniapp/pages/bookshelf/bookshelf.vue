<template>
	<view>
		<view :style="'margin-top:' + statusHeight + 'px'" style="margin-left: auto;margin-right: auto;">
			<view style="height: 30rpx;background-color: #fff;"></view>
			<view style="margin: 0 10px 0 10px;" v-if="img_gg!==''">
			    <image @click="ad()" style="height: 60px;border-radius: 6px;width: 100%;" :src="img_gg"></image>
			</view> 
		</view>
		
		
			<view class="wrap">
				<view class="item">
					<!-- <view class="title">
						{{class_title}}
					</view> -->
					<u-empty :text="data_null_text" mode="list" iconSize="280" icon="/static/bookshelf/mkz_default_collnull_new.png" style="margin-top: 88px; margin-bottom: auto;" :show="data_null"></u-empty>
					<u-loading-icon :show="loading"></u-loading-icon>
					<view class="bookcard">
						<!-- <u-swipe-action>
						    <u-swipe-action-item
						      :options="options"
							  v-for="(item,index) in data" :key="index"
							  @click="del(item.book.id)"
						    > -->
						<option-list
						        :list="data"
						        :threshold="threshold"
						        @click="rowClick"
						        @optionClick="optionClick"
						        :options="options">
								<template slot-scope="{ item }">
						<view class="u-body-item"  @click="get_detail(item.mid)">
							<!-- <image :src="item.pic" mode="aspectFill"></image> -->
							<!-- <u-lazy-load class="images" border-radius='10' height="180" :image="item.cover_url" threshold="300" img-mode="aspectFill"></u-lazy-load> -->
							<u--image :src="item.picx" radius='3' width="266rpx" height="155rpx" mode="aspectFill" :lazy-load="true" @error="imageError(item)">
								<view slot="loading" style="font-size: 24px;">加载中...</view>
							</u--image>
							<view class="content">
								<view class="booktitle">{{item.name}}</view>
								<!-- <view class="bookcontent u-line-2 u-light-color">
									{{item.book.summary}}
								</view> -->
								<view class="bookfoot">
									<view class="tag u-line-1 bookfoot u-light-color" style="font-size: 12px;">
										最新: {{item.news_name}}
										<!-- {{item.book.tags}} -->
										<!-- <u-tag style="margin-right: 10rpx;" borderColor="#A9A9A9" color="#A9A9A9" text="神魔" type="info" mode="plain" size="mini" /> -->
										<!-- <u-tag style="margin-right: 10rpx;" text="奇幻玄幻" type="info" mode="plain" size="mini" /> -->
									</view>
									<!-- <view class="msg u-light-color">
										{{item.large==0?"连载中":'完结'}}
									</view> -->
								</view>
							</view>
						</view>
						</template>
						<!-- </u-swipe-action-item>
						</u-swipe-action> -->
						</option-list>
					</view>
					<!-- <u-divider lineColor="#fff" textSize="11" text="到底啦~不要再拉啦^_^" v-show="divider_show"></u-divider> -->
				</view>
			</view>
		    
	</view>
</template>

<script>
	import optionList from '@/components/gzz-option-list/option-list.vue';
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	export default {
		components: {
		    w_md5,
			optionList
		},
		data() {
			return {
				loading: false,
				data_null: false,
				data_null_text: '暂无内容',
				statusHeight: 0,
				img_gg: '',
				url_gg: '',
				// img_gg: 'https://image.zymkcdn.com/file/news/000/004/456.jpg-1920x560.webp',
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				data: '',
				// options: [{
    //                 text: '删除',
				// 	style: {
    //                     backgroundColor: 'red',
    //                 }
    //             }],
                options: [//左滑的操作列表
                    {
                        color: '#ffffff', //字体颜色，非必须，默认 #ffffff
                        bgColor: 'red', //背景颜色，非必须，默认 #409EFF
                        text: '删除', //展示文字
                        width: 120, //按钮宽度 默认120rpx 适合两个字的宽度 这个宽度按需调整
                    }
                ],
                threshold: 50,
			}
		},
		onLoad() {
			this.statusHeight = uni.getSystemInfoSync().statusBarHeight
			this.req_ad()
		},
		onShow() {
			this.utoken = getApp().globalData.utoken
			
			if(!uni.getStorageSync('user')){
				console.log("未登录",uni.getStorageSync('user'))
				this.data_null = true
				this.data_null_text = "登录就可以同步书架啦^_^"
				this.data = []
			}else{
				console.log("已登录",uni.getStorageSync('user'))
				this.data_null = false
				this.data_null_text = "还没有收藏过漫画，快去追一部吧"
				this.get_bookshelf_list()
			}
		},
		onPullDownRefresh() {
			if(!uni.getStorageSync('user')){
				this.data = []
				this.data_null = true
				this.data_null_text = "登录就可以同步书架啦^_^"
				uni.stopPullDownRefresh()
			}else{
				this.data_null = false
				this.data_null_text = "还没有收藏过漫画，快去追一部吧"
				this.get_bookshelf_list()
			}
		},
		methods: {
			imageError: function(item) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
				this.$set(item, 'picx', '../../static/img_moren.jpg')
			},
			req_ad(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/kxapps/ads?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.img_gg = res.data.list[0].img
						this.url_gg = res.data.list[0].url
					}
				})
			},
            rowClick(item){
                console.log('点击某一行');
                console.log(item)
            },
            optionClick(item,oitem){
                console.log('点击某个操作按钮');
                console.log(item,oitem)
                if (oitem.text == '删除'){ //这里直接根据text判断点击了哪个按钮，也可自自己增加唯一标识key，当然也可以自己在组件按需定义对应事件
                    this.loading = true
                    this.del_bookshelf(item.mid)
                }else if(oitem.text == '编辑'){
                    this.listEdit(item);
                }
            },
			del(id){
				console.log(id)
				this.loading = true
				this.del_bookshelf(id)
			},
			get_detail(book_id){
				console.log(book_id)
				uni.navigateTo({
					url:'../detail/detail?id=' + book_id
				})
			},
			get_bookshelf_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						uni.stopPullDownRefresh()
						this.loading = false
						console.log("用户书架: ",res.data);
						if(res.data.code == 1){
							console.log("获取书架成功",res.data.list)
							this.data = res.data.list
							if(this.data.length==0){
								this.data_null = true
							}else{
								this.data_null = false
							}
						}else{
							this.data_null = true
						}
				    }
				});
			},
			del_bookshelf(id){
				console.log(id)
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&ids=" + id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/del/comic?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log(res.data)
						if(res.data.code == 1){
							console.log("删除书架成功")
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
							this.get_bookshelf_list()
						}else{
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: '删除失败'
							});
						}
				    }
				});
				
			},
			ad(){
				// plus.runtime.openWeb(this.url_gg)
				uni.navigateTo({
					url:'../webview/webview?uri=' + this.url_gg
				})
			}
		}
	}
</script>

<style lang="scss">
	.wrap {
		margin: 10px;
		/* padding: 18rpx; */
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
			margin-left: 10px;
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
