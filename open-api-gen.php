<?php

include './vendor/autoload.php';

use GoldSpecDigital\ObjectOrientedOAS\Objects\Info;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Components;
use GoldSpecDigital\ObjectOrientedOAS\Objects\SecurityScheme;
use GoldSpecDigital\ObjectOrientedOAS\Objects\SecurityRequirement;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response\Schema as ResponseSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Tag;
use GoldSpecDigital\ObjectOrientedOAS\OpenApi;


$versionNum = !empty($argv[1]) ? ltrim($argv[1], 'v') : '1';
$version = 'v' . $versionNum;

$rest = include(__DIR__ . '/application/config/rest/' . $version . '.php');
$outputFile = __DIR__ . '/docs/open-api/' . $version . '.json';

$apiConfig = isset($rest[$version]) ? $rest[$version] : [];

///////////////////////////////////////////////////////////////////////////
// Main API Details
$info = Info::create()
    ->title(
        !empty($apiConfig['title']) ? $apiConfig['title'] : 'Title'
    )
    ->version($versionNum)
    ->description(
        !empty($apiConfig['description']) ? $apiConfig['description'] : 'description'
    );


$securityScheme = SecurityScheme::create('bearerAuth')
    ->scheme('bearer')
    ->type('http')
    ->name('Bearer Auth');
$securityRequirement = SecurityRequirement::create()
    ->securityScheme($securityScheme);

$components = Components::create()->securitySchemes($securityScheme);

$openApi = OpenApi::create()
    ->openapi(OpenApi::OPENAPI_3_0_2)
    ->info($info)
    ->components($components);

$tags = [];
$schemas = [];
$paths = [];

foreach ($rest as $path => $config) {

    ///////////////////////////////////////////////////////////////////////////
    // Path
    $pathParts = explode('/', $path);
    $pathPartsCount = count($pathParts);

    $version = null;
    $entity = null;
    $id = null;
    if ($pathPartsCount == 3) {
        [$version, $entity, $id] = $pathParts;
    } elseif ($pathPartsCount == 2) {
        [$version, $entity] = $pathParts;
    } elseif ($pathPartsCount < 2) {
        continue;
    }

    $tagsConfig = !empty($rest[$version]['tags']) ? $rest[$version]['tags'] : [];

    $operations = [];
    foreach ($config as $method => $methodConfig) {
        ///////////////////////////////////////////////////////////////////////////
        // Method
        $oaMethod = strtolower($method);
        $oaOpId = !empty($id)
            ? $oaMethod . '.' . $entity . '.id'
            : $oaMethod . '.' . $entity;

        $oaOperation = Operation::$oaMethod()->summary(
                        !empty($methodConfig['description']) ? $methodConfig['description'] : ''
                    )->operationId($oaOpId);

        if (!empty($methodConfig['auth'])) {
            $oaOperation = $oaOperation->security($securityRequirement);
        }

        ///////////////////////////////////////////////////////////////////////////
        // Tag
        $tagId = !empty($methodConfig['tag']) ? $methodConfig['tag'] : $entity;
        $tagConfig = isset($tagsConfig[$tagId])? $tagsConfig[$tagId] : null;
        if ($tagConfig) {
            $tags[$tagId] = Tag::create($tagId)
                ->name(
                    !empty($tagConfig['name']) ? $tagConfig['name'] : ucfirst($entity)
                )
                ->description(
                    !empty($tagConfig['description']) ? $tagConfig['description'] : ''
                );
            $openApi = $openApi->tags(...$tags);
        }
        if (isset($tags[$tagId])) {
            $oaOperation = $oaOperation->tags($tags[$tagId]);
        }


        ///////////////////////////////////////////////////////////////////////////
        // Params
        $params = [];

        // Entity id param
        if ($id) {
            $params[] = Parameter::path()->name('id');
        }

        // Query params
        // TODO: allow proper param type definition via config
        $paramsConfig = !empty($methodConfig['params']) ? $methodConfig['params'] : [];
        $formProps = [];
        foreach ($paramsConfig as $paramName => $paramConfig) {
            if ($paramConfig) {
                $src = is_array($paramConfig) && !empty($paramConfig['src']) ? $paramConfig['src'] : 'query';

                $paramSchema = null;

                if (!empty($paramConfig['schema']) && $paramConfig['schema'] instanceof Schema) {
                    $paramSchema = $paramConfig['schema'];
                } else {
                    $type = !empty($paramConfig['type']) ? $paramConfig['type'] : '';
                    switch ($type) {
                        case 'int':
                            $paramSchema = Schema::integer($paramName);
                        break;
                        case 'number':
                            $paramSchema = Schema::number($paramName);
                            break;
                        case 'string':
                        default:
                            $paramSchema = Schema::string($paramName);
                        break;
                    }
                }

                if ($src == 'query') {
                    $params[] = Parameter::query()
                        ->name($paramName)->schema(
                            $paramSchema
                        );
                } elseif ($src == 'form') {
                    $formProps[] = $paramSchema;
                }
            }
        }
        $oaOperation = $oaOperation->parameters(...$params);
        $formSchema = null;
        if (!empty($formProps)) {
            $formSchema = Schema::object()->properties(...$formProps);
        }

        ///////////////////////////////////////////////////////////////////////////
        // Request Content
        // TODO: allow proper schema definition via config
        $schema = !empty($methodConfig['schema']) ? $methodConfig['schema'] : null;
        $requestBody = null;
        if (!empty($schema) && $schema instanceof Schema) {
            $requestBody = MediaType::json()->schema($schema);
        } elseif ($formSchema) {
            $requestBody = MediaType::formUrlEncoded()->schema($formSchema);
        }
        if ($requestBody) {
            $oaOperation = $oaOperation->requestBody(RequestBody::create()->content(
                $requestBody
            ));
        }

        //////////////////////////////////////////////////////////////////////////
        // Responses
        $responsesConfig = !empty($methodConfig['responses']) ? $methodConfig['responses'] : [];
        $responses = [];
        foreach ($responsesConfig as $responseId => $responseConfig) {
            $response = Response::create()
                ->statusCode(
                    !empty($responseConfig['code']) ? $responseConfig['code'] : 200
                )
                ->description(
                    !empty($responseConfig['description']) ? $responseConfig['description'] : ''
                );

            $schema = !empty($responseConfig['schema']) ? $responseConfig['schema'] : null;
            if (!empty($schema) && $schema instanceof Schema) {
                $response = $response->content(
                    MediaType::json()->schema($schema)
                );
            }

            $responses[] = $response;
        }
        if (!empty($responses)) {
            $oaOperation = $oaOperation->responses(...$responses);
        }

        $operations[] = $oaOperation;
    }

    /////////////////////////////////////////////////////////////////////////
    // Path
    $oaPathString = '/rest/' . implode('/', [$version, $entity]);
    if (!empty($id)) {
        $oaPathString = $oaPathString . '/{id}';
    }
    $oaPath = PathItem::create()
        ->route($oaPathString);

    if (!empty($operations)) {
        $oaPath = $oaPath->operations(...$operations);
    }

    $paths[] = $oaPath;
}

$openApi = $openApi->paths(...$paths);

file_put_contents($outputFile, $openApi->toJson());

echo 'Created ' . $outputFile;
