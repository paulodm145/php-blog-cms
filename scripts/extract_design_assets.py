#!/usr/bin/env python3
"""Extrai CSS de fontes/ícones e assets do protótipo para public/assets."""
import os
import re
import shutil

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PROTO = os.path.join(ROOT, 'design', 'unpacked', 'prototype')
HTML = os.path.join(PROTO, 'index.html')
OUT_CSS = os.path.join(ROOT, 'public', 'assets', 'css', 'design-base.css')
OUT_FONTS = os.path.join(ROOT, 'public', 'assets', 'fonts')
OUT_IMAGES = os.path.join(ROOT, 'public', 'assets', 'images')

HERO_SVG = '32e878a8-6fd9-4b03-a6db-1fa97dfbac90.svg'

html = open(HTML, encoding='utf-8').read()

# Apenas os <style> do bloco <helmet> (fontes, Font Awesome e estilos base do protótipo)
helmet = re.search(r'<helmet.*?</helmet>', html, re.S).group(0)
styles = re.findall(r'<style>(.*?)</style>', helmet, re.S)
css = '\n'.join(styles)

os.makedirs(OUT_FONTS, exist_ok=True)
os.makedirs(OUT_IMAGES, exist_ok=True)
os.makedirs(os.path.dirname(OUT_CSS), exist_ok=True)

copied = 0
for ref in sorted(set(re.findall(r'assets/([\w.-]+)', css))):
    src = os.path.join(PROTO, 'assets', ref)
    if os.path.isfile(src):
        shutil.copy(src, os.path.join(OUT_FONTS, ref))
        copied += 1

css = css.replace('url("assets/', 'url("/assets/fonts/')
open(OUT_CSS, 'w', encoding='utf-8').write(css)

shutil.copy(os.path.join(PROTO, 'assets', HERO_SVG),
            os.path.join(OUT_IMAGES, 'hero-illustration.svg'))

print('css bytes:', os.path.getsize(OUT_CSS))
print('font files copied:', copied)
print('hero svg copied')
