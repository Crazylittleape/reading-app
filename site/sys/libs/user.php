<?php
define('User_Reg',1);
define('User_Reg_Tel',1);
define('User_Reg_Vip',0);
define('User_Reg_Cion',1);
define('User_Reg_Vip_Day',0);
define('User_Tg_Cion',2);
define('User_Pl_Cion',0);
define('User_Pl_Num',10);
define('User_Gg','爱看官方公告：目前注册赠送1金币，成功邀请用户注册赠送2积分');

define('Author_Mode',1);
define('Author_Rz',0);
define('Author_Tx_Rmb',10);
define('Author_Add_Cion',0);
define('Author_Comic_Cion',200);
define('Author_Book_Cion',200);
define('Author_Fc_Ds',10);
define('Author_Fc_Yp',10);
define('Author_Fc_Comic',10);
define('Author_Fc_Book',10);

define('Mail_Type','smtp');
define('Mail_Host','');
define('Mail_Port',465);
define('Mail_Name','爱看漫画官方');
define('Mail_Email','');
define('Mail_User','');
define('Mail_Pass','');
define('Mail_Crypto','ssl');
define('Mail_Demo','2336333954@qq.com');
define('Mail_Code_Title','您正在&#123;site_name&#125;操作的验证码');
define('Mail_Code_Msg','验证码为：&#123;code&#125;，您正在进行&#123;type&#125;操作验证码将在5分钟后失效。请及时使用。如果非本人操作请忽略,有任何疑问与我们联系&#123;site_name&#125;&#123;web_url&#125;');
define('Mail_Drawing','0');
define('Mail_Drawing_Title','&#123;web_name&#125; 提醒您&lt;&lt;提现成功');
define('Mail_Drawing_Msg','&lt;p&gt;亲爱的会员：&#123;user_nichen&#125;你有一笔提现已完成，信息如下：&lt;/p&gt;&lt;p&gt;提现单号：&#123;drawing_dd&#125;&lt;/p&gt;&lt;p&gt;提现状态：&#123;drawing_zt&#125; &lt;/p&gt;&lt;p&gt;提现金额：&#123;drawing_rmb&#125; &lt;/p&gt;&lt;p&gt;提现时间：&#123;drawing_addtime&#125;&lt;/p&gt;');
define('Mail_Remind',1);
define('Mail_Remind_Title','&#123;web_name&#125; 提醒您&lt;&lt;&#123;comic_name&#125;&gt;&gt;已更新至&#123;comic_chapter_name&#125;');
define('Mail_Remind_Msg','&lt;div style=&quot;margin:0 auto;&quot;&gt;	&lt;div style=&quot;text-align:center;&quot;&gt;		&lt;h2&gt;			《&#123;comic_name&#125;》更新啦！		&lt;/h2&gt;亲爱的会员：&#123;user_nichen&#125;【&#123;web_name&#125;】提醒您《&#123;comic_name&#125;》已更新&#123;comic_chapter_name&#125;&lt;/div&gt;	&lt;div style=&quot;border:1px solid #ccc;&quot;&gt;		&lt;div&gt;			&lt;img src=&quot;&#123;comic_pic&#125;&quot; alt=&quot;&#123;comic_name&#125;&#123;comic_chapter_name&#125;&quot; width=&quot;230&quot; height=&quot;300&quot;&gt; 		&lt;/div&gt;		&lt;div style=&quot;margin:10px;&quot;&gt;			&lt;div&gt;				&lt;li&gt;					名称：&lt;span style=&quot;line-height:1.5;&quot;&gt;&lt;a href=&quot;&#123;comic_url&#125;&quot;&gt;《&#123;comic_name&#125;》&lt;/a&gt;&lt;/span&gt; 				&lt;/li&gt;				&lt;li&gt;					状态：&lt;span style=&quot;line-height:1.5;&quot;&gt;&#123;comic_serialize&#125;&lt;/span&gt; 				&lt;/li&gt;				&lt;li&gt;					更新时间：&lt;span style=&quot;line-height:1.5;&quot;&gt;&#123;comic_addtime&#125;&lt;/span&gt; 				&lt;/li&gt;				&lt;li&gt;					简介：&lt;span style=&quot;line-height:1.5;&quot;&gt;&#123;comic_text&#125;&lt;/span&gt; 				&lt;/li&gt;			&lt;/div&gt;		&lt;/div&gt;	&lt;/div&gt;漫画地址&lt;span&gt;&lt;a href=&quot;&#123;comic_url&#125;&quot;&gt;&#123;comic_url&#125;&lt;/a&gt;&lt;/span&gt; &lt;br&gt;&lt;br&gt;&#123;web_name&#125; 运营团队，本邮件为系统自动发送，请勿回复。退订请点击：&lt;a href=&quot;&#123;comic_remind_url&#125; &quot;&gt;&lt;span style=&quot;color:#ff0000;&quot;&gt;点击取消订阅&lt;/span&gt;&lt;/a&gt; &lt;/div&gt;');

define('Sms_Mode',0);
define('Sms_Appid','');
define('Sms_Appkey','');
define('Sms_Name','Mccms官方');
define('Sms_Tpl_Log','SMS_10000001');
define('Sms_Tpl_Bind','SMS_10000002');
define('Sms_Tpl_Pass','SMS_10000003');

define('Land_QQ_Appid','');
define('Land_QQ_Appkey','');
define('Land_QQ_Url','https://www.aikmh.com/index.php/user/open/callback/qq');
define('Land_Wx_Appid','');
define('Land_Wx_Appkey','');
define('Land_Wx_Url','https://www.aikmh.com/index.php/user/open/callback/weixin');
define('Land_Wb_Appid','');
define('Land_Wb_Appkey','');
define('Land_Wb_Url','https://www.aikmh.com/index.php/user/open/callback/weibo');