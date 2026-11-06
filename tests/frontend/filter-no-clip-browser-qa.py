"""Compatibility entrypoint for the current desktop catalog filter browser QA."""
from pathlib import Path
import runpy
runpy.run_path(str(Path(__file__).with_name('filter-measured-sticky-browser-qa.py')), run_name='__main__')
