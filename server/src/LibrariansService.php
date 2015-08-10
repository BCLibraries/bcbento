<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class LibrariansService extends AbstractLocalService implements ServiceInterface
{

    const MIN_LIBRARIAN_SCORE = '.1';

    public function __construct(Client $elastic_search, $es_version)
    {
        parent::__construct($elastic_search, $es_version);
        $this->index = 'librarians';
    }

    public function buildQuery($keyword, array $terms_response)
    {
        // Increase to make lower-level taxonomy results comparatively more valuable.
        $level_boost_multiple = 3;

        // Increase to use more matched taxonomy terms.
        $terms_to_use = 3;

        $level_boost = 1;
        $should = [];
        foreach ($terms_response as $taxonomy_term) {
            $i = 0;
            while ($i < $terms_to_use && isset($taxonomy_term[$i])) {

                $boost = $taxonomy_term[$i]['total'] * $level_boost;

                $should[] = [
                    'match_phrase' => [
                        'taxonomy' => [
                            'query' => $taxonomy_term[$i]['term'],
                            'boost' => $boost
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
                ],
                'from' => 0,
                'size' => 2
            ]
        ];
    }

    public function buildResponse(array $librarians)
    {
        $results = [];
        foreach ($librarians['hits']['hits'] as $hit) {
            if ($hit['_score'] < LibrariansService::MIN_LIBRARIAN_SCORE) {
                break;
            }
            $librarian = [
                'name'     => $hit['_source']['name'],
                'image'    => str_replace(
                    'libguides.bc.edu/',
                    'lgimages.s3.amazonaws.com',
                    $hit['_source']['img']
                ),
                'phone'    => $hit['_source']['profile'],
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

}