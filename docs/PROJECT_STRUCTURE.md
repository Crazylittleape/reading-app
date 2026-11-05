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

阅读页的白色、暖黄、护眼绿和黑色主题共用当前设备的 `reading-theme` 设置；未设置或值无效时使用暖黄。字号使用 `reading-font-size`，阅读进度仍按书籍分别存储。主题同步应用于正文、导航栏、按钮和目录，不改变书内图片颜色。
| 后台书籍 / 章节操作 | `site/sys/apps/controllers/admin/Book.php` |
| 后台菜单 | `site/sys/libs/nav.php` |
| 分表与正文路径 | `site/sys/apps/helpers/common_helper.php` |
| 签名协议 | `site/sys/apps/helpers/app_helper.php` |

第一阶段建议新增独立导入控制器与解析服务，避免把解析、事务和上传逻辑堆进现有书籍控制器。验收见 `DEVIN_PHASE1.md`。

## EPUB 导入（第一批：上传、解析与私有预览）

| 部分 | 位置 |
| --- | --- |
| 管理控制器 `/manage.php/import/...` | `site/sys/apps/controllers/admin/Import.php` |
| 集中限制 | `site/sys/apps/config/epub_import.php` |
| 解析器 | `site/sys/apps/libraries/Epub_parser.php` |
| 私有暂存 | `site/sys/apps/libraries/Import_staging.php` → `caches/import/a{管理员ID}/{随机ID}/` |
| 后台页面 | `site/template/admin/import/` |
| 测试 | `tests/test_epub_import.py`、`tests/epub_fixtures.py`（夹具在内存中生成） |

- 每个端点在构造函数中调用 `Admin::operator()`：每次请求重新读取 `mc_admin`，拒绝禁用（`sid != 0`）或已删除账号，并在服务端校验 `qx` 权限；菜单可见性不等于授权。
- 写操作（upload / save / delete / cleanup）只接受 POST，并校验与管理员 ID 和密码哈希绑定的 HMAC 令牌（12 小时有效）；全局 CSRF 仍关闭。
- 暂存只按当前管理员目录和 `meta.json` 中的 owner 查找，其他管理员访问得到 404；24 小时后过期，访问、上传或 cleanup 时删除。暂存不在 `attachment/`，也不写入 `mc_book` 或章节表；读者 API 看不到预览。
- 解析：ZipArchive 只读流式读取，按实际读取字节限制单条目和总量；拒绝路径穿越、绝对路径、符号链接、（大小写折叠后）重复路径、DTD 实体、脚本 / 事件属性 / `javascript:` 链接、外部资源、加密或 DRM（仅允许字体混淆，字体不导入）。图片以内容（魔数 + getimagesize + fileinfo）确认为 JPEG / PNG / GIF / WebP，并在尺寸检查后做有界的结构校验（PNG 校验块 CRC 并以 4 KiB 分片流式解压 IDAT，解压量必须恰好等于声明尺寸；JPEG 校验标记段、帧头、非空扫描数据和 EOI；GIF 校验数据子块和结尾符；WebP 校验 RIFF 分块），之后用系统 libgd 实际解码（PHP 自带的 libgd 在损坏的 WebP 上会触发无法捕获的致命错误，故 Dockerfile 使用 `--with-external-gd`）：JPEG 警告视为失败，动画 WebP 的每个 ANMF 帧单独解码，解码前核对 VP8 / VP8L 码流头中的实际尺寸必须等于 ANMF 帧尺寸（静态图等于画布尺寸），不符即拒绝，libgd 对损坏的 GIF LZW 数据不报错，所以另外逐帧检查 LZW 码流（只计长度，不分配像素）。解码前按像素数估算内存（PNG ×12、其余 ×8 字节）并受 `max_decode_bytes`（128 MiB）限制。截断、损坏或无法解码的图片返回 `invalid_image`；SVG 排除并提示。
- 复杂度预算（`epub_import.php`）：每个 XML / XHTML 文档在任何解析前统计标签数（`max_xml_tags`），每个分节和全书的段落 + 图片数分别受 `max_section_blocks` / `max_book_blocks` 限制，在追加前检查；超出返回 HTTP 422 `too_complex` 并释放暂存配额，不依赖调高 `memory_limit`。
- 分节严格按 OPF spine 顺序，一个 spine 项对应一个分节，导航只用于命名：EPUB 3 nav → NCX → 正文第一个 h1-h6 → “未命名小节 N”。粗粒度目录不会合并或丢弃更细的 spine 项。
- 封面页（landmarks / guide 标记，或首个仅含封面图的页面）和原书目录页默认“不保留”，因为封面图单独作为书籍封面、阅读目录由分节标题生成；管理员可手动勾选保留。纯图片页、前言、附录、非线性页和未命名页均保留。
- 同一管理员的暂存状态变更（配额预留、提交、保存、删除、过期清理）都持有 `a{管理员ID}/.lock` 排他锁；上传中的 `.tmp-*` 计入配额；删除先把目录原子重命名为 `.del-*`，已删除的预览不会以残缺内容出现在列表中。
- 预览保存把分节标题 / 保留状态作为单个 JSON 字段 `sections` 提交，不受 PHP `max_input_vars` 限制。保存只接受 `application/x-www-form-urlencoded`（页面 jQuery 提交的格式），其他类型（含 multipart）在任何修改前返回 415 `content_type`；书名、作者与 `sections` 一样从 urlencoded 原始请求体解析（`CI_Input` 已删掉 `$_POST` 中的非法 UTF-8 字节，`Mc_Input` 会做 HTML 编码）（`Mc_Input` 会把引号、`<`、`>`、`&` 转成 HTML 实体），只做字符串 / UTF-8 / 长度校验，超长或数组输入返回 422；页面输出时再转义。实际分节上限由 ZIP 条目数决定：EPUB 2 最多 `max_entries - 3` = 1997 个分节。
- 全书只有封面页或导航页含内容时（例如单张图片的 EPUB，或唯一 spine 项是带正文的 nav 文档），这些页面默认保留，不报 `no_content`。
- 容器入口脚本：root 只有 CHOWN/SETUID/SETGID，只对卷根目录和已知顶层目录做非递归 chown，其余写操作通过 `setpriv` 以 www-data 执行，因此新卷、带 0700 私有暂存的重启和旧入口脚本创建的缓存都能初始化。没有新增 capability。
- 测试：`tests/test_container_lifecycle.py`（`READING_LIFECYCLE_IT=1`）用自建卷检查入口脚本的生命周期；HTTP 集成测试（`READING_IMPORT_IT=1`）只接受本地、非默认项目名的 Compose 环境，只清理本次运行创建的管理员、分类和暂存目录。
- 数据库导入（第二批）与发布 / 阅读端集成（第三批）尚未实现。
