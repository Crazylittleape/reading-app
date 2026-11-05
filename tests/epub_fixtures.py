"""Generate tiny synthetic EPUB archives in memory for the import
tests.

No EPUB, image or archive file is committed: every archive is produced at test
time (the only embedded payload is a synthetic 2x2 JPEG) so repository hygiene
checks keep rejecting real book content.
"""
import base64
import io
import struct
import zipfile
import zlib

CONTAINER = ('<?xml version="1.0" encoding="UTF-8"?>\n'
             '<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'
             '<rootfiles><rootfile full-path="{opf}" media-type="application/oebps-package+xml"/></rootfiles>'
             '</container>')


def png_chunk(kind, data):
    return struct.pack('>I', len(data)) + kind + data + struct.pack('>I', zlib.crc32(kind + data) & 0xffffffff)


def png_with(width, height, idat):
    """An 8-bit RGB PNG with correct chunk CRCs around an arbitrary IDAT payload."""
    return (b'\x89PNG\r\n\x1a\n' + png_chunk(b'IHDR', struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0))
            + png_chunk(b'IDAT', idat) + png_chunk(b'IEND', b''))


def png(width=2, height=2, rgb=(200, 40, 40)):
    raw = b''.join(b'\x00' + bytes(rgb) * width for _ in range(height))
    return png_with(width, height, zlib.compress(raw))


# 2x2 baseline JPEG generated once with Pillow and embedded so the tests need no imaging library.
JPEG_2X2 = base64.b64decode(
    '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDABALDA4MChAODQ4SERATGCgaGBYWGDEjJR0oOjM9PDkzODdASFxOQERXRTc4UG1RV19iZ2hnPk1xeXBkeFxlZ2P/'
    '2wBDARESEhgVGC8aGi9jQjhCY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2NjY2P/wAARCAACAAIDASIAAhEBAxEB/8QA'
    'HwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkK'
    'FhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXG'
    'x8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAEC'
    'AxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOE'
    'hYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDLoooriPpj/9k=')


def jpeg():
    return JPEG_2X2


