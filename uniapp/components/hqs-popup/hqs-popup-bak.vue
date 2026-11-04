<style lang="scss">
.hqs-popup {
	.qs-con {
		background: #fff;
	}
	.qs-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: relative;
	}
	.qs-title {
		font-size: 16px;
		// font-weight: bold;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}
	.hidden { visibility: hidden; }
	.qs-side {
		min-width: 60px;
		padding: 15px 20px;
		color: #888;
		flex-shrink: 0;
		&:active { opacity: .8; }
	}
	.qs-h-scroll {
		height: 100vh;
		// #ifdef H5
		margin-top: 44px;
		height: calc(100vh - 44px);
		// #endif
	}
	.ta-r { text-align: right; }
}
</style>

<template>
<uni-popup ref="popup" 
	:mask-click="maskClick" @change="onChange"
	:type="from" class="hqs-popup">
	<view class="qs-con" 
		:style="conStyle" 
		@mousedown="onTouch" @mousemove="onTouch" @mouseup="onTouch"
		@touchstart="onTouch" @touchmove="onTouch" @touchend="onTouch">
		<block v-if="isVertical">
			<view class="qs-header" v-if="from == 'bottom' && showHeader">
				<view class="qs-side" :class="{'hidden': !showBack && !$slots.back}"
					@click="onBack">
					<slot name="back">
						<text>返回</text>
					</slot>
				</view>
				<slot name="title">
					<text class="qs-title">{{ title }}</text>
				</slot>
				<view class="qs-side ta-r" :class="{'hidden': !showClose}"
					@click="close">
					<slot name="close">
						<u-icon :name="closeIcon" :size="16" v-if="closeIcon"></u-icon>
						<text v-else>关闭</text>
					</slot>
				</view>
			</view>
			<slot name="sub-header"></slot>
			<!-- <scroll-view scroll-y :style="{ height }"
				@scroll="onScroll">
				<view>
					<slot></slot>
				</view>
			</scroll-view> -->
			<mescroll-uni ref="mescrollRef" @init="mescrollInit" @down="downCallback" @up="upCallback" :down="downOption" :up="upOption" :height="height" bottom="65px" >
				<u-sticky>
				<u-line></u-line>
				</u-sticky>
				<view v-for="(item,index) in chapter_list" :key="index" style="display: flex;padding: 10px;">
					<view style="border:0.5px solid red;border-radius: 5px;width: 120px;height: 65px;background-size: cover;background-image: url(/static/detail/mkz_bg_chapter_loading_img.png);">
						<image :src="item.picx" style="width: 120px;height: 65px;border-radius: 5px;" lazy-load></image>
						<!-- <u-image :src="item.picx" width="120px" radius="5px" height="65px" :lazyLoad="true">
							<view slot="loading" style="width: 120px;height: 65px;border-radius: 5px;"><image src="/static/detail/mkz_bg_chapter_loading_img.png" style="width: 100%;height: 100%;border-radius: 5px;" mode="scaleToFill"></image></view>
						</u-image> -->
					</view>
					<view style="margin-left: 10px;margin-top: auto;margin-bottom: auto;">
						<view>
							<u-text :text="item.name"></u-text>
						</view>
						<view>
							<u-text :text="item.addtime | date('yyyy-mm-dd')"></u-text>
						</view>
					</view>
				</view>
			</mescroll-uni>
			<!-- <view style="height: 65px;"></view> -->
			<slot name="bottom"></slot>
		</block>
		<block v-else-if="!isVertical">
			<scroll-view scroll-y class="qs-h-scroll" :style="{ width }">
				<slot></slot>
			</scroll-view>
		</block>
	</view>
</uni-popup>
</template>

