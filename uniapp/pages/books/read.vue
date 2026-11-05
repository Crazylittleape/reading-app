<template>
    <view class="reader" :style="themeStyle">
        <view class="toolbar">
            <button size="mini" @click="showCatalogue = !showCatalogue">目录</button>
            <view class="font-controls">
                <button size="mini" :disabled="fontSize <= 16" @click="changeFont(-2)">A−</button>
                <button size="mini" :disabled="fontSize >= 30" @click="changeFont(2)">A＋</button>
            </view>
        </view>
        <view class="theme-controls" aria-label="阅读背景">
            <text class="theme-label">背景</text>
            <button v-for="item in themes" :key="item.id" size="mini" class="theme-option" :class="{ selected: themeId === item.id }" :aria-pressed="themeId === item.id" @click="changeTheme(item.id)">
                <view class="theme-swatch" :style="{ backgroundColor: item.background }"></view>
                <text>{{ item.name }}</text>
            </button>
        </view>
        <view v-if="loading" class="state">正在加载正文…</view>
        <view v-else-if="error" class="state">
            <view>{{ error }}</view>
            <button size="mini" @click="loadChapter(requestedChapterId, true)">重试</button>
        </view>
        <view v-else class="content" :style="{ fontSize: fontSize + 'px' }">
            <view class="chapter-title">{{ chapter.name }}</view>
            <view v-for="(block, index) in blocks" :key="index" class="paragraph">
                <image v-if="block.image" class="illustration" :src="block.image" mode="widthFix" @load="imageLoaded" @error="imageLoaded"></image>
                <text v-else selectable>{{ block.text }}</text>
            </view>
            <view v-if="Number(chapter.pay) !== 0" class="access-note">{{ accessMessage }}</view>
            <view v-if="!blocks.length" class="state">本节暂无正文。</view>
            <view class="chapter-nav">
                <button size="mini" :disabled="!Number(chapter.szid)" @click="goChapter(chapter.szid)">上一节</button>
                <button size="mini" @click="showCatalogue = true">目录</button>
                <button size="mini" :disabled="!Number(chapter.xzid)" @click="goChapter(chapter.xzid)">下一节</button>
            </view>
        </view>
        <view v-if="showCatalogue" class="catalogue-mask" @click="showCatalogue = false">
            <view class="catalogue-panel" @click.stop>
                <view class="catalogue-header"><text>目录</text><text @click="showCatalogue = false">关闭</text></view>
                <scroll-view scroll-y class="catalogue-scroll">
                    <view v-for="(item, index) in chapters" :key="item.id" class="catalogue-item" :class="{ current: Number(item.id) === chapterId }" @click="goChapter(item.id)">{{ index + 1 }}　{{ item.name }}</view>
                </scroll-view>
            </view>
        </view>
    </view>
</template>

<script>
import { requestBook, bookImageUrl, bookProgressKey } from '@/common/book-api.js'

const themes = [
    { id: 'white', name: '白色', background: '#ffffff', text: '#303030', surface: '#f5f5f5', border: '#dedede', muted: '#696969', accent: '#97421c' },
    { id: 'yellow', name: '暖黄', background: '#fff9f0', text: '#342f29', surface: '#fff0df', border: '#e5d5bf', muted: '#766957', accent: '#97421c' },
    { id: 'green', name: '护眼绿', background: '#dcebd7', text: '#293b2a', surface: '#eaf3e6', border: '#b1c5a9', muted: '#52674d', accent: '#315f32' },
    { id: 'black', name: '黑色', background: '#181a1b', text: '#d8d8d2', surface: '#252829', border: '#454a4b', muted: '#a8b0ab', accent: '#edbb83' }
]

