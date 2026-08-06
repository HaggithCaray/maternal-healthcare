#!/bin/bash
cd /home/ruselportes/maternal-health-care/documentation/docx-export

echo "Step 1: Extracting mermaid diagrams..."
docker run --rm -v "/home/ruselportes/maternal-health-care/documentation:/app" -w /app/docx-export node:20-alpine node step1_write_mmd.mjs

echo "Setting permissions for Docker writers..."
chmod -R 777 images

echo "Step 2: Generating PNG images using Mermaid-CLI..."
for i in {0..18}; do
    echo "Rendering diagram_$i..."
    docker run --rm -u root -v "/home/ruselportes/maternal-health-care/documentation/docx-export:/data" minlag/mermaid-cli -i /data/images/diagram_$i.mmd -o /data/images/diagram_$i.png -b transparent
done

echo "Step 3: Updating markdown with image links..."
docker run --rm -v "/home/ruselportes/maternal-health-care/documentation:/app" -w /app/docx-export node:20-alpine node step3_replace_and_pandoc.mjs

echo "Step 4: Generating DOCX via Pandoc..."
docker run --rm -v "/home/ruselportes/maternal-health-care/documentation:/data" pandoc/core /data/SoftwareDocumentationWithImages.md -o /data/TrackERB_SoftwareDocumentation_Automated.docx

echo "Done! The DOCX file is ready."
