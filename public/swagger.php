<?php
// Swagger-PHP Documentation Generator
require("../vendor/autoload.php");
// Scan the current directory for OpenAPI annotations
$openapi = \OpenApi\Generator::scan(['.']);
// Set the response header to indicate YAML content
header('Content-Type: application/x-yaml');
// Output the generated OpenAPI documentation in YAML format
echo $openapi->toYaml();
 