# Maintainer audit probes (synthetic): valid outer structure but undecodable pixel data, plus a valid
# two-frame animated WebP. ANIM_GIF / ALPHA_ANIM_WEBP were generated once with Pillow.
AUDIT_PROBES = {
    'bad-filter.png': base64.b64decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAIAAAD91JpzAAAAD0lEQVR4nPsvAAYMCmAAABSQAiB1kSqVAAAAAElFTkSuQmCC'),
    'missing-tables.jpg': base64.b64decode(
    '/9j/4AAQSkZJRgABAQAAAQABAAD/wAARCAACAAIDASIAAhEBAxEB/9oADAMBAAIRAxEAPwDLoooriPpj/9k='),
    'bad-lzw.gif': base64.b64decode(
    'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIC//8AOw=='),
    'header-only.webp': base64.b64decode(
    'UklGRhIAAABXRUJQVlA4TAUAAAAvAUAAAAA='),
    'valid-animated.webp': base64.b64decode(
    'UklGRsQAAABXRUJQVlA4WAoAAAACAAAAAQAAAQAAQU5JTQYAAAAAAAAAAABBTk1GSgAAAAAAAAAAAAEAAAEAAGQAAAJWUDggMgAAADABAJ0BKg'
    'IAAgABQCYloAADcAD+8ut///mwP/bz/wR6Af//0uD//pcH//S4P/SkAAAAQU5NRkYAAAAAAAAAAAABAAABAABkAAAAVlA4IC4AAAA0AQCdASoC'
    'AAIAAAAmJaAAA3AA/vtV4///S4P/+lwf/9Lg/9Lg//rV5Vesq6AA'),
}
ANIM_GIF = base64.b64decode(
    'R0lGODdhAwAHAIcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADMAAGYAAJkAAMwAAP8AAAAzADMzAGYzAJkzAMwzAP8zAABmAD'
    'NmAGZmAJlmAMxmAP9mAACZADOZAGaZAJmZAMyZAP+ZAADMADPMAGbMAJnMAMzMAP/MAAD/ADP/AGb/AJn/AMz/AP//AAAAMzMAM2YAM5kAM8wA'
    'M/8AMwAzMzMzM2YzM5kzM8wzM/8zMwBmMzNmM2ZmM5lmM8xmM/9mMwCZMzOZM2aZM5mZM8yZM/+ZMwDMMzPMM2bMM5nMM8zMM//MMwD/MzP/M2'
    'b/M5n/M8z/M///MwAAZjMAZmYAZpkAZswAZv8AZgAzZjMzZmYzZpkzZswzZv8zZgBmZjNmZmZmZplmZsxmZv9mZgCZZjOZZmaZZpmZZsyZZv+Z'
    'ZgDMZjPMZmbMZpnMZszMZv/MZgD/ZjP/Zmb/Zpn/Zsz/Zv//ZgAAmTMAmWYAmZkAmcwAmf8AmQAzmTMzmWYzmZkzmcwzmf8zmQBmmTNmmWZmmZ'
    'lmmcxmmf9mmQCZmTOZmWaZmZmZmcyZmf+ZmQDMmTPMmWbMmZnMmczMmf/MmQD/mTP/mWb/mZn/mcz/mf//mQAAzDMAzGYAzJkAzMwAzP8AzAAz'
    'zDMzzGYzzJkzzMwzzP8zzABmzDNmzGZmzJlmzMxmzP9mzACZzDOZzGaZzJmZzMyZzP+ZzADMzDPMzGbMzJnMzMzMzP/MzAD/zDP/zGb/zJn/zM'
    'z/zP//zAAA/zMA/2YA/5kA/8wA//8A/wAz/zMz/2Yz/5kz/8wz//8z/wBm/zNm/2Zm/5lm/8xm//9m/wCZ/zOZ/2aZ/5mZ/8yZ//+Z/wDM/zPM'
    '/2bM/5nM/8zM///M/wD//zP//2b//5n//8z//////wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACwAAAAAAwAHAAAIGgAJ8fqQqFkEUieUiREiCJaPMCV0jAqnxkRAACwAAAAA'
    'AwAHAIcAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAzAABmAACZAADMAAD/AAAAMwAzMwBmMwCZMwDMMwD/MwAAZgAzZgBmZgCZZg'
    'DMZgD/ZgAAmQAzmQBmmQCZmQDMmQD/mQAAzAAzzABmzACZzADMzAD/zAAA/wAz/wBm/wCZ/wDM/wD//wAAADMzADNmADOZADPMADP/ADMAMzMz'
    'MzNmMzOZMzPMMzP/MzMAZjMzZjNmZjOZZjPMZjP/ZjMAmTMzmTNmmTOZmTPMmTP/mTMAzDMzzDNmzDOZzDPMzDP/zDMA/zMz/zNm/zOZ/zPM/z'
    'P//zMAAGYzAGZmAGaZAGbMAGb/AGYAM2YzM2ZmM2aZM2bMM2b/M2YAZmYzZmZmZmaZZmbMZmb/ZmYAmWYzmWZmmWaZmWbMmWb/mWYAzGYzzGZm'
    'zGaZzGbMzGb/zGYA/2Yz/2Zm/2aZ/2bM/2b//2YAAJkzAJlmAJmZAJnMAJn/AJkAM5kzM5lmM5mZM5nMM5n/M5kAZpkzZplmZpmZZpnMZpn/Zp'
    'kAmZkzmZlmmZmZmZnMmZn/mZkAzJkzzJlmzJmZzJnMzJn/zJkA/5kz/5lm/5mZ/5nM/5n//5kAAMwzAMxmAMyZAMzMAMz/AMwAM8wzM8xmM8yZ'
    'M8zMM8z/M8wAZswzZsxmZsyZZszMZsz/ZswAmcwzmcxmmcyZmczMmcz/mcwAzMwzzMxmzMyZzMzMzMz/zMwA/8wz/8xm/8yZ/8zM/8z//8wAAP'
    '8zAP9mAP+ZAP/MAP//AP8AM/8zM/9mM/+ZM//MM///M/8AZv8zZv9mZv+ZZv/MZv//Zv8Amf8zmf9mmf+Zmf/Mmf//mf8AzP8zzP9mzP+ZzP/M'
    'zP//zP8A//8z//9m//+Z///M//////8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
    'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIGgB5+GjDiBMYM952zHE1wsqKUV/gNANUqFNAADs=')
ALPHA_ANIM_WEBP = base64.b64decode(
    'UklGRmwBAABXRUJQVlA4WAoAAAASAAAAAwAAAgAAQU5JTQYAAAAAAAAAAABBTk1GkAAAAAAAAAAAAAMAAAIAAGQAAAJBTFBIDQAAAAASB8yLbL'
    'Nzx5rrMycAVlA4IGIAAABQAQCdASoEAAMAAsBMJbAABYwAAP7ho+hNsx/6j/Sn/EeSIf/2V0wmU4p4SuIL0oVxBelCuIL03/7LP/cd/uO9I3/3'
    'Eop3lDdMc//k5JMtYOzP/nSuKwrfa8ffEAnLYkcAAEFOTUaoAAAAAAAAAAAAAwAAAgAAZAAAAkFMUEgNAAAAANxxjx3Fu3Z0GGEbtABWUDggeg'
    'AAAFABAJ0BKgQAAwACwEwlsAAFjAAA/uCTwvT//qEDM+PE/3oMcxq/tae/xO+PhyfGbvP/6oBf+8iz/+qAX/vIs//qgF/7ycg/2AfgLAl/YB+C'
    'L4f2Afgjv//d/x8faX9iT/wPNy/H2lqj75FXrPzzuEAGHFByjstg0gAA')


