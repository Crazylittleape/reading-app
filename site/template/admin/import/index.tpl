<?php $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EPUB导入</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="<?=Web_Base_Path?>admin/css/style.css">
    <script src="<?=Web_Base_Path?>jquery/jquery.min.js"></script>
    <script src="<?=Web_Base_Path?>layui/layui.js"></script>
    <script src="<?=Web_Base_Path?>admin/js/common.js"></script>
</head>
<body class="bsbg">
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-card-header">上传 EPUB（仅生成私有预览，不会写入书库或公开目录）</div>
        <div class="layui-card-body">
            <form class="layui-form" id="epub-upload" action="<?=$h(links('import','upload'))?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?=$h($csrf)?>">
                <div class="layui-form-item">
                    <label class="layui-form-label">EPUB 文件</label>
                    <div class="layui-input-block">
                        <input type="file" name="epub" id="epub-file" accept=".epub,application/epub+zip" required>
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block layui-word-aux">
                        支持无 DRM 的 EPUB 2 / EPUB 3 文字书及书内 JPEG/PNG/GIF/WebP 图片。文件不超过 <?=round($limits['max_upload_bytes']/1048576)?> MiB，
                        最多 <?=intval($limits['max_entries'])?> 个压缩条目，解压总量不超过 <?=round($limits['max_total_bytes']/1048576)?> MiB，单个条目不超过 <?=round($limits['max_entry_bytes']/1048576)?> MiB。
                        预览保留 <?=round($limits['staging_ttl']/3600)?> 小时。
                    </div>
                </div>
                <div class="layui-form-item">
                    <div class="layui-input-block">
                        <button type="submit" class="layui-btn" id="epub-submit">上传并解析</button>
                        <span id="epub-status" class="layui-word-aux"></span>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="layui-card">
        <div class="layui-card-header">我的预览
            <button type="button" class="layui-btn layui-btn-xs layui-btn-primary" id="epub-cleanup" data-url="<?=$h(links('import','cleanup'))?>">清理过期预览</button>
        </div>
        <div class="layui-card-body">
            <table class="layui-table">
                <thead><tr><th>书名</th><th>文件</th><th>分节</th><th>创建时间</th><th>过期时间</th><th>操作</th></tr></thead>
                <tbody>
                <?php if (empty($stages)) { ?>
                    <tr><td colspan="6">暂无预览</td></tr>
                <?php } foreach ($stages as $row) { ?>
                    <tr>
                        <td><?=$h($row['title'])?></td>
                        <td><?=$h($row['file'])?></td>
                        <td><?=intval($row['sections'])?></td>
                        <td><?=date('Y-m-d H:i', $row['created'])?></td>
                        <td><?=date('Y-m-d H:i', $row['expires'])?></td>
                        <td>
                            <a class="layui-btn layui-btn-xs" href="<?=$h(links('import','preview',$row['id']))?>">预览/编辑</a>
                            <button type="button" class="layui-btn layui-btn-xs layui-btn-danger epub-delete" data-id="<?=$h($row['id'])?>">删除</button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
(function () {
    var csrf = $('#epub-upload input[name=csrf_token]').val();
    var deleteUrl = <?=json_encode(links('import','delete'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
    var maxBytes = <?=intval($limits['max_upload_bytes'])?>;
    function say(msg) { $('#epub-status').text(msg); }
    function fail(xhr) {
        var res = xhr.responseJSON || {};
        say(res.msg || ('请求失败（HTTP ' + xhr.status + '）'));
    }
    $('#epub-upload').on('submit', function (e) {
        e.preventDefault();
        var input = document.getElementById('epub-file');
        if (!input.files || !input.files.length) return say('请选择 EPUB 文件');
        if (input.files[0].size > maxBytes) return say('文件超过 10 MiB 限制');
        var form = new FormData(this);
        $('#epub-submit').prop('disabled', true);
        say('正在上传并解析…');
        $.ajax({ url: this.action, type: 'POST', data: form, processData: false, contentType: false, dataType: 'json',
            headers: { Accept: 'application/json' } })
            .done(function (res) { if (res.code == 1) { window.location.href = res.url; } else { say(res.msg); } })
            .fail(fail)
            .always(function () { $('#epub-submit').prop('disabled', false); });
    });
    $('.epub-delete').on('click', function () {
        if (!window.confirm('确定删除该预览？')) return;
        $.ajax({ url: deleteUrl, type: 'POST', data: { id: $(this).data('id'), csrf_token: csrf }, dataType: 'json' })
            .done(function () { window.location.reload(); }).fail(fail);
    });
    $('#epub-cleanup').on('click', function () {
        $.ajax({ url: $(this).data('url'), type: 'POST', data: { csrf_token: csrf }, dataType: 'json' })
            .done(function (res) { say(res.msg); window.setTimeout(function () { window.location.reload(); }, 800); }).fail(fail);
    });
})();
</script>
</body>
</html>
