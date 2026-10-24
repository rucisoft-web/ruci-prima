# Membuat berkas logo Ruci PRIMA (public/brand). Pakai: pip install fonttools; python3 scripts/brand-logo.py public/brand
# Teks diubah menjadi path dari font Inter (lisensi SIL OFL), sehingga SVG tidak bergantung pada font terpasang.
import sys, os
from fontTools.ttLib import TTFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen
from fontTools.pens.boundsPen import BoundsPen

F = '/usr/share/fonts/opentype/inter/'
NAVY, GOLD, WHITE = '#0b3a5c', '#c9963e', '#ffffff'
out = sys.argv[1]
os.makedirs(out, exist_ok=True)

def text(fontfile, s, size, x, y, tracking=0.0):
    """Teks -> path SVG (baseline y), tracking dalam em. Mengembalikan (d, lebar)."""
    f = TTFont(F + fontfile); gs = f.getGlyphSet(); cmap = f.getBestCmap(); upm = f['head'].unitsPerEm
    k = size / upm; pen = SVGPathPen(gs); cx = 0
    for i, ch in enumerate(s):
        g = cmap[ord(ch)]
        gs[g].draw(TransformPen(pen, (k, 0, 0, -k, x + cx, y)))
        cx += gs[g].width * k + (tracking * size if i < len(s) - 1 else 0)
    return pen.getCommands(), cx

P = 'M16 46V18a3 3 0 0 1 3-3h15a11.5 11.5 0 0 1 0 23h-8v8a3 3 0 0 1-3 3h-4a3 3 0 0 1-3-3zM26 23v7h8a3.5 3.5 0 0 0 0-7z'
DOT = '<rect x="39.5" y="41" width="8.5" height="8.5" rx="2.2" fill="%s"/>'

def mark(bg, fg, x=0, y=0, s=1.0, stroke=None):
    st = f' stroke="{stroke}" stroke-width="1"' if stroke else ''
    return (f'<g transform="translate({x} {y}) scale({s})"><rect width="64" height="64" rx="15" fill="{bg}"{st}/>'
            f'<path fill-rule="evenodd" fill="{fg}" d="{P}"/>' + DOT % GOLD + '</g>')

def svg(w, h, body, title):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w:.0f} {h:.0f}" role="img" aria-label="{title}"><title>{title}</title>{body}</svg>\n'

def save(name, s):
    open(os.path.join(out, name), 'w').write(s)

# Simbol
save('ruci-prima-mark.svg', svg(64, 64, mark(NAVY, WHITE), 'Ruci PRIMA'))
save('ruci-prima-mark-light.svg', svg(64, 64, mark(WHITE, NAVY, stroke='#e3ddd0'), 'Ruci PRIMA'))

def horizontal(fg, mbg, mfg, tagline=True, tag='#9a7531'):
    h = 64
    d1, w1 = text('InterDisplay-Medium.otf', 'Ruci', 30, 80, 36)
    d2, w2 = text('InterDisplay-ExtraBold.otf', 'PRIMA', 30, 80 + w1 + 9, 36, 0.06)
    body = mark(mbg, mfg) + f'<path fill="{fg}" d="{d1}"/><path fill="{fg}" d="{d2}"/>'
    w = 80 + w1 + 9 + w2
    if tagline:
        d3, w3 = text('Inter-SemiBold.otf', 'RUANG · INVENTARIS · MOBILITAS · ADMINISTRASI', 8.6, 81, 54, 0.12)
        body += f'<path fill="{tag}" d="{d3}"/>'
        w = max(w, 81 + w3)
    return svg(w + 2, h, body, 'Ruci PRIMA')

save('ruci-prima-logo.svg', horizontal(NAVY, NAVY, WHITE))
save('ruci-prima-logo-dark.svg', horizontal(WHITE, WHITE, NAVY, tag=GOLD))
save('ruci-prima-logo-simple-dark.svg', horizontal(WHITE, WHITE, NAVY, tagline=False))
save('ruci-prima-logo-simple.svg', horizontal(NAVY, NAVY, WHITE, tagline=False))

# Bertumpuk (untuk sampul, kop surat, layar pembuka)
d1, w1 = text('InterDisplay-Medium.otf', 'Ruci', 34, 0, 0)
d2, w2 = text('InterDisplay-ExtraBold.otf', 'PRIMA', 34, 0, 0, 0.06)
tw = w1 + 10 + w2
_, w3 = text('Inter-SemiBold.otf', 'RUANG · INVENTARIS · MOBILITAS · ADMINISTRASI', 9, 0, 0, 0.12)
W = max(tw, w3) + 24
d1, _ = text('InterDisplay-Medium.otf', 'Ruci', 34, (W - tw) / 2, 140)
d2, _ = text('InterDisplay-ExtraBold.otf', 'PRIMA', 34, (W - tw) / 2 + w1 + 10, 140, 0.06)
d3, _ = text('Inter-SemiBold.otf', 'RUANG · INVENTARIS · MOBILITAS · ADMINISTRASI', 9, (W - w3) / 2, 164, 0.12)
save('ruci-prima-logo-stacked.svg', svg(W, 176, mark(NAVY, WHITE, (W - 88) / 2, 0, 88 / 64) + f'<path fill="{NAVY}" d="{d1}"/><path fill="{NAVY}" d="{d2}"/><path fill="#9a7531" d="{d3}"/>', 'Ruci PRIMA'))
print('ok')
