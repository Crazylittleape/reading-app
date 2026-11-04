# 项目目录与数据流

本文件描述代码职责和运行数据边界。初始化与启动命令以根目录 README 为准。

## 目录职责

| 路径 | 职责 | 提交规则 |
| --- | --- | --- |
| `README.md`、`AGENTS.md` | 启动说明与开发约定 | 提交 |
| `compose.yml`、`Dockerfile` | 容器编排与 PHP 环境 | 提交，不嵌入真实凭据 |
| `apache.conf`、`nginx.conf`、`php.ini` | 网关、脚本入口与运行限制 | 提交 |
| `.env.example` | 非敏感配置示例 | 提交 |
| `.env`、`secrets.json`、`deployment.json` | 环境凭据和部署记录 | 严格排除 |
| `site/index.php` | 普通网站与 App API 入口 | 提交 |
| `site/manage.php` | 管理后台入口 | 提交 |
| `site/sys/apps/controllers/admin/` | 登录、书籍与章节管理控制器 | 提交 |
| `site/sys/apps/controllers/appv1/` | App 书库与阅读 API | 提交 |
| `site/sys/apps/helpers/` | 签名、章节分表和正文路径等函数 | 提交 |
| `site/sys/apps/models/` | 数据访问、小说与模板业务 | 提交 |
| `site/sys/libs/` | CMS 环境配置桥接与基础配置 | 仅提交不含真实凭据的源码 |
| `site/sys/system/`、`site/sys/class/` | 框架与第三方 SDK | 保留声明，避免无关改动 |
| `site/template/`、`site/packs/` | 后台 / 旧网站模板及通用静态资源 | 提交 |
| `site/caches/`、`site/attachment/` | 宿主运行占位目录 | 排除；真实运行内容在命名卷 |
| `site/app/` | H5 构建结果和运行时配置 | 排除，由工具生成 |
| `uniapp/pages/books/` | 书库、详情目录、文字阅读页 | 提交 |
| `uniapp/common/book-api.js` | 请求签名、设备标识和图片地址 | 提交，不嵌入真实配置 |
| `uniapp/pages/` 其他目录 | 原项目漫画、账号等页面 | 保留，不等同于已验收 |
| `uniapp/uni_modules/` | 本地前端模块 | 提交源码与声明 |
| `uniapp/package.json`、`package-lock.json` | 构建依赖 | 提交并保持一致 |
| `uniapp/node_modules/`、`dist/`、`unpackage/` | 安装依赖和编译产物 | 排除 |
| `uniapp/public/runtime-config.js`、原生端 `runtime-config.json` | 初始化生成的客户端配置 | 排除 |
| `db-init/01-schema.sql` | 空数据库表结构 | 提交，不包含数据行 |
| `db-init/90-local-bootstrap.sql` | 首次管理员初始化数据 | 排除 |
| `scripts/bootstrap.py` | 生成本地配置与初始化文件 | 提交 |
| `scripts/build_h5.py` | 安装 / 编译前端并同步至服务目录 | 提交 |
| `scripts/container-entrypoint.sh` | 准备容器缓存目录和权限后启动服务 | 提交 |
| `scripts/check_repository.py`、`smoke.py` | 提交安全检查与服务冒烟检查 | 提交 |
| `tests/`、`docs/` | 回归测试、合成样本和交接说明 | 提交，不带私人内容 |

## 请求链路

```text
浏览器 / H5
    │ 同源请求
    ▼
Nginx 网关 :18087
    ▼
Apache / PHP
    ├── /app/                  编译后的 H5
    ├── /manage.php/...        管理后台
    ├── /index.php/appv1/...   阅读 API
    └── /attachment/...        封面与插图
            │
            ├── MySQL              书籍、章节目录与用户信息
            └── caches/txt/        由接口读取的章节正文
```

PHP 与数据库位于内部容器网络，数据库没有宿主机公开端口。Apache 拒绝通过 HTTP 直接读取 `sys/`、`template/` 和 `caches/`；上传目录不可执行 PHP。新增导入功能不得通过开放运行目录或放宽全部脚本路由绕过这些限制。

## 持久化数据

| Compose 卷 | 内容 | 备份含义 |
| --- | --- | --- |
| `reading-db` | MySQL 数据 | 与正文、资源和密钥成套备份 |
| `reading-cache` | 正文 TXT、会话与运行缓存 | 正文是业务数据，不能当作可随意清理的缓存 |
| `reading-uploads` | 封面和内嵌图等上传文件 | 与数据库引用保持一致 |

宿主目录仅是初始化占位。应按实际 Compose 卷导出数据，而不是只备份仓库目录。卷的实际名称可能带 Compose 项目前缀。

## 书籍与章节存储

- `mc_book` 保存书籍元数据、分类、章节统计和上架状态。
- 章节表为 `mc_book_chapter_{book_id % 100}`，规则在 `site/sys/apps/helpers/common_helper.php`。
- 章节记录包含 `id`、`bid`、`xid`、`name`、`vip`、`cion`、`text_num` 和审核状态。
- UTF-8 正文路径为 `caches/txt/{book_id}/{md5(str(chapter_id) + APP_BOOK_KEY)}.txt`，按换行分段。
- 阅读 API 返回 `data.book.text` 段落数组，`data.szid` / `data.xzid` 为上下节 ID。
- 封面和内嵌图使用服务端生成的安全相对路径，避免原 EPUB 临时路径或特定书名依赖。

不同环境重新分配 ID 或更换 `APP_BOOK_KEY` 时，需重新生成正文路径，不能复用旧文件名。

原始表结构使用 MyISAM，不提供事务回滚。实现原子导入时必须明确相关表的事务方案和数据库之外的文件补偿，不能仅加入 `BEGIN` / `ROLLBACK`。

## 配置与鉴权边界

服务端从环境读取数据库及 CMS 配置；H5 从 `/app/runtime-config.js` 获得公开配置并使用同源 API。客户端签名配置本来就可被读到，不能替代管理员会话或会员权限校验。数据库密码、加密密钥、正文密钥及管理员凭据不得输出至前端。

## 常见改动位置

| 需求 | 位置 |
| --- | --- |
| 阅读与本地进度 | `uniapp/pages/books/read.vue` |
| 列表与目录 | `uniapp/pages/books/index.vue`、`detail.vue` |
| 请求与图片处理 | `uniapp/common/book-api.js` |
| 阅读 API | `site/sys/apps/controllers/appv1/Book.php` |
| 后台书籍 / 章节操作 | `site/sys/apps/controllers/admin/Book.php` |
| 后台菜单 | `site/sys/libs/nav.php` |
| 分表与正文路径 | `site/sys/apps/helpers/common_helper.php` |
| 签名协议 | `site/sys/apps/helpers/app_helper.php` |

第一阶段建议新增独立导入控制器与解析服务，避免把解析、事务和上传逻辑堆进现有书籍控制器。验收见 `DEVIN_PHASE1.md`。
