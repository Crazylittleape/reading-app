<template>
	<view :class="chapter_show?'control-scroll':''">
		<u-navbar
		    :safeAreaInsetTop="true"
			:placeholder="false"
			:border="false"
			:fixed="true"
			:bgColor="nav_color"
			autoBack
		>
		    <view
		        class=""
				style="display: flex;"
		        slot="left"
		    >
		        <u-icon name="/static/image/mkz_ic_mhdetail_nav_return.png" width="22" height="22" color="#ffffff" style="margin-left: 4px;"></u-icon>
				<view :style="bgcolor">
				    <u-text size="16" :text="title" color="#FFFFFF" lines="1"></u-text>
				</view>
				<!-- <u-image src="/static/image/mkz_ic_mhdetail_nav_return.png" width="18px" height="18px"></u-image> -->
		    </view>
			<view
				style="display: flex;"
			    slot="right"
				@click="share_qrcode()"
			>
			    <u-icon name="/static/image/mkz_ic_mhdetail_nav_share.png" width="22" height="22" color="#ffffff" style="margin-right: 5px;"></u-icon>
				<!-- <u-image src="/static/image/mkz_ic_mhdetail_nav_share.png" width="18px" height="18px"></u-image> -->
			</view>
		</u-navbar>
		
		<u-sticky zIndex="3">
			<view class="page-conatiner" v-show="nav_show" :style="bgcolor">
				<view class="card" :style="'height:'+nav_height+'px'">
					<view class="bg-image" :style="'height:'+nav_height+'px'">
						<image :src="data.picx"></image>
					</view>
					<view class="bg-gray"></view>
				</view>
			</view>
		</u-sticky>
		
		<mescroll-body ref="mescrollRef" @init="mescrollInit" @down="downCallback" @up="upCallback" :down="downOption" :up="upOption">
		    <view style="width: 100%;height: 215px;background-size: cover;" :style="'background-image: url(../../static/img_moren.jpg)'">
		    <!-- <image style="width: 100%;height: 266px;" :src="data.cover_url" mode="scaleToFill"></image> -->
		        <!-- <image style="width: 100%" :src="data.cover_url" mode="aspectFill"></image> -->
		    	<!-- <view style="display: flex;height: 215px;background-size: cover;background-position: center;box-shadow:0px -25px 50px gray inset;" :style="'background-image: url(' + data.picx + ')'"> -->
				<view style="display: flex;height: 215px;background-size: cover;background-position: center;" :style="'background-image: url(' + data.picx + ')'">
					<view style="background: linear-gradient(180deg,#ff000000,rgba(0,0,0,.5));width: 100%;height: 100%;">
						<view style="display: flex;height: 100%;">
		    		        <view style="display: flex;margin-bottom: 25px;margin-top: auto;margin-left: 15px;margin-right: 15px;width: 100%;align-items: center;">
		    		        	<view style="display: flex; flex-direction: column;">
		                            <text style="font-weight: 600;color: #fff;font-size: 20px;">{{data.name}}</text>
		    		        		<view style="margin-top: 5px;margin-bottom: 2px;">
		    		        		    <text style="color: #ffffff;font-size: 11px;background-color: rgba(255,255,255,0.3); padding: 2px 8px 2px 8px;border-radius: 3px;">{{data.hits}}人气</text>
		    		        		</view>
		    		        	    <!-- <text style="font-weight: 300;color: #fff;font-size: 14px;">状态：{{data.state}}</text> -->
		    		        	</view>
		    		        	<view style="margin-left: auto; margin-right: 5px;">
		    		        		<view style="width: 60px;">
		    		        		    <view style="color: #ffffff;font-size: 22px;background-color: rgba(255,255,255,0.91); padding: 2px 8px 2px 8px;border-radius: 6px;display: flex;justify-content: center;">
		    		        		    	<text style="color: #ff5500;font-weight: bold;">
		    		        		    		{{data.score}}
		    		        		    	</text>
		    		        		    </view>
		    		        		    <view style="color: #ffffff;font-size: 22px;background-color: rgba(255,255,255,0.3); padding: 2px 8px 2px 8px;border-radius: 6px;display: flex;justify-content: center;margin-top: -9px;padding-top: 11px;">
		    		        		    	<u-rate count="5" v-model="data.score" :allowHalf="true" size="8" gutter="0"></u-rate>
		    		        		    </view>
		    		        		</view>
		    		        	</view>
		    	                <!-- <u-tag @click="isfavor" v-if="isfavor_status" style="margin-left: auto; margin-right: 10px;" type="warning" size="mini" shape="circle" icon="star-fill" text="取消收藏"></u-tag>
		    		        	<u-tag @click="isfavor" v-else style="margin-left: auto; margin-right: 10px;" type="warning" size="mini" shape="circle" icon="star" text="收藏"></u-tag> -->
		    	            </view>
						</view>
						<view style="margin-top: -15px;border-radius: 15px 15px 0 0;height: 15px;width: 100%;background-color: #fff;"></view>
					</view>
		    	</view>
		    </view>
			
			
			<!-- 简介 -->
			<view style="margin-left: 15px;margin-right: 15px;">
			    <text-ellipsis font-size="28rpx" show-more-label="展开" show-shrink="" style="color: #5a5a5a;">
			    	{{data.text}}
			    </text-ellipsis>
			</view>
			<!-- 作者 -->
			<view style="display: flex;font-size: 26rpx;color: #000000;font-weight: 500;margin: 13px 15px 13px 15px;">
				<view style="margin-top: auto;margin-bottom: auto;">
				    <text style="font-size: 26rpx;color: #333333;">作者:</text>
				</view>
				<u-avatar size="20" style="margin: auto 3px auto 5px;"></u-avatar>
				<view style="margin-top: auto;margin-bottom: auto;">
				    {{data.author}}
				</view>
			</view>
			<!-- 热度数据 -->
			<view style="display: flex;justify-content: space-between;width: 83%;margin: 10px auto 15px auto;">
				<view style="display: flex;" @click="slw_open">
					<view style="margin-top: auto;margin-bottom: auto;">
					    <u-image width="25px" height="25px" src="/static/detail/mkz_ic_detail_ds.png"></u-image>
					</view>
					<view style="margin-left: 5px;margin-top: auto;margin-bottom: auto;">
						<view style="margin: auto auto 1px auto;display: flex;justify-content: center;">
						    <text style="font-size: 11px;font-weight: 900;line-height: 1;">{{data.cion}}</text>
						</view>
						<view style="margin: 0px auto auto auto;display: flex;">
						    <text style="font-size: 12px;color: #999999;line-height: 1;">打赏</text>
						</view>
					</view>
				</view>
				<view style="display: flex;" @click="typ_open">
					<view style="margin-top: auto;margin-bottom: auto;">
					    <u-image width="25px" height="25px" src="/static/detail/mkz_ic_detail_yp.png"></u-image>
					</view>
					<view style="margin-left: 5px;margin-top: auto;margin-bottom: auto;">
						<view style="margin: auto auto 1px auto;display: flex;justify-content: center;">
						    <text style="font-size: 11px;font-weight: 900;line-height: 1;">{{data.ticket}}</text>
						</view>
						<view style="margin: 0px auto auto auto;display: flex;">
						    <text style="font-size: 12px;color: #999999;line-height: 1;">月票</text>
						</view>
					</view>
				</view>
				<view style="display: flex;" @click="isfavor()">
					<view style="margin-top: auto;margin-bottom: auto;">
					    <u-image width="25px" height="25px" src="/static/detail/mkz_ic_detail_jrsd.png"></u-image>
					</view>
					<view style="margin-left: 5px;margin-top: auto;margin-bottom: auto;">
						<view style="margin: auto auto 1px auto;display: flex;justify-content: center;">
						    <text style="font-size: 11px;font-weight: 900;line-height: 1;">{{data.shits}}</text>
						</view>
						<view style="margin: 0px auto auto auto;display: flex;">
						    <text style="font-size: 12px;color: #999999;line-height: 1;">收藏</text>
						</view>
					</view>
				</view>
			</view>
			
			<u-line dashed></u-line>
			
			<!-- 章节列表 -->
			<view style="margin: 15px 15px 15px 15px;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 5px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">{{data.state}}</text>
					<u-icon v-if="data.nums!=='0'" :label="'已更新'+data.nums+'话'" labelPos="left" labelSize="12px" labelColor="grey" size="14px" bold color="grey" name="arrow-right" @click="chapter_open"></u-icon>
					<u-icon v-else label="暂无更新" labelPos="left" labelSize="12px" labelColor="grey"></u-icon>
				</view>
				<view v-if="data.nums!=='0'">
				<u-scroll-list :indicator="false">
				    <view v-for="(item, index) in data.chapter_list" :key="index"  @click="read_chapter(item.id)">
						<view style="position: relative;">
						    <u-image width="130px" height="70px" radius="5" :src="item.picx" style="border:0.5px solid #eee;border-radius:5px; margin-right: 10px;margin-bottom: 8px;">
						    	<view slot="error" style="font-size: 24rpx;">加载失败</view>
								<view slot="loading" style="width: 130px;height: 70px;border-radius: 5px;">
									<image src="/static/detail/mkz_bg_chapter_loading_img.png" style="width: 100%;height: 100%;border-radius: 5px;" mode="scaleToFill"></image>
								</view>
						    </u-image>
							<view style="position: absolute;left: 0px;top: -2px;border-radius: 0px;" v-if="item.cion!=='0'">
								<image src="/static/detail/mkz_ic_detaillist_vip_w.png" style="width: 48rpx;height: 32rpx;border-radius: 0px;" lazy-load></image>
							</view>
							<view style="position: absolute;left: 0px;top: -7rpx;border-radius: 0px;" v-else-if="item.vip!=='0'">
								<image src="/static/detail/mkz_ic_readlist_vip.png" style="width: 56rpx;height: 28rpx;border-radius: 0px;" lazy-load></image>
							</view>
						</view>
						<u--text :lines="1" :text="item.name" size="14" color="#444444"></u--text>
				    </view>
					<view style="width: 60px;margin-top: auto;margin-bottom: auto;" @click="chapter_open">
					    <u-icon name="/static/detail/mkz_ic_detail_mllist_r.png" size="30" style="margin-left: 15px;margin-right: 15px;"></u-icon>
					    <view style="display: flex;justify-content: center;margin-top: 5px;">
						    <text style="color: #aaaaaa;font-size: 12px;">查看更多</text>
						</view>
					</view>
				</u-scroll-list>
				</view>
			</view>
			
			<!-- 评论列表 -->
			<view style="margin: 0px 15px 15px 15px;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 5px;">
					<view>
						<view>
						    <text style="font-size: 18px;font-weight: bold;">#{{data.name}}</text>
						</view>
						<view style="display: flex;">
						    <text style="font-size: 12px;color: #aaaaaa;">{{data.comment_nums == 0 ? '暂无评论,快来抢沙发吧' : '与'+data.comment_nums+'人一起讨论'}}</text>
						    <u-icon name="arrow-right" size="13px" color="#aaaaaa" bold></u-icon>
						</view>
					</view>
					<view @click="comment_show=true" style="background-color: #FDF6EC;border-radius: 50px;display: flex;justify-content: center;align-items: center;padding: 2px;margin-top: auto;margin-bottom: auto;">
						<u-image src="/static/detail/mkz_ic_detail_ft.png" width="44rpx" height="44rpx" style="margin-left: 3px;"></u-image>
						<text style="color: #ff620e;font-size: 12px;margin: auto 3px auto 1px;">发帖~</text>
					</view>
				</view>
				
				<u-empty
				        mode="comment"
				        icon="http://cdn.uviewui.com/uview/empty/comment.png"
						text="暂无评论,快来抢沙发吧"
						textSize="12"
						v-if="data.comment_nums==0"
				>
				</u-empty>
				
				<view v-for="(item,index) in data.comment_list" :key="index" style="margin-bottom: 25px;">
					<view style="display: flex;">
						<view style="margin-top: auto;margin-bottom: auto;">
						    <u-avatar :src="item.upic" size="30"></u-avatar>
						</view>
						<view style="margin: auto auto auto 8px;">
							<view style="display: flex;">
								<text style="font-size: 13px;">{{item.unichen}}</text>
							</view>
							<view style="display: flex;">
								<text style="font-size: 11px;color: #aaaaaa;">{{item.addtime}}</text>
							</view>
						</view>
					</view>
					<view style="margin-top: 8px;">
						<text style="font-size: 15px;color: #161616;">
							{{item.text}}
						</text>
					</view>
					<view style="margin: 10px 0px auto auto;display: flex;width: 40%;justify-content: space-between;">
						<view style="display: flex;">
						    <view style="display: flex;">
						        <u-icon name="/static/detail/ic_coment_pl.png" size="18"></u-icon>
						    </view>
						    <view style="display: flex;margin-left: 1px;">
						        <text style="color: #aaaaaa;font-size: 12px;">{{item.reply_num == 0 ? '' : item.reply_num}}</text>
						    </view>
						</view>
						<view>
							<view v-if="item.is_zan==0" style="display: flex;" @click="comment_zan_click(item.id)">
								<view style="display: flex;">
								    <u-icon name="/static/detail/ic_coment_dz_off_1.png" size="18"></u-icon>
								</view>
								<view style="display: flex;margin-left: 1px;">
								    <text style="color: #aaaaaa;font-size: 12px;">{{item.zan}}</text>
								</view>
							</view>
						    <view v-else style="display: flex;" @click="comment_zan_cancel(item.id)">
								<view style="display: flex;">
						    	    <u-icon name="/static/detail/ic_coment_dz_on.png" size="18"></u-icon>
								</view>
								<view style="display: flex;margin-left: 1px;">
						    	    <text style="color: #ff620e;font-size: 12px;">{{item.zan}}</text>
								</view>
						    </view>
						</view>
					</view>
				</view>
			</view>
			
			<view style="margin: 15px 15px 15px 15px;">
				<view style="display: flex;justify-content: space-between;margin-bottom: 15px;margin-top: 5px;">
					<text style="font-weight: 500;margin-left: 10rpx;color: #000;font-size: 18px;">猜您喜欢</text>
				</view>
				<u-scroll-list :indicator="false">
				    <view v-for="(item, index) in data.love_list" :key="index" @click="shuaxin(item.id)">
						<view style="position: relative;">
						    <u-image width="105px" height="140px" radius="5" :src="item.pic" style="margin-right: 10px;margin-bottom: 8px;">
						    	<view slot="error" style="font-size: 24rpx;">加载失败</view>
						    </u-image>
							<!-- <view style="position: absolute;bottom: 3px;left: 3px; z-index: 2;text-shadow: 0px 4px 5px #000;">
								<u--text :lines="1" :text="item.addtime" size="11" color="#ffffff"></u--text>
							</view> -->
						</view>
						<u--text :lines="1" :text="item.name" size="14" color="#444444"></u--text>
				    </view>
				</u-scroll-list>
			</view>
			
			
			
			<view style="height: 65px;"></view>
		</mescroll-body>
		
		<!-- 送礼物弹窗 -->
		<hqs-popup title="打赏作者大大一个小礼物吧~" v-model="slw_show" :height="slw_height+'px'" showClose="close">
			<view style="position: fixed;top: -30px;left: 0;right: 0; z-index: 9999999;display: flex;justify-content: space-between;">
				<view style="background-color: #5b3f30;display: flex;height: 18px;padding: 3px;border-radius: 0 50px 50px 0;">
					<view style="display: flex;background:linear-gradient(#ff660f,#ff961f);border-radius: 50px;padding-right: 5px;">
						<image src="../../static/detail/mkz_ic_dstc_dsb.png" style="width: 26rpx;height: 24rpx;margin: auto;padding: 3px;"></image>
						<text style="color: #fff;font-size: 28rpx;margin: auto;">{{data.cion_rank}}</text>
					</view>
					<view style="color: #fff;margin: auto 3px;">
						<!-- <text>打赏榜第{{data.cion_rank}}名</text> -->
						<u-icon :label="'打赏榜第'+data.cion_rank+'名'" color="#fff" size="13px" labelColor="#fff" labelPos="left" labelSize="28rpx" name="arrow-right" @click=""></u-icon>
					</view>
				</view>
				<lff-barrage ref="slw" :maxTop="240" type="leftBottom"></lff-barrage>
			</view>
			<view class="c">
			    <view style="display: flex;justify-content: space-between;padding-left: 15px;padding-right: 15px;flex-wrap:wrap;">
			    	<view v-for="(item,index) in slw_data" :key="index" style="border-radius: 5px;padding: 5px;width: 18%;margin-bottom: 5px" @click="slw_id=item.id;slw_cion=item.cion" :style="slw_id==item.id?'border: 1px solid #fd610e;background-color: #fdefe1;':'border: 1px solid #dbdbdb;'">
			    		<view style="display: flex;justify-content: center;margin-bottom: 3px;">
			    			<image :src="item.pic" style="width: 72rpx;height: 72rpx;"></image>
			    		</view>
			    		<view style="display: flex;justify-content: center;">
			    			<text style="font-size: 26rpx;" :style="slw_id==item.id?'color: #fd610e;':''">{{item.name}}</text>
			    		</view>
						<view style="display: flex;justify-content: center;">
							<text style="font-size: 22rpx;" :style="slw_id==item.id?'color: #fd610e;':'color: grey;'">{{item.cion}}{{data.cion_name}}</text>
						</view>
			    	</view>
			    </view>
				<view style="display: flex;justify-content: space-between;">
					<view style="padding: 15px;font-size: 28rpx;color: #c6c7c8;">
						<text>我的{{data.cion_name}}：{{user.cion}}</text>
					</view>
					<view style="padding: 15px;font-size: 28rpx;color: #fd610e;" @click="$u.route('/pages/ucenter/coin/coin')">
						<text>充值></text>
					</view>
				</view>
			    <view style="margin: 15px;padding:10px;border-radius: 50px;background-color: #fd610e;">
			    	<view style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-if="!isLogin">
			    		<text>请登录后打赏</text>
			    	</view>
			    	<view @click="$u.route('/pages/ucenter/coin/coin')" style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-else-if="slw_cion-0>user.cion-0">
			    		<text>{{data.cion_name}}不足，去购买</text>
			    	</view>
			    	<view style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-else @click="slw_send()">
			    		<text>立即打赏</text>
			    	</view>
			    </view>
			    <view style="height: 1px;"></view>
			</view>
		</hqs-popup>
		
		<!-- 投月票弹窗 -->
		<hqs-popup :title="'为《' + data.name + '》投月票'" v-model="typ_show" :height="typ_height+'px'" showClose="close">
			<view class="b">
			<view style="background-color: #f6f6f6;display: flex;padding: 13px 73px 13px 15px;position: relative;justify-content: space-between;">
				<view style="margin-top: auto;margin-bottom: auto;">
					<view style="color: #666666;font-size: 26rpx;">
						<text>当前排名></text>
					</view>
					<view style="color: #fd610e;font-size: 50rpx;font-weight: 600;display: flex;justify-content: center;">
						<text>{{data.ticket_rank}}</text>
					</view>
				</view>
				<view style="height: 50px;width: 1px;background-color: #e8e8e8;margin-top: auto;margin-bottom: auto;"></view>
				<view style="font-size: 28rpx;margin-top: auto;margin-bottom: auto;">
					<view style="line-height: 50rpx;">
						<text>当前月票数 {{data.ticket}}</text>
					</view>
					<view style="line-height: 50rpx;" v-if="data.ticket_rank==1">
						<text>当前月票榜第一名，继续保持哦!</text>
					</view>
					<view style="line-height: 50rpx;" v-else>
						<text>距上一名，还差您的关键票</text>
					</view>
				</view>
				<view style="position: absolute;right: 0;bottom: -3px;">
					<image src="/static/detail/mkz_pic_pmzs.png" style="width: 146rpx;height: 116rpx;"></image>
				</view>
			</view>
			<view style="display: flex;justify-content: space-between;">
				<view style="padding: 15px;font-size: 26rpx;color: #c6c7c8;">
					<text>我的月票：{{user.ticket}}</text>
					<!-- <text>我的{{data.cion_name}}：{{user.cion}}</text> -->
				</view>
			</view>
			<view style="display: flex;justify-content: space-between;padding-left: 15px;padding-right: 15px;">
				<view style="border-radius: 5px;padding: 5px;width: 18%;" @click="typ_num=1" :style="typ_num==1?'border: 1px solid #fd610e;background-color: #fdefe1;':'border: 1px solid #dbdbdb;'">
					<view style="display: flex;justify-content: center;">
						<image :src="typ_num==1?'/static/detail/mkz_ic_typ_yp1_on.png':'/static/detail/mkz_ic_typ_yp1_off.png'" style="width: 72rpx;height: 72rpx;"></image>
					</view>
					<view style="display: flex;justify-content: center;">
						<text style="font-size: 26rpx;" :style="typ_num==1?'color: #fd610e;':'color: grey;'">1张月票</text>
					</view>
				</view>
				<view style="border-radius: 5px;padding: 5px;width: 18%;" @click="typ_num=2" :style="typ_num==2?'border: 1px solid #fd610e;background-color: #fdefe1;':'border: 1px solid #dbdbdb;'">
					<view style="display: flex;justify-content: center;">
						<image :src="typ_num==2?'/static/detail/mkz_ic_typ_yp2_on.png':'/static/detail/mkz_ic_typ_yp2_off.png'" style="width: 72rpx;height: 72rpx;"></image>
					</view>
					<view style="display: flex;justify-content: center;">
						<text style="font-size: 26rpx;" :style="typ_num==2?'color: #fd610e;':'color: grey;'">2张月票</text>
					</view>
				</view>
				<view style="border-radius: 5px;padding: 5px;width: 18%;" @click="typ_num=3" :style="typ_num==3?'border: 1px solid #fd610e;background-color: #fdefe1;':'border: 1px solid #dbdbdb;'">
					<view style="display: flex;justify-content: center;">
						<image :src="typ_num==3?'/static/detail/mkz_ic_typ_yp3_on.png':'/static/detail/mkz_ic_typ_yp3_off.png'" style="width: 72rpx;height: 72rpx;"></image>
					</view>
					<view style="display: flex;justify-content: center;">
						<text style="font-size: 26rpx;" :style="typ_num==3?'color: #fd610e;':'color: grey;'">3张月票</text>
					</view>
				</view>
				<view style="border-radius: 5px;padding: 5px;width: 18%;" @click="typ_num=4" :style="typ_num==4?'border: 1px solid #fd610e;background-color: #fdefe1;':'border: 1px solid #dbdbdb;'">
					<view style="display: flex;justify-content: center;">
						<image :src="typ_num==4?'/static/detail/mkz_ic_typ_yp4_on.png':'/static/detail/mkz_ic_typ_yp4_off.png'" style="width: 72rpx;height: 72rpx;"></image>
					</view>
					<view style="display: flex;justify-content: center;">
						<text style="font-size: 26rpx;" :style="typ_num==4?'color: #fd610e;':'color: grey;'">全部月票</text>
					</view>
				</view>
			</view>
			<view style="margin: 15px;padding:10px;border-radius: 50px;background-color: #fd610e;">
				<view style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-if="!isLogin">
					<text>请登录后投票</text>
				</view>
				<view @click="$u.route('/pages/ucenter/ticket/ticket')" style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-else-if="typ_num>user.ticket-0">
					<text>月票不足，去购买</text>
				</view>
				<view style="display: flex;justify-content: center;margin: auto;color: #fff;font-size: 32rpx;" v-else @click="typ_send()">
					<text>立即投票</text>
				</view>
			</view>
			<view style="height: 1px;"></view>
			</view>
		</hqs-popup>
		
		<!-- 章节列表弹窗 -->
		<hqs-popup title="目录" v-model="chapter_show" showClose="close" :height="chapter_height+'px'" showBack>
			<u-sticky>
			<u-line></u-line>
			</u-sticky>
			<view style="height: 88rpx;left: 0;right: 0;top: 0;display: flex;margin: auto 15px;justify-content: space-between;">
				<view style="display: flex;margin-top: auto;margin-bottom: auto;">
					<text style="font-weight: 600;font-size: 16px;">连载中({{chapter_list_data.length}})</text>
				</view>
				<view style="display: flex;margin-top: auto;margin-bottom: auto;justify-content: space-between;width: 20%;">
					<view style="font-size: 28rpx;" :class="sort==0?'active':''" @click="sort_click(0)">
						<text>正序</text>
					</view>
					<view style="color: grey;font-size: 12px;">
						<text>|</text>
					</view>
					<view style="font-size: 28rpx;" :class="sort==1?'active':''" @click="sort_click(1)">
						<text>倒叙</text>
					</view>
				</view>
			</view>
			<virtual-list :items="chapter_list_data" :size="size" :remain="remain">
				<template v-slot:default="slotItem">
					<list-item :item="slotItem.item" class="a" :id="id"></list-item>
				</template>
			</virtual-list>
		</hqs-popup>
		
		<!-- 底部按钮 -->
		<view class="tabbar" :style="typ_show || slw_show?'z-index:0':'' || isfavor_show?'z-index:0':'' || comment_show?'z-index:0':'' ">
			<u-line></u-line>
			<view style="display: flex; margin-left: auto;margin-right: auto;height: 100%;">
			    <view style="display: flex;margin-left: 20px;margin-right: 0px; width: 100%;">
			        <!-- 收藏按钮 -->
					<view style="display: flex;" @click="isfavor()">
						<view style="margin:auto;">
							<view style="margin-left: auto;margin-right: auto;display: flex;justify-content: center;">
							    <u-image v-if="data.fav==0" width="25px" height="25px" src="/static/detail/ic_read_sc.png"></u-image>
							    <u-image v-else width="25px" height="25px" src="/static/detail/ic_read_sc_on.png"></u-image>
							</view>
							<view style="margin-left: auto;margin-right: auto;background-color: #FFFFFF;margin-top: -6px;display: flex;justify-content: center;">
							    <text style="background-color: #FFFFFF;line-height: 1;font-weight: 600; font-size: 10px;color: #000000;z-index: 999;">{{data.shits}}</text>
							</view>
						</view>
						<view style="margin-top: auto;margin-bottom: auto;">
						    <text v-if="data.fav==0" style="margin-left: 8px;color: #000000;font-size: 14px;">收藏</text>
							<text v-else style="margin-left: 8px;color: #a8a8a8;font-size: 14px;">收藏</text>
						</view>
					</view>
					<view style="margin: auto 10px auto 10px;">
					    <u-line direction="col" dashed length="16px" :hairline="false"></u-line>
					</view>
					<!-- 评论按钮 -->
					<view style="display: flex;" @click="comment_show=true">
						<view style="margin:auto;">
							<view style="margin-left: auto;margin-right: auto;display: flex;justify-content: center;">
							    <u-image width="25px" height="25px" src="/static/detail/ic_read_nav_pl.png"></u-image>
							</view>
							<view style="margin-left: auto;margin-right: auto;background-color: #FFFFFF;margin-top: -6px;display: flex;justify-content: center;">
							    <text style="background-color: #FFFFFF;line-height: 1;font-weight: 600; font-size: 10px;color: #000000;z-index: 999;">{{data.comment_nums}}</text>
							</view>
						</view>
						<view style="margin-top: auto;margin-bottom: auto;">
						    <text style="margin-left: 8px;color: #000000;font-size: 14px;">评论</text>
						</view>
					</view>
					<u-button @click="read()" hoverStayTime="0" v-if="read_status==0" text="开始阅读" color="linear-gradient(to right, rgb(255, 88, 33), rgb(255, 88, 88))" style="width: 49%;height: 60%; margin: auto;border-radius: 50px;box-shadow: -1px 1px 1px 1px #ff5821;"></u-button>
					<u-button @click="read()" hoverStayTime="0" v-else-if="read_status==1" text="继续阅读" color="linear-gradient(to right, rgb(255, 88, 33), rgb(255, 88, 88))" style="width: 49%;height: 60%; margin: auto;border-radius: 50px;box-shadow: -1px 1px 1px 1px #ff5821;"></u-button>
					<u-button @click="read()" hoverStayTime="0" v-else text="等待更新" color="linear-gradient(to right, rgb(255, 88, 33), rgb(255, 88, 88))" style="width: 49%;height: 60%; margin: auto;border-radius: 50px;box-shadow: -1px 1px 1px 1px #ff5821;"></u-button>
			    </view>
			</view>
		</view>
		
		<u-popup :show="comment_show" round="15" @close="comment_show=false">
			<view style="border-radius: 50px;border: 3rpx solid #eee;margin: 8px auto 0px auto;width: 6%;"></view>
			<view style="padding: 15px;">
				<textarea v-model="comment" focus maxlength="200" auto-height style="border-radius: 8px;background-color: #f5f5f6;padding: 10px;width: auto;font-size: 30rpx;min-height: 88px;" placeholder="是时候来发骚评论了吧~~" />
			</view>
			<view style="display: flex;justify-content: space-between;padding: 0 15px 15px 15px;align-items: center;">
				<view style="display: flex;font-size: 30rpx;" @click="random_comment()">
					<view>
						<u-icon name="chat" bold size="18"></u-icon>
					</view>
					<view style="margin-left: 3px;font-weight: 500;">
						快评
					</view>
				</view>
				<view @click="subComment()" style="border-radius: 50px;background-color: #ff5500;padding: 3px 13px;font-size: 30rpx;color: #fff;">
					发表
				</view>
			</view>
		</u-popup>
		
		<!-- 分享海报 -->
		<XQ-GeneratePoster ref="draw" />
		
		<!-- 取消收藏弹窗 -->
		<u-modal :show="isfavor_show" title="" content="亲，确定要取消收藏吗？" showCancelButton confirmColor="#fd610e" @cancel="isfavor_show=false" @confirm="cancel_fav()" :asyncClose="true"></u-modal>
		
		<!-- 全屏加载中 -->
		<u-loading-page image="/static/loading.gif" imgStyle="width: 111px;height: 135px" :loading="loading_show" loadingText="主人，再等等..." fontSize="16" style="z-index: 9999999;"></u-loading-page>
	</view>
