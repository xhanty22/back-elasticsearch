<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;

class MasterController extends Controller
{
    protected $client, $client_kibana;
    public $defaultColumns = ['nit'];

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts([env('ELASTICSEARCH_HOST')])
            // ->setBasicAuthentication(env('ELASTICSEARCH_USERNAME'), env('ELASTICSEARCH_PASSWORD'))
            ->setApiKey(env('ELASTICSEARCH_API_KEY'))
            ->build();

        $this->client_kibana = new Client([
            'base_uri' => env('KIBANA_HOST'), // Reemplazar con la URL base de la API
            'auth' => [env('ELASTICSEARCH_USERNAME'), env('ELASTICSEARCH_PASSWORD')], // Reemplazar con el usuario y la contraseña
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function indexes(Request $request)
    {
        try {
            $response = $this->client_kibana->request('GET', '/api/index_management/indices');

            // Decodificar el cuerpo de la respuesta
            $data = json_decode($response->getBody()->getContents(), true);

            $indices = [];

            foreach ($data as $key => $value) {
                if ($value["replica"] == 1) {
                    array_push($indices, $value);
                }
            }

            return response()->json([
                'status' => true,
                'message' => "Request Success",
                'data' => [
                    'indices' => $indices
                ]
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => "Internal Server Error",
                'data' => [
                    'error' => $ex->getMessage()
                ]
            ], 500);
        }
    }

    public function documents(Request $request)
    {
        try {
            $index = $request->input('index') ?? 'hojas_vida'; // Default index is hojas_vida
            $size = $request->input('size') ?? 100; // Default size is 100 documents
            $search = $request->input('search'); // Default size is 100 documents
            $columns = $request->input('columns') ?? null; // Default columns is null

            // Campos de búsqueda
            $searchData = [];
            $columnsData = [];
            $allData = [];

            if ($search && is_array($search) && count($search) > 0) {
                $querySearch = implode(' ', array_map(function($value) {
                    return '"' . $value . '"';
                }, $search));
            
                $searchData = [
                    'query_string' => [
                        'query' => $querySearch,
                        'default_field' => '*', // Todos los campos
                    ]
                ];
            }

            if ($columns && is_array($columns) && count($columns) > 0) {
                foreach ($columns as $key => $column) {
                    $wilcarOne = [];

                    foreach ($column as $key2 => $value) {
                        // Validar si el valor es un número
                        if (is_numeric($value)) {
                            $wilcarOne[] = ['wildcard' => [$key => '*' . $value . '*']];
                        } else {
                            $wilcarOne[] = ['match' => [$key => ['query' => $value, 'fuzziness' => 'AUTO']]];
                        }
                    }

                    $columnsData[] = [
                        'bool' => [
                            'should' => $wilcarOne,
                            'minimum_should_match' => 1
                        ]
                    ];
                }
            }

            if(!$searchData && !$columnsData) {
                $allData = [
                    'match_all' => new \stdClass()
                ];
            }

            $query = [];

            if ($searchData && is_array($searchData) && count($searchData) > 0) {
                array_push($query, $searchData);
            }

            if ($columnsData && is_array($columnsData) && count($columnsData) > 0) {
                $query = array_merge($query, $columnsData);
            }

            if ($allData && is_array($allData) && count($allData) > 0) {
                array_push($query, $allData);
            }

            $params = [
                'index' => $index,
                'body' => [
                    'query' => [
                        "bool" => [
                            "must" => $query,
                        ]
                    ],
                    'size' => $size, // Set the size parameter
                ]
            ];

            // Obtener el contenido de todos los documentos en un índice
            $response = $this->client->search($params);

            $documents = [];
            $columns = [];
            $headersColumns = [];
            foreach ($response['hits']['hits'] as $hit) {
                $documents[] = [
                    'id' => $hit['_id'],  // ID del documento
                    'source' => $hit['_source']  // Contenido del documento
                ];

                // Obtener las columnas de los documentos
                $columns = array_keys($hit['_source']);
            }

            foreach ($columns as $key => $column) {
                // Eliminar la columnas que contienen un @
                if (strpos($column, '@') === false) {
                    // Header de las columnas las que contienen un *, y las $defaultColumns
                    if (strpos($column, '#') !== false || in_array($column, $this->defaultColumns)) {
                        //$value = str_replace(' ', '_', $column);
                        $header = [
                            'header' => true,
                            'value' => $column,
                            'label' => str_replace('#', '', $column)
                        ];

                        $headersColumns[] = $header;
                    } else {
                        //$value = str_replace(' ', '_', $column);
                        $header = [
                            'header' => false,
                            'value' => $column,
                            'label' => $column
                        ];

                        $headersColumns[] = $header;
                    }
                }
            }

            // Colocar los labels de las columnas en la primera letra en mayúscula
            foreach ($headersColumns as $key => $header) {
                $headersColumns[$key]['label'] = ucfirst($header['label']);
            }

            return response()->json([
                'status' => true,
                'message' => "Request Success",
                'data' => [
                    'count' => count($documents),
                    'header' => $headersColumns,
                    // 'columns' => $columns,
                    'documents' => $documents
                ]
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => "Internal Server Error",
                'data' => [
                    'error' => $ex->getMessage()
                ]
            ], 500);
        }
    }
}
