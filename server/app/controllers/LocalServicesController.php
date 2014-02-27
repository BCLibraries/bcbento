<?php

use Doctrine\Common\Cache\ApcCache;
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

    /**
     * @var Doctrine\Common\Cache\ApcCache
     */
    private $_cache;

    private $_keyword;

    const MIN_LIBRARIAN_SCORE = '.3';
    const MIN_GUIDE_SCORE = '.3';

    public function __construct(
        Elasticsearch\Client $elastic_search,
        Response $response,
        Request $request,
        ApcCache $cache
    ) {
        $this->_elastic_search = $elastic_search;
        $this->_response = $response;
        $this->_request = $request;
        $this->_cache = $cache;
    }

    public function services()
    {
        $input = $this->_request->get('any');
        $terms_response = $this->_getRelevantTerms($input);
        $this->_buildLibrariansQuery($terms_response);
        $librarians = $this->_getLibrarians($terms_response);
        return $this->_response->json(['librarians' => $librarians]);
    }

    public function guides()
    {
        $this->_keyword = $this->_request->get('any');
        $input = $this->_request->get('any');
        $terms_reponse = $this->_getRelevantTerms($input);
        $this->_buildSubjectGuidesQuery($terms_reponse);
        $guides = $this->_getGuides($terms_reponse);
        return $this->_response->json(['guides' => $guides]);
    }

    protected function _getRelevantTerms($keyword)
    {
        $cache_key = $this->_cache_key($keyword);
        if ($this->_cache->contains($cache_key)) {
            return $this->_cache->fetch($cache_key);
        }
        $params = [];
        $params['index'] = 'records';
        $params['body'] = [
            'query'  => [
                'filtered' => [
                    'query' => [
                        'match_phrase' => [
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

        $this->_cache->save($cache_key, $facet_array, 60 * 60 * 24);

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
            if ($hit['_score'] < self::MIN_LIBRARIAN_SCORE) {
                break;
            }
            $librarian = [
                'name'     => $hit['_source']['name'],
                'image'    => str_replace(
                    'libguides.bc.edu/',
                    'lgimages.s3.amazonaws.com',
                    $hit['_source']['imageSrc']
                ),
                'phone'    => $hit['_source']['profileURL'],
                'email'    => $hit['_source']['email'],
                'location' => $hit['_source']['location'],
                'score'    => $hit['_score']
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

    protected function _getGuides(array $taxonomy_terms)
    {
        $params = [
            'index' => 'libguides_v0',
            'body'  => $this->_buildSubjectGuidesQuery($taxonomy_terms)
        ];
        $librarians = $this->_elastic_search->search($params);
        return $this->_buildSubjectGuideResponse($librarians);
    }

    protected function _buildSubjectGuidesQuery(array $taxonomy_terms)
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
                        'taxonomy' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $taxonomy_term[$i]['total'] * $level_boost
                        ]
                    ]
                ];
                $i++;
            }
            $level_boost *= $level_boost_multiple;
        }
        $should[] = [
          'match' => [
              '_all' => [
                  'query' => $this->_keyword
              ]
          ]
        ];

        return [
            'query' => [
                'bool' => [
                    'should' => $should
                ]
            ]
        ];
    }

    protected function _buildSubjectGuideResponse(array $subject_guides)
    {
        $results = [];

        $seen = [];

        foreach ($subject_guides['hits']['hits'] as $hit) {

            $title = htmlspecialchars_decode($hit['_source']['guide_name']);

            if (isset($seen[$title])) {
                continue;
            }

            if ($hit['_score'] < self::MIN_GUIDE_SCORE) {
                break;
            }

            $results[] = [
                'title'       => $title,
                'url'         => $hit['_source']['guide_url'],
                'score'       => $hit['_score'],
                'description' => $hit['_score']['guide_description']
            ];

            $seen[$title] = true;
        }

        return $results;
    }

    protected function _cache_key($term)
    {
        return 'SEARCH_TERMS_' . $term;
    }
}