export default {
    data() {
        return {
            bookId: 0, chapterId: 0, requestedChapterId: 0, chapter: {}, chapters: [], blocks: [],
            loading: true, error: '', showCatalogue: false, fontSize: 20, themes, themeId: 'yellow',
            scrollTop: 0, restoreTop: 0, pendingImages: 0
        }
    },
    computed: {
        readerTheme() {
            return themes.find(item => item.id === this.themeId) || themes[1]
        },
        themeStyle() {
            const theme = this.readerTheme
            return {
                '--reader-bg': theme.background, '--reader-text': theme.text,
                '--reader-surface': theme.surface, '--reader-border': theme.border,
                '--reader-muted': theme.muted, '--reader-accent': theme.accent
            }
        },
        accessMessage() {
            const pay = Number(this.chapter.pay)
            if (pay === -1) return '本节需要登录，当前仅显示试读内容。'
            if (pay === 1) return '本节需要 VIP 会员，当前仅显示试读内容。'
            return '本节需要购买，当前仅显示试读内容。'
        }
    },
    onLoad(options) {
        this.bookId = Number(options.bid) || 0
        this.chapterId = Number(options.zid) || 0
        this.fontSize = Math.max(16, Math.min(30, Number(uni.getStorageSync('reading-font-size')) || 20))
        const savedTheme = uni.getStorageSync('reading-theme')
        this.themeId = themes.some(item => item.id === savedTheme) ? savedTheme : 'yellow'
        this.applyTheme()
        const saved = uni.getStorageSync(bookProgressKey(this.bookId)) || {}
        if (options.resume === '1' && Number(saved.chapterId) === this.chapterId) this.restoreTop = Number(saved.scrollTop) || 0
        this.loadChapter(this.chapterId, true)
        requestBook('chapter', { id: this.bookId }).then(result => {
            this.chapters = result.list || []
        }).catch(error => {
            uni.showToast({ title: error.message, icon: 'none' })
        })
    },
    onPageScroll(event) {
        this.scrollTop = event.scrollTop
    },
    onHide() {
        this.saveProgress()
    },
    onUnload() {
        this.saveProgress()
    },
    methods: {
        async loadChapter(id, restore) {
            if (this.loading && this.chapter.id) return
            this.loading = true
            this.error = ''
            this.requestedChapterId = Number(id) || 0
            if (!restore) this.restoreTop = 0
            try {
                if (!this.bookId) throw new Error('书籍地址无效')
                const result = await requestBook('read', { bid: this.bookId, zid: Number(id) || 0 })
                this.chapter = result.data
                this.chapterId = Number(this.chapter.id)
                const lines = (this.chapter.book.text || []).slice()
                const title = String(this.chapter.name || '').replace(/\s+/g, '')
                if (lines.length && String(lines[0]).replace(/\s+/g, '') === title) lines.shift()
                this.blocks = lines.map(line => {
                    const text = String(line)
                    const match = text.trim().match(/^【插图：(\/attachment\/book\/import\/[A-Za-z0-9_-]+\/assets\/[^\s】]+)】$/)
                    return match ? { image: bookImageUrl(match[1]) } : { text }
                }).filter(block => block.image || block.text.trim())
                if (!restore) this.restoreTop = 0
                this.pendingImages = this.restoreTop > 0 ? this.blocks.filter(block => block.image).length : 0
                this.scrollTop = this.restoreTop
                uni.setNavigationBarTitle({ title: this.chapter.name || '阅读' })
                this.$nextTick(() => {
                    if (!this.pendingImages) this.restorePosition()
                    this.saveProgress()
                })
            } catch (error) {
                this.error = error.message
            } finally {
                this.loading = false
            }
        },
        imageLoaded() {
            if (!this.pendingImages) return
            this.pendingImages -= 1
            if (!this.pendingImages) this.restorePosition()
        },
        restorePosition() {
            const top = this.restoreTop
            this.restoreTop = 0
            this.scrollTop = top
            uni.pageScrollTo({ scrollTop: top, duration: 0 })
        },
        saveProgress() {
            if (!this.chapter.id || this.error) return
            uni.setStorageSync(bookProgressKey(this.bookId), {
                chapterId: this.chapterId,
                chapterTitle: this.chapter.name,
                scrollTop: this.restoreTop || this.scrollTop
            })
        },
        goChapter(id) {
            if (!Number(id) || this.loading) return
            this.saveProgress()
            this.showCatalogue = false
            this.loadChapter(Number(id), false)
        },
        changeFont(delta) {
            this.fontSize = Math.max(16, Math.min(30, this.fontSize + delta))
            uni.setStorageSync('reading-font-size', this.fontSize)
        },
        changeTheme(id) {
            if (!themes.some(item => item.id === id)) return
            this.themeId = id
            uni.setStorageSync('reading-theme', id)
            this.applyTheme()
        },
        applyTheme() {
            uni.setNavigationBarColor({
                frontColor: this.themeId === 'black' ? '#ffffff' : '#000000',
                backgroundColor: this.readerTheme.background
            })
        }
    }
}
</script>

