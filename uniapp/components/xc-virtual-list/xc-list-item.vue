<template>
	<view class="item" style="display: flex;padding: 15px;" @click="click(item.id)">
		<view style="position: relative; border:0.5px solid #eee;border-radius: 5px;width: 120px;height: 65px;background-size: cover;background-image: url(/static/detail/mkz_bg_chapter_loading_img.png);">
			<image :src="item.picx" style="width: 120px;height: 65px;border-radius: 5px;" lazy-load></image>
			<view style="position: absolute;left: 0px;top: -2px;border-radius: 0px;" v-if="item.cion!=='0'">
				<image src="/static/detail/mkz_ic_detaillist_vip_w.png" style="width: 48rpx;height: 32rpx;border-radius: 0px;" lazy-load></image>
			</view>
			<view style="position: absolute;left: 0px;top: -7rpx;border-radius: 0px;" v-else-if="item.vip!=='0'">
				<image src="/static/detail/mkz_ic_readlist_vip.png" style="width: 56rpx;height: 28rpx;border-radius: 0px;" lazy-load></image>
			</view>
			<!-- <u-image :src="item.picx" width="120px" radius="5px" height="65px" :lazyLoad="true">
				<view slot="loading" style="width: 120px;height: 65px;border-radius: 5px;">
					<image src="/static/detail/mkz_bg_chapter_loading_img.png" style="width: 100%;height: 100%;border-radius: 5px;" mode="scaleToFill"></image>
				</view>
			</u-image> -->
		</view>
		<view style="margin-left: 13px;margin-top: auto;margin-bottom: auto;">
			<view style="line-height: 50rpx;">
				<u-text :text="item.name" size="14"></u-text>
			</view>
			<view style="line-height: 50rpx;">
				<u-text :text="item.addtime | date('yyyy-mm-dd')" color="grey" size="12"></u-text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: {
			item: {
				type: Object,
				default: () => ({
					id: 1,
					picx: '/static/detail/mkz_bg_chapter_loading_img.png',
					name: 1,
					addtime: '2020-01-01 22:22:22',
					cion: '0',
					vip: '0'
				})
			},
			id: Number,
		},
		methods: {
			click(chapter_id){
				uni.navigateTo({
					url:'/pages/read/read?book_id=' + this.id + '&id=' + chapter_id
				})
			},
		}
	}
</script>

<style>
</style>