def riff_chunks(data):
    pos, chunks = 12, []
    while pos + 8 <= len(data):
        size = int.from_bytes(data[pos + 4:pos + 8], 'little')
        chunks.append((bytes(data[pos:pos + 4]), pos, size))
        pos += 8 + size + (size & 1)
    return chunks


def webp_misdeclared(canvas=True, frames=True):
    """valid-animated.webp with the VP8X canvas and/or every ANMF frame declared 1x1 over the real 2x2 bitstreams."""
    data = bytearray(AUDIT_PROBES['valid-animated.webp'])
    for kind, pos, _ in riff_chunks(data):
        if kind == b'VP8X' and canvas:
            data[pos + 12:pos + 18] = b'\0' * 6
        if kind == b'ANMF' and frames:
            data[pos + 14:pos + 20] = b'\0' * 6
    return bytes(data)


def webp_still(canvas=None):
    """The first 2x2 VP8 frame of valid-animated.webp as a still WebP; canvas=(w, h) wraps it in VP8X."""
    data = AUDIT_PROBES['valid-animated.webp']
    _, pos, size = next(c for c in riff_chunks(data) if c[0] == b'ANMF')
    frame = data[pos + 8:pos + 8 + size]
    inner = frame[16:]
    vp8 = inner[:8 + int.from_bytes(inner[4:8], 'little')]
    assert vp8[:4] == b'VP8 '
    body = vp8
    if canvas:
        dims = (canvas[0] - 1).to_bytes(3, 'little') + (canvas[1] - 1).to_bytes(3, 'little')
        body = b'VP8X' + (10).to_bytes(4, 'little') + b'\0' * 4 + dims + vp8
    return b'RIFF' + (4 + len(body)).to_bytes(4, 'little') + b'WEBP' + body


def gif():
    return (b'GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00'
            b',\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;')


def xhtml(body, title='t', head=''):
    return ('<?xml version="1.0" encoding="UTF-8"?>\n<!DOCTYPE html>\n'
            '<html xmlns="http://www.w3.org/1999/xhtml" xmlns:epub="http://www.idpf.org/2007/ops">'
            '<head><title>' + title + '</title>' + head + '</head><body>' + body + '</body></html>')


def build(files, mimetype=b'application/epub+zip', extra=None):
    """files: ordered list of (name, bytes|str) or (ZipInfo, bytes)."""
    buf = io.BytesIO()
    with zipfile.ZipFile(buf, 'w', zipfile.ZIP_DEFLATED) as zf:
        if mimetype is not None:
            zf.writestr(zipfile.ZipInfo('mimetype'), mimetype, compress_type=zipfile.ZIP_STORED)
        for name, data in files:
            if isinstance(data, str):
                data = data.encode('utf-8')
            zf.writestr(name, data)
        if extra:
            extra(zf)
    return buf.getvalue()


def opf3(manifest, spine, title='合成测试书 &amp; 卷一', author='测试作者', lang='zh-CN', extra_meta='', spine_attrs=''):
    return ('<?xml version="1.0" encoding="UTF-8"?>\n'
            '<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="uid">'
            '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'
            '<dc:identifier id="uid">urn:uuid:00000000-0000-4000-8000-000000000003</dc:identifier>'
            '<dc:title>' + title + '</dc:title><dc:creator id="a1">' + author + '</dc:creator>'
            '<meta refines="#a1" property="role" scheme="marc:relators">aut</meta>'
            '<dc:language>' + lang + '</dc:language>' + extra_meta + '</metadata>'
            '<manifest>' + manifest + '</manifest><spine' + spine_attrs + '>' + spine + '</spine></package>')