<script>
import UniPopup from './uni-popup.vue'
import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
export default {
	mixins: [MescrollMixin], // 使用mixin
	components: {
		UniPopup,
	},
	props: {
		// 弹窗显示可通过v-model控制
		value: Boolean,
		
		// 章节列表数据
		data: {
			type: Object
		},

		// 弹窗打开开始方向
		from: {
			type: String,
			default: 'bottom',
		},
		
		// 内容区边缘圆角大小
		round: {
			type: Number,
			default: 10,
		},
		// 弹窗内容宽度(当from=left或right时起作用)
		width: {
			type: String,
			default: '60vw',
		},
		// 弹窗内容高度(当from=top或bottom时起作用)
		height: {
			type: String,
			default: '50vh',
		},
		
		// 显示默认头部标题栏（仅在底部弹出时有）
		showHeader: {
			type: Boolean,
			default: true,
		},
		// 弹窗标题
		title: String,

		// 显示左侧（返回）按钮，如果v-slot:back存在时，也会显示此按钮
		showBack: Boolean,

		// 显示关闭按钮，如果是字符串，将作为uview（需另外引入）的u-icon组件的name（如showClose="close"，会显示close图标）
		showClose: {
			type: [Boolean, String],
			default: true,
		},

		// 是否可点击模态背景关闭弹窗
		maskClick: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			downOption: {
				use : false
			},
			upOption: {
				use : true,
				textColor: "gray",
				textLoading: '加载中', // 加载中的提示文本
				textNoMore: '-- 没有更多数据 --', // 没有更多数据的提示文本
				toTop: {
					// 回到顶部按钮,需配置src才显示
					src: "https://www.mescroll.com/img/mescroll-totop.png", // 图片路径
					offset: 1000, // 列表滚动多少距离才显示回到顶部按钮,默认1000
					duration: 300, // 回到顶部的动画时长,默认300ms (当值为0或300则使用系统自带回到顶部,更流畅; 其他值则通过step模拟,部分机型可能不够流畅,所以非特殊情况不建议修改此项)
					zIndex: 9990, // fixed定位z-index值
					left: null, // 到左边的距离, 默认null. 此项有值时,right不生效. (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
					right: 20, // 到右边的距离, 默认20 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
					bottom: 220, // 到底部的距离, 默认120 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
					safearea: false, // bottom的偏移量是否加上底部安全区的距离, 默认false, 需要适配iPhoneX时使用 (具体的界面如果不配置此项,则取mescroll组件props的safearea值)
					width: 72, // 回到顶部图标的宽度, 默认72 (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
					radius: "50%" // 圆角, 默认"50%" (支持20, "20rpx", "20px", "20%"格式的值, 其中纯数字则默认单位rpx)
				},
				empty: {
					use: true, // 是否显示空布局
					icon: "/static/bookshelf/mkz_default_collnull_new.png", // 图标路径
					tip: '~ 暂时还没有章节 ~', // 提示
					// btnText: '去逛逛 >', // 按钮
					fixed: false, // 是否使用fixed定位,默认false; 配置fixed为true,以下的top和zIndex才生效 (transform会使fixed失效,最终会降级为absolute)
					top: "100rpx", // fixed定位的top值 (完整的单位值,如 "10%"; "100rpx")
					zIndex: 99 // fixed定位z-index值
				},
			},
			scrollTop: 0,
			panStyle: '',
			showPopup: false,
			chapter_list: []
		}
	},
	computed: {
		closeIcon() {
			if(typeof this.showClose == 'string') return this.showClose
			return false
		},
		isVertical() {
			return ['bottom', 'top'].includes(this.from)
		},
		conStyle() {
			let style = this.panStyle || ''
			let r = this.round
			if(r > 0) {
				r += 'px'
				if(this.from == 'bottom') r = [r, r, 0, 0]
				else if(this.from == 'left') r = [0, r, r, 0]
				else if(this.from == 'right') r = [r, 0, 0, r]
				else r = [0, 0, r, r]
				style += `border-radius: ${r.join(' ')};`
			}
			return style
		},
	},
	watch: {
		value(val) {
			if(val == this.showPopup) return
			if(val) this.open()
			else this.close()
		},
		showPopup(val) {
			this.$emit('input', val)
		},
	},
	mounted() {
		if(this.value) this.open()
	},
	methods: {
		onScroll(e) {
			this.scrollTop = e.detail.scrollTop
		},
		onTouch(ev) {
			// if(!this.maskClick) return
			const { pageX, pageY } = ev.changedTouches[0] || ev
			if(['touchstart', 'mousedown'].includes(ev.type)) {
				this.startX = pageX
				this.startY = pageY
				this.startTime = ev.timeStamp
			} else {
				if(!this.startTime) return
				const orien = this.isVertical ? 'Y' : 'X'
				let moveDis = pageY - this.startY
				if(this.from == 'left') moveDis = this.startX - pageX
				else if(this.from == 'right') moveDis = pageX - this.startX
				else if(this.from == 'top') moveDis = -moveDis
				if(!this.maskClick) moveDis /= 3
				const duration = (ev.timeStamp - this.startTime)
				const speed = moveDis/duration
				if(['touchend', 'mouseup'].includes(ev.type)) {
					if(this.panStyle) {
						if(this.maskClick && (moveDis > 120 || speed > 0.25)) {
							this.close()
						}
						else {
							this.panStyle = `transform: translate${orien}(0); transition: all ease 200ms;`
						}
						setTimeout(() => {
							this.panStyle = ''
						}, 300)
					}
					// conScrollTop = 0
					this.startTime = 0
					return
				}
				// if(this.scrollTop > 0) return
				
				if(moveDis > 0) {
					if(this.from == 'left' || this.from == 'top') moveDis *= -1
					this.panStyle = `transform: translate${orien}(${moveDis}px); transition: none;`
				}
			}
		},
		onChange({ show }) {
			this.showPopup = show
		},
		open() {
			this.$refs.popup.open()
			this.showPopup = true
		},
		close() {
			this.$refs.popup.close()
			this.chapter_list = []
			// this.mescroll.resetUpScroll()
			this.showPopup = false
		},
		onBack() {
			this.$emit('back')
		},
		
		/*下拉刷新的回调 */
		downCallback() {
			// 这里加载你想下拉刷新的数据, 比如刷新轮播数据
			// loadSwiper();
			// 下拉刷新的回调,默认重置上拉加载列表为第一页 (自动执行 page.num=1, 再触发upCallback方法 )
			this.mescroll.resetUpScroll()
		},
		/*上拉加载的回调: 其中page.num:当前页 从1开始, page.size:每页数据条数,默认10 */
		upCallback(page) {
			this.chapter_list = this.data
			// uni.showToast({
			// 	title: this.chapter_list.length + ':' + this.data.length,
			// 	icon: 'none'
			// })
			// if(this.data.length >= 20){
			// 	if(this.data.length==this.chapter_list.length){
			// 		this.mescroll.endBySize(this.chapter_list.length, this.data.length);
			// 		return
			// 	}
			// 	if(page.num==1){
			// 		this.chapter_list = this.data.slice(0,20)
			// 		this.mescroll.endBySize(this.chapter_list.length, this.data.length);
			// 	}else{
			// 		this.data.slice(page.num*20-20,page.num*20).forEach(item => this.chapter_list.push(item))
			// 		this.mescroll.endBySize(this.chapter_list.length, this.data.length);
			// 		// this.chapter_list = this.chapter_list.concat(this.data.slice(page.num*20-20,page.num*20))
			// 	}
				
			// 	// uni.showToast({
			// 	// 	title:"大于20章"
			// 	// })
			// }else{
			// 	this.chapter_list = this.data
			// 	this.mescroll.endBySize(this.chapter_list.length, this.data.length);
			// 	// uni.showToast({
			// 	// 	title:"小于20章"
			// 	// })
			// }
			
			//联网加载数据
		// 	let curTab = this.tabs[this.tabIndex]
		// 	let keyword = curTab.name // 具体项目中,您可能取的是tab中的type,status等字段
		// 	apiGoods(page.num, page.size, keyword).then(res=>{
		// 		//联网成功的回调,隐藏下拉刷新和上拉加载的状态;
		// 		//mescroll会根据传的参数,自动判断列表如果无任何数据,则提示空;列表无下一页数据,则提示无更多数据;
		
		// 		//方法一(推荐): 后台接口有返回列表的总页数 totalPage
		// 		//this.mescroll.endByPage(res.list.length, res.totalPage); //必传参数(当前页的数据个数, 总页数)
		
		// 		//方法二(推荐): 后台接口有返回列表的总数据量 totalSize
		// 		//this.mescroll.endBySize(res.list.length, res.totalSize); //必传参数(当前页的数据个数, 总数据量)
		
		// 		//方法三(推荐): 您有其他方式知道是否有下一页 hasNext
		// 		//this.mescroll.endSuccess(res.list.length, res.hasNext); //必传参数(当前页的数据个数, 是否有下一页true/false)
		
		// 		//方法四 (不推荐),会存在一个小问题:比如列表共有20条数据,每页加载10条,共2页.如果只根据当前页的数据个数判断,则需翻到第三页才会知道无更多数据
		// 		this.mescroll.endSuccess(res.list.length);
		
		// 		//设置列表数据
		// 		if(page.num == 1) this.goods = []; //如果是第一页需手动制空列表
		// 		this.goods=this.goods.concat(res.list); //追加新数据
				
		// 	}).catch(()=>{
		// 		//联网失败, 结束加载
		// 		this.mescroll.endErr();
		// 	})
		},
	}
}
</script>
