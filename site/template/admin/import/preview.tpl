<?php $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>EPUB预览</title>
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,user-scalable=yes, minimum-scale=0.4, initial-scale=0.8,target-densitydpi=low-dpi" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="stylesheet" href="<?=Web_Base_Path?>admin/css/style.css">
    <script src="<?=Web_Base_Path?>jquery/jquery.min.js"></script>
    <script src="<?=Web_Base_Path?>layui/layui.js"></script>
    <script src="<?=Web_Base_Path?>admin/js/common.js"></script>
    <style>
        .epub-cols { display: flex; gap: 15px; align-items: flex-start; }
        .epub-cols > .layui-card { flex: 1; min-width: 0; }
        #epub-reader { max-height: 640px; overflow: auto; line-height: 1.8; }
        #epub-reader p { text-indent: 2em; margin: 0 0 8px; white-space: pre-wrap; }
        #epub-reader img { max-width: 100%; display: block; margin: 8px auto; }
        #epub-sections input[type=text] { width: 100%; }
        #epub-sections tr.excluded td { color: #999; }
        .epub-warn { color: #c00; }
    </style>
</head>
<body class="bsbg">
<div class="layui-fluid" id="epub-preview"
     data-status="<?=$h(links('import','status',$id))?>"
     data-section="<?=$h(links('import','section',$id))?>"
     data-save="<?=$h(links('import','save',$id))?>"
     data-delete="<?=$h(links('import','delete'))?>"
     data-index="<?=$h(links('import','index'))?>"
     data-id="<?=$h($id)?>">
    <form class="layui-form" id="epub-form">
        <input type="hidden" name="csrf_token" value="<?=$h($csrf)?>">
        <div class="layui-card">
            <div class="layui-card-header">书籍信息（私有预览，未导入书库，未发布）<span id="epub-expires" class="layui-word-aux"></span></div>
            <div class="layui-card-body">
                <div class="layui-form-item">
                    <label class="layui-form-label">书名</label>
                    <div class="layui-input-block"><input type="text" name="title" maxlength="128" class="layui-input" required></div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">作者</label>
                    <div class="layui-input-block"><input type="text" name="author" maxlength="64" class="layui-input" required></div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">分类</label>
                    <div class="layui-input-block">
                        <select name="cid" lay-ignore class="layui-input">
                            <option value="0">请选择分类</option>
                            <?php foreach ($class as $row) { ?>
                                <option value="<?=intval($row['id'])?>"><?=$h($row['name'])?></option>
                                <?php foreach ($row['children'] as $child) { ?>
                                    <option value="<?=intval($child['id'])?>">&nbsp;&nbsp;└ <?=$h($child['name'])?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">来源信息</label>
                    <div class="layui-input-block layui-word-aux" id="epub-source"></div>
                </div>
                <div class="layui-form-item" id="epub-cover-row" style="display:none">
                    <label class="layui-form-label">封面</label>
                    <div class="layui-input-block"><img id="epub-cover" alt="封面" style="max-height:160px"></div>
                </div>
                <div class="layui-form-item"><div class="layui-input-block" id="epub-warnings"></div></div>
            </div>
        </div>
        <div class="epub-cols">
            <div class="layui-card">
                <div class="layui-card-header">分节（按 OPF spine 顺序）</div>
                <div class="layui-card-body">
                    <table class="layui-table" id="epub-sections">
                        <thead><tr><th>#</th><th>保留</th><th>标题</th><th>类型</th><th>段落/图片</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="layui-card">
                <div class="layui-card-header" id="epub-reader-title">正文预览</div>
                <div class="layui-card-body"><div id="epub-reader">点击左侧“查看”预览正文</div></div>
            </div>
        </div>
        <div class="layui-form-item" style="margin-top:15px">
            <button type="submit" class="layui-btn">保存预览修改</button>
            <button type="button" class="layui-btn layui-btn-danger" id="epub-delete">删除预览</button>
            <a class="layui-btn layui-btn-primary" id="epub-back">返回</a>
            <span id="epub-msg" class="layui-word-aux"></span>
        </div>
    </form>
</div>
<script>
(function () {
    // All EPUB-derived values are inserted with text()/val()/textContent; no untrusted markup is rendered.
    var root = $('#epub-preview');
    var form = $('#epub-form');
    var csrf = form.find('input[name=csrf_token]').val();
    var kinds = { text: '正文', image: '图片页', cover: '封面页', nav: '目录页', empty: '空白页', unsupported: '不支持' };
    var sources = { nav: 'EPUB3 导航', ncx: 'NCX', heading: '正文标题', fallback: '自动编号' };
    $('#epub-back').attr('href', root.data('index'));
    function say(msg) { $('#epub-msg').text(msg); }
    function fail(xhr) { var res = xhr.responseJSON || {}; say(res.msg || ('请求失败（HTTP ' + xhr.status + '）')); }
    function cell(text) { return $('<td>').text(text); }
    function showSection(n, title) {
        $('#epub-reader-title').text('正文预览：' + title);
        var box = $('#epub-reader').empty().text('加载中…');
        $.getJSON(root.data('section') + '/' + n).done(function (res) {
            box.empty();
            if (!res.blocks.length) box.text('（无内容）');
            $.each(res.blocks, function (_, block) {
                if (block.type === 'text') box.append($('<p>').text(block.text));
                else box.append($('<img>').attr({ src: block.url, alt: block.alt || '' }));
            });
        }).fail(fail);
    }
    function render(stage) {
        form.find('[name=title]').val(stage.title);
        form.find('[name=author]').val(stage.author);
        form.find('[name=cid]').val(String(stage.cid));
        $('#epub-expires').text('，过期时间 ' + new Date(stage.expires * 1000).toLocaleString());
        $('#epub-source').text('文件 ' + stage.file + '；EPUB ' + stage.version + '；语言 ' + (stage.language || '未声明') +
            '；原书名 ' + (stage.source_title || '未声明') + '；原作者 ' + (stage.source_author || '未声明') +
            '；字数 ' + stage.text_num + '；图片 ' + stage.images);
        if (stage.cover) { $('#epub-cover').attr('src', stage.cover); $('#epub-cover-row').show(); }
        var warn = $('#epub-warnings').empty();
        $.each(stage.warnings, function (_, w) { warn.append($('<div class="epub-warn">').text(w)); });
        var body = $('#epub-sections tbody').empty();
        $.each(stage.sections, function (i, s) {
            var row = $('<tr>').toggleClass('excluded', !s.include);
            var check = $('<input type="checkbox" class="epub-include" lay-ignore>').prop('checked', s.include);
            check.on('change', function () { row.toggleClass('excluded', !this.checked); });
            var title = $('<input type="text" maxlength="128" class="layui-input epub-title">').val(s.title)
                .attr('title', '自动标题：' + s.auto_title + '（' + (sources[s.title_source] || s.title_source) + '）');
            var view = $('<a class="layui-btn layui-btn-xs" href="javascript:;">查看</a>').on('click', function () { showSection(s.n, title.val()); });
            row.append(cell(i + 1), $('<td>').append(check), $('<td>').append(title),
                $('<td>').text(kinds[s.kind] || s.kind).attr('title', s.note + '；' + s.source),
                cell(s.paragraphs + ' / ' + s.images), $('<td>').append(view));
            body.append(row);
        });
    }
    $.getJSON(root.data('status')).done(function (res) { render(res.stage); }).fail(fail);
    form.on('submit', function (e) {
        e.preventDefault();
        // Section edits travel as one JSON field so long books stay within PHP max_input_vars.
        var sections = $('#epub-sections tbody tr').map(function () {
            return { title: $(this).find('.epub-title').val(), include: $(this).find('.epub-include').prop('checked') };
        }).get();
        var data = { csrf_token: csrf, title: form.find('[name=title]').val(), author: form.find('[name=author]').val(),
            cid: form.find('[name=cid]').val(), sections: JSON.stringify(sections) };
        $.ajax({ url: root.data('save'), type: 'POST', data: data, dataType: 'json' })
            .done(function (res) { say(res.msg); }).fail(fail);
    });
    $('#epub-delete').on('click', function () {
        if (!window.confirm('确定删除该预览？')) return;
        $.ajax({ url: root.data('delete'), type: 'POST', data: { id: root.data('id'), csrf_token: csrf }, dataType: 'json' })
            .done(function () { window.location.href = root.data('index'); }).fail(fail);
    });
})();
</script>
</body>
</html>