def minimal(body='<p>正文。</p>', extra_files=(), manifest_extra='', opf_override=None, chapter_name='OEBPS/c1.xhtml'):
    """A one-chapter EPUB 3 used as the base for most rejection cases."""
    manifest = ('<item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'
                '<item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/>' + manifest_extra)
    nav = xhtml('<nav epub:type="toc"><ol><li><a href="c1.xhtml">第一章</a></li></ol></nav>')
    files = [('META-INF/container.xml', CONTAINER.format(opf='OEBPS/content.opf')),
             ('OEBPS/content.opf', opf_override or opf3(manifest, '<itemref idref="c1"/>')),
             ('OEBPS/nav.xhtml', nav),
             (chapter_name, xhtml(body))]
    return build(files + list(extra_files))


def epub3_full():
    """EPUB 3: cover page, nav page, coarse nav (2 links) over a finer 6-item spine,
    non-ASCII and percent-escaped paths, an image-only page and an untitled continuation."""
    manifest = (
        '<item id="cover-img" href="images/封面.png" media-type="image/png" properties="cover-image"/>'
        '<item id="pic" href="images/pic%201.gif" media-type="image/gif"/>'
        '<item id="cover" href="cover.xhtml" media-type="application/xhtml+xml"/>'
        '<item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'
        '<item id="front" href="text/%E5%89%8D%E8%A8%80.xhtml" media-type="application/xhtml+xml"/>'
        '<item id="c1" href="text/%E7%AC%AC%E4%B8%80%E7%AB%A0.xhtml" media-type="application/xhtml+xml"/>'
        '<item id="c1b" href="text/part%202.xhtml" media-type="application/xhtml+xml"/>'
        '<item id="plate" href="text/plate.xhtml" media-type="application/xhtml+xml"/>'
        '<item id="app" href="text/appendix.xhtml" media-type="application/xhtml+xml"/>')
    spine = ''.join('<itemref idref="%s"/>' % i for i in ('cover', 'nav', 'front', 'c1', 'c1b', 'plate', 'app'))
    nav = xhtml('<nav epub:type="toc"><h1>目录</h1><ol>'
                '<li><a href="text/%E5%89%8D%E8%A8%80.xhtml">前言</a></li>'
                '<li><a href="text/%E7%AC%AC%E4%B8%80%E7%AB%A0.xhtml#start">第一章 起点</a></li></ol></nav>'
                '<nav epub:type="landmarks"><ol><li><a epub:type="cover" href="cover.xhtml">封面</a></li></ol></nav>')
    files = [
        ('META-INF/container.xml', CONTAINER.format(opf='OEBPS/content.opf')),
        ('OEBPS/content.opf', opf3(manifest, spine)),
        ('OEBPS/cover.xhtml', xhtml('<div><img src="images/%E5%B0%81%E9%9D%A2.png" alt="封面"/></div>')),
        ('OEBPS/nav.xhtml', nav),
        ('OEBPS/text/前言.xhtml', xhtml('<h2>前言</h2><p>这是前言&nbsp;内容 &amp; 说明。</p>')),
        ('OEBPS/text/第一章.xhtml', xhtml('<h1>第一章</h1><p>第一段。</p><p>第二段<br/>换行。</p>')),
        ('OEBPS/text/part 2.xhtml', xhtml('<p>续写的第三段，没有标题。</p>')),
        ('OEBPS/text/plate.xhtml', xhtml('<p><img src="../images/pic%201.gif" alt="插图"/></p>')),
        ('OEBPS/text/appendix.xhtml', xhtml('<section><h2>附录 A</h2><p>附录正文。</p></section>')),
        ('OEBPS/images/封面.png', png(3, 4)),
        ('OEBPS/images/pic 1.gif', gif()),
    ]
    return build(files)


def epub2_full():
    """EPUB 2: NCX navigation, guide cover reference, meta name=cover, opf:role author."""
    opf = ('<?xml version="1.0" encoding="UTF-8"?>\n'
           '<package xmlns="http://www.idpf.org/2007/opf" version="2.0" unique-identifier="uid">'
           '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">'
           '<dc:title>Synthetic Two</dc:title><dc:creator opf:role="edt">Editor</dc:creator>'
           '<dc:creator opf:role="aut">Ada Writer</dc:creator><dc:language>en</dc:language>'
           '<dc:identifier id="uid">synthetic-2</dc:identifier><meta name="cover" content="cimg"/></metadata>'
           '<manifest><item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>'
           '<item id="cimg" href="cover.png" media-type="image/png"/>'
           '<item id="cp" href="cover.html" media-type="application/xhtml+xml"/>'
           '<item id="ch1" href="ch1.html" media-type="application/xhtml+xml"/>'
           '<item id="ch2" href="ch2.html" media-type="application/xhtml+xml"/></manifest>'
           '<spine toc="ncx"><itemref idref="cp"/><itemref idref="ch1"/><itemref idref="ch2"/></spine>'
           '<guide><reference type="cover" href="cover.html" title="Cover"/></guide></package>')
    ncx = ('<?xml version="1.0" encoding="UTF-8"?>\n<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">'
           '<head/><docTitle><text>Synthetic Two</text></docTitle><navMap>'
           '<navPoint id="n1" playOrder="1"><navLabel><text>Chapter One</text></navLabel><content src="ch1.html"/></navPoint>'
           '<navPoint id="n2" playOrder="2"><navLabel><text>Chapter &amp; Two</text></navLabel><content src="ch2.html"/></navPoint>'
           '</navMap></ncx>')
    page = '<html xmlns="http://www.w3.org/1999/xhtml"><head><title>x</title></head><body>%s</body></html>'
    files = [('META-INF/container.xml', CONTAINER.format(opf='content.opf')),
             ('content.opf', opf), ('toc.ncx', ncx),
             ('cover.html', page % '<img src="cover.png" alt="cover"/>'),
             ('ch1.html', page % '<p>It was &lt;synthetic&gt; text.</p><pre>line one\nline two</pre>'),
             ('ch2.html', page % '<div>Second <em>chapter</em> body.</div>'),
             ('cover.png', png(2, 3))]
    return build(files)


