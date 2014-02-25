<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

/**
 * Created by PhpStorm.
 * User: florinb
 * Date: 2/21/14
 * Time: 12:32 PM
 */
class LocalServicesController extends BaseController
{

    /**
     * @var ElasticSearch\Client
     */
    private $_elastic_search;
    /**
     * @var Illuminate\Support\Facades\Response
     */
    private $_response;
    /**
     * @var Illuminate\Http\Request
     */
    private $_request;

    public function __construct(
        Elasticsearch\Client $elastic_search,
        Response $response,
        Request $request
    ) {
        $this->_elastic_search = $elastic_search;
        $this->_response = $response;
        $this->_request = $request;
    }

    public function services()
    {
        $input = $this->_request->get('any');
        $terms_response = $this->_getRelevantTerms($input);
        $this->_buildLibrariansQuery($terms_response);
        $librarians = $this->_getLibrarians($terms_response);
        return $this->_response->json(['librarians' => $librarians]);
    }

    protected function _getRelevantTerms($keyword)
    {
        $params = [];
        $params['index'] = 'records';
        $params['body'] = [
            'query'  => [
                'filtered' => [
                    'query'  => [
                        'match' => [
                            '_all' => [
                                'query' => $keyword
                            ]
                        ]
                    ]
                ]
            ],
            'from'   => 0,
            'size'   => 0,
            'sort'   => [],
            'facets' => [
                'LCCDep1' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep1',
                        'value_script' => 'doc.score'
                    ]
                ],
                'LCCDep2' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep2',
                        'value_script' => 'doc.score'
                    ]
                ],
                'LCCDep3' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep3',
                        'value_script' => 'doc.score'
                    ]
                ]
            ]
        ];

        $response = $this->_elastic_search->search($params);
        $facet_array = [];
        foreach ($response['facets'] as $facet) {
            $facet_array[] = $facet['terms'];
        }
        return $facet_array;
    }

    protected function _getLibrarians(array $taxonomy_terms)
    {
        $params = [
            'index' => 'librarians',
            'body'  => $this->_buildLibrariansQuery($taxonomy_terms)
        ];
        $librarians = $this->_elastic_search->search($params);
        return $this->_buildLibrariansResponse($librarians);
    }

    protected function _buildLibrariansQuery(array $taxonomy_terms)
    {
        // Increase to make lower-level taxonomy results comparatively more valuable.
        $level_boost_multiple = 10;

        // Increase to use more matched taxonomy terms.
        $terms_to_use = 3;

        $level_boost = 1;
        $should = [];
        foreach ($taxonomy_terms as $taxonomy_term) {
            $i = 0;
            while ($i < $terms_to_use && isset($taxonomy_term[$i])) {
                $should[] = [
                    'match_phrase' => [
                        'tags' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $taxonomy_term[$i]['total'] * $level_boost
                        ]
                    ]
                ];
                $i++;
            }
            $level_boost *= $level_boost_multiple;
        }

        return [
            'query' => [
                'bool' => [
                    'should' => $should
                ]
            ]
        ];
    }

    protected function _buildLibrariansResponse(array $librarians)
    {
        $results = [];
        foreach ($librarians['hits']['hits'] as $hit) {
            $librarian = [
                'name'     => $hit['_source']['name'],
                'image'    => str_replace(
                    'libguides.bc.edu/',
                    'lgimages.s3.amazonaws.com',
                    $hit['_source']['imageSrc']
                ),
                'phone'    => $hit['_source']['profileURL'],
                'email'    => $hit['_source']['email'],
                'location' => $hit['_source']['location']
            ];
            $subjects = [];
            foreach ($hit['_source']['subjects'] as $subject) {
                list($term, $url) = explode('***', $subject);
                $subjects[] = [
                    'term' => $term,
                    'url'  => $url
                ];
            }
            $librarian['subjects'] = $subjects;
            $results[] = $librarian;
        }

        return $results;
    }
}