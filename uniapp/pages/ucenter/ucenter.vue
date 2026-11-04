<template>  
    <view class="container">  
		
		<view class="user-section">
			<image class="bg" src="/static/ucenter/user-bg.jpg"></image>
			<view class="user-info-box" @click="navTo('/pages/ucenter/uinfo/uinfo')">
				<view class="portrait-box">
					<image class="portrait" :src="userInfo.picx || '/static/ucenter/missing-face.png'"></image>
				</view>
				<view class="info-box">
					<text class="username">{{userInfo.nichen || userInfo.name || '游客'}}</text>
				</view>
				<view class="vip" v-if="userInfo.vip=='1'">
					<image src="/static/ucenter/mkz_ic_me_vip_hj.png" style="height: 32rpx;width: 32rpx;"></image>
				</view>
			</view>
			<view class="vip-card-box">
				<image class="card-bg" src="/static/ucenter/vip-card-bg.png" mode="bottom right"></image>
				<view class="b-btn" @click="navTo('/pages/ucenter/vip/vip')">
					{{userInfo.vip!=='1'?"立即开通":"续费会员"}}
				</view>
				<view class="tit">
					<view class="yticon">
					    <u-icon size="16" name="integral" color="#f6e5a3"></u-icon>
					</view>
					VIP专属特权
				</view>
				<text class="e-m">{{userInfo.vip!=='1'?"开通VIP会员":"超级VIP用户"}}</text>
				<text class="e-b">{{userInfo.vip!=='1'?"享受更多VIP福利&权益":"欢迎来到我的世界,我尊贵的VIP主人~"}}</text>
			</view>
		</view>
		
		<view 
			class="cover-container"
			:style="[{
				transform: coverTransform,
				transition: coverTransition
			}]"
			@touchstart="coverTouchstart"
			@touchmove="coverTouchmove"
			@touchend="coverTouchend"
		>
			<image class="arc" src="/static/ucenter/arc.png"></image>
			
			<view class="tj-sction">
				<view class="tj-item" @click="navTo('/pages/ucenter/coin/coin')">
					<text class="num">{{userInfo.cion || 0}}</text>
					<text>{{userInfo.cion_name || "金币"}}</text>
				</view>
				<view class="tj-item" @click="navTo('/pages/ucenter/ticket/ticket')">
					<text class="num">{{userInfo.ticket || 0}}</text>
					<text>月票</text>
				</view>
				<view class="tj-item" @click="navTo('/pages/ucenter/vip/vip')">
					<text v-if="userInfo.vip==1" class="num">{{userInfo.vipday}}</text>
					<text v-else class="num">未开通</text>
					<text>会员</text>
				</view>
			</view>
			<!-- 明细列表 -->
			<view class="order-section">
				<view class="order-item" @click="navTo('/pages/ucenter/record/task')" hover-class="common-hover"  :hover-stay-time="50">
					<view class="yticon">
					    <u-icon size="26" name="gift" color="#000"></u-icon>
					</view>
					<text>奖励明细</text>
				</view>
				<view class="order-item" @click="navTo('/pages/ucenter/record/consume')"  hover-class="common-hover" :hover-stay-time="50">
					<view class="yticon">
					    <u-icon size="26" name="coupon" color="#000"></u-icon>
					</view>
					<text>消费明细</text>
				</view>
				<view class="order-item" @click="navTo('/pages/ucenter/record/order')" hover-class="common-hover"  :hover-stay-time="50">
					<view class="yticon">
					    <u-icon size="26" name="order" color="#000"></u-icon>
					</view>
					<text>订单明细</text>
				</view>
				<!-- <view class="order-item" @click="navTo('/pages/order/order?state=4')" hover-class="common-hover"  :hover-stay-time="50">
					<text class="yticon icon-shouhoutuikuan"></text>
					<text>充值中心</text>
				</view> -->
			</view>
			<!-- 列表 -->
			<view class="history-section icon">
				<list-cell :icon="'https://mc.manhua.pro/kximg/rwzx.png'" iconSize="20px" iconColor="#e07472" title="任务中心" @eventClick="navGo('/pages/ucenter/task/task')" :tips="userInfo.cion_name?'每日做任务领海量'+userInfo.cion_name:'每日做任务领海量金币'"></list-cell>
				<list-cell :icon="'https://mc.manhua.pro/kximg/ydjl.png'" iconSize="20px" iconColor="#5fcda2" title="阅读记录" tips="" @eventClick="navGo('/pages/ucenter/history/history')"></list-cell>
				<list-cell :icon="'https://mc.manhua.pro/kximg/wdpl.png'" iconSize="20px" iconColor="#9789f7" title="我的评论" tips="" @eventClick="navTo('/pages/ucenter/comment/comment')"></list-cell>
				<list-cell :icon="'https://mc.manhua.pro/kximg/zhsz.png'" iconSize="20px" iconColor="#54b4ef" title="账号设置" tips="" @eventClick="navTo('/pages/ucenter/setting/setting?id='+userInfo.id)"></list-cell>
				<list-cell :icon="'https://mc.manhua.pro/kximg/gywm.png'" iconSize="20px" iconColor="#ee883b" title="关于我们" tips="" @eventClick="navGo('/pages/ucenter/about/about')"></list-cell>
				<!-- <list-cell icon="" iconColor="#54b4ef" title="我的收藏"></list-cell> -->
			</view>
			<view class="history-section icon" v-if="utoken!==''">
				<list-cell :icon="'https://mc.manhua.pro/kximg/tcdl.png'" iconSize="20px" iconColor="#5fcda2" title="退出登录" tips="" @eventClick="logout()"></list-cell>
			</view>
		</view>
			
		<u-modal :show="modal_show" :title="modal_title" :content='modal_content' confirmColor="#FFD74B" @cancel="cancel" @confirm="confirm" asyncClose showCancelButton></u-modal>
    </view>  
