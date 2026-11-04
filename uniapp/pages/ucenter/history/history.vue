<template>
	<view>
		<view class="wrap">
			<view class="item">
				<!-- <view class="title">
					{{class_title}}
				</view> -->
				<u-empty text="您还没有阅读过书籍哟" mode="list" iconSize="280" icon="/static/image/list.png" style="margin-top: 88px; margin-bottom: auto;" :show="data_null"></u-empty>
				<u-loading-icon :show="loading"></u-loading-icon>
				<view class="bookcard">
					<!-- <u-swipe-action>
					    <u-swipe-action-item
					      :options="options"
						  v-for="(item,index) in data" :key="index"
						  @click="del(index)"
					    > -->
					<option-list
					        :list="data"
					        :threshold="threshold"
					        @click="rowClick"
					        @optionClick="optionClick"
					        :options="options">
							<template slot-scope="{ item }">
					<view class="u-body-item"  @click="get_detail(item)">
						<!-- <image :src="item.pic" mode="aspectFill"></image> -->
						<!-- <u-lazy-load class="images" border-radius='10' height="180" :image="item.cover_url" threshold="300" img-mode="aspectFill"></u-lazy-load> -->
						<u--image :src="item.book_img" radius='3' width="266rpx" height="155rpx" mode="aspectFill" @error="imageError(item)">
							<view slot="loading" style="font-size: 24px;">加载中...</view>
						</u--image>
						<view class="content">
							<view class="booktitle">{{item.book_name}}</view>
							<view class="bookcontent u-line-2 u-light-color">
								<!-- {{item.book.summary}} -->
							</view>
							<view class="bookfoot">
								<view class="tag u-line-1 bookfoot u-light-color" style="font-size: 12px;">
									阅读至：{{item.chapter_name}}
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
	export default {
		components: {
			optionList
		},
		data() {
			return {
				loading: false,
				data_null: false,
				data: '',
				// options: [{
				//     text: '删除',
				// 	style: {
				//         backgroundColor: 'red',
				//     }
				// }],
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
		onShow() {
			this.loading = true
			this.get_history_list()
		},
		onPullDownRefresh() {
			this.get_history_list()
		},
		methods: {
			imageError: function(item) {
				// console.error('image发生error事件，携带值为' + e.detail.errMsg)
				this.$set(item, 'book_img', '../../../static/img_moren.jpg')
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
			        this.data.splice(item.item_id,1)
			        uni.setStorageSync('mh_history',this.data)
			        this.get_history_list()
			    }else if(oitem.text == '编辑'){
			        this.listEdit(item);
			    }
			},
			del(index){
				this.loading = true
				this.data.splice(index,1)
				uni.setStorageSync('mh_history',this.data)
				this.get_history_list()
			},
			get_detail(item){
				console.log(item)
				getApp().globalData.book_img = item.book_img
				uni.navigateTo({
					url:'../../read/read?book_id=' + item.book_id
				})
			},
			get_history_list(){
				let his = uni.getStorageSync('mh_history')
				if (typeof his !== 'object') {
					this.data = []
				}else{
					this.data = his
				}
				if(this.data.length==0){
					this.data_null = true
				}else{
					this.data_null = false
				}
				this.loading = false
				uni.stopPullDownRefresh()
				console.log(this.data)
			},
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
