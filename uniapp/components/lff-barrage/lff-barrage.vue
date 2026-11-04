<template>
	<view style="width: 55%;height: 100%;pointer-events: none; top: 0;position: absolute;right: 0;">
		<view class="danmu-li" v-for="(item,index) in listData" :class="item.type" :style="item.style" :key="index">
			<view class="danmu-inner">
				<view class="user-box">
					<view class="user-img">
						<view class="img-box">
							<image :src="item.avatar"></image>
						</view>
					</view>
					<view class="user-text cl1">
						{{item.nick}}
					</view>
					<view style="color: rgba(255, 255, 255, 1);font-size: 28rpx;font-weight: 400;width: auto;white-space: nowrap;">
						打赏了
					</view>
					<view class="user-status cl1">
						{{item.name}}
					</view>
				</view>
			</view>
		</view>
	</view>
</template>
<script>
	export default {
		props: {
			//rightToLeft leftToRight leftBottom
			type: {
				type: String,
				default: 'rightToLeft'
			},
			minTime: {
				type: Number,
				default: 4
			},
			maxTime: {
				type: Number,
				default: 9
			},
			minTop: {
				type: Number,
				default: 0
			},
			maxTop: {
				type: Number,
				default: 240
			},
			hrackH: { //轨道高度
				type: Number,
				default: 40
			},
			noStacked:{ //不允许堆叠(暂不可用)
				type:Array,
				default(){
					return []
				}
			}
		},
		data() {
			return {
				listData: []
			}
		},
		mounted() {
			this.hrackNum = Math.floor((this.maxTop - this.minTop) / this.hrackH);
		},
		methods: {
			add(obj) {
				let data = {
					name: obj.name,
					nick: obj.nick,
					avatar: obj.avatar,
					id:Date.parse(new Date()),
					time: Math.ceil(Math.floor(Math.random() * (this.maxTime - this.minTime + 1) + this.minTime)),
					type: this.type
				}
				if (this.type === 'leftBottom') {
					let objData = {
						name: data.name,
						nick: data.nick,
						avatar: data.avatar,
						type: 'leftBottomEnter',
						style: {
							transition: `all 0.5s`,
							animationDuration: `0.5s`,
							transform: `translateX(0%)`,
							bottom: `${this.minTop}px`
						}
					}
					let listLen = this.listData.length;
					let hrackNum = this.hrackNum;
					for (let i in this.listData) {
						if(this.listData[i].status === 'reuse'){ //重用
							this.$set(this.listData,i,objData);
						}else if(this.listData[i].status === 'reset'){ //重置
							this.listData[i].style.transition = 'none';
							this.listData[i].style.bottom = 0;
							this.listData[i].status = 'reuse';
						}else if(this.listData[i].status === 'recycle'){ //回收
							this.listData[i].type = 'leftBottomExit';
							this.listData[i].status = 'reset';
						}else{
							this.listData[i].style.bottom = parseInt(this.listData[i].style.bottom) + this.hrackH + 'px';
						}
						if(parseInt(this.listData[i].style.bottom) >= (this.maxTop - this.hrackH) && this.listData[i].status !== 'reset'){ //需要回收
							this.listData[i].status = 'recycle';
						}
					}
					if(listLen < hrackNum + 2){
						this.listData.push(objData);
					}
				} else if (this.type === 'rightToLeft' || this.type === 'leftToRight') {
					let objData = this.horStacked(data);
					for (let i in this.listData) {
						if (this.listData[i].delTime <= Date.parse(new Date())) {
							this.repaint(i, objData.type);
							objData.type = '';
							this.$set(this.listData, i, objData);
							return
						}
					}
					this.listData.push(objData);
				}
			},
			horStacked(data){
				return {
					name: data.name,
					nick: data.nick,
					avatar: data.avatar,
					type: this.type,
					style: {
						animationDuration: `${data.time}s`,
						top: `${Math.ceil(Math.random() * (this.maxTop - this.minTop + 1) + this.minTop)}px`
					},
					delTime: Date.parse(new Date()) + data.time * 1200
				}
			},
			repaint(index, type) {
				setTimeout(() => {
					this.listData[index].type = type;
				}, 100)
			}
		}

	}
</script>
<style>

</style>
<style lang="scss">
	@keyframes leftBottomEnter {
		0% {
			transform: translateY(100%);
			opacity: 0;
		}

		100% {
			transform: translateY(0%);
			opacity: 1;
		}
	}

	@keyframes leftBottomExit {
		0% {
			transform: translateY(0%);
			opacity: 1;
		}
		
		100% {
			transform: translateY(-200%);
			opacity: 0;
		}
	}

	@keyframes leftToRight {
		0% {
			transform: translateX(-100%);
		}

		100% {
			transform: translateX(100%);
		}
	}

	@keyframes rightToLeft {
		0% {
			transform: translateX(100%);
		}

		100% {
			transform: translateX(-100%);
		}
	}

	.danmu-li {
		position: absolute;
		width: 100%;
		transform: translateX(100%);
		animation-timing-function: linear;

		&.leftBottomEnter {
			animation-name: leftBottomEnter;
		}
		&.leftBottomExit{
			animation-name: leftBottomExit;
			animation-fill-mode: forwards;
		}

		&.rightToLeft {
			animation-name: rightToLeft;
		}

		&.leftToRight {
			animation-name: leftToRight;
		}

		.danmu-inner {
			display: inline-block;

			.user-box {
				display: flex;
				width: 95%;
				padding: 8rpx 0rpx 8rpx 8rpx;
				background: rgba(0, 0, 0, 0.4);
				border-radius: 50px;
				align-items: center;

				.user-img {
					.img-box {
						display: flex;
						width: auto;
						image {
							width: 46rpx;
							height: 46rpx;
							background: rgba(55, 55, 55, 1);
							border-radius: 50%;
						}
					}
				}

				.user-status {
					color: #fd610e;
					width: auto;
					margin-left: auto;
					margin-right: 10px;
					white-space: nowrap;
					font-size: 28rpx;
					font-weight: 400;
					// color: rgba(255, 255, 255, 1);
				}

				.user-text {
					margin-left: 10rpx;
					margin-right: auto;
					display: -webkit-box;/*弹性伸缩盒子模型显示*/
					    -webkit-box-orient: vertical;   /*排列方式*/ 
					    -webkit-line-clamp: 1;/*显示文本行数*/
					    overflow: hidden;/*溢出隐藏*/
						// text-overflow:ellipsip;
						word-break: break-all;
					font-size: 28rpx;
					font-weight: 400;
					width: 40px;
					color: rgba(255, 255, 255, 1);
				}
			}
		}
	}
</style>