</template>  
<script>  
	import listCell from '@/components/mix-list-cell';
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
    import {  
        mapState 
    } from 'vuex';  
	let startY = 0, moveY = 0, pageAtTop = true;
    export default {
		components: {
			listCell,
			w_md5
		},
		data(){
			return {
				coverTransform: 'translateY(0px)',
				coverTransition: '0s',
				moving: false,
				userInfo: {picx:'http://q2.qlogo.cn/headimg_dl?dst_uin=1660875559&spec=100'},
				utoken: '',
				modal_show: false,
				modal_title:'温馨提示',
				modal_content:'是否确认退出？',
				version: 0
			}
		},
		onLoad(){
		},
		// #ifndef MP
		onNavigationBarButtonTap(e) {
			const index = e.index;
			if (index === 0) {
				this.navTo('/pages/set/set');
			}else if(index === 1){
				// #ifdef APP-PLUS
				const pages = getCurrentPages();
				const page = pages[pages.length - 1];
				const currentWebview = page.$getAppWebview();
				currentWebview.hideTitleNViewButtonRedDot({
					index
				});
				// #endif
				uni.navigateTo({
					url: '/pages/notice/notice'
				})
			}
		},
		onShow() {
			this.utoken = uni.getStorageSync('user')
			// #ifdef APP-PLUS
			// 获取手机系统信息
			var _this = this;
			uni.getSystemInfo({
				success: function(res) {
					_this.platform = res.platform //ios  or android
					console.log('手机系统信息', _this.platform);
				}
			});
			// 获取版本号
			plus.runtime.getProperty(plus.runtime.appid, function(inf) {
				_this.version = inf.version
			});
			console.log('当前版本', this.version);
			// #endif
			this.get_userinfo()
			// this.get_update()
		},
		// #endif
        methods: {
			logout(){
				this.modal_show = true
			},
			confirm(){
				this.logout_ok()
			},
			cancel(){
				this.modal_show = false
			},
			logout_ok(){
				this.modal_show = false
				getApp().globalData.uid = ''
				getApp().globalData.utoken = ''
				this.utoken = ''
				uni.removeStorageSync('user')
				console.log("退出登录: ",uni.getStorageSync('user'));
				this.get_userinfo()
			},
			get_userinfo(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/user/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("用户信息：",res.data)
						this.userInfo = res.data.user
				    }
				});
			},
			/**
			 * 统一跳转接口,拦截未登录路由
			 * navigator标签现在默认没有转场动画，所以用view
			 */
			navTo(url){
				if(this.utoken==''){
					url = '/pages/login/login';
				}
				uni.navigateTo({  
					url
				})
			}, 
			/**
			 * 统一跳转接口,不拦截登录路由
			 * navigator标签现在默认没有转场动画，所以用view
			 */
			navGo(url){
				uni.navigateTo({  
					url
				})
			}, 
	
			/**
			 *  会员卡下拉和回弹
			 *  1.关闭bounce避免ios端下拉冲突
			 *  2.由于touchmove事件的缺陷（以前做小程序就遇到，比如20跳到40，h5反而好很多），下拉的时候会有掉帧的感觉
			 *    transition设置0.1秒延迟，让css来过渡这段空窗期
			 *  3.回弹效果可修改曲线值来调整效果，推荐一个好用的bezier生成工具 http://cubic-bezier.com/
			 */
			coverTouchstart(e){
				if(pageAtTop === false){
					return;
				}
				this.coverTransition = 'transform .1s linear';
				startY = e.touches[0].clientY;
			},
			coverTouchmove(e){
				moveY = e.touches[0].clientY;
				let moveDistance = moveY - startY;
				if(moveDistance < 0){
					this.moving = false;
					return;
				}
				this.moving = true;
				if(moveDistance >= 80 && moveDistance < 100){
					moveDistance = 80;
				}
		
				if(moveDistance > 0 && moveDistance <= 80){
					this.coverTransform = `translateY(${moveDistance}px)`;
				}
			},
			coverTouchend(){
				if(this.moving === false){
					return;
				}
				this.moving = false;
				this.coverTransition = 'transform 0.3s cubic-bezier(.21,1.93,.53,.64)';
				this.coverTransform = 'translateY(0px)';
			}
        }  
    }  
