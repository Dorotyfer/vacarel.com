<?php
require __DIR__.'/app/report-request.php';
require __DIR__.'/app/ReportExport.php';
echo ReportExport::html($report);