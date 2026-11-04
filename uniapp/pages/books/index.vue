<template>
    <view class="library">
        <view class="heading">发现好书</view>
        <view class="subtitle">从一本书开始，慢慢读。</view>
        <view v-if="error" class="message">
            <text>{{ error }}</text>
            <button size="mini" class="retry" @click="loadBooks(true)">重新加载</button>
        </view>
        <view v-for="book in books" :key="book.id" class="book-row" @click="openBook(book.id)">
            <image v-if="book.pic || book.picx" class="cover" :src="imageUrl(book.pic || book.picx)" mode="aspectFill"></image>
            <view v-else class="cover cover-empty">书籍</view>
            <view class="book-info">
                <view class="book-name">{{ book.name }}</view>
                <view class="author">{{ book.author || '作者未填写' }}</view>
                <view class="description">{{ plainText(book.text) || '点击查看目录，开始阅读。' }}</view>
                <text class="read-link">查看书籍 ›</text>
            </view>
        </view>
        <view v-if="loading" class="state">正在加载书籍…</view>
        <view v-else-if="!error && !books.length" class="state">书库暂时没有书籍。</view>
        <view v-else-if="!error && !hasMore" class="state">已显示全部书籍</view>
    </view>
</template>

<script>
import { requestBook, bookImageUrl } from '@/common/book-api.js'

export default {
    data() {
        return { books: [], loading: false, error: '', page: 0, hasMore: true }
    },
    onLoad() {
        this.loadBooks(true)
    },
    onPullDownRefresh() {
        this.loadBooks(true)
    },
    onReachBottom() {
        if (this.hasMore) this.loadBooks(false)
    },
    methods: {
        imageUrl: bookImageUrl,
        plainText(value) {
            return String(value || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ')
        },
        openBook(id) {
            uni.navigateTo({ url: '/pages/books/detail?id=' + id })
        },
        async loadBooks(reset) {
            if (this.loading) {
                uni.stopPullDownRefresh()
                return
            }
            this.loading = true
            this.error = ''
            const page = reset ? 1 : this.page + 1
            try {
                const result = await requestBook('data', { page, size: 20, sort: 'id' })
                const list = result.list || []
                this.books = reset ? list : this.books.concat(list)
                this.page = page
                this.hasMore = page < Number(result.pagejs)
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
                uni.stopPullDownRefresh()
            }
        }
    }
}
</script>

<style scoped>
.library{padding:36rpx 30rpx;background:#fff;min-height:100vh;box-sizing:border-box}
.heading{font-size:44rpx;font-weight:600;color:#222}
.subtitle{font-size:26rpx;color:#999;margin:12rpx 0 34rpx}
.book-row{display:flex;padding:30rpx 0;border-bottom:1rpx solid #f2f2f2}
.cover{width:176rpx;height:242rpx;border-radius:10rpx;flex-shrink:0;background:#fff1e8}
.cover-empty{display:flex;align-items:center;justify-content:center;color:#ff5500}
.book-info{padding-left:28rpx;flex:1;min-width:0}
.book-name{font-size:34rpx;font-weight:600;line-height:1.5;color:#222}
.author{font-size:24rpx;color:#888;margin:12rpx 0}
.description{font-size:26rpx;color:#777;line-height:1.8;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.read-link{display:inline-block;color:#ff5500;font-size:26rpx;margin-top:18rpx}
.message,.state{padding:45rpx 0;text-align:center;font-size:28rpx;color:#888}
.retry{display:block;margin-top:20rpx;color:#ff5500;background:#fff4ed}
</style>
