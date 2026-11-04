<template>
	<view class="text_ellipsis_wrap" ref="boxWrap" :style="wrapStyle">
		<view @tap="toggleShowMore">
		<slot />
		</view>
		
		<view
			class="show_more"
			v-if="showMore && overflow"
			:style="{ background: showMoreBg }"
			@tap="toggleShowMore"
		>
			<text v-if="!isOpen">...</text>
			<text class="color_primary" style="color: #ff5500;">{{ showLabel }}</text>
		</view>

		<!-- <view class="shrink color_primary" v-if="isOpen && opened" @tap="toggleShowMore">
			{{ shrinkLabel }}
		</view> -->
	</view>
</template>

<script>
/**
 * TextEllipsis 文本缩略/展开
 * @description 此组件一般用于内容缩略显示和展开
 * @property {Number} lineClamp 显示行数，超过则缩略显示
 * @example <text-ellipsis>默认超过三行缩略显示</text-ellipsis>
 */
export default {
	name: 'TextEllipsis',

	props: {
		// 显示行数
		lineClamp: {
			type: Number,
			default: 3
		},
		// 字体大小
		fontSize: {
			type: String,
			default: '26rpx'
		},
		// 行高(单位rpx)
		lineHeight: {
			type: Number,
			default: 45
		},
		// 显示`全文`字眼
		showMore: {
			type: Boolean,
			default: true
		},
		// 全文展示文本
		showMoreLabel: {
			type: String,
			default: '全文'
		},
		// 全文展示文本背景色
		showMoreBg: {
			type: String,
			default: '#FFFFFF'
		},
		// 显示`收起`字眼
		showShrink: {
			type: Boolean,
			default: true
		},
		// 收起文本
		shrinkLabel: {
			type: String,
			default: '收起'
		}
	},

	data() {
		return {
			overflow: false, // 文本是否溢出
			isOpen: false, // 当前是否展开
			opened: false, // 是否展开过
			calculated: false // 计算过高度是否溢出
		};
	},

	computed: {
		wrapStyle() {
			const { lineClamp, lineHeight, fontSize, isOpen, calculated } = this;
			const multiline = lineClamp > 1;
			const result = {
				lineClamp,
				fontSize,
				lineHeight: `${lineHeight}rpx`,
				whiteSpace: multiline ? 'wrap' : 'nowrap'
			};
			if (multiline && !isOpen && calculated) {
				result.display = '-webkit-box';
			}
			return result;
		},

		showLabel() {
			const { showMore, overflow, isOpen, showMoreLabel, shrinkLabel } = this;

			return showMore && overflow && !isOpen ? showMoreLabel : shrinkLabel;
		}
	},

	mounted() {
		this.setShowMore();
	},

	updated() {
		this.setShowMore();
	},

	methods: {
		async setShowMore() {
			await this.$nextTick();

			const query = uni.createSelectorQuery().in(this);
			query
				.select('.text_ellipsis_wrap')
				.boundingClientRect(data => {
					// 文本内容高度是否超过 显示行数*行高(单位px)
					const isOver = data.height > this.lineClamp * (this.lineHeight / 2);

					if (isOver && !this.overflow) {
						this.overflow = true;
					}

					this.calculated = true;
				})
				.exec();
		},

		toggleShowMore() {
			this.isOpen = !this.isOpen;

			this.opened = true;
		},
		toggleShow() {
			this.isOpen = !this.isOpen;
		
			this.opened = true;
		}
	}
};
</script>

<style lang="scss" scoped>
.text_ellipsis_wrap {
	position: relative;
	text-overflow: ellipsis;
	overflow: hidden;
	-webkit-box-orient: vertical;

	.show_more {
		position: absolute;
		line-height: inherit;
		right: 0;
		bottom: 0;
		opacity: 0.9;
		padding: 0 5rpx;
	}

	.shrink {
		text-align: right;
	}
}
</style>