</script>  
<style lang='scss'>
	page{
		background: #f9f9f9;
	}
	%flex-center {
	 display:flex;
	 flex-direction: column;
	 justify-content: center;
	 align-items: center;
	}
	%section {
	  display:flex;
	  justify-content: space-around;
	  align-content: center;
	  background: #fff;
	  border-radius: 10upx;
	}

	.user-section{
		height: 520upx;
		padding: 100upx 30upx 0;
		position:relative;
		.bg{
			position:absolute;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			filter: blur(1px);
			opacity: .7;
		}
	}
	.user-info-box{
		height: 180upx;
		display:flex;
		align-items:center;
		position:relative;
		z-index: 1;
		.portrait{
			width: 130upx;
			height: 130upx;
			border:5upx solid #fff;
			border-radius: 50%;
		}
		.username{
			font-size: 32upx + 6upx;
			color: #303133;
			margin-left: 20upx;
		}
		.vip{
			height: 32rpx;
			width: 32rpx;
			margin-left: 2px;
			margin-right: auto;
		}
	}

	.vip-card-box{
		display:flex;
		flex-direction: column;
		color: #f7d680;
		height: 240upx;
		background: linear-gradient(to left top, rgba(0,0,0,.7), rgba(0,0,0,.8));
		border-radius: 16upx 16upx 0 0;
		overflow: hidden;
		position: relative;
		padding: 20upx 24upx;
		.card-bg{
			position:absolute;
			top: 20upx;
			right: 0;
			width: 380upx;
			height: 260upx;
		}
		.b-btn{
			position: absolute;
			right: 20upx;
			top: 16upx;
			width: 132upx;
			height: 40upx;
			text-align: center;
			line-height: 40upx;
			font-size: 22upx;
			color: #36343c;
			border-radius: 20px;
			background: linear-gradient(to left top, #f9e6af, #ffd465);
			z-index: 1;
		}
		.tit{
			font-size: 28upx+2upx;
			color: #f7d680;
			margin-bottom: 28upx;
			display: flex;
			.yticon{
				color: #f6e5a3;
				margin-right: 16upx;
				margin-top: auto;
				margin-bottom: auto;
			}
		}
		.e-b{
			font-size: 24upx;
			color: #d8cba9;
			margin-top: 10upx;
		}
	}
	.cover-container{
		background: #f8f8f8;
		margin-top: -250upx;
		padding: 0 30upx;
		position:relative;
		background: #f9f9f9;
		padding-bottom: 20upx;
		.arc{
			position:absolute;
			left: 0;
			top: -34upx;
			width: 100%;
			height: 36upx;
		}
	}
	.tj-sction{
		@extend %section;
		.tj-item{
			@extend %flex-center;
			flex-direction: column;
			height: 140upx;
			font-size: 24upx;
			color: #75787d;
		}
		.num{
			font-size: 32upx;
			color: #303133;
			margin-bottom: 8upx;
		}
	}
	.order-section{
		@extend %section;
		padding: 28upx 0;
		margin-top: 20upx;
		.order-item{
			@extend %flex-center;
			width: 120upx;
			height: 120upx;
			border-radius: 10upx;
			font-size: 24upx;
			color: #303133;
		}
		.yticon{
			font-size: 48upx;
			margin-bottom: 18upx;
			color: #fa436a;
		}
		.icon-shouhoutuikuan{
			font-size:44upx;
		}
	}
	.history-section{
		padding: 0upx 0 0;
		margin-top: 20upx;
		background: #fff;
		border-radius:10upx;
		.sec-header{
			display:flex;
			align-items: center;
			font-size: 28upx;
			color: #303133;
			line-height: 40upx;
			margin-left: 30upx;
			.yticon{
				font-size: 44upx;
				color: #5eba8f;
				margin-right: 16upx;
				line-height: 40upx;
			}
		}
	}
	
</style>