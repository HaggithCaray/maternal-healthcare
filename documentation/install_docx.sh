#!/bin/bash
cd /home/ruselportes/maternal-health-care/documentation
mkdir -p docx-export
cd docx-export
docker run --rm -v "$(pwd):/app" -w /app node:20-alpine npm init -y
docker run --rm -v "$(pwd):/app" -w /app node:20-alpine npm install docx @mermaid-js/mermaid-cli
