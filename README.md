# NotePadOnline

## NPO是什么?
NotePadOnline(以下简称为"NPO")是一款类石墨文档的办公协同跨终端文档同步程序，支持文档设密、自定义分享开关、分享设密、导出为Word等功能。NPO是不依赖数据库的轻量程序，且在迭代更新中，兼具安全性和实用性。

## NPO的未来计划？
### 1.NPO正准备自"笔记同步"升级到"办公协同"
版权、用户、企业等相关功能将在后续完善
### 2.NPO的更新计划中有
[1]办公协同相关
[2]短网址支持
[3]注销过期笔记
[4]更多主题样式
注意：处于更新计划中仍有可能会因各种原因在未来被砍掉

## 为什么有NPO？
NPO最早的灵感是基于EverTools基金会的NotePad_Live。之所以延续NotePad_Live是因为作者喜爱并享受NotePad.live的便利，然而不久前宣称永不关闭的NotePad.live已经无法打开，于是便有了延续并重构NotePad_Live的想法与计划。
目前正打算升级到“办公协同”是受到石墨文档的启发

## 作者是谁？
欢迎关注我的个人博客(http://94joy.cn)

## Docker 部署

镜像多架构（linux/amd64、linux/arm64），标签有 `latest`、版本号、日期三种。

使用 `docker compose`（仓库自带 `docker-compose.yml`）：

```bash
# 仓库自带 .env.example，复制后填入分享链签名密钥
cp .env.example .env
sed -i "s/^NPO_SHARE_SECRET=.*/NPO_SHARE_SECRET=$(openssl rand -hex 32)/" .env
docker compose up -d
```

`.env.example` 里只有两个变量：`NPO_SHARE_SECRET`（必填）和 `APP_VERSION`（可选，默认 `dev`）。
Windows 下没有 `sed`，直接把 `openssl rand -hex 32` 的输出手工填进 `.env` 即可。

镜像不存在时 `up -d` 会自动构建；**改了源码后必须加 `--build` 强制重建**，否则仍跑旧镜像：

```bash
docker compose up -d --build
```

或直接用镜像运行：

```bash
docker run -d --name notepadonline -p 8080:80 \
  -e NPO_SHARE_SECRET="$(openssl rand -hex 32)" \
  -v npo-data:/var/www/html/_tmp \
  ghcr.io/ldxw/notepadonline:latest
```

- 映射端口 `8080:80`，浏览器访问 `http://<主机>:8080/`
- 笔记数据在容器内 `/var/www/html/_tmp`，务必挂载卷；备份就是备份这个卷

笔记数据有两种挂载方式，二选一。

**命名卷（默认，推荐）**：`docker-compose.yml` 里已配好 `npo-data:/var/www/html/_tmp`。
数据由 Docker 管理，`docker compose down` 不加 `-v` 就不会丢，容器重建后依旧在。
想备份或迁移，把卷打包出来即可：

```bash
docker run --rm -v npo-data:/data -v "$PWD":/backup alpine \
  tar czf /backup/npo-data.tar.gz -C /data .
```

**绑定宿主机目录**：想直接在宿主机上看到笔记文件时，把 `docker-compose.yml` 里的那行换成注释里的

```yaml
volumes:
  - ./data:/var/www/html/_tmp
```

然后先建目录并交出写权限——容器内 PHP 以 `www-data`（uid 33）运行：

```bash
mkdir -p data && sudo chown -R 33:33 data
docker compose up -d
```

容器启动时会以 root 身份再 `chown -R` 一次 `/var/www/html/_tmp`，所以**新建的空目录**通常不用手工处理。
但下面这两种情况仍需要它失败时的提示来排查：

- 宿主目录里的旧文件属主不对，且挂载参数不允许改（如只读挂载），启动日志会打印警告。
- 已经跑起来的容器里改不到属主，表现为**输入文字后页面提示“保存失败…失败原因:error”**。

遇到保存失败，按顺序确认：

```bash
ls -ln data/                                          # 属主必须是 33 33
docker compose logs --tail=20 notepadonline           # 看有没有 [NotePadOnline] 警告
docker compose exec notepadonline tail -20 /var/www/html/_tmp/error.log
```

`error.log` 里是 `Permission denied` 就是权限问题，`sudo chown -R 33:33 data`
后重启容器即可。

三点注意：

- 权限不对时写入会失败，表现为笔记存不上、`_tmp/error.log` 里是 `Permission denied`。
- **从命名卷切换到绑定目录后，原来卷里的笔记不会自动出现**——它们是两份独立的数据。
  需要保留旧数据就先导出来：
  `docker run --rm -v npo-data:/data -v "$PWD/data":/backup alpine sh -c 'cp -a /data/. /backup/ && chown -R 33:33 /backup'`
- 绑定挂载会**遮盖镜像内** `_tmp/.htaccess`（那层直接访问 deny 就没了）。
  `docker/apache.conf` 的 `<LocationMatch "^/_tmp/">` 仍在 Apache 层拦截 `/_tmp/` 的 HTTP 访问，
  数据不会暴露在 Web 上，只是少了这层纵深防御。要保留就把它复制过去：

  ```bash
  sudo cp _tmp/.htaccess data/
  ```

### 必须配置的环境变量

| 变量 | 作用 |
| --- | --- |
| `NPO_SHARE_SECRET` | 分享链的 HMAC 签名密钥，任意足够长的随机串 |

未设置时程序不会报错，而是静默降级：分享链接为空、`/share/*` 一律 302 回首页。
更换密钥会让此前生成的所有分享链立即失效。

### 可选的外观变量

这几个都在 `.env` 里设置，留空即用程序内默认值，不设也不影响运行。

| 变量 | 默认值 | 作用 |
| --- | --- | --- |
| `NPO_SITE_NAME` | `NPO` | 站点名，显示在浏览器标题与导航栏品牌位；留空或不设都用默认值 |
| `NPO_BROADCAST_TITLE` | `广播DEMO` | 顶部广播条的加粗标题 |
| `NPO_BROADCAST_TEXT` | `这里是广播DEMO内容` | 顶部广播条的正文 |
| `NPO_FAVICON` | 空 | 浏览器标签页图标地址，站内相对路径或绝对 URL 均可；留空则不输出图标链接 |
| `NPO_THEME` | 空 | 配色主题名，取 `content/themes/` 下的文件名去掉 `.css`；内置 `dark`、`sepia`，留空或填不存在的名字都回退默认浅色 |
| `NPO_FOOTER_TEXT` | 空 | 页脚左侧的自定义文字；右侧固定显示「© 年份 站点名」，留空则只有版权 |

想用默认广播就**不写**这两行；想关掉广播，把变量显式设为空值：

```ini
NPO_BROADCAST_TITLE=
NPO_BROADCAST_TEXT=
```

`docker-compose.yml` 里对这两个变量用的是 `${VAR-默认值}` 而不是 `${VAR:-默认值}`，
所以「完全不写」和「写了空值」含义不同——前者取默认，后者是你主动置空。
只填其中一个时只显示那一部分，另一个不占位。
广播条本身在窄屏（<992px）下是隐藏的，这是原有布局。

`NPO_FAVICON` 填站内路径（如 `/assets/image/favicon.ico`）或完整 URL 都行。
图标文件放进 `assets/image/` 就能被直接访问，不用改 Web 配置。
只接受 `http(s)`、`data:image/` 和站内路径，其他协议会被忽略。
不设这一项时页面不输出图标链接，浏览器仍会自行请求 `/favicon.ico`（仓库没带这个文件，
所以会落 404 回到首页），这是本来的行为。

**换主题**：主题是 `content/themes/<名字>.css` 这样一份样式覆盖文件，在基础样式之后加载，
只改配色不改布局。仓库自带 `dark`（暗色）和 `sepia`（米黄护眼）两套：

```ini
NPO_THEME=dark
```

主题名只允许字母、数字、下划线和连字符（最长 32 个字符），并且必须存在对应的 `.css` 文件，
否则**静默回退默认浅色**——填错不会报错，也不会影响页面打开。
想自己做一套，复制 `content/themes/dark.css` 改颜色即可，文件名（不含 `.css`）就是变量取值。
主题是页面级样式覆盖，分享页与编辑页共用同一套。

`/content/themes/` 会随镜像一起打包（Dockerfile 里 `COPY .`），
自定义主题放进这个目录后重新构建镜像就能用；用绑定挂载部署时无需重建。

**页脚**：编辑页与分享页底部都有一条 2rem 高的页脚，右侧固定显示 `© 年份 站点名`
（年份按访问时的服务器时间取），左侧可放自定义文字：

```ini
NPO_FOOTER_TEXT=由 94Joy 提供
```

这个变量和其它外观变量一样是「设置即覆盖」：不写就只有版权，
写了空值（`NPO_FOOTER_TEXT=`）也是只有版权。窄屏（<992px）下页脚仍然显示，
只是字号从 13px 缩到 12px；进入全屏编辑时页脚会隐藏，退出后恢复。
文字里可以带空格和中文，HTML 特殊字符会被转义，不会当成标签渲染。

## 编辑器能力

编辑页工具栏提供标题、粗体/斜体/下划线/删除线、文字与背景色、链接、列表、对齐、
**引用**、**表情**、**插入图片**、表格、**代码块**、撤销/重做。

- **引用**：点工具栏的引用按钮，当前段落变成 `<blockquote>`。
- **代码块**：点代码按钮弹出输入面板，粘贴内容后点插入，生成 `<pre><code>`。
  行内代码用 Markdown 习惯写作 `` `code` `` 不会自动转换，需手动加格式。
- **表情**：表情面板内置 60 多个 Unicode 表情，插入的是文本而非图片，可以正常复制与导出。
- **图片**：以 base64 直接嵌进正文，没有上传后端。单张上限 **200KB**，
  一次只能插一张；超过上限会弹出「插入失败」提示，不会被静默丢弃。
  服务端会再校验一次，非 `data:image/` 的 `src` 一律清除。
- **全屏编辑**：编辑页右上角有全屏按钮，按钮只在宽度 ≥992px 时显示，
  窄屏不显示。按 `Esc` 或再点一次按钮退出；分享页没有这个按钮。
- **视频**：工具栏**没有**视频按钮，服务端也会剥离 `<video>`/`<iframe>`，粘贴带视频的内容会丢。

体积上限仍是单篇 **512KB 原始内容**（base64 编码后 4MB），插入大图前留意这个总数。
引用、代码块、表情、图片都会随笔记一起保存，重载后原样恢复，也会随主题切换配色。

### 自行构建镜像（GitHub Actions）

`.github/workflows/docker.yml` 构建 `linux/amd64` + `linux/arm64`，
推送 `latest`、版本号、日期三种标签到 GHCR。

两种触发方式：

- **推送标签即构建**：标签以数字开头（如 `3.22`）会自动触发，版本号取标签名。
  标签必须指向带 `Dockerfile` 的提交，否则构建会在检查步骤失败。
- **手动触发**：在 Actions 页面运行，可填版本号（留空则读仓库根目录 `VERSION`）。

```bash
git tag 3.22 && git push ssh://git@github.com/ldxw/NotepadOnline.git 3.22
```

同时推阿里云 ACR 由输入项 `push_aliyun` 控制，**默认关闭**，只推 GHCR 时什么都不用配。
这个输入项只对手动触发有效，标签触发的构建只推 GHCR。

需要推阿里云时，先勾上 `push_aliyun`，并在仓库 Settings → Secrets and variables → Actions
里配置这四个 secret，缺任何一个都会让构建失败：

| Secret | 含义 |
| --- | --- |
| `ALIYUN_REGISTRY` | 仓库地址，如 `registry.cn-hangzhou.aliyuncs.com` |
| `ALIYUN_NAMESPACE` | 命名空间 |
| `ALIYUN_USERNAME` | 阿里云账号 |
| `ALIYUN_PASSWORD` | 仓库密码 |

阿里云 ACR 不认 OCI 1.1 的制品清单，构建时带 provenance 或 SBOM 会推送失败：

```text
denied: unknown manifest class for application/vnd.oci.empty.v1+json
```

buildx 默认会附一份 min 级 provenance，所以 workflow 里显式写了 `provenance: false`
和 `sbom: false`。代价是 GHCR 上也没有这两份签名材料了，换来的是两个仓库拿到同一个
manifest digest，便于核对是不是同一份镜像。要恢复的话，得把推送拆成两步、只对阿里云那步关闭。

### 使用前请了解

- NPO 没有账号体系，**笔记名就是访问凭据**。知道文档名的人即可查看和修改未设密码的笔记。
  请勿在其中存放敏感内容；担心名字被人撞上，请先在「设定 → 密码」里给笔记设编辑密码。
- 文档体积上限为 512KB 原始内容（编码后 4MB），超出会返回 `tooLarge`。

## 演示(Demo)与下载(Download)
演示:NPO(http://n.94joy.cn)
下载1:Github(https://github.com/2613df/NotepadOnline/releases)  

## 快拿银子来砸我吧！
用爱发电是持不久的，作者没饭吃NPO项目也难以为继。因此NPO项目需要各位大大的帮助，如果您有能力赞助NPO项目，欢迎扫码拿钱来砸我！1元也可！  
![推荐使用微信赞助](https://github.com/2613df/NotepadOnline/raw/master/assets/image/wechatpay.png)  
非常感谢您了！！！！！  
如果您是学生且愿意支持这个项目的话，就请将这个项目或是n.94joy.cn这个网址推荐给身边的同学吧，如果能点个星星就更好了，万分感谢您的支持。  
如果你对项目或是网站发展有建议的话，请在Github上发表您的想法，我会第一时间处理！