import md5 from '@/js_sdk/zww-md5/w_md5.js'

let runtime = {}
// #ifdef H5
runtime = window.READING_APP_CONFIG || {}
// #endif
// #ifdef APP-PLUS
runtime = require('@/runtime-config.json')
// #endif
export const API_KEY = runtime.apiKey || ''
export const API_BASE = runtime.apiBase || ''
const serverOrigin = runtime.serverOrigin || ''

export function initBookDevice() {
    let deviceid = uni.getStorageSync('reading-device-id')
    if (!deviceid) {
        deviceid = 'reader-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2)
        uni.setStorageSync('reading-device-id', deviceid)
    }
    const platform = uni.getSystemInfoSync().platform
    return { deviceid, facility: platform === 'ios' ? 'ios' : 'android' }
}

export function requestBook(method, values = {}) {
    if (!API_KEY) return Promise.reject(new Error('阅读服务尚未配置'))
    const device = initBookDevice()
    const params = Object.assign({}, values, device, {
        timestamp: Date.now(),
        user_id: 0
    })
    const keys = Object.keys(params).filter(key => params[key] !== null && params[key] !== undefined && String(params[key]) !== '').sort()
    const raw = keys.map(key => key + '=' + String(params[key])).join('&')
    const sign = md5.hex_md5_32Upper(raw + API_KEY)
    const query = keys.map(key => encodeURIComponent(key) + '=' + encodeURIComponent(String(params[key]))).join('&')
    return new Promise((resolve, reject) => {
        uni.request({
            url: API_BASE + '/index.php/appv1/book/' + method + '?' + query + '&sign=' + sign,
            timeout: 15000,
            success: response => {
                const body = response.data
                if (response.statusCode !== 200 || !body || typeof body !== 'object') {
                    reject(new Error('服务暂时不可用，请稍后重试'))
                } else if (Number(body.code) !== 1) {
                    reject(new Error(body.msg || '读取失败，请重试'))
                } else {
                    resolve(body)
                }
            },
            fail: () => reject(new Error('连接失败，请检查网络后重试'))
        })
    })
}

export function bookImageUrl(value) {
    if (!value) return ''
    let origin = API_BASE
    // #ifdef H5
    origin = window.location.origin
    if (serverOrigin && value.indexOf(serverOrigin + '/') === 0) return origin + value.slice(serverOrigin.length)
    // #endif
    if (/^https?:\/\//i.test(value)) return value
    return origin + (value.charAt(0) === '/' ? value : '/' + value)
}

export function bookProgressKey(bookId) {
    return 'reading-book-' + bookId
}