def symlink_entry(zf):
    info = zipfile.ZipInfo('OEBPS/link.xhtml')
    info.create_system = 3
    info.external_attr = (0o120777 << 16)
    zf.writestr(info, '/etc/passwd')


def lying_size(data, name):
    """Rewrite the declared uncompressed size of `name` to a smaller value in both headers."""
    out = bytearray(data)
    encoded = name.encode()
    with zipfile.ZipFile(io.BytesIO(data)) as zf:
        info = zf.getinfo(name)
    real = info.file_size
    fake = struct.pack('<I', 16)
    lh = info.header_offset
    out[lh + 22:lh + 26] = fake
    cd = bytes(out).rfind(b'PK\x01\x02' + bytes(out[lh + 4:lh + 6]))
    while cd != -1:
        nlen = struct.unpack('<H', bytes(out[cd + 28:cd + 30]))[0]
        if bytes(out[cd + 46:cd + 46 + nlen]) == encoded:
            out[cd + 24:cd + 28] = fake
            break
        cd = bytes(out).rfind(b'PK\x01\x02', 0, cd)
    assert real > 16
    return bytes(out)


def image_only_single():
    """A single image-only XHTML spine item with no cover metadata."""
    manifest = ('<item id="p" href="page.xhtml" media-type="application/xhtml+xml"/>'
                '<item id="i" href="art.png" media-type="image/png"/>')
    return build([('META-INF/container.xml', CONTAINER.format(opf='OEBPS/content.opf')),
                  ('OEBPS/content.opf', opf3(manifest, '<itemref idref="p"/>')),
                  ('OEBPS/page.xhtml', xhtml('<div><img src="art.png" alt="画"/></div>')),
                  ('OEBPS/art.png', png(4, 4))])


def nav_only_with_body():
    """The EPUB 3 nav document is the sole spine item and also carries body text."""
    manifest = '<item id="nav" href="nav.xhtml" media-type="application/xhtml+xml" properties="nav"/>'
    nav = xhtml('<nav epub:type="toc"><ol><li><a href="nav.xhtml#t">正文</a></li></ol></nav>'
                '<h1 id="t">唯一正文</h1><p>导航文件里的正文段落。</p>')
    return build([('META-INF/container.xml', CONTAINER.format(opf='OEBPS/content.opf')),
                  ('OEBPS/content.opf', opf3(manifest, '<itemref idref="nav"/>')),
                  ('OEBPS/nav.xhtml', nav)])


def many_sections(count):
    """EPUB 2 with `count` spine sections; mimetype + container + OPF + count files entries."""
    manifest = ''.join('<item id="s%d" href="s%d.xhtml" media-type="application/xhtml+xml"/>' % (i, i) for i in range(count))
    spine = ''.join('<itemref idref="s%d"/>' % i for i in range(count))
    opf = ('<?xml version="1.0" encoding="UTF-8"?>\n<package xmlns="http://www.idpf.org/2007/opf" version="2.0">'
           '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Long</dc:title><dc:language>zh</dc:language></metadata>'
           '<manifest>' + manifest + '</manifest><spine>' + spine + '</spine></package>')
    files = [('META-INF/container.xml', CONTAINER.format(opf='content.opf')), ('content.opf', opf)]
    files += [('s%d.xhtml' % i, xhtml('<h2>第%d节</h2><p>内容%d</p>' % (i + 1, i + 1))) for i in range(count)]
    return build(files)
