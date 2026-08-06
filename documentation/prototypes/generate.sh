#!/bin/bash
cd /home/ruselportes/maternal-health-care/documentation/prototypes
docker run --rm -v "$(pwd):/data" pandoc/core /data/prototypes.md -o /data/TrackERB_Application_Prototypes.docx