</template>

<script>
	import w_md5 from "../../js_sdk/zww-md5/w_md5.js"
	import MescrollMixin from "@/uni_modules/mescroll-uni/components/mescroll-uni/mescroll-mixins.js";
	import VirtualList from '@/components/xc-virtual-list/xc-virtual-list.vue'
	import ListItem from '@/components/xc-virtual-list/xc-list-item.vue';
	import lffBarrage from '@/components/lff-barrage/lff-barrage.vue' //弹幕组件
	export default {
		mixins: [MescrollMixin], // 使用mixin
		components: {
		    w_md5,
			VirtualList,
			ListItem,
			lffBarrage
		},
		data() {
			return {
				isLogin: false, //登陆状态
				user: {}, //用户详情,金币和会员状态
				typ_show: false, //投月票弹窗
				typ_num: 1, //投月票选项,1,2,3,4对应四个选项
				typ_height: 0, //投月票弹窗高度
				slw_data: [], //礼物列表
				slw_show: false, //送礼物弹窗
				slw_id: 1, //礼物ID
				slw_cion: 0, //礼物价格
				slw_height: 0, //送礼物弹窗高度
				dsq_id: '', //打上列表弹幕定时器id
				bgcolor: 0, //导航栏渐变背景
				userinfo: '',
				nav_color: "rgba(0,0,0,0)",
				chapter_show: false,
				chapter_height: 0,
				chapter_heighta: 0,
				loading_show: true, // 全屏加载中
				jianpan: 30,
				comment: '',
				comment_list: [],
				comment_show: false,
				random_comment_list: [
					'同九义，汝河秀？',
					'同样是腰间盘，阁下为何这么突出？',
					'可爱，想太阳',
					'我以为你喜欢海，原来你喜欢浪。',
					'完了，你也不理我了，我成狗不理了。',
					'现在大学生吃饱了没事干就想找个男朋友女朋友。而我就比较牛逼了，我吃不饱。',
					'每当我准备认真看书的时候，这个世界就变得非常有趣，任何风吹草动都能吸引我。',
					'脚踏两只船，迟早要翻船，脚踏万只船，翻都翻不完。',
					'暑假在家没事做？不如跟我一起做复读机吧。复制这段话再发出去，每天收入0元，我和身边朋友都在做，反正闲着也是闲着。',
					'我和赌毒不共戴天',
					'你应该很孤单吧，一个人缓慢的翻着漫画，看着评论，而这一秒你正好看到我的评论，这一秒你是属于我的，你好，陌生人，我顶你个肺！',
					'有的人喜欢粉色，因为他少女。有的人喜欢红色，因为他热烈。有的人喜欢黑色，因为他emo。有的人喜欢白色，因为他纯洁。有的人喜欢蓝色，因为他忧郁。有的人喜欢绿色，因为他鲜活。有的人喜欢黄色，发给我谢谢'
				], //快评列表
				read_status: 0, //0=从未阅读，1=继续阅读，2=无法阅读
				chapter_name: '',
				title: '', // 导航栏漫画名称
				data_null: false,
				data_null_text: '暂无内容',
				nav_show: false, //导航栏背景是否显示
				nav_height: 0, //导航栏高度
				img_gg: '',
				url_gg: '',
				// img_gg: 'https://image.zymkcdn.com/file/news/000/004/456.jpg-1920x560.webp',
				isfavor_status: false,
				isfavor_show: false, //取消收藏弹窗显示控制
				love_list: '',
				chapter_list: '',
				chapter_list_data: [{"id":"1","name":"开心漫画","addtime":"","cion":"0","vip":"0"}],
				size: 0,
				item_height: 0,
				remain: 1,
				sort: 0 ,// 0=正序，1=倒叙
				
				chapter_page_num: 1,
				id: 0, //漫画ID
				time: '',
				token: '',
				app_key: '',
				websiteUrl: '',
				utoken: '',
				data: '',
				tabs_index: 0,
				downOption: {
					use : true,
					auto: false,
					autoShowLoading: true,
					inOffsetRate: 0.4,
					// offset: 50
				},
				upOption: {
					use : false
				},
				option: {
				    //绘制海报内容
				    codeUrl: 'https://pic1.zhimg.com/80/v2-2714df42147132464a71af391ed04be4_720w.jpg', //二维码
				    // codeUrl: '/static/tym.jpg', //二维码
				    coverUrl: 'https://pic1.zhimg.com/80/v2-2714df42147132464a71af391ed04be4_720w.jpg', //图片
				    headUrl: 'https://pic1.zhimg.com/80/v2-b9df1fdfe67f2177d5c84af90dcadfc1_720w.jpg?source=1940ef5c', //头像
				    bgUrl: 'https://pic3.zhimg.com/v2-8fbde0f9ac6a19a23aa839e73394618a_b.jpg', //图片背景填充，和 fillStyle 只能传一个，bgUrl 优先级高于 fillStyle
				    fillStyle: '#0688ff', //纯色背景填充颜色
				    nickName: '神秘人', //登录的用户名
				    miniName: '@邀请你陪我一起来看漫画啦！', //邀请内容
				    tkName:'漫画名称', //漫画名称
				    tkAuthor:'漫画简介', //漫画简介
				    tkType: '漫画作者', //漫画作者
				    cost: '完结', //连载状态
				    isPub: '人气' //漫画人气
				}
			}
		},
		onPageScroll : function(e) {
			this.nav_show = true
			this.title = this.data.name
			if (e.scrollTop >= 0 && e.scrollTop<=8) { 
			    // this.flag = true
				this.bgcolor="opacity:0"
			} else if(e.scrollTop > 8 && e.scrollTop<=16){
			    // this.flag = false
				this.bgcolor="opacity:0.1"
			}else if(e.scrollTop >16 && e.scrollTop<=24){
				this.bgcolor="opacity:0.2"
			}else if(e.scrollTop >24 && e.scrollTop<=32){
				this.bgcolor="opacity:0.3"
			}else if(e.scrollTop >32 && e.scrollTop<=40){
				this.bgcolor="opacity:0.4"
			}else if(e.scrollTop >40 && e.scrollTop<=48){
				this.bgcolor="opacity:0.5"
			}else if(e.scrollTop >48 && e.scrollTop<=56){
				this.bgcolor="opacity:0.6"
			}else if(e.scrollTop >56 && e.scrollTop<=64){
				this.bgcolor="opacity:0.7"
			}else if(e.scrollTop >64 && e.scrollTop<=72){
				this.bgcolor="opacity:0.8"
			}else if(e.scrollTop >72 && e.scrollTop<=80){
				this.bgcolor="opacity:0.9"
			}else if(e.scrollTop >80 ){
				this.bgcolor="opacity:1"
			}
		},
		onLoad: function (option) {
			this.id = option.id
			console.log("屏幕高度:",uni.getSystemInfoSync().screenHeight)
			console.log("可使用窗口高度:",uni.getSystemInfoSync().windowHeight)
			this.chapter_height = uni.getSystemInfoSync().windowHeight - uni.getSystemInfoSync().statusBarHeight - 60
			this.chapter_heighta = this.chapter_height - 44
			this.nav_height = uni.getSystemInfoSync().statusBarHeight+44
			uni.showToast({
				title: uni.getSystemInfoSync().statusBarHeight,
				icon:'none'
			})
			this.req_ad()
		},
		onShow() {
			this.shuaxin(this.id)
		},
		onBackPress(e){
			console.log(e)
			if(e.from=='backbutton'){
				if(this.chapter_show){
					this.chapter_show = false
				}else if(this.typ_show){
					this.typ_show = false
				}else if(this.slw_show){
					this.slw_show = false
				}else if(this.comment_show){
					this.comment_show = false
				}else{
					uni.navigateBack({})
				}
				return true
			}
		},
		
		watch: {
		    slw_show: {
		        handler(newv, old) {
					if(!newv){
						clearInterval(this.dsq_id)
					}
		        }
		    }
		},
		methods: {
			// 送礼物
			slw_send(){
				if(this.isLogin){
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&gid=" + this.slw_id + "&mid=" + this.id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/gift/send?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							console.log("打赏结果: ",res.data);
							if(res.data.code==1){
								uni.showToast({
								    icon: 'success',
									// position: 'bottom',
								    title: '打赏成功'
								});
							}else{
								uni.showToast({
								    icon: 'none',
									// position: 'bottom',
								    title: res.data.msg
								});
							}
							this.shuaxin(this.id)
					    }
					});
				}else{
					uni.showToast({
						title:"请先登录",
						icon: 'error'
					})
					uni.navigateTo({
						url: '/pages/login/login'
					})
				}
			},
			// 投月票
			typ_send(){
				if(this.isLogin){
					
					let num = this.typ_num
					if(this.typ_num==4){
						num = this.user.ticket
					}
					
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					
					var param = "bid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.id + "&ticket=" + num + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/gift/ticket_send?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							console.log("投月票结果: ",res.data);
							if(res.data.code==1){
								uni.showToast({
								    icon: 'success',
									// position: 'bottom',
								    title: '投票成功'
								});
							}else{
								uni.showToast({
								    icon: 'none',
									// position: 'bottom',
								    title: res.data.msg
								});
							}
							this.shuaxin(this.id)
					    }
					});
				}else{
					uni.showToast({
						title:"请先登录",
						icon: 'error'
					})
					uni.navigateTo({
						url: '/pages/login/login'
					})
				}
			},
			sort_click(id){
				if(id==0){
					if(this.sort!==0){
						this.chapter_list_data = this.chapter_list_data.reverse()
						this.sort = 0
					}
				}else{
					if(this.sort!==1){
						this.chapter_list_data = this.chapter_list_data.reverse()
						this.sort = 1
					}
				}
			},
			req_userinfo(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				return new Promise((resolve, reject) => {
				    uni.request({
				        url: getApp().globalData.websiteUrl + '/index.php/appv1/user/index?' + param + "&sign=" + sign,
				    	header: {
				    	    'content-type': 'application/json' //自定义请求头信息
				    	},
				        success: (res) => {
				    		console.log("用户信息：",res.data)
				    		resolve(res.data.user)
				        }
				    });
				})
			},
			
			async share_qrcode() {
				
				if(!uni.getStorageSync('user')){
					this.option.headUrl = "/static/mrtx.gif" //头像
					this.option.nickName = "神秘人" //名称
				}else{
					this.userinfo = await this.req_userinfo()
					this.option.headUrl = this.userinfo.pic //头像
					this.option.nickName = this.userinfo.nichen || this.userinfo.name //名称
				}
				
				this.option.codeUrl = "https://api.pwmqr.com/qrcode/create/?url=" + this.data.share_url
				this.option.coverUrl = this.data.picx
				// this.option.bgUrl = this.data.pic //海报背景图
				this.option.tkName = this.data.name //漫画名称
				this.option.tkAuthor = this.data.text //漫画简介
				this.option.tkType = this.data.author //漫画作者
				this.option.cost = this.data.state //连载状态
				this.option.isPub = this.data.hits + "人气" //漫画人气
			    this.$refs.draw.share_qrcode(this.option);
			},
			chapter_close(){
				this.chapter_show = false
			},
			chapter_open(){
				// uni.navigateTo({
				// 	url: './chapter/chapter?id=' + this.id
				// })
				this.chapter_show = true
				setTimeout(() => {
					let _this = this;
					uni.getSystemInfo({
					  success: function (res) { // res - 各种参数
					
					    let obj = uni.createSelectorQuery().select('.a')
					
					    obj.boundingClientRect(function (data) { // data - 各种参数
					      
					      console.log(data)
						  _this.item_height = data.height
						  console.log(_this.item_height)
						  // this.size = +uni.upx2px(52);
						  _this.size = _this.item_height
						  console.log(_this.chapter_heighta)
						  _this.remain = _this.chapter_heighta / _this.size / 10 * 9
						  console.log(_this.remain)
					
					    }).exec()
					  }
					})
				}, 800);
			},
			// 投月票弹窗
			typ_open(){
				if(!this.isLogin){
					uni.navigateTo({
						url: '../login/login'
					})
					return;
				}
				this.typ_show = true
				setTimeout(() => {
					let _this = this;
					uni.getSystemInfo({
					  success: function (res) { // res - 各种参数
					
					    let obj = uni.createSelectorQuery().select('.b')
					
					    obj.boundingClientRect(function (data) { // data - 各种参数
					      
					      console.log(data)
						  _this.typ_height = data.height
					
					    }).exec()
					  }
					})
				}, 500);
			},
			// 送礼物弹窗
			slw_open(){
				if(!this.isLogin){
					uni.navigateTo({
						url: '../login/login'
					})
					return;
				}
				this.slw_show = true
				setTimeout(() => {
					let _this = this;
					uni.getSystemInfo({
					  success: function (res) { // res - 各种参数
					
					    let obj = uni.createSelectorQuery().select('.c')
					
					    obj.boundingClientRect(function (data) { // data - 各种参数
					      
					      console.log(data)
						  _this.slw_height = data.height
					
					    }).exec()
					  }
					})
				}, 800);
				
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.id + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/gift/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("礼物列表: ",res.data);
						this.slw_data = res.data.list
				    }
				});
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/api/rend/gift?mid=" + this.id,
					success: (res) => {
						console.log(res.data)
						if(res.data.list.length!==0){
							this.slw_dm(res.data.list)
						}
					}
				})
			},
			// 送礼物弹幕
			slw_dm(data){
				let slw_list = data
				let i = 0
				this.dsq_id = setInterval(() => {
					this.$refs.slw.add({name:slw_list[i].name,nick:slw_list[i].unichen,avatar:slw_list[i].upic});
					
					if(i==slw_list.length-1){
						i=0
					}else{
						i += 1;
					}
				},2000)
			},
			req_ad(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				
				uni.request({
					url: getApp().globalData.websiteUrl + "/index.php/appv1/kxapps/ads?" + param + "&sign=" + sign,
					success: (res) => {
						console.log(res.data)
						this.img_gg = res.data.list[2].img
						this.url_gg = res.data.list[2].url
					}
				})
			},
			keyboardheightchange(e){
				console.log(e.detail.height)
				this.jianpan = e.detail.height + 30
			},
			// 快评
			random_comment(){
				this.comment = this.random_comment_list[Math.floor(Math.random() * this.random_comment_list.length) + 1]
			},
			// 评论点赞
			comment_zan_click(id){
				if(!uni.getStorageSync('user')){
					console.log("未登录")
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&fid=0" + "&id=" + id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/comment/zan?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							console.log("评论点赞结果: ",res.data);
							if(res.data.code==1){
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: '点赞成功'
								});
								this.shuaxin(this.id)
							}else{
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: res.data.msg
								});
							}
							// this.get_comment_list()
					    }
					});
				}
			},
			//评论赞取消
			comment_zan_cancel(id){
				if(!uni.getStorageSync('user')){
					console.log("未登录")
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&fid=0" + "&id=" + id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/comment/zan?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							console.log("评论点赞结果: ",res.data);
							if(res.data.code==1){
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: '取消点赞'
								});
								this.shuaxin(this.id)
							}else{
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: res.data.msg
								});
							}
							// this.get_comment_list()
					    }
					});
				}
			},
			read_chapter(chapter_id){
				getApp().globalData.book_img = this.data.picx
				uni.navigateTo({
					url:'../read/read?book_id=' + this.id + '&id=' + chapter_id
				})
			},
			read(){
				if(this.read_status==2){
					console.log("没有章节")
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "没有章节可看了"
					});
				}else{
					getApp().globalData.book_img = this.data.picx
					uni.navigateTo({
						url:'../read/read?book_id=' + this.id
					})
				}
			},
			get_comment_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				var param = "bid=0&deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.id + "&page=1&size=150&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comment/index?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("评论列表: ",res.data);
						this.comment_list = res.data.list
				    }
				});
			},
			subComment(){
				if(this.comment==""){
					return uni.showToast({
						icon:'none',
						title:'是不是忘记写点什么了'
					})
				}
				if(!uni.getStorageSync('user')){
					console.log("未登录")
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&mid=" + this.id + "&text=" + this.comment + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
					var sign = w_md5.hex_md5_32Upper(param + this.app_key)
					uni.request({
					    url: getApp().globalData.websiteUrl + '/index.php/appv1/comment/add?' + param + "&sign=" + sign,
						header: {
						    'content-type': 'application/json' //自定义请求头信息
						},
					    success: (res) => {
							this.comment_show = false
							this.comment = ''
							console.log("评论结果: ",res.data);
							uni.showToast({
							    icon: 'none',
								position: 'bottom',
							    title: res.data.msg
							});
							this.shuaxin(this.id)
							// this.get_comment_list()
					    }
					});
				}
			},
			shuaxin(id){
				// this.loading_show = true
				this.chapter_page_num = 1
				this.read_status = 2
				this.chapter_name=''
				
				this.id = id
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					this.isLogin = false
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					this.isLogin = true
				}
				// this.get_chapter_list()
				// this.get_comment_list()
				
				//获取阅读记录
				var mh_history = uni.getStorageSync('mh_history')
				if (typeof mh_history !== 'object') {
					mh_history = []
				}
				mh_history.forEach((value) => {
					if (Number(value.book_id) === Number(this.id)) {
						this.read_status = 1
						this.chapter_name = value.chapter_name
					}
				})
				this.get_detail()
				this.get_chapter_list()
			},
			isfavor(){
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					uni.showToast({
					    icon: 'none',
						position: 'bottom',
					    title: "请先登录"
					});
					uni.navigateTo({
						url:"../login/login"
					})
				}else{
					console.log("已登录",uni.getStorageSync('user'))
					var time = new Date().getTime()
					this.app_key = getApp().globalData.websiteKey
					
					if(this.data.fav!==0){
						this.isfavor_show = true
					}else{
						var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
						var sign = w_md5.hex_md5_32Upper(param + this.app_key)
						uni.request({
						    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/add?' + param + "&sign=" + sign,
							header: {
							    'content-type': 'application/json' //自定义请求头信息
							},
						    success: (res) => {
								console.log("加入书架成功")
								this.data.fav = 1
								this.data.shits = parseInt(this.data.shits)+1
								uni.showToast({
								    icon: 'none',
									position: 'bottom',
								    title: "收藏成功,请在书架查看"
								});
								this.isfavor_status = true //无用
						    }
						});
					}
				}
			},
			// 取消收藏
			cancel_fav(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&ids=" + this.id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/cases/del/comic?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log("删除书架成功")
						this.data.fav = 0
						this.data.shits = parseInt(this.data.shits)-1
						uni.showToast({
						    icon: 'none',
							position: 'bottom',
						    title: "取消收藏成功"
						});
						this.isfavor_status = false //无用
						this.isfavor_show = false
				    }
				});
			},
			get_chapter_list(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.id + "&timestamp=" + time
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/chapter?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						this.loading_show = false
						// console.log(res.data)
						this.chapter_list_data = res.data.list
				    }
				});
			},
			get_detail(){
				var time = new Date().getTime()
				this.app_key = getApp().globalData.websiteKey
				
				if(!uni.getStorageSync('user')){
					console.log("未登录",uni.getStorageSync('user'))
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.id + "&timestamp=" + time
				}else{
					var param = "deviceid=" + getApp().globalData.deviceid + "&facility=" + getApp().globalData.facility + "&id=" + this.id + "&timestamp=" + time + "&user_id=" + uni.getStorageSync('user').uid + "&user_token=" + uni.getStorageSync('user').utoken
				}
				
				var sign = w_md5.hex_md5_32Upper(param + this.app_key)
				uni.request({
				    url: getApp().globalData.websiteUrl + '/index.php/appv1/comic/info?' + param + "&sign=" + sign,
					header: {
					    'content-type': 'application/json' //自定义请求头信息
					},
				    success: (res) => {
						console.log(res.data)
						this.data = res.data.comic
						this.love_list = res.data.comic.love_list
						this.mescroll.endSuccess();
						this.loading_show = false
						if(this.title!==''){
							this.title = this.data.name
						}
						
						if(res.data.comic.chapter_list.length==0){
							this.data_null = true,
							this.data_null_text = '暂无章节'
							this.read_status = 2
							this.chapter_name = '暂无章节'
						}else{
							if(this.chapter_name==''){
								this.read_status = 0
								this.chapter_name = res.data.comic.chapter_list[0].name
							}
						}
						// 判断是否登录
						if(this.isLogin){
							this.user = res.data.comic.user
						}
				    }
				});
			},
			/*目录列表下拉刷新的回调 */
			downCallback() {
				// if(this.tabs_index==1){
				// 	console.log("目录下拉刷新")
				// 	this.get_chapter_list()
				// }
				// if(this.tabs_index==2){
				// 	console.log("评论下拉刷新")
				// 	this.get_comment_list()
				// }
				this.chapter_page_num = 1
				this.get_detail()
				
				// setTimeout(() => {
				//  	this.mescroll.endSuccess();
				// }, 2000);
			},
			ad(){
				// plus.runtime.openWeb(this.url_gg)
				uni.navigateTo({
					url:'../webview/webview?uri=' + this.url_gg
				})
			}
		}
	}
