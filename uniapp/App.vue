<script>
	import { API_BASE, API_KEY, initBookDevice } from '@/common/book-api.js'

	export default {
		globalData: {
			websiteUrl: API_BASE,
			websiteKey: API_KEY,
			facility: '', //系统 Android 、ios
			deviceid: '', //系统 id
			img_websiteUrl: '',
			uid: '',
			utoken: '',
			webUrl: API_BASE,
			book_img: '',
			sigmob: 1849548002,
			version: ''
		},
		onLaunch: function() {
			Object.assign(this.globalData, initBookDevice())
		},
		onShow: function() {
			// #ifdef APP-PLUS
			if (typeof plus !== 'undefined' && plus.runtime.arguments) {
				console.log(plus.runtime.arguments)
			}
			// #endif
		},
		onHide: function() {
			console.log('App Hide')
		}
	}
</script>

<style lang="scss">
	/* 注意要写在第一行，同时给style标签加入lang="scss"属性 */
	@import "@/uni_modules/uview-ui/index.scss";
</style>
