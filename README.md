# Reading App

基于 PHP 阅读后台与 uni-app / Vue 2 前端整理的开发仓库。当前可运行范围为书库列表、书籍详情、章节目录、文字和插图阅读，以及后台书籍与章节管理。

仓库只保存代码、数据库结构和启动工具。真实凭据、电子书、正文缓存、数据库数据、备份和前端构建产物均不属于版本管理内容。

## 当前状态

- H5 通过后台 API 获取书籍、目录和正文，支持上下节、字号和本机阅读进度。
- Docker Compose 隔离数据库、PHP 服务与网关；默认仅监听本机 `127.0.0.1:18087`。
- 新环境从空书库启动，不带入既有部署的用户、图书或管理员凭据。
- 通用 EPUB 上传、预览、确认导入和上架流程尚待实现，范围见 [第一阶段开发说明](docs/DEVIN_PHASE1.md)。

当前交付为 H5 手机网页和服务端代码，不包含已签名的 Android / iOS 安装包。会员、支付、短信、广告及采集不在当前验证范围内。

## 技术栈与前置条件

| 层级 | 组成 |
| --- | --- |
| 前端 | uni-app、Vue 2.6、uView 2、Webpack / Vue CLI |
| 后台 | PHP 8.1、CodeIgniter 3 / Mccms 业务代码 |
| 数据库 | MySQL 5.7，沿用现有章节分表 |
| 服务 | Apache、Nginx、Docker Compose |
| 本地工具 | Python 3.9+、Git、Node.js 与 npm |

需要 Docker Engine 与 `docker compose`。前端建议使用 Node.js 22，并按锁文件安装；旧依赖可能输出版本或弃用警告。宿主机无需另装 PHP 或 MySQL。

PHP 8.1、MySQL 5.7 和 Vue 2 已属旧版本技术栈。本仓库是可复现的开发基线，不代表已完成全量安全审计或适合直接公开收费运营。依赖升级应单独评估与验证。

## 快速启动

在仓库根目录执行：

```bash
python3 scripts/bootstrap.py
python3 scripts/build_h5.py --install
docker compose up -d --build
```

初始化脚本生成被 Git 忽略的 `.env`、运行占位目录和首次数据库初始化所需的本地文件。构建脚本按锁文件安装前端依赖、编译 H5，并同步至 `site/app/`。

默认入口：

- 阅读页面：`http://localhost:18087/app/`
- 后台登录：`http://localhost:18087/manage.php/login`

管理员账号、密码和认证码以本地 `.env` 的 `ADMIN_USERNAME`、`ADMIN_PASSWORD`、`ADMIN_CODE` 为准。不要把该文件粘贴到 issue、PR、日志或聊天中。

首次创建数据库卷时才执行 `db-init/` 的初始化 SQL。已有卷不会因重启而重新初始化；修改 `.env` 也不会自动修改已有数据库账号或管理员密码。不要通过删除数据卷处理普通配置更新。

## 配置边界

配置字段与非敏感示例见 [`.env.example`](.env.example)。

| 字段 | 用途 |
| --- | --- |
| `APP_URL`、`APP_NAME` | 外部访问地址和站点名称 |
| `MYSQL_PASSWORD`、`MYSQL_ROOT_PASSWORD` | 数据库凭据 |
| `APP_ENCRYPTION_KEY` | 服务端加密 / 会话配置 |
| `APP_BOOK_KEY` | 正文文件命名使用的服务端密钥 |
| `APP_API_KEY` | 兼容现有 App 请求签名协议的客户端标识 |
| `ADMIN_USERNAME`、`ADMIN_PASSWORD`、`ADMIN_CODE` | 新环境首次管理员初始化 |

`APP_API_KEY` 会提供给客户端，不能作为用户鉴权、会员授权或管理权限的安全边界。管理员密码、数据库密码、加密密钥和正文密钥不得进入前端配置。

H5 使用同源接口，配置来自 `/app/runtime-config.js`。初始化生成的 `uniapp/public/runtime-config.js` 和原生端 `runtime-config.json` 均不提交。构建产物也不提交；切换环境时使用目标环境配置，不能复制生产 `.env` 到仓库。

正文文件名依赖章节 ID 与 `APP_BOOK_KEY`。迁移时须保留匹配的数据库、正文与密钥；不能只改密钥后继续使用旧文件。

## 验证

提交前执行：

```bash
python3 -m unittest discover -s tests
python3 scripts/check_repository.py
```

服务启动后执行：

```bash
docker compose ps
python3 scripts/smoke.py --base-url http://localhost:18087
```

冒烟检查覆盖服务入口、书库接口、后台登录与敏感路径封堵。空书库是新环境的正常状态；内容测试只用自己生成或明确允许使用的小型测试书。

前端改动后运行 `python3 scripts/build_h5.py`。底层 `npm run build:h5` 只生成 `uniapp/dist/build/h5/`，不会独立完成服务端目录同步。

可选的 GitHub Actions 配置见 [CI 模板](docs/ci/verify.yml)，复制到 `.github/workflows/verify.yml` 后启用；推送工作流需要 GitHub 的 `workflow` 权限。模板检查源码、初始化、锁文件构建和 Compose 配置。

## 目录与协作

```text
site/          PHP 后台、API、模板与通用静态资源
uniapp/        前端源码和依赖锁文件
db-init/       数据库结构与被忽略的本地初始化文件
scripts/       初始化、构建和验证工具
tests/         回归测试与合成样本
docs/          目录说明和开发交接要求
```

详细职责见 [项目目录说明](docs/PROJECT_STRUCTURE.md)。后续按“任务与验收 → Devin 独立工作分支 → 补丁交付 → 本地审计和复测 → 维护者推送”协作。Devin 通过匿名克隆获取公开源码，不获得维护者 GitHub 凭据或生产环境权限。第一阶段要求见 [Devin 开发说明](docs/DEVIN_PHASE1.md)。

本轮开发期间仓库临时公开。第一阶段 A01–A14 全部验收并推送后，由维护者转回私有；这不会撤回公开期间已被保留的副本，也不改变既有第三方许可证。

## 部署与数据保护

默认监听地址仅供本机开发。公开发布应配置独立域名、HTTPS 和反向代理，并限制管理入口；不要覆盖服务器上已有站点或容器。

默认 Compose 项目名为 `reading-app-dev`，测试多个实例时使用独立的 `docker compose -p <项目名>` 和端口。代码目录只读挂载。真实运行数据使用独立 Docker 命名卷：`reading-db` 保存数据库，`reading-cache` 保存正文和运行缓存，`reading-uploads` 保存封面及插图。Compose 通常会在卷名前加项目名前缀；以 `docker volume ls` / `docker compose config --volumes` 的实际结果为准。

备份和恢复须同时处理数据库、正文、上传资源及匹配的服务端密钥。初始化创建的宿主占位目录不等同于运行卷中的数据。普通更新不需要、也不应执行 `docker compose down -v`。

`.gitignore` 与提交检查共同防止误提交，但不替代人工审查。`.env`、`secrets.json`、`deployment.json`、私钥、数据库导出、正文和上传文件均留在版本管理外。

## 上游与使用范围

代码包含既有 CMS、前端组件和第三方 SDK。保留原有许可证和版权声明，例如 `site/LICENSE` 及各组件目录的声明；这些文件不自动构成对整个组合项目的统一授权。公开分发或商业使用前应核实上游代码、素材和内容的授权范围。