<style scoped>
.reader{background:var(--reader-bg);min-height:100vh;color:var(--reader-text);padding-bottom:70rpx}
.toolbar{display:flex;justify-content:space-between;align-items:center;padding:20rpx 30rpx;border-bottom:1rpx solid var(--reader-border)}
.toolbar button,.chapter-nav button,.theme-option{margin:0;background:var(--reader-surface);color:var(--reader-accent);border:1rpx solid var(--reader-border)}
.toolbar button[disabled],.chapter-nav button[disabled]{color:var(--reader-muted);background:var(--reader-surface);opacity:.6}
.font-controls{display:flex;gap:18rpx}
.theme-controls{display:flex;align-items:center;flex-wrap:wrap;gap:12rpx;padding:18rpx 30rpx;border-bottom:1rpx solid var(--reader-border)}
.theme-label{font-size:25rpx;color:var(--reader-muted);margin-right:4rpx}
.theme-option{display:flex;align-items:center;gap:10rpx;padding:0 18rpx;font-size:24rpx;line-height:2.4;color:var(--reader-text)}
.theme-option.selected{border-color:var(--reader-accent);box-shadow:inset 0 0 0 1rpx var(--reader-accent)}
.theme-swatch{width:24rpx;height:24rpx;border-radius:50%;border:1rpx solid var(--reader-muted)}
.content{padding:20rpx 34rpx;line-height:1.95;overflow-wrap:anywhere}
.chapter-title{font-size:1.22em;font-weight:600;line-height:1.6;margin:28rpx 0 50rpx}
.paragraph{margin:0 0 30rpx;white-space:pre-wrap}
.illustration{display:block;width:100%;height:auto;margin:22rpx auto}
.chapter-nav{display:flex;justify-content:space-between;gap:18rpx;margin-top:60rpx;padding-top:30rpx;border-top:1rpx solid var(--reader-border)}
.state{padding:70rpx 30rpx;font-size:28rpx;color:var(--reader-muted);text-align:center}.state button{margin-top:25rpx;background:var(--reader-surface);color:var(--reader-accent)}
.access-note{padding:25rpx;background:var(--reader-surface);font-size:28rpx;color:var(--reader-accent);line-height:1.8}
.catalogue-mask{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:1000;display:flex;justify-content:flex-start}
.catalogue-panel{width:85%;max-width:620px;height:100%;background:var(--reader-bg);display:flex;flex-direction:column}
.catalogue-header{display:flex;justify-content:space-between;padding:30rpx;font-size:32rpx;border-bottom:1rpx solid var(--reader-border)}
.catalogue-header text:last-child{font-size:27rpx;color:var(--reader-accent)}
.catalogue-scroll{height:0;flex:1}.catalogue-item{font-size:28rpx;line-height:1.7;padding:24rpx 30rpx;border-bottom:1rpx solid var(--reader-border);color:var(--reader-text)}.catalogue-item.current{color:var(--reader-accent);background:var(--reader-surface)}
</style>