</script>

<style lang="scss">
	
	.chapter_title{
		// font-size: 14px;
		// font-weight: 300;
		// color: #fff;
		display: -webkit-box;
		/* -webkit-box-orient: vertical; */
		/*! autoprefixer: off */
		-webkit-box-orient: vertical;
		/* autoprefixer: on */
		-webkit-line-clamp: 2;
		overflow: hidden;
	}
	
	.tabbar {
		z-index: 99999;
		width: 100%;
		height: 65px;
		position: fixed;
		bottom: 0px;
		background-color: #FFFFFF;
		left: 0;
		right: 0;
	}
	.cm_tabbar {
		width: 95%;
		height: 55px;
		position: fixed;
		background-color: #F0F0F0;
		left: 0;
		right: 0;
	}
	
	.i_new_title{
		font-size: 14px;
		font-weight: 300;
		color: #000;
		display: -webkit-box;
		/* -webkit-box-orient: vertical; */
		/*! autoprefixer: off */
		-webkit-box-orient: vertical;
		/* autoprefixer: on */
		-webkit-line-clamp: 1;
		overflow: hidden;
	}
	.scroll-list {
		@include flex(column);
	
		&__goods-item {
			margin-right: 10px;
	
			&__image {
				width: 80px;
				height: 110px;
				border-radius: 8px;
			}
	
			&__text {
				color: #f56c6c;
				text-align: center;
				font-size: 12px;
				margin-top: 5px;
			}
		}
	
		&__show-more {
			background-color: #fff0f0;
			border-radius: 3px;
			padding: 3px 6px;
			@include flex(column);
			align-items: center;
	
			&__text {
				font-size: 12px;
				width: 12px;
				color: #f56c6c;
				line-height: 16px;
			}
		}
	}
	
	
	.page-conatiner {
			.card {
				position: fixed;
				top: 0;
				width: 100%;
				z-index: 2;
				overflow: hidden;
				.bg-image {
				    position: absolute;
				    width: 100%;
				    top: 0;
				    z-index: -1;
				    filter: blur(20rpx) brightness(70%); // 模糊半径和变暗
				    transform: scale(1.8); // 控制边缘是否模糊，小于1的话边缘会模糊
					image {
						height: 100%;
						width: 100%;
						vertical-align: middle;
					}
				}
				.bg-gray { // 相当于加了一层遮罩
				    position: absolute;
				    height: 100%;
				    width: 100%;
				    top: 0;
				    background: rgba(0, 0, 0, .1);
				    z-index: -1;
				    opacity: 0.5;
				}
			}
			
		}
		
		.active{
			color: #fd610e;
		}
		
		//禁止滚动
		.control-scroll{ 
			height: 100vh;
		    overflow-y: hidden; 
		}
</style>
