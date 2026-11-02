#!/usr/bin/env python3
"""Publish the canonical content-page CSS without requiring a Vite rebuild."""
from pathlib import Path
root = Path(__file__).resolve().parent.parent
source = root / 'resources/css/nextplay-content-design-system.css'
target = root / 'public/css/nextplay-content-design-system.css'
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text('/* GENERATED from resources/css/nextplay-content-design-system.css */\n' + source.read_text())
print(f'Built {target.relative_to(root)}')
