<template>
    <view class="detail">
        <view v-if="loading" class="state">正在加载书籍…</view>
        <view v-else-if="error" class="state">
            <view>{{ error }}</view>
            <button size="mini" @click="loadBook">重试</button>
        </view>
        <view v-else>
            <view class="book-head">
                <image v-if="book.pic || book.picx" class="cover" :src="imageUrl(book.pic || book.picx)" mode="aspectFill"></image>
                <view class="info">
                    <view class="name">{{ book.name }}</view>
                    <view class="author">{{ book.author }}</view>
                    <view class="count">{{ chapters.length }} 节 · {{ book.state || '可阅读' }}</view>
                </view>
            </view>
            <view v-if="description" class="description">{{ description }}</view>
            <button class="start" :disabled="!chapters.length" @click="startReading">{{ progress.chapterId ? '继续阅读' : '开始阅读' }}</button>
            <view v-if="progress.chapterTitle" class="progress">上次读到：{{ progress.chapterTitle }}</view>
            <view class="catalogue-title">目录 <text>{{ chapters.length }} 节</text></view>
            <view v-if="!chapters.length" class="state">暂无可阅读章节。</view>
            <view v-for="(chapter, index) in chapters" :key="chapter.id" class="chapter" @click="openChapter(chapter.id, false)">
                <text class="chapter-number">{{ index + 1 }}</text>
                <text class="chapter-name">{{ chapter.name }}</text>
                <text v-if="Number(chapter.vip) || Number(chapter.cion)" class="locked">付费</text>
                <text v-else class="arrow">›</text>
            </view>
        </view>
    </view>
</template>

<script>
import { requestBook, bookImageUrl, bookProgressKey } from '@/common/book-api.js'

export default {
    data() {
        return { id: 0, book: {}, chapters: [], loading: true, error: '', progress: {} }
    },
    computed: {
        description() {
            return String(this.book.text || '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ')
        }
    },
    onLoad(options) {
        this.id = Number(options.id) || 0
        this.loadBook()
    },
    onShow() {
        this.progress = uni.getStorageSync(bookProgressKey(this.id)) || {}
    },
    methods: {
        imageUrl: bookImageUrl,
        async loadBook() {
            this.loading = true
            this.error = ''
            try {
                if (!this.id) throw new Error('书籍地址无效')
                const result = await Promise.all([
                    requestBook('info', { id: this.id }),
                    requestBook('chapter', { id: this.id })
                ])
                this.book = result[0].book
                this.chapters = result[1].list || []
                uni.setNavigationBarTitle({ title: this.book.name })
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },
        startReading() {
            const saved = this.chapters.find(chapter => Number(chapter.id) === Number(this.progress.chapterId))
            const chapter = saved || this.chapters[0]
            if (chapter) this.openChapter(chapter.id, Boolean(saved))
        },
        openChapter(id, resume) {
            uni.navigateTo({ url: '/pages/books/read?bid=' + this.id + '&zid=' + id + (resume ? '&resume=1' : '') })
        }
    }
}
</script>

<style scoped>
.detail{padding:36rpx 30rpx 80rpx;background:#fff;min-height:100vh;box-sizing:border-box}
.book-head{display:flex;gap:30rpx}
.cover{width:190rpx;height:264rpx;flex-shrink:0;border-radius:10rpx;background:#fff1e8}
.info{padding-top:12rpx;min-width:0}
.name{font-size:40rpx;font-weight:600;line-height:1.5;color:#222}
.author{font-size:27rpx;color:#777;margin-top:24rpx}
.count{font-size:25rpx;color:#999;margin-top:18rpx}
.description{font-size:28rpx;line-height:1.9;color:#666;margin:30rpx 0;white-space:pre-wrap}
.start{margin:32rpx 0 16rpx;background:#ff5500;color:#fff;border-radius:50rpx;font-size:30rpx}
.progress{font-size:24rpx;color:#999;text-align:center;margin-bottom:20rpx}
.catalogue-title{font-size:34rpx;font-weight:600;padding:32rpx 0 18rpx;border-bottom:1rpx solid #eee}
.catalogue-title text{font-size:25rpx;font-weight:400;color:#999;margin-left:12rpx}
.chapter{display:flex;align-items:center;padding:28rpx 0;border-bottom:1rpx solid #f3f3f3;font-size:28rpx;line-height:1.6}
.chapter-number{width:55rpx;color:#aaa;font-size:24rpx;flex-shrink:0}
.chapter-name{flex:1;color:#333}
.arrow{color:#aaa;padding-left:16rpx}.locked{font-size:23rpx;color:#ff5500;margin-left:16rpx}
.state{padding:65rpx 0;text-align:center;color:#888;font-size:28rpx}.state button{margin-top:24rpx}
</style>
